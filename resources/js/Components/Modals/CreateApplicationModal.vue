<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    franchises: { type: Array, default: () => [] },
    operators: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'submit']);

const applicationTypes = [
    { id: 'new_franchise', name: 'New Franchise', description: 'Initiate a new franchise application.', icon: 'M12 4v16m8-8H4' },
    { id: 'change_owner_deceased', name: 'Change of Owner (Deceased)', description: 'Transfer ownership of an existing franchise to an heir.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' }
];

const currentStep = ref(1); 
const selectedType = ref('new_franchise'); 
const activeDropdown = ref(null);
const searchFranchiseText = ref('');
const searchOperatorText = ref('');

const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) { console.error("Failed to load locations:", error); }
});

const form = useForm({
    type: 'new_franchise', selected_franchise_id: '', remarks: '',
    owner_mode: 'new', existing_operator_id: '',
    new_owner_first_name: '', new_owner_middle_name: '', new_owner_last_name: '', new_owner_contact: '', new_owner_email: '', new_owner_tin: '', 
    new_owner_address: '', new_owner_province: '', new_owner_city: '', new_owner_barangay: '', 
});

const filteredProvinces = computed(() => !form.new_owner_province ? provincesList.value : provincesList.value.filter(p => p.name.toLowerCase().includes(form.new_owner_province.toLowerCase())));
const filteredCities = computed(() => !form.new_owner_city ? citiesList.value : citiesList.value.filter(c => c.name.toLowerCase().includes(form.new_owner_city.toLowerCase())));
const filteredBarangays = computed(() => !form.new_owner_barangay ? barangaysList.value : barangaysList.value.filter(b => b.name.toLowerCase().includes(form.new_owner_barangay.toLowerCase())));
const filteredFranchises = computed(() => !searchFranchiseText.value ? props.franchises : props.franchises.filter(f => (f.franchise_number && f.franchise_number.toLowerCase().includes(searchFranchiseText.value.toLowerCase())) || (f.unit?.plate_number && f.unit.plate_number.toLowerCase().includes(searchFranchiseText.value.toLowerCase()))));
const filteredOperators = computed(() => !searchOperatorText.value ? props.operators : props.operators.filter(o => `${o.first_name} ${o.last_name}`.toLowerCase().includes(searchOperatorText.value.toLowerCase())));

const selectProvince = async (prov) => {
    form.new_owner_province = prov.name; activeDropdown.value = null; form.new_owner_city = ''; form.new_owner_barangay = ''; citiesList.value = []; barangaysList.value = []; form.clearErrors('new_owner_province');
    try {
        const res = await fetch(prov.isNCR ? `https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities` : `https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`);
        citiesList.value = await res.json();
    } catch (error) { console.error(error); }
};

const selectCity = async (city) => {
    form.new_owner_city = city.name; activeDropdown.value = null; form.new_owner_barangay = ''; barangaysList.value = []; form.clearErrors('new_owner_city');
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
        barangaysList.value = await res.json();
    } catch (error) { console.error(error); }
};

const selectBarangay = (brgy) => { form.new_owner_barangay = brgy.name; activeDropdown.value = null; form.clearErrors('new_owner_barangay'); };
const selectFranchise = (fran) => { form.selected_franchise_id = fran.id; searchFranchiseText.value = `${fran.franchise_number || 'No Franchise Number'} - Plate: ${fran.unit?.plate_number || 'N/A'}`; activeDropdown.value = null; form.clearErrors('selected_franchise_id'); };
const selectOperator = (op) => { form.existing_operator_id = op.id; searchOperatorText.value = `${op.first_name} ${op.last_name}`; activeDropdown.value = null; form.clearErrors('existing_operator_id'); };

const formatContactNumber = (val) => {
    if (!val) return ''; let parts = val.replace(/\D/g, ''); if (parts.length > 11) parts = parts.substring(0, 11); 
    let formatted = ''; if (parts.length > 0) formatted += parts.substring(0, 4); if (parts.length >= 5) formatted += '-' + parts.substring(4, 7); if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) formatted += '-'; return formatted;
};

const formatTinNumber = (val) => {
    if (!val) return ''; let parts = val.replace(/\D/g, ''); if (parts.length > 14) parts = parts.substring(0, 14); 
    let formatted = ''; if (parts.length > 0) formatted += parts.substring(0, 3); if (parts.length >= 4) formatted += '-' + parts.substring(3, 6); if (parts.length >= 7) formatted += '-' + parts.substring(6, 9); if (parts.length >= 10) formatted += '-' + parts.substring(9, 14);
    if (val.endsWith('-') && (parts.length === 3 || parts.length === 6 || parts.length === 9)) formatted += '-'; return formatted;
};

const isStep2Valid = computed(() => {
    if (selectedType.value === 'change_owner_deceased' && !form.selected_franchise_id) return false;
    if (form.owner_mode === 'existing') return !!form.existing_operator_id;
    return form.new_owner_first_name && form.new_owner_last_name && form.new_owner_contact.length >= 13 && form.new_owner_tin.length >= 17 && form.new_owner_province && form.new_owner_city && form.new_owner_barangay && form.new_owner_address;
});

const closeModal = () => { form.reset(); form.clearErrors(); currentStep.value = 1; searchFranchiseText.value = ''; searchOperatorText.value = ''; emit('close'); };
const selectType = (typeId) => { selectedType.value = typeId; form.type = typeId; currentStep.value = 2; form.selected_franchise_id = ''; form.owner_mode = 'new'; searchFranchiseText.value = ''; searchOperatorText.value = ''; };

const submit = () => {
    form.transform((data) => ({ ...data, application_type: selectedType.value === 'new_franchise' ? 'New Franchise' : 'Change of Owner (Deceased)' }))
        .post(route('admin.applications.store'), { preserveScroll: true, onSuccess: () => { emit('submit'); closeModal(); } });
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[950px] max-h-[90vh] flex flex-col overflow-hidden">
                <div v-if="activeDropdown" @click="activeDropdown = null" class="absolute inset-0 z-10"></div>

                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0 z-20">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ currentStep === 1 ? 'Select Application Type' : 'Owner Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Step {{ currentStep }} of 2</p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"><svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar relative z-20">
                    <div v-if="form.errors.error" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg shadow-sm">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            <span class="font-bold">Submission Failed</span>
                        </div>
                        <p class="mt-1 text-sm ml-7">{{ form.errors.error }}</p>
                    </div>
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div v-for="type in applicationTypes" :key="type.id" @click="selectType(type.id)" class="flex items-center p-4 border rounded-xl hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-colors group">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-blue-600 mr-4 group-hover:bg-blue-600 group-hover:text-white transition-colors"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" /></svg></div>
                            <div><h3 class="font-bold text-gray-800">{{ type.name }}</h3><p class="text-sm text-gray-500">{{ type.description }}</p></div>
                        </div>
                    </div>

                    <div v-if="currentStep === 2" class="space-y-8 animate-fade-in">
                        <div v-if="selectedType === 'change_owner_deceased'" class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div class="flex items-start gap-1"><InputLabel value="Select Existing Franchise to Transfer" /><span class="text-red-600 font-bold">*</span></div>
                            <div class="relative z-40 mt-1">
                                <TextInput v-model="searchFranchiseText" @focus="activeDropdown = 'franchise'" @input="form.selected_franchise_id = ''; form.clearErrors('selected_franchise_id')" class="block w-full" placeholder="Search by Franchise Number or Plate..." autocomplete="off" />
                                <div v-if="activeDropdown === 'franchise'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                    <ul class="py-1"><li v-for="fran in filteredFranchises" :key="fran.id" @click="selectFranchise(fran)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ fran.franchise_number || 'No Franchise Number' }} - Plate: {{ fran.unit?.plate_number || 'N/A' }}</li></ul>
                                </div>
                            </div>
                            <InputError :message="form.errors.selected_franchise_id" class="mt-2" />
                        </div>

                        <div v-if="selectedType === 'new_franchise'" class="flex p-1 bg-gray-100 rounded-lg w-fit">
                            <button @click="form.owner_mode = 'new'" :class="form.owner_mode === 'new' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium rounded-md transition-all">New Operator</button>
                            <button @click="form.owner_mode = 'existing'" :class="form.owner_mode === 'existing' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-2 text-sm font-medium rounded-md transition-all">Existing Operator</button>
                        </div>

                        <div v-if="form.owner_mode === 'existing'">
                            <div class="flex items-start gap-1"><InputLabel value="Select Existing Operator" /><span class="text-red-600 font-bold">*</span></div>
                            <div class="relative z-40 mt-1">
                                <TextInput v-model="searchOperatorText" @focus="activeDropdown = 'operator'" @input="form.existing_operator_id = ''; form.clearErrors('existing_operator_id')" class="block w-full" placeholder="Search Operator by Name..." autocomplete="off" />
                                <div v-if="activeDropdown === 'operator'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                    <ul class="py-1"><li v-for="operator in filteredOperators" :key="operator.id" @click="selectOperator(operator)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ operator.first_name }} {{ operator.last_name }}</li></ul>
                                </div>
                            </div>
                            <InputError :message="form.errors.existing_operator_id" class="mt-2" />
                        </div>

                        <div v-if="form.owner_mode === 'new'" class="space-y-6">
                            <h3 class="text-md font-bold text-gray-800 border-b pb-2">Applicant Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><div class="flex items-start gap-1"><InputLabel value="First Name" /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="form.new_owner_first_name" @input="form.clearErrors('new_owner_first_name')" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_first_name" class="mt-2" /></div>
                                <div><InputLabel value="Middle Name" /><TextInput v-model="form.new_owner_middle_name" class="mt-1 block w-full" /></div>
                                <div><div class="flex items-start gap-1"><InputLabel value="Last Name" /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="form.new_owner_last_name" @input="form.clearErrors('new_owner_last_name')" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_last_name" class="mt-2" /></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><div class="flex items-start gap-1"><InputLabel value="Email" /></div><TextInput type="email" v-model="form.new_owner_email" @input="form.clearErrors('new_owner_email')" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_email" class="mt-2" /></div>
                                <div><div class="flex items-start gap-1"><InputLabel value="Contact No." /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="form.new_owner_contact" @input="form.new_owner_contact = formatContactNumber($event.target.value); form.clearErrors('new_owner_contact')" maxlength="13" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_contact" class="mt-2" /></div>
                                <div><div class="flex items-start gap-1"><InputLabel value="TIN Number" /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="form.new_owner_tin" @input="form.new_owner_tin = formatTinNumber($event.target.value); form.clearErrors('new_owner_tin')" maxlength="17" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_tin" class="mt-2" /></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div><div class="flex items-start gap-1"><InputLabel value="Street / House No." /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="form.new_owner_address" @input="form.clearErrors('new_owner_address')" class="mt-1 block w-full" /><InputError :message="form.errors.new_owner_address" class="mt-2" /></div>
                                <div class="relative"><div class="flex items-start gap-1"><InputLabel value="Province" /><span class="text-red-600 font-bold">*</span></div><div class="relative z-40"><TextInput v-model="form.new_owner_province" @focus="activeDropdown = 'province'" @input="form.clearErrors('new_owner_province')" class="mt-1 block w-full" />
                                    <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-60 overflow-y-auto z-50"><ul class="py-1"><li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ prov.name }}</li></ul></div></div><InputError :message="form.errors.new_owner_province" class="mt-2" /></div>
                                <div class="relative"><div class="flex items-start gap-1"><InputLabel value="City" /><span class="text-red-600 font-bold">*</span></div><div class="relative z-30"><TextInput v-model="form.new_owner_city" @focus="activeDropdown = 'city'" @input="form.clearErrors('new_owner_city')" :disabled="!citiesList.length" class="mt-1 block w-full" />
                                    <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-60 overflow-y-auto z-50"><ul class="py-1"><li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ city.name }}</li></ul></div></div><InputError :message="form.errors.new_owner_city" class="mt-2" /></div>
                                <div class="relative"><div class="flex items-start gap-1"><InputLabel value="Barangay" /><span class="text-red-600 font-bold">*</span></div><div class="relative z-20"><TextInput v-model="form.new_owner_barangay" @focus="activeDropdown = 'barangay'" @input="form.clearErrors('new_owner_barangay')" :disabled="!barangaysList.length" class="mt-1 block w-full" />
                                    <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-xl max-h-60 overflow-y-auto z-50"><ul class="py-1"><li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ brgy.name }}</li></ul></div></div><InputError :message="form.errors.new_owner_barangay" class="mt-2" /></div>
                            </div>
                        </div>

                        <div class="mt-4"><InputLabel value="Application Remarks" /><TextInput v-model="form.remarks" type="text" class="w-full mt-1" /></div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center rounded-b-2xl z-20">
                    <SecondaryButton v-if="currentStep > 1" @click="currentStep--">Back</SecondaryButton><div v-else></div>
                    <PrimaryButton v-if="currentStep === 2" @click="submit" :disabled="form.processing || !isStep2Valid" :class="{'opacity-50 cursor-not-allowed': form.processing || !isStep2Valid}">{{ form.processing ? 'Submitting...' : 'Send to Applicant' }}</PrimaryButton>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; } .fade-enter-from, .fade-leave-to { opacity: 0; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; } @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.custom-scrollbar::-webkit-scrollbar { width: 5px; } .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
</style>