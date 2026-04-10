<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    evaluationRequirements: { type: Object, default: () => ({}) },
    franchises: { type: Array, default: () => [] },
    barangays: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    operators: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    applications: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'submit']);
const page = usePage();

// --- PSGC API STATES ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

const selectedProvinceCode = ref('');
const selectedCityCode = ref('');

// --- MODAL STATES ---
const currentStep = ref(1); 
const selectedType = ref('change_unit'); 
const ownerMode = ref('existing');
const unitMode = ref('existing');
const docPreviews = ref({}); 
const unitPhotoPreviews = ref({ front: null, back: null, left: null, right: null }); 

// Warning Modal States
const showWarningModal = ref(false);
const warningMessage = ref('');
const conflictingApplication = ref(null);

const applicationTypes = [
    { id: 'change_unit', name: 'Change of Unit', description: 'Replace tricycle unit.', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 'change_owner', name: 'Change of Owner', description: 'Transfer ownership.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    // Add the Renewal option here
    { id: 'renewal', name: 'Renewal', description: 'Renew existing franchise.', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
    // ADD NEW DRIVER OPTION
    { id: 'new_driver', name: 'New Driver', description: 'Propose a new driver.', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' }
];
// --- FORMS ---
const form = useForm({
    type: 'change_unit', 
    selected_franchise_id: '', 
    remarks: '',
    
    owner_mode: 'existing', 
    unit_mode: 'existing',

    // Owner Fields
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
    
    // Unit Fields
    existing_unit_id: '', make_id: '', model_year: '', plate_number: '', motor_number: '', chassis_number: '', cr_number: '',
    unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    
    // New Driver Fields
    new_driver_first_name: '', new_driver_middle_name: '', new_driver_last_name: '',
    new_driver_contact: '', new_driver_license_number: '', new_driver_license_expiration_date: '',
    new_driver_province: '', new_driver_city: '', new_driver_barangay: '', new_driver_street: '',
    driver_user_photo: null, driver_license_front: null, driver_license_back: null,

    // Document uploads
    documents: {} 
});

// --- FORMATTERS ---
const formatContactNumber = (val) => {
    let parts = val.replace(/[^0-9]/g, '');
    if (parts.length > 11) parts = parts.substring(0, 11);
    
    let formatted = parts.substring(0, 4);
    if (parts.length >= 5) formatted += '-' + parts.substring(4, 7);
    if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) {
        formatted += '-';
    }
    return formatted;
};

const formatTinNumber = (val) => {
    let parts = val.replace(/[^0-9]/g, '');
    if (parts.length > 12) parts = parts.substring(0, 12);
    
    let formatted = parts.substring(0, 3);
    if (parts.length >= 4) formatted += '-' + parts.substring(3, 6);
    if (parts.length >= 7) formatted += '-' + parts.substring(6, 9);
    if (parts.length >= 10) formatted += '-' + parts.substring(9, 12);
    
    if (val.endsWith('-') && (parts.length === 3 || parts.length === 6 || parts.length === 9)) {
        formatted += '-';
    }
    return formatted;
};

// --- API FETCHING LOGIC ---
onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load provinces:", error);
    }
});

const fetchCities = async (provinceCode) => {
    if (!provinceCode) return;
    try {
        const isNCR = provinceCode === '130000000';
        const endpoint = isNCR 
            ? 'https://psgc.gitlab.io/api/regions/130000000/cities-municipalities'
            : `https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities`;
        const res = await fetch(endpoint);
        citiesList.value = (await res.json()).sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) { console.error(error); }
};

const fetchBarangays = async (cityCode) => {
    if (!cityCode) return;
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays`);
        barangaysList.value = (await res.json()).sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) { console.error(error); }
};

// --- API DROPDOWN HANDLERS ---
const handleProvinceChange = async (event) => {
    const code = event.target.value;
    selectedProvinceCode.value = code;
    const p = provincesList.value.find(x => x.code === code);
    
    if (selectedType.value === 'new_driver') {
        form.new_driver_province = p ? p.name : '';
        form.new_driver_city = ''; 
        form.new_driver_barangay = '';
    } else {
        form.new_owner_province = p ? p.name : '';
        form.new_owner_city = ''; 
        form.new_owner_barangay = '';
    }
    
    selectedCityCode.value = '';
    citiesList.value = []; barangaysList.value = [];
    
    await fetchCities(code);
};

const handleCityChange = async (event) => {
    const code = event.target.value;
    selectedCityCode.value = code;
    const c = citiesList.value.find(x => x.code === code);
    
    if (selectedType.value === 'new_driver') {
        form.new_driver_city = c ? c.name : '';
        form.new_driver_barangay = '';
    } else {
        form.new_owner_city = c ? c.name : '';
        form.new_owner_barangay = '';
    }

    barangaysList.value = [];
    
    await fetchBarangays(code);
};

// Watch for Server-Side Validation Errors as a fallback
watch(() => form.errors.selected_franchise_id, (newError) => {
    // Only trigger the warning modal for server-returned duplicate/conflict errors, 
    // NOT our simple client-side "Required" validation.
    if (newError && newError !== 'Please select a franchise to modify.') {
        warningMessage.value = newError;
        showWarningModal.value = true;
        currentStep.value = 2; 
        form.selected_franchise_id = ''; 
        form.clearErrors('selected_franchise_id'); 
    }
});

const currentEvaluationRequirements = computed(() => {
    const typeObj = applicationTypes.find(t => t.id === selectedType.value);
    if (!typeObj) return [];
    return props.evaluationRequirements[typeObj.name] || [];
});

const isUnitRequired = computed(() => ['change_unit'].includes(selectedType.value));
const isOwnerRequired = computed(() => ['change_owner'].includes(selectedType.value));
const isNewDriverRequired = computed(() => ['new_driver'].includes(selectedType.value));
const isFranchiseSelectRequired = computed(() => true); 

const areAllDocsUploaded = computed(() => {
    const reqs = currentEvaluationRequirements.value;
    return reqs.every(r => form.documents[r.id]);
});

const duplicateApplicationError = computed(() => {
    if (!form.selected_franchise_id) return null;

    const selectedFranchise = props.franchises.find(f => f.id == form.selected_franchise_id);
    if (!selectedFranchise) return null;

    if (selectedType.value === 'change_unit') {
        if (selectedFranchise.conflicting_change_unit) {
            return `An active Change of Unit application already exists for this franchise. Please complete or cancel the existing application before submitting a new one.`;
        }
    } else if (selectedType.value === 'change_owner') {
        if (selectedFranchise.conflicting_change_owner) {
            return `An active Change of Owner application already exists for this franchise. Please complete or cancel the existing application before submitting a new one.`;
        }
    } else if (selectedType.value === 'new_driver') {
        if (selectedFranchise.conflicting_new_driver) {
            return `An active New Driver application already exists for this franchise. Please complete or cancel the existing application before submitting a new one.`;
        }
    } else if (selectedType.value === 'renewal') {
        // Handle explicit active renewal collision
        if (selectedFranchise.conflicting_renewal) {
            return `A Renewal application for the current fiscal year already exists for this franchise.`;
        } 
        // NEW CRITERIA: Ensure they can only manually create if one was previously rejected
        else if (!selectedFranchise.has_rejected_renewal) {
            return `Manual creation of Renewal applications is disabled. You may only manually create a renewal if your auto-generated renewal application has been Rejected.`;
        }
    }
    
    return null;
});

const validateFranchiseSelection = () => {
    const error = duplicateApplicationError.value;
    if (error) {
        warningMessage.value = error;
        
        const selectedFranchise = props.franchises.find(f => f.id == form.selected_franchise_id);
        
        // Directly pull the conflicting application from the selected franchise object
        if (selectedType.value === 'renewal') {
            conflictingApplication.value = selectedFranchise?.conflicting_renewal || null;
        } else if (selectedType.value === 'change_unit') {
            conflictingApplication.value = selectedFranchise?.conflicting_change_unit || null;
        } else if (selectedType.value === 'change_owner') {
            conflictingApplication.value = selectedFranchise?.conflicting_change_owner || null;
        } else if (selectedType.value === 'new_driver') {
            conflictingApplication.value = selectedFranchise?.conflicting_new_driver || null;
        } else {
            conflictingApplication.value = null;
        }

        showWarningModal.value = true;
        form.selected_franchise_id = '';
    }
};

const goToNextStep = () => {
    if (currentStep.value !== 2) {
        currentStep.value++;
        return;
    }

    form.clearErrors();
    let isValid = true;

    if (isFranchiseSelectRequired.value && !form.selected_franchise_id) {
        form.setError('selected_franchise_id', 'Please select a franchise to modify.');
        isValid = false;
    }

    if (isOwnerRequired.value) {
        if (ownerMode.value === 'existing') {
            if (!form.existing_operator_id) {
                form.setError('existing_operator_id', 'Please select an existing owner.');
                isValid = false;
            }
        } else if (ownerMode.value === 'new') {
            if (!form.new_owner_first_name) { form.setError('new_owner_first_name', 'Required.'); isValid = false; }
            if (!form.new_owner_last_name) { form.setError('new_owner_last_name', 'Required.'); isValid = false; }
            if (!form.new_owner_contact) { form.setError('new_owner_contact', 'Required.'); isValid = false; }
            if (!form.new_owner_tin) { form.setError('new_owner_tin', 'Required.'); isValid = false; }
            if (!form.new_owner_address) { form.setError('new_owner_address', 'Required.'); isValid = false; }
            if (!form.new_owner_province) { form.setError('new_owner_province', 'Required.'); isValid = false; }
            if (!form.new_owner_city) { form.setError('new_owner_city', 'Required.'); isValid = false; }
            if (!form.new_owner_barangay) { form.setError('new_owner_barangay', 'Required.'); isValid = false; }
        }
    }

    if (isUnitRequired.value) {
        if (unitMode.value === 'existing') {
            if (!form.existing_unit_id) {
                form.setError('existing_unit_id', 'Please select an existing unit.');
                isValid = false;
            }
        } else if (unitMode.value === 'new') {
            if (!form.make_id) { form.setError('make_id', 'Required.'); isValid = false; }
            if (!form.model_year) { form.setError('model_year', 'Required.'); isValid = false; }
            if (!form.plate_number) { form.setError('plate_number', 'Required.'); isValid = false; }
            if (!form.motor_number) { form.setError('motor_number', 'Required.'); isValid = false; }
            if (!form.chassis_number) { form.setError('chassis_number', 'Required.'); isValid = false; }
            if (!form.cr_number) { form.setError('cr_number', 'Required.'); isValid = false; }
            if (!form.unit_front_photo || !form.unit_back_photo || !form.unit_left_photo || !form.unit_right_photo) {
                form.setError('unit_photos', 'Please upload all 4 photos (front, back, left, right) of the proposed unit.');
                isValid = false;
            }
        }
    }

    if (isNewDriverRequired.value) {
        if (!form.new_driver_first_name) { form.setError('new_driver_first_name', 'Required.'); isValid = false; }
        if (!form.new_driver_last_name) { form.setError('new_driver_last_name', 'Required.'); isValid = false; }
        if (!form.new_driver_contact) { form.setError('new_driver_contact', 'Required.'); isValid = false; }
        if (!form.new_driver_license_number) { form.setError('new_driver_license_number', 'Required.'); isValid = false; }
        if (!form.new_driver_license_expiration_date) { form.setError('new_driver_license_expiration_date', 'Required.'); isValid = false; }
        if (!form.new_driver_province) { form.setError('new_driver_province', 'Required.'); isValid = false; }
        if (!form.new_driver_city) { form.setError('new_driver_city', 'Required.'); isValid = false; }
        if (!form.new_driver_barangay) { form.setError('new_driver_barangay', 'Required.'); isValid = false; }
        if (!form.new_driver_street) { form.setError('new_driver_street', 'Required.'); isValid = false; }
        if (!form.driver_user_photo) { form.setError('driver_user_photo', 'Required.'); isValid = false; }
        if (!form.driver_license_front) { form.setError('driver_license_front', 'Required.'); isValid = false; }
        if (!form.driver_license_back) { form.setError('driver_license_back', 'Required.'); isValid = false; }
    }

    if (!areAllDocsUploaded.value) {
        form.setError('documents', 'Please upload all required evaluation documents before proceeding.');
        isValid = false;
    }

    if (duplicateApplicationError.value) {
        validateFranchiseSelection(); 
        isValid = false;
    }

    if (isValid) {
        currentStep.value++;
    }
};

const closeModal = () => { 
    form.reset();
    form.clearErrors();
    currentStep.value = 1;
    docPreviews.value = {}; 
    unitPhotoPreviews.value = { front: null, back: null, left: null, right: null };
    ownerMode.value = 'existing'; unitMode.value = 'existing';
    
    // Reset API state
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';
    citiesList.value = [];
    barangaysList.value = [];

    emit('close');
};

const goBack = () => {
    if (currentStep.value === 1) {
        closeModal();
    } else {
        currentStep.value--;
        if (currentStep.value === 1) {
            // Completely reset form and errors when returning to Step 1
            form.reset();
            form.clearErrors();
            docPreviews.value = {}; 
            unitPhotoPreviews.value = { front: null, back: null, left: null, right: null };
            ownerMode.value = 'existing'; 
            unitMode.value = 'existing';
            selectedProvinceCode.value = '';
            selectedCityCode.value = '';
        }
    }
};

const selectType = (typeId) => { 
    selectedType.value = typeId; 
    form.type = typeId; 
    currentStep.value = 2; 
    
    form.documents = {};
    docPreviews.value = {};
    form.unit_front_photo = null; form.unit_back_photo = null; form.unit_left_photo = null; form.unit_right_photo = null;
    form.existing_operator_id = '';
    form.existing_unit_id = '';
    form.selected_franchise_id = ''; 
};

const handleDocChange = (event, reqId) => {
    const file = event.target.files[0];
    if (file) { 
        form.documents = { ...form.documents, [reqId]: file };
        docPreviews.value = { ...docPreviews.value, [reqId]: file.name };
    }
};

const handleUnitPhoto = (event, side) => {
    const file = event.target.files[0];
    if (file) { form[`unit_${side}_photo`] = file; unitPhotoPreviews.value[side] = URL.createObjectURL(file); }
};

const submit = () => {
    if (selectedType.value === 'change_unit') {
        form.unit_mode = unitMode.value;
        form.post(route('franchise.applications.store-change-unit'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('submit'); 
                closeModal();
            },
        });
    } else if (selectedType.value === 'change_owner') {
        form.owner_mode = ownerMode.value; 
        form.post(route('franchise.applications.store-change-owner'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('submit'); 
                closeModal();
            },
        });
    } else if (selectedType.value === 'renewal') {
        // Handle Renewal Submission
        form.post(route('franchise.applications.store-renewal'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('submit'); 
                closeModal();
            },
        });
    } else if (selectedType.value === 'new_driver') {
        form.post(route('franchise.applications.store-new-driver'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('submit'); 
                closeModal();
            },
        });
    } 
};

const ownerSearchQuery = ref('');
const isOwnerDropdownOpen = ref(false);

const filteredAvailableOperators = computed(() => {
    // The current logged-in operator making the application is passed globally by the controller.
    // We can pull their ID directly from Inertia's page props.
    const currentOperatorId = page.props.operator?.id;

    return props.operators.filter(o => {
        // 1. Exclude the current logged-in operator from the dropdown
        if (currentOperatorId && o.id === currentOperatorId) return false;

        // 2. Apply text search
        if (ownerSearchQuery.value) {
            const query = ownerSearchQuery.value.toLowerCase();
            return o.name.toLowerCase().includes(query) || (o.email && o.email.toLowerCase().includes(query));
        }

        return true;
    });
});

const selectExistingOwner = (operator) => {
    form.existing_operator_id = operator.id;
    ownerSearchQuery.value = operator.name;
    isOwnerDropdownOpen.value = false;
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[850px] max-h-[90vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">
                            {{ currentStep === 1 ? 'Select Application Type' : applicationTypes.find(t => t.id === selectedType).name }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Step {{ currentStep }} of 3</p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                    
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

                    <div v-if="currentStep === 2" class="space-y-6">
                        <div v-if="isFranchiseSelectRequired">
                            <InputLabel>Select Existing Franchise to Modify <span class="text-red-500">*</span></InputLabel>
                            <select v-model="form.selected_franchise_id" @change="validateFranchiseSelection" :class="form.errors.selected_franchise_id ? 'border-red-500 ring-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'" class="w-full rounded-lg shadow-sm text-sm py-2 mt-1">
                                <option value="" disabled>-- Choose Unit --</option>
                                <option v-for="fran in franchises" :key="fran.id" :value="fran.id">
                                    {{ fran.mtfrb_case_no || `Franchise #${fran.id}` }} 
                                    - {{ fran.current_active_unit?.new_unit?.make?.name || 'Unknown Make' }} 
                                    (Plate: {{ fran.current_active_unit?.new_unit?.plate_number || 'N/A' }})
                                </option>
                            </select>
                            <p v-if="form.errors.selected_franchise_id" class="text-red-500 text-xs mt-1">{{ form.errors.selected_franchise_id }}</p>
                        </div>

                        <div v-if="isOwnerRequired">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">New Owner Details</h3>
                            <div class="flex border-b border-gray-200 mb-4">
                                <button type="button" @click="ownerMode = 'existing'" :class="ownerMode === 'existing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Select Existing Owner</button>
                                <button type="button" @click="ownerMode = 'new'" :class="ownerMode === 'new' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Register New Owner</button>
                            </div>

                            <div v-if="ownerMode === 'existing'" class="p-4 bg-gray-50 rounded-lg border border-gray-200 relative">
    <InputLabel>Search / Select Existing Owner <span class="text-red-500">*</span></InputLabel>
    <p v-if="form.errors.existing_operator_id" class="text-red-500 text-xs mt-1 mb-2">{{ form.errors.existing_operator_id }}</p>

    <div v-if="isOwnerDropdownOpen" @click="isOwnerDropdownOpen = false" class="fixed inset-0 z-10"></div>

    <div class="relative mt-1 z-20">
        <input 
            type="text" 
            v-model="ownerSearchQuery"
            @focus="isOwnerDropdownOpen = true"
            @input="isOwnerDropdownOpen = true; form.existing_operator_id = ''"
            placeholder="Search by name or email..."
            class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 py-2 pr-10"
        />
        
        <button 
            v-if="ownerSearchQuery" 
            @click="ownerSearchQuery = ''; form.existing_operator_id = ''; isOwnerDropdownOpen = true" 
            class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600"
            type="button"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div 
            v-if="isOwnerDropdownOpen" 
            class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-y-auto custom-scrollbar"
        >
            <div v-if="filteredAvailableOperators.length === 0" class="p-3 text-sm text-gray-500 text-center">
                No matching owners found.
            </div>
            <div 
                v-for="o in filteredAvailableOperators" 
                :key="o.id" 
                @click="selectExistingOwner(o)"
                class="p-3 text-sm hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                :class="{'bg-blue-100 font-semibold text-blue-700': form.existing_operator_id === o.id}"
            >
                {{ o.name }} <span class="text-xs text-gray-500 ml-1">({{ o.email }})</span>
            </div>
        </div>
    </div>
</div>

                            <div v-if="ownerMode === 'new'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div><InputLabel>First Name <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_owner_first_name" :class="{'border-red-500': form.errors.new_owner_first_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_owner_first_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_first_name }}</p></div>
                                <div><InputLabel>Middle Name</InputLabel><TextInput v-model="form.new_owner_middle_name" :class="{'border-red-500': form.errors.new_owner_middle_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_owner_middle_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_middle_name }}</p></div>
                                <div><InputLabel>Last Name <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_owner_last_name" :class="{'border-red-500': form.errors.new_owner_last_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_owner_last_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_last_name }}</p></div>
                                
                                <div class="sm:col-span-2"><InputLabel>Email Address</InputLabel><TextInput type="email" v-model="form.new_owner_email" :class="{'border-red-500': form.errors.new_owner_email}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_owner_email" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_email }}</p></div>
                                <div>
                                    <InputLabel>Contact Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.new_owner_contact" @input="form.new_owner_contact = formatContactNumber($event.target.value)" :class="{'border-red-500': form.errors.new_owner_contact}" class="w-full text-sm py-1.5 mt-1" placeholder="09XX-XXX-XXXX"/>
                                    <p v-if="form.errors.new_owner_contact" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_contact }}</p>
                                </div>
                                
                                <div>
                                    <InputLabel>TIN Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.new_owner_tin" @input="form.new_owner_tin = formatTinNumber($event.target.value)" :class="{'border-red-500': form.errors.new_owner_tin}" class="w-full text-sm py-1.5 mt-1" placeholder="XXX-XXX-XXX-XXX" />
                                    <p v-if="form.errors.new_owner_tin" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_tin }}</p>
                                </div>
                                
                                <div class="sm:col-span-2"><InputLabel>Street Address <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_owner_address" :class="{'border-red-500': form.errors.new_owner_address}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_owner_address" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_address }}</p></div>
                                
                                <div>
                                    <InputLabel>Province <span class="text-red-500">*</span></InputLabel>
                                    <select v-model="selectedProvinceCode" @change="handleProvinceChange" :class="{'border-red-500': form.errors.new_owner_province}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="" disabled>-- Select Province --</option>
                                        <option v-for="p in provincesList" :key="p.code" :value="p.code">{{ p.name }}</option>
                                    </select>
                                    <p v-if="form.errors.new_owner_province" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_province }}</p>
                                </div>
                                <div>
                                    <InputLabel>City/Municipality <span class="text-red-500">*</span></InputLabel>
                                    <select v-model="selectedCityCode" @change="handleCityChange" :disabled="!citiesList.length" :class="{'border-red-500': form.errors.new_owner_city}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                                        <option value="" disabled>-- Select City --</option>
                                        <option v-for="c in citiesList" :key="c.code" :value="c.code">{{ c.name }}</option>
                                    </select>
                                    <p v-if="form.errors.new_owner_city" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_city }}</p>
                                </div>
                                <div>
                                    <InputLabel>Barangay <span class="text-red-500">*</span></InputLabel>
                                    <select v-model="form.new_owner_barangay" :disabled="!barangaysList.length" :class="{'border-red-500': form.errors.new_owner_barangay}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100">
                                        <option value="" disabled>-- Select Barangay --</option>
                                        <option v-for="b in barangaysList" :key="b.code" :value="b.name">{{ b.name }}</option>
                                    </select>
                                    <p v-if="form.errors.new_owner_barangay" class="text-red-500 text-xs mt-1">{{ form.errors.new_owner_barangay }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="isUnitRequired">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">New Unit Details</h3>
                            <div class="flex border-b border-gray-200 mb-4">
                                <button type="button" @click="unitMode = 'existing'" :class="unitMode === 'existing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Select Existing Unit</button>
                                <button type="button" @click="unitMode = 'new'" :class="unitMode === 'new' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Register New Unit</button>
                            </div>

                            <div v-if="unitMode === 'existing'" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <InputLabel>Search / Select Existing Unit <span class="text-red-500">*</span></InputLabel>
                                <select v-model="form.existing_unit_id" :class="{'border-red-500': form.errors.existing_unit_id}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 py-2 mt-1">
                                    <option value="">-- Select Unit --</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.plate }} - {{ u.make }} (Motor: {{ u.motor }})</option>
                                </select>
                                <p v-if="form.errors.existing_unit_id" class="text-red-500 text-xs mt-1">{{ form.errors.existing_unit_id }}</p>
                            </div>

                            <div v-if="unitMode === 'new'" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <InputLabel>Make <span class="text-red-500">*</span></InputLabel>
                                        <select v-model="form.make_id" :class="{'border-red-500': form.errors.make_id}" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1">
                                            <option value="">-- Select Make --</option>
                                            <option v-for="m in unitMakes" :key="m.id" :value="m.id">{{ m.name }}</option>
                                        </select>
                                        <p v-if="form.errors.make_id" class="text-red-500 text-xs mt-1">{{ form.errors.make_id }}</p>
                                    </div>
                                    <div><InputLabel>Model Year <span class="text-red-500">*</span></InputLabel><TextInput type="number" v-model="form.model_year" :class="{'border-red-500': form.errors.model_year}" class="w-full text-sm py-1.5 mt-1" placeholder="YYYY" />
                                        <p v-if="form.errors.model_year" class="text-red-500 text-xs mt-1">{{ form.errors.model_year }}</p>
                                    </div>
                                    <div><InputLabel>Plate No. <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.plate_number" :class="{'border-red-500': form.errors.plate_number}" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.plate_number" class="text-red-500 text-xs mt-1">{{ form.errors.plate_number }}</p>
                                    </div>
                                    <div><InputLabel>Motor No. <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.motor_number" :class="{'border-red-500': form.errors.motor_number}" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.motor_number" class="text-red-500 text-xs mt-1">{{ form.errors.motor_number }}</p>
                                    </div>
                                    <div><InputLabel>Chassis No. <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.chassis_number" :class="{'border-red-500': form.errors.chassis_number}" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.chassis_number" class="text-red-500 text-xs mt-1">{{ form.errors.chassis_number }}</p>
                                    </div>
                                    <div><InputLabel>CR No. <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.cr_number" :class="{'border-red-500': form.errors.cr_number}" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.cr_number" class="text-red-500 text-xs mt-1">{{ form.errors.cr_number }}</p>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <InputLabel class="mb-1">Proposed Unit Photos <span class="text-red-500">*</span></InputLabel>
                                    <p v-if="form.errors.unit_photos" class="text-red-500 text-xs mb-2">{{ form.errors.unit_photos }}</p>
                                    <p class="text-[11px] text-gray-500 mb-2">Upload clear photos of all 4 sides of the proposed unit.</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" 
                                                class="aspect-square bg-gray-50 hover:bg-gray-100 border border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center relative cursor-pointer overflow-hidden transition-colors">
                                            <img v-if="unitPhotoPreviews[side]" :src="unitPhotoPreviews[side]" class="w-full h-full object-cover" />
                                            <div v-else class="text-center p-2">
                                                <svg class="w-6 h-6 text-gray-400 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider block">{{ side }}</span>
                                            </div>
                                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" accept="image/*" @change="(e) => handleUnitPhoto(e, side)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="isNewDriverRequired" class="space-y-6">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-4 flex items-start space-x-3">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <div><h4 class="text-sm font-semibold text-blue-900">New Driver Application</h4><p class="text-xs text-blue-700 mt-1">Provide the details of the proposed new driver. The license number MUST be unique!</p></div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div><InputLabel>First Name <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_driver_first_name" :class="{'border-red-500': form.errors.new_driver_first_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_driver_first_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_first_name }}</p></div>
                                <div><InputLabel>Middle Name</InputLabel><TextInput v-model="form.new_driver_middle_name" :class="{'border-red-500': form.errors.new_driver_middle_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_driver_middle_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_middle_name }}</p></div>
                                <div><InputLabel>Last Name <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_driver_last_name" :class="{'border-red-500': form.errors.new_driver_last_name}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_driver_last_name" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_last_name }}</p></div>
                                
                                <div><InputLabel>Contact Number <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_driver_contact" @input="form.new_driver_contact = formatContactNumber($event.target.value)" :class="{'border-red-500': form.errors.new_driver_contact}" class="w-full text-sm py-1.5 mt-1" placeholder="09XX-XXX-XXXX"/><p v-if="form.errors.new_driver_contact" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_contact }}</p></div>
                                <div><InputLabel>License Number <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_driver_license_number" :class="{'border-red-500': form.errors.new_driver_license_number}" class="w-full text-sm py-1.5 mt-1 uppercase" /><p v-if="form.errors.new_driver_license_number" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_license_number }}</p></div>
                                <div><InputLabel>Expiration Date <span class="text-red-500">*</span></InputLabel><TextInput type="date" v-model="form.new_driver_license_expiration_date" :class="{'border-red-500': form.errors.new_driver_license_expiration_date}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_driver_license_expiration_date" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_license_expiration_date }}</p></div>

                                <div><InputLabel>Province <span class="text-red-500">*</span></InputLabel><select v-model="selectedProvinceCode" @change="handleProvinceChange" :class="{'border-red-500': form.errors.new_driver_province}" class="w-full text-sm border-gray-300 rounded-md py-1.5 mt-1"><option value="">Select Province</option><option v-for="p in provincesList" :key="p.code" :value="p.code">{{ p.name }}</option></select><p v-if="form.errors.new_driver_province" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_province }}</p></div>
                                <div><InputLabel>City/Municipality <span class="text-red-500">*</span></InputLabel><select v-model="selectedCityCode" @change="handleCityChange" :disabled="!selectedProvinceCode" :class="{'border-red-500': form.errors.new_driver_city}" class="w-full text-sm border-gray-300 rounded-md py-1.5 mt-1"><option value="">Select City</option><option v-for="c in citiesList" :key="c.code" :value="c.code">{{ c.name }}</option></select><p v-if="form.errors.new_driver_city" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_city }}</p></div>
                                <div><InputLabel>Barangay <span class="text-red-500">*</span></InputLabel><select v-model="form.new_driver_barangay" :disabled="!selectedCityCode" :class="{'border-red-500': form.errors.new_driver_barangay}" class="w-full text-sm border-gray-300 rounded-md py-1.5 mt-1"><option value="">Select Barangay</option><option v-for="b in barangaysList" :key="b.code" :value="b.name">{{ b.name }}</option></select><p v-if="form.errors.new_driver_barangay" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_barangay }}</p></div>
                                <div class="sm:col-span-3"><InputLabel>Street Address <span class="text-red-500">*</span></InputLabel><TextInput v-model="form.new_driver_street" :class="{'border-red-500': form.errors.new_driver_street}" class="w-full text-sm py-1.5 mt-1" /><p v-if="form.errors.new_driver_street" class="text-red-500 text-xs mt-1">{{ form.errors.new_driver_street }}</p></div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t pt-4">
                                <div>
                                    <InputLabel>Driver Photo (2x2) <span class="text-red-500">*</span></InputLabel>
                                    <input type="file" @change="e => form.driver_user_photo = e.target.files[0]" class="mt-1 block w-full text-xs" accept="image/*" />
                                    <p v-if="form.errors.driver_user_photo" class="text-red-500 text-xs mt-1">{{ form.errors.driver_user_photo }}</p>
                                </div>
                                <div>
                                    <InputLabel>License Front <span class="text-red-500">*</span></InputLabel>
                                    <input type="file" @change="e => form.driver_license_front = e.target.files[0]" class="mt-1 block w-full text-xs" accept="image/*" />
                                    <p v-if="form.errors.driver_license_front" class="text-red-500 text-xs mt-1">{{ form.errors.driver_license_front }}</p>
                                </div>
                                <div>
                                    <InputLabel>License Back <span class="text-red-500">*</span></InputLabel>
                                    <input type="file" @change="e => form.driver_license_back = e.target.files[0]" class="mt-1 block w-full text-xs" accept="image/*" />
                                    <p v-if="form.errors.driver_license_back" class="text-red-500 text-xs mt-1">{{ form.errors.driver_license_back }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <h3 class="text-sm font-bold text-gray-800 mb-1 border-b border-gray-200 pb-2">Evaluation Requirements</h3>
                            <p class="text-xs text-gray-500 mb-2 leading-tight">Please upload clear copies (PDF, JPG, PNG) of the following mandatory documents.</p>
                            <p v-if="form.errors.documents" class="text-red-500 text-xs mb-3 font-semibold bg-red-50 p-2 rounded-md border border-red-200">{{ form.errors.documents }}</p>
                            
                            <div class="space-y-3">
                                <!-- <p v-if="form.errors.documents" class="text-red-500 text-sm mb-3 font-semibold bg-red-50 p-2 rounded-md border border-red-200">
                                    {{ form.errors.documents }}
                                </p> -->
                                <div v-for="req in currentEvaluationRequirements" :key="req.id" 
                                        class="flex items-center justify-between p-3 border rounded-lg bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                    <div class="flex-1 pr-4">
                                        <p class="text-sm font-bold text-gray-800 leading-snug">{{ req.name }}</p>
                                        <div class="mt-1 flex items-center gap-2">
                                            <svg v-if="docPreviews[req.id]" class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            <span v-if="docPreviews[req.id]" class="text-[11px] text-green-600 font-medium truncate max-w-[150px] sm:max-w-[250px]" :title="docPreviews[req.id]">
                                                {{ docPreviews[req.id] }}
                                            </span>
                                            <span v-else class="text-[10px] text-red-500 font-bold uppercase tracking-wide">Required</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="px-4 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-bold rounded-md shadow-sm cursor-pointer hover:bg-gray-50 hover:text-blue-600 transition-all focus-within:ring-2 focus-within:ring-blue-500 whitespace-nowrap">
                                            {{ docPreviews[req.id] ? 'Change' : 'Upload' }}
                                            <input type="file" class="sr-only" @change="e => handleDocChange(e, req.id)" accept="image/*,.pdf" />
                                        </label>
                                    </div>
                                </div>
                                <div v-if="currentEvaluationRequirements.length === 0" class="text-sm text-gray-500 italic">
                                    No specific documents required for this application type.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="currentStep === 3" class="space-y-4">
                        <div class="bg-gray-50 p-6 rounded-xl text-sm space-y-4 border border-gray-200">
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Transaction Type</p>
                                <p class="font-bold text-gray-900 text-base">{{ applicationTypes.find(t => t.id === selectedType).name }}</p>
                            </div>
                            <div class="pt-4 border-t border-gray-200">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-2">Documents Ready for Upload</p>
                                <ul class="space-y-1.5">
                                    <li v-for="req in currentEvaluationRequirements" :key="req.id" class="flex items-center gap-2 text-xs font-medium text-gray-700 bg-white p-2 rounded border border-gray-100 shadow-sm">
                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ req.name }}
                                    </li>
                                    <li v-if="currentEvaluationRequirements.length === 0" class="text-xs text-gray-500 italic">None</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between flex-shrink-0">
                    <SecondaryButton @click="goBack">
                        {{ currentStep === 1 ? 'Cancel' : 'Back' }}
                    </SecondaryButton>
                    
                    <PrimaryButton v-if="currentStep === 2" @click="goToNextStep">
                        Next Review
                    </PrimaryButton>
                    
                    <PrimaryButton v-if="currentStep === 3" @click="submit" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                        {{ form.processing ? 'Submitting...' : 'Confirm Submission' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>

<transition name="fade">
    <div v-if="showWarningModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showWarningModal = false"></div>
        
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center transform transition-all">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                <svg class="h-7 w-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">Application Prohibited</h3>
            <p class="text-sm text-gray-600 mb-6 px-2">{{ warningMessage }}</p>

            <div v-if="conflictingApplication" class="mb-6 bg-gray-50 border border-gray-200 rounded-xl p-4 text-left">
                <p class="text-[10px] text-gray-500 uppercase font-bold tracking-wider mb-2">Existing Application Record</p>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-bold text-gray-900 font-mono">{{ conflictingApplication.ref_no }}</span>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-[10px] font-bold uppercase">{{ conflictingApplication.status }}</span>
                </div>
                <p class="text-sm text-gray-700 font-medium">{{ conflictingApplication.type || conflictingApplication.application_type }}</p>
                <p class="text-xs text-gray-500 mt-2 italic">"{{ conflictingApplication.remarks || 'No remarks provided.' }}"</p>
            </div>

            <PrimaryButton @click="showWarningModal = false" class="w-full justify-center !bg-red-600 hover:!bg-red-700 focus:!ring-red-500">
                Understood
            </PrimaryButton>
        </div>
    </div>
</transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>