<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    evaluationRequirements: { type: Object, default: () => ({}) },
    franchises: { type: Array, default: () => [] },
    barangays: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    operators: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'submit']);
const page = usePage();

// --- MODAL STATES ---
const currentStep = ref(1); 
const selectedType = ref('renewal');
const ownerMode = ref('existing');
const unitMode = ref('existing');
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);
const docPreviews = ref({}); 
const unitPhotoPreviews = ref({ front: null, back: null, left: null, right: null }); 

// Warning Modal States
const showWarningModal = ref(false);
const warningMessage = ref('');

const applicationTypes = [
    { id: 'renewal', name: 'Renewal', description: 'Renew franchise validity.', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
    { id: 'change_unit', name: 'Change of Unit', description: 'Replace tricycle unit.', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 'change_owner', name: 'Change of Owner', description: 'Transfer ownership.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
];

// --- FORMS ---
const form = useForm({
    type: 'renewal', 
    selected_franchise_id: '', 
    remarks: '',
    
    owner_mode: 'existing', 

    // Owner Fields
    existing_operator_id: '', 
    new_owner_first_name: '', 
    new_owner_middle_name: '', 
    new_owner_last_name: '', 
    new_owner_contact: '', 
    new_owner_email: '', 
    new_owner_tin: '', 
    new_owner_address: '', 
    new_owner_barangay: '', 
    new_owner_city: 'Zamboanga City',
    
    // Unit Fields
    existing_unit_id: '', make_id: '', model_year: '', plate_number: '', motor_number: '', chassis_number: '', cr_number: '',
    unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    
    // Document uploads
    documents: {} 
});

// Watch for Server-Side Validation Errors as a fallback
watch(() => form.errors.selected_franchise_id, (newError) => {
    if (newError) {
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

const filteredBarangays = computed(() => props.barangays.filter(b => b.name.toLowerCase().includes(barangayQuery.value.toLowerCase())));
const isUnitRequired = computed(() => ['change_unit'].includes(selectedType.value));
const isOwnerRequired = computed(() => ['change_owner'].includes(selectedType.value));
const isFranchiseSelectRequired = computed(() => true); 

const areAllDocsUploaded = computed(() => {
    const reqs = currentEvaluationRequirements.value;
    return reqs.every(r => form.documents[r.id]);
});

// Uses pre-calculated server states instead of localized date calculations
const duplicateApplicationError = computed(() => {
    if (!form.selected_franchise_id) return null;

    const selectedFranchise = props.franchises.find(f => f.id == form.selected_franchise_id);
    if (!selectedFranchise) return null;

    if (selectedType.value === 'renewal') {
        if (selectedFranchise.has_renewal_this_year) {
            return `A Renewal application for the current fiscal year already exists for this franchise.`;
        }
    } else if (selectedType.value === 'change_unit') {
        if (selectedFranchise.has_active_change_unit) {
            return `An active Change of Unit application already exists for this franchise. Please complete or cancel the existing application before submitting a new one.`;
        }
    } else if (selectedType.value === 'change_owner') {
        if (selectedFranchise.has_active_change_owner) {
            return `An active Change of Owner application already exists for this franchise. Please complete or cancel the existing application before submitting a new one.`;
        }
    }
    
    return null;
});

// Triggers instantly when a franchise is selected from the dropdown
const validateFranchiseSelection = () => {
    const error = duplicateApplicationError.value;
    if (error) {
        warningMessage.value = error;
        showWarningModal.value = true;
        form.selected_franchise_id = '';
    }
};

const isStep2Valid = computed(() => {
    if (isFranchiseSelectRequired.value && !form.selected_franchise_id) return false;
    if (duplicateApplicationError.value) return false;
    if (!areAllDocsUploaded.value) return false;

    if (isOwnerRequired.value) {
        if (ownerMode.value === 'existing' && !form.existing_operator_id) return false;
        if (ownerMode.value === 'new') {
            if (!form.new_owner_first_name || !form.new_owner_last_name || 
                !form.new_owner_contact || !form.new_owner_address || 
                !form.new_owner_barangay || !form.new_owner_tin) {
                return false;
            }
        }
    }

    if (isUnitRequired.value) {
        if (unitMode.value === 'existing' && !form.existing_unit_id) return false;
        if (unitMode.value === 'new') {
            if (!form.make_id || !form.model_year || !form.plate_number || !form.motor_number || !form.chassis_number || !form.cr_number) return false;
            if (!form.unit_front_photo || !form.unit_back_photo || !form.unit_left_photo || !form.unit_right_photo) return false;
        }
    }

    return true;
});

const closeModal = () => { 
    form.reset();
    form.clearErrors();
    currentStep.value = 1;
    docPreviews.value = {}; 
    unitPhotoPreviews.value = { front: null, back: null, left: null, right: null };
    ownerMode.value = 'existing'; unitMode.value = 'existing';
    emit('close');
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
    if (file) { form.documents[reqId] = file; docPreviews.value[reqId] = file.name; }
};

const handleUnitPhoto = (event, side) => {
    const file = event.target.files[0];
    if (file) { form[`unit_${side}_photo`] = file; unitPhotoPreviews.value[side] = URL.createObjectURL(file); }
};

const submit = () => {
    if (selectedType.value === 'change_unit') {
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
        form.post(route('franchise.applications.store-renewal'), {
            preserveScroll: true,
            onSuccess: () => {
                emit('submit'); 
                closeModal();
            },
        });
    }
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
                            <InputLabel value="Select Existing Franchise to Modify/Renew" />
                            <select v-model="form.selected_franchise_id" @change="validateFranchiseSelection" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-2 mt-1">
                                <option value="" disabled>-- Choose Unit --</option>
                                <option v-for="fran in franchises" :key="fran.id" :value="fran.id">
                                    {{ fran.mtfrb_case_no || `Franchise #${fran.id}` }} 
                                    - {{ fran.current_active_unit?.new_unit?.make?.name || 'Unknown Make' }} 
                                    (Plate: {{ fran.current_active_unit?.new_unit?.plate_number || 'N/A' }})
                                </option>
                            </select>
                        </div>

                        <div v-if="isOwnerRequired">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">New Owner Details</h3>
                            <div class="flex border-b border-gray-200 mb-4">
                                <button type="button" @click="ownerMode = 'existing'" :class="ownerMode === 'existing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Select Existing Owner</button>
                                <button type="button" @click="ownerMode = 'new'" :class="ownerMode === 'new' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Register New Owner</button>
                            </div>

                            <div v-if="ownerMode === 'existing'" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <InputLabel value="Search / Select Existing Owner" />
                                <select v-model="form.existing_operator_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 py-2 mt-1">
                                    <option value="">-- Select Owner --</option>
                                    <option v-for="o in operators" :key="o.id" :value="o.id">{{ o.name }} ({{ o.email }})</option>
                                </select>
                            </div>

                            <div v-if="ownerMode === 'new'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div><InputLabel value="First Name" /><TextInput v-model="form.new_owner_first_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                <div><InputLabel value="Middle Name" /><TextInput v-model="form.new_owner_middle_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                <div><InputLabel value="Last Name" /><TextInput v-model="form.new_owner_last_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                
                                <div class="sm:col-span-2"><InputLabel value="Email Address" /><TextInput type="email" v-model="form.new_owner_email" class="w-full text-sm py-1.5 mt-1" /></div>
                                <div><InputLabel value="Contact Number" /><TextInput v-model="form.new_owner_contact" class="w-full text-sm py-1.5 mt-1" placeholder="09XX-XXX-XXXX"/></div>
                                
                                <div><InputLabel value="TIN Number" /><TextInput v-model="form.new_owner_tin" class="w-full text-sm py-1.5 mt-1" /></div>
                                
                                <div class="sm:col-span-2"><InputLabel value="Street Address" /><TextInput v-model="form.new_owner_address" class="w-full text-sm py-1.5 mt-1" /></div>
                                <div class="sm:col-span-2">
                                    <InputLabel value="Barangay" />
                                    <div class="relative mt-1">
                                        <TextInput v-model="barangayQuery" @focus="showBarangayDropdown = true" placeholder="Search..." class="w-full text-sm py-1.5" />
                                        <div v-if="showBarangayDropdown" class="absolute z-10 w-full bg-white border mt-1 rounded shadow-lg max-h-32 overflow-y-auto">
                                            <div v-for="brgy in filteredBarangays" :key="brgy.id" 
                                                @click="form.new_owner_barangay = brgy.name; barangayQuery = brgy.name; showBarangayDropdown = false" 
                                                class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">
                                                {{ brgy.name }}
                                            </div>
                                            <div v-if="filteredBarangays.length === 0" class="px-3 py-2 text-sm text-gray-500 italic">No barangays found.</div>
                                        </div>
                                    </div>
                                </div>
                                <div><InputLabel value="City" /><TextInput v-model="form.new_owner_city" disabled class="w-full text-sm py-1.5 mt-1 bg-gray-100" /></div>
                            </div>
                        </div>

                        <div v-if="isUnitRequired">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">New Unit Details</h3>
                            <div class="flex border-b border-gray-200 mb-4">
                                <button type="button" @click="unitMode = 'existing'" :class="unitMode === 'existing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Select Existing Unit</button>
                                <button type="button" @click="unitMode = 'new'" :class="unitMode === 'new' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="px-4 py-2 border-b-2 font-medium text-sm transition-colors">Register New Unit</button>
                            </div>

                            <div v-if="unitMode === 'existing'" class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <InputLabel value="Search / Select Existing Unit" />
                                <select v-model="form.existing_unit_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 py-2 mt-1">
                                    <option value="">-- Select Unit --</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.plate }} - {{ u.make }} (Motor: {{ u.motor }})</option>
                                </select>
                            </div>

                            <div v-if="unitMode === 'new'" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <InputLabel value="Make" />
                                        <select v-model="form.make_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1">
                                            <option value="">-- Select Make --</option>
                                            <option v-for="m in unitMakes" :key="m.id" :value="m.id">{{ m.name }}</option>
                                        </select>
                                        <p v-if="form.errors.make_id" class="text-red-500 text-xs mt-1">{{ form.errors.make_id }}</p>
                                    </div>
                                    <div><InputLabel value="Model Year" /><TextInput type="number" v-model="form.model_year" class="w-full text-sm py-1.5 mt-1" placeholder="YYYY" />
                                        <p v-if="form.errors.model_year" class="text-red-500 text-xs mt-1">{{ form.errors.model_year }}</p>
                                    </div>
                                    <div><InputLabel value="Plate No." /><TextInput v-model="form.plate_number" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.plate_number" class="text-red-500 text-xs mt-1">{{ form.errors.plate_number }}</p>
                                    </div>
                                    <div><InputLabel value="Motor No." /><TextInput v-model="form.motor_number" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.motor_number" class="text-red-500 text-xs mt-1">{{ form.errors.motor_number }}</p>
                                    </div>
                                    <div><InputLabel value="Chassis No." /><TextInput v-model="form.chassis_number" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.chassis_number" class="text-red-500 text-xs mt-1">{{ form.errors.chassis_number }}</p>
                                    </div>
                                    <div><InputLabel value="CR No." /><TextInput v-model="form.cr_number" class="w-full text-sm py-1.5 mt-1 uppercase" />
                                        <p v-if="form.errors.cr_number" class="text-red-500 text-xs mt-1">{{ form.errors.cr_number }}</p>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <InputLabel value="Proposed Unit Photos" class="mb-1" />
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

                        <div class="pt-2">
                            <h3 class="text-sm font-bold text-gray-800 mb-1 border-b border-gray-200 pb-2">Evaluation Requirements</h3>
                            <p class="text-xs text-gray-500 mb-4 leading-tight">Please upload clear copies (PDF, JPG, PNG) of the following mandatory documents.</p>
                            
                            <div class="space-y-3">
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
                    <SecondaryButton @click="currentStep === 1 ? closeModal() : currentStep--">
                        {{ currentStep === 1 ? 'Cancel' : 'Back' }}
                    </SecondaryButton>
                    
                    <PrimaryButton v-if="currentStep < 3" @click="currentStep++" :disabled="currentStep === 2 && !isStep2Valid">
                        Next Review
                    </PrimaryButton>
                    
                    <PrimaryButton v-else @click="submit" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                        {{ form.processing ? 'Submitting...' : 'Confirm Submission' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>

    <transition name="fade">
        <div v-if="showWarningModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showWarningModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 text-center transform transition-all">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                    <svg class="h-7 w-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Application Prohibited</h3>
                <p class="text-sm text-gray-600 mb-6 px-2">{{ warningMessage }}</p>
                <PrimaryButton @click="showWarningModal = false" class="w-full justify-center !bg-red-600 hover:!bg-red-700 focus:!ring-red-500">
                    Acknowledge
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