<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    franchises: { type: Array, default: () => [] },
    barangays: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    operators: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] } 
});

const emit = defineEmits(['close', 'submit']);

// --- Application Types ---
const applicationTypes = [
    { 
        id: 'new_franchise', 
        name: 'New Franchise', 
        description: 'Apply for a new franchise operation including owner and dynamic unit details.', 
        icon: 'M12 4v16m8-8H4' 
    },
    { 
        id: 'change_owner_deceased', 
        name: 'Change of Owner (Deceased)', 
        description: 'Transfer ownership of an existing franchise to an heir.', 
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' 
    }
];

// --- MODAL STATES ---
const currentStep = ref(1); 
const selectedType = ref('new_franchise'); 
const activeDropdown = ref(null);
const expandedUnitIndex = ref(0);

// Search text models for searchable dropdowns
const searchFranchiseText = ref('');
const searchOperatorText = ref('');

// --- API State for Addresses ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load locations:", error);
    }
});

// --- FORMS ---
const getEmptyUnit = () => ({
    make_id: '', zone_id: '', model_year: '', plate_number: '', cr_number: '', motor_number: '', chassis_number: '',
    unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    cr_photo: null, or_photo: null
});

const unitPhotoPreviews = ref([{ 
    front: null, back: null, left: null, right: null,
    cr: null, or: null
}]); 

const form = useForm({
    type: 'new_franchise', 
    selected_franchise_id: '', 
    remarks: '',
    
    // Owner Fields
    owner_mode: 'new', // 'new' or 'existing'
    existing_operator_id: '',

    new_owner_first_name: '', 
    new_owner_middle_name: '', 
    new_owner_last_name: '', 
    new_owner_contact: '', 
    new_owner_email: '', 
    new_owner_tin: '', 
    new_owner_address: '', 
    new_owner_province: '', 
    new_owner_city: '', 
    new_owner_barangay: '', 
    
    // Dynamic Units Array
    units: [ getEmptyUnit() ]
});

// --- Custom Search & Selection Logic ---
const filteredProvinces = computed(() => {
    if (!form.new_owner_province) return provincesList.value;
    return provincesList.value.filter(p => p.name.toLowerCase().includes(form.new_owner_province.toLowerCase()));
});

const filteredCities = computed(() => {
    if (!form.new_owner_city) return citiesList.value;
    return citiesList.value.filter(c => c.name.toLowerCase().includes(form.new_owner_city.toLowerCase()));
});

const filteredBarangays = computed(() => {
    if (!form.new_owner_barangay) return barangaysList.value;
    return barangaysList.value.filter(b => b.name.toLowerCase().includes(form.new_owner_barangay.toLowerCase()));
});

const filteredFranchises = computed(() => {
    if (!searchFranchiseText.value) return props.franchises;
    const lowerSearch = searchFranchiseText.value.toLowerCase();
    return props.franchises.filter(f => 
        (f.franchise_number && f.franchise_number.toLowerCase().includes(lowerSearch)) ||
        (f.unit?.plate_number && f.unit.plate_number.toLowerCase().includes(lowerSearch))
    );
});

const filteredOperators = computed(() => {
    if (!searchOperatorText.value) return props.operators;
    const lowerSearch = searchOperatorText.value.toLowerCase();
    return props.operators.filter(o => 
        `${o.first_name} ${o.last_name}`.toLowerCase().includes(lowerSearch)
    );
});

// -- Selection Methods --
const selectProvince = async (prov) => {
    form.new_owner_province = prov.name;
    form.clearErrors('new_owner_province');
    activeDropdown.value = null;
    
    form.new_owner_city = '';
    form.new_owner_barangay = '';
    citiesList.value = [];
    barangaysList.value = [];

    try {
        if (prov.isNCR) {
            const res = await fetch(`https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        } else {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        }
    } catch (error) { console.error("Failed to load cities:", error); }
};

const selectCity = async (city) => {
    form.new_owner_city = city.name;
    form.clearErrors('new_owner_city');
    activeDropdown.value = null;
    form.new_owner_barangay = '';
    barangaysList.value = [];

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
        barangaysList.value = await res.json();
    } catch (error) { console.error("Failed to load barangays:", error); }
};

const selectBarangay = (brgy) => {
    form.new_owner_barangay = brgy.name;
    form.clearErrors('new_owner_barangay');
    activeDropdown.value = null;
};

const selectFranchise = (fran) => {
    form.selected_franchise_id = fran.id;
    searchFranchiseText.value = `${fran.franchise_number || 'No Franchise Number'} - Plate: ${fran.unit?.plate_number || 'N/A'}`;
    form.clearErrors('selected_franchise_id');
    activeDropdown.value = null;
};

const selectOperator = (op) => {
    form.existing_operator_id = op.id;
    searchOperatorText.value = `${op.first_name} ${op.last_name}`;
    form.clearErrors('existing_operator_id');
    activeDropdown.value = null;
};

// --- FORMATTERS ---
const formatContactNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 11) parts = parts.substring(0, 11); 

    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 4);
    if (parts.length >= 5) formatted += '-' + parts.substring(4, 7);
    if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) formatted += '-';

    return formatted;
};

const formatTinNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 14) parts = parts.substring(0, 14); 

    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 3);
    if (parts.length >= 4) formatted += '-' + parts.substring(3, 6);
    if (parts.length >= 7) formatted += '-' + parts.substring(6, 9);
    if (parts.length >= 10) formatted += '-' + parts.substring(9, 14);
    if (val.endsWith('-') && (parts.length === 3 || parts.length === 6 || parts.length === 9)) formatted += '-';

    return formatted;
};

// --- DYNAMIC UNITS LOGIC ---
const addUnit = () => {
    form.units.push(getEmptyUnit());
    unitPhotoPreviews.value.push({ front: null, back: null, left: null, right: null, cr: null, or: null });
    expandedUnitIndex.value = form.units.length - 1;
};

const removeUnit = (index) => {
    if (form.units.length > 1) {
        form.units.splice(index, 1);
        unitPhotoPreviews.value.splice(index, 1);
    }
};

const toggleUnit = (index) => {
    expandedUnitIndex.value = expandedUnitIndex.value === index ? -1 : index;
};

const handleFileChange = (event, index, field, previewField) => {
    const file = event.target.files[0];
    if (file) {
        form.units[index][field] = file;
        unitPhotoPreviews.value[index][previewField] = URL.createObjectURL(file);
        form.clearErrors(`units.${index}.${field}`);
    }
};

// --- COMPUTED VALIDATION & STEPS ---
const totalSteps = computed(() => selectedType.value === 'new_franchise' ? 3 : 2);
const stepName = computed(() => {
    if (currentStep.value === 1) return 'Select Application Type';
    if (currentStep.value === 2) return 'Owner Details';
    return 'Franchise Units';
});

const isStep2Valid = computed(() => {
    if (selectedType.value === 'change_owner_deceased' && !form.selected_franchise_id) return false;

    if (form.owner_mode === 'existing') {
        if (!form.existing_operator_id) return false;
    } else {
        if (!form.new_owner_first_name || !form.new_owner_last_name || form.new_owner_contact.length < 13 || form.new_owner_tin.length < 17) return false;
        if (!form.new_owner_province || !form.new_owner_city || !form.new_owner_barangay || !form.new_owner_address) return false;
    }
    return true;
});

const isStep3Valid = computed(() => {
    if (selectedType.value !== 'new_franchise') return true; 
    return form.units.every(u => 
        u.make_id && u.zone_id && u.model_year && u.plate_number && 
        u.motor_number && u.chassis_number && u.cr_number && // Now mandatory
        u.unit_front_photo && u.cr_photo && u.or_photo
    );
});

// --- ACTIONS ---
const closeModal = () => { 
    form.reset();
    form.clearErrors();
    currentStep.value = 1;
    unitPhotoPreviews.value = [{ front: null, back: null, left: null, right: null, cr: null, or: null }];
    citiesList.value = []; barangaysList.value = [];
    searchFranchiseText.value = '';
    searchOperatorText.value = '';
    emit('close');
};

const selectType = (typeId) => { 
    selectedType.value = typeId; 
    form.type = typeId; 
    currentStep.value = 2; 
    form.units = [ getEmptyUnit() ];
    unitPhotoPreviews.value = [{ front: null, back: null, left: null, right: null, cr: null, or: null }];
    form.selected_franchise_id = ''; 
    form.owner_mode = 'new';
    searchFranchiseText.value = '';
    searchOperatorText.value = '';
};

const goNext = () => {
    if (currentStep.value === 2 && selectedType.value === 'change_owner_deceased') return;
    currentStep.value++;
};

const goBack = () => {
    currentStep.value--;
};

const submit = () => {
    // Exact mapping requested
    let mappedType = selectedType.value === 'new_franchise' ? 'New Franchise' : 'Change of Owner (Deceased)';

    form.transform((data) => ({
        ...data,
        application_type: mappedType,
    })).post(route('admin.applications.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit'); 
            closeModal();
        },
    });
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[950px] max-h-[90vh] flex flex-col overflow-hidden">
                <div v-if="activeDropdown" @click="activeDropdown = null" class="absolute inset-0 z-10"></div>

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0 z-20 relative">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ stepName }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Step {{ currentStep }} of {{ totalSteps }}</p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar relative z-20">
                    
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div v-for="type in applicationTypes" :key="type.id" 
                            @click="selectType(type.id)"
                            class="flex items-center p-4 border rounded-xl hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-colors group">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-blue-600 mr-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" /></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">{{ type.name }}</h3>
                                <p class="text-sm text-gray-500">{{ type.description }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentStep === 2" class="space-y-8 animate-fade-in">
                        
                        <div v-if="selectedType === 'change_owner_deceased'" class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div class="flex items-start gap-1"><InputLabel value="Select Existing Franchise to Transfer" /><span class="text-red-600 font-bold">*</span></div>
                            <div class="relative z-40 mt-1">
                                <TextInput 
                                    v-model="searchFranchiseText" 
                                    @focus="activeDropdown = 'franchise'"
                                    @input="form.selected_franchise_id = ''; form.clearErrors('selected_franchise_id')"
                                    class="block w-full" placeholder="Search by Franchise Number or Plate..." autocomplete="off"
                                />
                                <div v-if="activeDropdown === 'franchise'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                    <ul class="py-1">
                                        <li v-for="fran in filteredFranchises" :key="fran.id" @click="selectFranchise(fran)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                            {{ fran.franchise_number || 'No Franchise Number' }} - Plate: {{ fran.unit?.plate_number || 'N/A' }}
                                        </li>
                                        <li v-if="!filteredFranchises.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No franchises found</li>
                                    </ul>
                                </div>
                            </div>
                            <InputError :message="form.errors.selected_franchise_id" class="mt-2" />
                        </div>

                        <div v-if="selectedType === 'new_franchise'" class="flex p-1 bg-gray-100 rounded-lg w-fit">
                            <button @click="form.owner_mode = 'new'" :class="form.owner_mode === 'new' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                                New Operator
                            </button>
                            <button @click="form.owner_mode = 'existing'" :class="form.owner_mode === 'existing' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium rounded-md transition-all">
                                Existing Operator
                            </button>
                        </div>

                        <div v-if="form.owner_mode === 'existing'">
                            <div class="flex items-start gap-1"><InputLabel value="Select Existing Operator" /><span class="text-red-600 font-bold">*</span></div>
                            <div class="relative z-40 mt-1">
                                <TextInput 
                                    v-model="searchOperatorText" 
                                    @focus="activeDropdown = 'operator'"
                                    @input="form.existing_operator_id = ''; form.clearErrors('existing_operator_id')"
                                    class="block w-full" placeholder="Search Operator by Name..." autocomplete="off"
                                />
                                <div v-if="activeDropdown === 'operator'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                    <ul class="py-1">
                                        <li v-for="operator in filteredOperators" :key="operator.id" @click="selectOperator(operator)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                            {{ operator.first_name }} {{ operator.last_name }}
                                        </li>
                                        <li v-if="!filteredOperators.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No operators found</li>
                                    </ul>
                                </div>
                            </div>
                            <InputError :message="form.errors.existing_operator_id" class="mt-2" />
                        </div>

                        <div v-if="form.owner_mode === 'new'" class="space-y-6">
                            <h3 class="text-md font-bold text-gray-800 border-b pb-2">Applicant Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="First Name" /><span class="text-red-600 font-bold">*</span></div>
                                    <TextInput v-model="form.new_owner_first_name" @input="form.clearErrors('new_owner_first_name')" placeholder="e.g. Juan" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_first_name" class="mt-2" />
                                </div>
                                <div><InputLabel value="Middle Name" /><TextInput v-model="form.new_owner_middle_name" placeholder="e.g. Dela Cruz" class="mt-1 block w-full" /></div>
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="Last Name" /><span class="text-red-600 font-bold">*</span></div>
                                    <TextInput v-model="form.new_owner_last_name" @input="form.clearErrors('new_owner_last_name')" placeholder="e.g. Santos" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_last_name" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="Email" /></div>
                                    <TextInput type="email" v-model="form.new_owner_email" @input="form.clearErrors('new_owner_email')" placeholder="e.g. juan@example.com" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_email" class="mt-2" />
                                </div>
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="Contact No." /><span class="text-red-600 font-bold">*</span></div>
                                    <TextInput v-model="form.new_owner_contact" @input="form.new_owner_contact = formatContactNumber($event.target.value); form.clearErrors('new_owner_contact')" placeholder="09XX-XXX-XXXX" maxlength="13" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_contact" class="mt-2" />
                                </div>
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="TIN Number" /><span class="text-red-600 font-bold">*</span></div>
                                    <TextInput v-model="form.new_owner_tin" @input="form.new_owner_tin = formatTinNumber($event.target.value); form.clearErrors('new_owner_tin')" placeholder="XXX-XXX-XXX-00000" maxlength="17" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_tin" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <div class="flex items-start gap-1"><InputLabel value="Street / House No." /><span class="text-red-600 font-bold">*</span></div>
                                    <TextInput v-model="form.new_owner_address" @input="form.clearErrors('new_owner_address')" placeholder="e.g. 123 Main Street" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.new_owner_address" class="mt-2" />
                                </div>
                                
                                <div class="relative">
                                    <div class="flex items-start gap-1"><InputLabel value="Province" /><span class="text-red-600 font-bold">*</span></div>
                                    <div class="relative z-40">
                                        <TextInput 
                                            v-model="form.new_owner_province" 
                                            @focus="activeDropdown = 'province'"
                                            @input="form.clearErrors('new_owner_province')"
                                            class="mt-1 block w-full" placeholder="Search Province..." autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ prov.name }}</li>
                                                <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.new_owner_province" class="mt-2" />
                                </div>

                                <div class="relative">
                                    <div class="flex items-start gap-1"><InputLabel value="City/Municipality" /><span class="text-red-600 font-bold">*</span></div>
                                    <div class="relative z-30">
                                        <TextInput 
                                            v-model="form.new_owner_city" 
                                            @focus="activeDropdown = 'city'"
                                            @input="form.clearErrors('new_owner_city')"
                                            :disabled="!citiesList.length"
                                            class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500" placeholder="Search City..." autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ city.name }}</li>
                                                <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.new_owner_city" class="mt-2" />
                                </div>

                                <div class="relative">
                                    <div class="flex items-start gap-1"><InputLabel value="Barangay" /><span class="text-red-600 font-bold">*</span></div>
                                    <div class="relative z-20">
                                        <TextInput 
                                            v-model="form.new_owner_barangay" 
                                            @focus="activeDropdown = 'barangay'"
                                            @input="form.clearErrors('new_owner_barangay')"
                                            :disabled="!barangaysList.length"
                                            class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500" placeholder="Search Barangay..." autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">{{ brgy.name }}</li>
                                                <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.new_owner_barangay" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel value="Application Remarks" />
                            <TextInput v-model="form.remarks" type="text" class="w-full mt-1" placeholder="Enter any notes..." />
                        </div>
                    </div>

                    <div v-if="currentStep === 3 && selectedType === 'new_franchise'" class="space-y-6 animate-fade-in">
                        <div class="flex justify-between items-center border-b pb-4">
                            <h2 class="text-xl font-semibold text-gray-800">Franchise Units</h2>
                            <button type="button" @click="addUnit" class="text-sm bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition font-medium">Add Unit</button>
                        </div>
                        
                        <div v-for="(unit, index) in form.units" :key="index" class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden transition-all duration-300 relative z-0">
                            
                            <div @click="toggleUnit(index)" class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50" :class="{'bg-blue-50': expandedUnitIndex === index}">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm" :class="expandedUnitIndex === index ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'">{{ index + 1 }}</div>
                                    <div><h3 class="font-bold text-gray-700">{{ unit.make_id ? unitMakes.find(m => m.id === unit.make_id)?.name : 'New Unit' }}</h3><p class="text-xs text-gray-500">{{ unit.plate_number || 'No Plate' }}</p></div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button v-if="form.units.length > 1" type="button" @click.stop="removeUnit(index)" class="text-red-500 text-sm font-medium">Remove</button>
                                    <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="{'rotate-180': expandedUnitIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>

                            <div v-show="expandedUnitIndex === index" class="p-6 bg-gray-50 border-t border-gray-100">
                                <div class="mb-6 bg-white p-4 rounded border border-blue-100">
                                    <div class="flex items-start gap-1"><InputLabel value="Target Zone" /><span class="text-red-600 font-bold">*</span></div>
                                    <select v-model="unit.zone_id" @change="form.clearErrors(`units.${index}.zone_id`)" class="mt-1 block w-full border-blue-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                        <option value="" disabled>Select Zone</option>
                                        <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.description }} ({{ zone.color }})</option>
                                    </select>
                                    <InputError :message="form.errors[`units.${index}.zone_id`]" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="Make" /><span class="text-red-600 font-bold">*</span></div>
                                        <select v-model="unit.make_id" @change="form.clearErrors(`units.${index}.make_id`)" class="mt-1 block w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                            <option value="" disabled>Select</option>
                                            <option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                                        </select>
                                        <InputError :message="form.errors[`units.${index}.make_id`]" class="mt-2" />
                                    </div>
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="Model Year" /><span class="text-red-600 font-bold">*</span></div>
                                        <TextInput type="number" v-model="unit.model_year" @input="form.clearErrors(`units.${index}.model_year`)" placeholder="e.g. 2024" class="mt-1 block w-full" />
                                        <InputError :message="form.errors[`units.${index}.model_year`]" class="mt-2" />
                                    </div>
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="Plate No." /><span class="text-red-600 font-bold">*</span></div>
                                        <TextInput v-model="unit.plate_number" @input="form.clearErrors(`units.${index}.plate_number`)" class="mt-1 block w-full" />
                                        <InputError :message="form.errors[`units.${index}.plate_number`]" class="mt-2" />
                                    </div>
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="Motor No." /><span class="text-red-600 font-bold">*</span></div>
                                        <TextInput v-model="unit.motor_number" @input="form.clearErrors(`units.${index}.motor_number`)" class="mt-1 block w-full" />
                                        <InputError :message="form.errors[`units.${index}.motor_number`]" class="mt-2" />
                                    </div>
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="Chassis No." /><span class="text-red-600 font-bold">*</span></div>
                                        <TextInput v-model="unit.chassis_number" @input="form.clearErrors(`units.${index}.chassis_number`)" class="mt-1 block w-full" />
                                        <InputError :message="form.errors[`units.${index}.chassis_number`]" class="mt-2" />
                                    </div>
                                    <div>
                                        <div class="flex items-start gap-1"><InputLabel value="CR Number" /><span class="text-red-600 font-bold">*</span></div>
                                        <TextInput v-model="unit.cr_number" @input="form.clearErrors(`units.${index}.cr_number`)" class="mt-1 block w-full" />
                                        <InputError :message="form.errors[`units.${index}.cr_number`]" class="mt-2" />
                                    </div>
                                </div>

                                <div class="mt-6 border-t border-gray-200 pt-4">
                                    <InputLabel value="Required Unit Photos & Documents" class="mb-4 text-base font-bold text-gray-800" />
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="border rounded-lg p-3 text-center bg-white shadow-sm relative overflow-hidden group">
                                            <div class="flex justify-center gap-1 mb-2"><p class="text-xs text-gray-500 uppercase font-bold">{{ side }}</p><span v-if="side === 'front'" class="text-red-600 font-bold text-xs">*</span></div>
                                            <div v-if="unitPhotoPreviews[index][side]" class="mb-3 relative group rounded overflow-hidden">
                                                <img :src="unitPhotoPreviews[index][side]" class="w-full h-24 object-cover" />
                                                <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <span class="text-white text-xs font-medium">Change</span>
                                                </div>
                                            </div>
                                            <label class="cursor-pointer inline-flex items-center justify-center w-full px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-gray-50 hover:bg-gray-100 transition-colors">
                                                <span>{{ unitPhotoPreviews[index][side] ? 'Replace Photo' : 'Upload Photo' }}</span>
                                                <input type="file" @change="e => handleFileChange(e, index, `unit_${side}_photo`, side)" class="hidden" accept="image/*">
                                            </label>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <div v-for="(docInfo, docKey) in { cr_photo: {label: 'OR Document', key: 'cr'}, or_photo: {label: 'CR Document', key: 'or'} }" :key="docKey" class="border border-dashed border-gray-300 rounded-lg p-4 bg-white text-center">
                                            <div class="flex justify-center gap-1 mb-2"><span class="text-sm font-semibold text-gray-700">{{ docInfo.label }}</span><span class="text-red-600 font-bold">*</span></div>
                                            <p v-if="unitPhotoPreviews[index][docInfo.key]" class="text-xs text-green-600 font-medium mb-3 truncate px-2">File Uploaded</p>
                                            <label class="cursor-pointer inline-flex items-center justify-center px-4 py-2 border border-blue-300 shadow-sm text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors w-full">
                                                <span>{{ unitPhotoPreviews[index][docInfo.key] ? 'Change File' : 'Upload File' }}</span>
                                                <input type="file" @change="e => handleFileChange(e, index, docKey, docInfo.key)" class="hidden" accept=".pdf,image/*">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center rounded-b-2xl flex-shrink-0 z-20">
                    <SecondaryButton v-if="currentStep > 1" @click="goBack">Back</SecondaryButton>
                    <div v-else></div>
                    
                    <PrimaryButton v-if="currentStep === 2 && selectedType === 'new_franchise'" @click="goNext" :disabled="!isStep2Valid" :class="{'opacity-50 cursor-not-allowed': !isStep2Valid}">Next Step</PrimaryButton>

                    <PrimaryButton v-if="currentStep === totalSteps" @click="submit" :class="{'opacity-50 cursor-not-allowed': form.processing || (!isStep2Valid && selectedType === 'change_owner_deceased') || (!isStep3Valid && selectedType === 'new_franchise')}" :disabled="form.processing || (!isStep2Valid && selectedType === 'change_owner_deceased') || (!isStep3Valid && selectedType === 'new_franchise')">
                        {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
</style>