<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- DATA STATE ---
const allApplications = ref([
    // Active / Ongoing
    { 
        id: 101, ref_no: 'APP-2024-055', type: 'Renewal', 
        date: '2024-11-12', status: 'Pending', current_step: 1, 
        remarks: 'Application submitted. Waiting for initial review.',
        is_active: true
    },
    { 
        id: 102, ref_no: 'APP-2024-048', type: 'Change of Owner', 
        date: '2024-11-10', status: 'Under Review', current_step: 2, 
        remarks: 'Legal office verifying Deed of Sale authenticity.',
        is_active: true
    },
    // RETURNED ITEM (Clickable)
    { 
        id: 103, ref_no: 'APP-2024-042', type: 'Change of Unit', 
        date: '2024-11-05', status: 'Returned', current_step: 2, 
        remarks: 'ACTION REQUIRED: Uploaded OR/CR is blurred. Please re-upload clear copy.',
        is_active: true
    },
    { 
        id: 104, ref_no: 'APP-2024-039', type: 'Change of Unit', 
        date: '2024-10-28', status: 'Inspection', current_step: 3, 
        remarks: 'Unit scheduled for physical inspection on Nov 15, 2:00 PM.',
        is_active: true
    },
    { 
        id: 105, ref_no: 'APP-2024-035', type: 'Renewal', 
        date: '2024-10-25', status: 'For Payment', current_step: 3, 
        remarks: 'Assessment approved. Please proceed to payment.',
        is_active: true
    },
    { 
        id: 106, ref_no: 'APP-2024-030', type: 'Change of Owner', 
        date: '2024-10-20', status: 'Processing', current_step: 4, 
        remarks: 'Finalizing franchise amendment printing.',
        is_active: true
    },
    // Past
    { 
        id: 99, ref_no: 'APP-2024-001', type: 'Renewal', 
        date: '2024-01-15', status: 'Approved', current_step: 4, 
        remarks: 'Renewal successful.',
        is_active: false
    },
    { 
        id: 98, ref_no: 'APP-2023-089', type: 'Change of Unit', 
        date: '2023-12-10', status: 'Rejected', current_step: 2, 
        remarks: 'Unit age exceeds limit (15 years).',
        is_active: false
    },
]);

// --- STATUS STEPPER CONFIG ---
const processSteps = [
    { id: 1, label: 'Sub' }, 
    { id: 2, label: 'Rev' },
    { id: 3, label: 'Insp/Pay' },
    { id: 4, label: 'Done' },
];

// --- MODAL STATES ---
const showNewAppModal = ref(false);
const showComplyModal = ref(false);
const selectedReturnedApp = ref(null); 

const currentStep = ref(1); 
const selectedType = ref('renewal');

// Existing vs New Modes
const ownerMode = ref('existing');
const unitMode = ref('existing');

const barangayQuery = ref('');
const showBarangayDropdown = ref(false);
const compliancePreview = ref(null); 
const docPreviews = ref({}); // For documents
const unitPhotoPreviews = ref({ front: null, back: null, left: null, right: null }); // For unit photos

// --- CONFIGURATION & DUMMY DATA ---
const applicationTypes = [
    { id: 'renewal', name: 'Renewal', description: 'Renew franchise validity.', icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
    { id: 'change_unit', name: 'Change of Unit', description: 'Replace tricycle unit.', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 'change_owner', name: 'Change of Owner', description: 'Transfer ownership.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
];

const evaluationRequirementsConfig = {
    renewal: [
        { id: 'prev_permit', name: 'Previous Franchise Permit' },
        { id: 'emission', name: 'Emission Test Result' },
        { id: 'or_cr', name: 'Vehicle OR/CR' },
        { id: 'insurance', name: 'Insurance Policy' }
    ],
    change_unit: [
        { id: 'new_or_cr', name: 'OR/CR of New Unit' },
        { id: 'deed_of_sale_unit', name: 'Deed of Sale / Invoice of New Unit' },
        { id: 'emission', name: 'Emission Test Result' }
    ],
    change_owner: [
        { id: 'deed_of_sale_owner', name: 'Deed of Sale' },
        { id: 'valid_id', name: 'Valid ID of New Owner (Govt Issued)' },
        { id: 'brgy_clearance', name: 'Barangay Clearance of New Owner' },
        { id: 'police_clearance', name: 'Police Clearance of New Owner' }
    ]
};

const dummyBarangays = [{ name: 'San Jose' }, { name: 'Tetuan' }, { name: 'Putik' }, { name: 'Tumaga' }, { name: 'Pasonanca' }];
const dummyMakes = [{ id: 1, name: 'Honda' }, { id: 2, name: 'Kawasaki' }, { id: 3, name: 'Bajaj' }, { id: 4, name: 'Yamaha' }];
const dummyMyFranchises = [
    { id: 101, plate: 'ABC-123', make: 'Honda' },
    { id: 102, plate: 'XYZ-789', make: 'Kawasaki' }
];

const dummyOwners = [
    { id: 1, name: 'Juan Dela Cruz', email: 'juan@example.com' },
    { id: 2, name: 'Maria Santos', email: 'maria@example.com' },
    { id: 3, name: 'Pedro Penduko', email: 'pedro@example.com' },
];

const dummyUnits = [
    { id: 1, plate: 'ABC-123', make: 'Honda', motor: 'H123456', chassis: 'C123456' },
    { id: 2, plate: 'XYZ-789', make: 'Kawasaki', motor: 'K987654', chassis: 'K987654' },
    { id: 3, plate: 'DBP-001', make: 'Suzuki', motor: 'S112233', chassis: 'S445566' },
];

// --- FORMS ---
const form = useForm({
    type: 'renewal',
    selected_franchise_id: '', 
    application_date: new Date().toISOString().split('T')[0], 
    remarks: '',

    // Owner Fields
    existing_owner_id: '',
    first_name: '', middle_name: '', last_name: '', contact_number: '', email: '', 
    street_address: '', barangay: '', city: 'Zamboanga City',

    // Unit Fields
    existing_unit_id: '',
    make_id: '', model_year: '', plate_number: '', motor_number: '', chassis_number: '', cr_number: '',
    unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    
    // Evaluation Docs
    documents: {} 
});

const complianceForm = useForm({
    file: null,
    remarks: ''
});

// --- HELPERS & ACTIONS ---
const activeApplications = computed(() => allApplications.value.filter(app => app.is_active));
const pastApplications = computed(() => allApplications.value.filter(app => !app.is_active));

const filteredBarangays = computed(() => dummyBarangays.filter(b => b.name.toLowerCase().includes(barangayQuery.value.toLowerCase())));
const isUnitRequired = computed(() => ['change_unit'].includes(selectedType.value));
const isOwnerRequired = computed(() => ['change_owner'].includes(selectedType.value));
const isFranchiseSelectRequired = computed(() => true); 

const areAllDocsUploaded = computed(() => {
    const reqs = evaluationRequirementsConfig[selectedType.value] || [];
    return reqs.every(r => form.documents[r.id]);
});

// Open Modal Actions
const openModal = () => { 
    showNewAppModal.value = true; 
    currentStep.value = 1; 
    form.reset(); 
    docPreviews.value = {}; 
    unitPhotoPreviews.value = { front: null, back: null, left: null, right: null };
    ownerMode.value = 'existing';
    unitMode.value = 'existing';
};
const closeModal = () => { showNewAppModal.value = false; form.reset(); };

const handleCardClick = (app) => {
    if (app.status === 'Returned') {
        selectedReturnedApp.value = app;
        complianceForm.reset();
        compliancePreview.value = null;
        showComplyModal.value = true;
    }
};

const closeComplyModal = () => {
    showComplyModal.value = false;
    selectedReturnedApp.value = null;
};

// Form Actions
const selectType = (typeId) => { selectedType.value = typeId; form.type = typeId; currentStep.value = 2; };

const handleDocChange = (event, reqId) => {
    const file = event.target.files[0];
    if (file) { 
        form.documents[reqId] = file; 
        docPreviews.value[reqId] = file.name; 
    }
};

const handleUnitPhoto = (event, side) => {
    const file = event.target.files[0];
    if (file) {
        form[`unit_${side}_photo`] = file;
        unitPhotoPreviews.value[side] = URL.createObjectURL(file);
    }
};

const handleComplianceFile = (event) => {
    const file = event.target.files[0];
    if (file) { 
        complianceForm.file = file; 
        compliancePreview.value = file.name;
    }
};

const submitApplication = () => {
    const newApp = {
        id: Math.random(),
        ref_no: `APP-2024-${Math.floor(Math.random() * 1000)}`,
        type: applicationTypes.find(t => t.id === selectedType.value).name,
        date: new Date().toISOString().split('T')[0],
        status: 'Pending',
        current_step: 1,
        remarks: 'New submission',
        is_active: true
    };
    allApplications.value.unshift(newApp);
    alert("Application Submitted!");
    closeModal();
};

const submitCompliance = () => {
    const index = allApplications.value.findIndex(a => a.id === selectedReturnedApp.value.id);
    if (index !== -1) {
        allApplications.value[index].status = 'Under Review'; 
        allApplications.value[index].remarks = 'Resubmitted: ' + (complianceForm.remarks || 'Compliance documents uploaded.');
    }
    alert("Compliance submitted successfully!");
    closeComplyModal();
};

// Retrieval Helpers
const getMakeName = (id) => dummyMakes.find(m => m.id === id)?.name || '-';
const getSelectedFranchiseLabel = () => {
    const f = dummyMyFranchises.find(fran => fran.id === form.selected_franchise_id);
    return f ? `${f.plate} - ${f.make}` : 'Not Selected';
};
const getSelectedOwnerLabel = () => {
    const o = dummyOwners.find(owner => owner.id === form.existing_owner_id);
    return o ? `${o.name} (${o.email})` : 'Not Selected';
};
const getSelectedUnitLabel = () => {
    const u = dummyUnits.find(unit => unit.id === form.existing_unit_id);
    return u ? `${u.plate} - ${u.make}` : 'Not Selected';
};

const getStepStatus = (app, stepId) => {
    if (app.status === 'Returned' && app.current_step === stepId) return 'error';
    if (app.current_step > stepId) return 'completed';
    if (app.current_step === stepId) return 'current';
    return 'upcoming';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My Applications" />

        <div class="bg-gray-50/50">
            <div class="">
                
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
                        <p class="text-sm text-gray-500">Track current requests and view history.</p>
                    </div>
                    <PrimaryButton @click="openModal" class="shadow-md !text-xs !px-4 !py-2">
                        + New Application
                    </PrimaryButton>
                </div>

                <div class="mb-10">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 border-l-4 border-blue-500 pl-3">Active Applications</h2>
                    
                    <div v-if="activeApplications.length === 0" class="bg-white rounded-lg p-6 text-center border border-dashed text-gray-400 text-sm">
                        No active applications.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="app in activeApplications" :key="app.id" 
                             @click="handleCardClick(app)"
                             class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 flex flex-col h-full"
                             :class="{ 'cursor-pointer hover:ring-2 hover:ring-red-400 hover:shadow-md': app.status === 'Returned', 'hover:shadow-md': app.status !== 'Returned' }">
                            
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-800 uppercase">{{ app.type }}</span>
                                    <span class="text-[10px] text-gray-500 font-mono">{{ app.ref_no }}</span>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border"
                                    :class="{
                                        'bg-yellow-50 text-yellow-700 border-yellow-200': app.status === 'Pending',
                                        'bg-blue-50 text-blue-700 border-blue-200': ['Under Review', 'Inspection', 'Processing', 'For Payment'].includes(app.status),
                                        'bg-red-100 text-red-700 border-red-200 animate-pulse': app.status === 'Returned'
                                    }">
                                    {{ app.status }}
                                </span>
                            </div>

                            <div class="px-4 py-4 flex-1 flex flex-col justify-between">
                                <div class="relative mb-3">
                                    <div class="absolute top-1/2 left-0 w-full h-0.5 bg-gray-100 -translate-y-1/2 z-0"></div>
                                    <div class="absolute top-1/2 left-0 h-0.5 bg-green-500 -translate-y-1/2 z-0 transition-all duration-500"
                                         :style="{ width: `${((app.current_step - 1) / (processSteps.length - 1)) * 100}%` }">
                                    </div>
                                    <div class="relative z-10 flex justify-between w-full">
                                        <div v-for="step in processSteps" :key="step.id" class="flex flex-col items-center">
                                            <div class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold border transition-colors bg-white"
                                                :class="{
                                                    'border-green-500 bg-green-500 text-white': getStepStatus(app, step.id) === 'completed',
                                                    'border-green-500 text-green-600': getStepStatus(app, step.id) === 'current',
                                                    'border-red-500 text-red-600': getStepStatus(app, step.id) === 'error',
                                                    'border-gray-200 text-gray-300': getStepStatus(app, step.id) === 'upcoming'
                                                }">
                                                <span v-if="getStepStatus(app, step.id) === 'completed'">✓</span>
                                                <span v-else-if="getStepStatus(app, step.id) === 'error'">!</span>
                                                <span v-else>{{ step.id }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between items-start text-xs text-gray-600 gap-2 mt-auto">
                                    <p class="line-clamp-2 flex-1 italic" :class="{'text-red-600 font-medium': app.status === 'Returned'}" :title="app.remarks">"{{ app.remarks }}"</p>
                                    <span class="text-gray-400 whitespace-nowrap text-[10px]">{{ app.date }}</span>
                                </div>
                                
                                <div v-if="app.status === 'Returned'" class="mt-3 text-center text-xs font-bold text-red-500 uppercase tracking-wide border-t pt-2 border-red-100">
                                    Click to Comply &rarr;
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-sm font-bold text-gray-700 uppercase">Application History</h2>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="pastApplications.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No past applications found.</td>
                                </tr>
                                <tr v-for="app in pastApplications" :key="app.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-gray-900 font-mono">{{ app.ref_no }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-700">{{ app.type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">{{ app.date }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-[10px] leading-5 font-semibold rounded-full uppercase"
                                            :class="{
                                                'bg-green-100 text-green-800': app.status === 'Approved',
                                                'bg-red-100 text-red-800': app.status === 'Rejected' || app.status === 'Cancelled'
                                            }">
                                            {{ app.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 max-w-xs truncate">{{ app.remarks }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <transition name="fade">
            <div v-if="showNewAppModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
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
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                        
                        <div v-if="currentStep === 1" class="space-y-4">
                            <div v-for="type in applicationTypes" :key="type.id" 
                                @click="selectType(type.id)"
                                class="flex items-center p-4 border rounded-xl hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-colors group">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-blue-600 mr-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" />
                                    </svg>
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
                                <select v-model="form.selected_franchise_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                                    <option value="">-- Choose Unit --</option>
                                    <option v-for="fran in dummyMyFranchises" :key="fran.id" :value="fran.id">
                                        {{ fran.plate }} - {{ fran.make }}
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
                                    <select v-model="form.existing_owner_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:border-blue-500 py-2 mt-1">
                                        <option value="">-- Select Owner --</option>
                                        <option v-for="o in dummyOwners" :key="o.id" :value="o.id">{{ o.name }} ({{ o.email }})</option>
                                    </select>
                                </div>

                                <div v-if="ownerMode === 'new'" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div><InputLabel value="First Name" /><TextInput v-model="form.first_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                    <div><InputLabel value="Middle Name" /><TextInput v-model="form.middle_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                    <div><InputLabel value="Last Name" /><TextInput v-model="form.last_name" class="w-full text-sm py-1.5 mt-1" /></div>
                                    
                                    <div class="sm:col-span-2"><InputLabel value="Email Address" /><TextInput type="email" v-model="form.email" class="w-full text-sm py-1.5 mt-1" /></div>
                                    <div><InputLabel value="Contact Number" /><TextInput v-model="form.contact_number" class="w-full text-sm py-1.5 mt-1" placeholder="09XX-XXX-XXXX"/></div>
                                    
                                    <div><InputLabel value="Street Address" /><TextInput v-model="form.street_address" class="w-full text-sm py-1.5 mt-1" /></div>
                                    <div>
                                        <InputLabel value="Barangay" />
                                        <div class="relative mt-1">
                                            <TextInput v-model="barangayQuery" @focus="showBarangayDropdown = true" placeholder="Search..." class="w-full text-sm py-1.5" />
                                            <div v-if="showBarangayDropdown" class="absolute z-10 w-full bg-white border mt-1 rounded shadow-lg max-h-32 overflow-y-auto">
                                                <div v-for="brgy in filteredBarangays" :key="brgy.name" 
                                                    @click="form.barangay = brgy.name; barangayQuery = brgy.name; showBarangayDropdown = false" 
                                                    class="px-3 py-2 text-sm hover:bg-gray-100 cursor-pointer">
                                                    {{ brgy.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div><InputLabel value="City" /><TextInput v-model="form.city" disabled class="w-full text-sm py-1.5 mt-1 bg-gray-100" /></div>
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
                                        <option v-for="u in dummyUnits" :key="u.id" :value="u.id">{{ u.plate }} - {{ u.make }} (Motor: {{ u.motor }})</option>
                                    </select>
                                </div>

                                <div v-if="unitMode === 'new'" class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <InputLabel value="Make" />
                                            <select v-model="form.make_id" class="w-full border-gray-300 rounded-lg shadow-sm text-sm py-1.5 mt-1">
                                                <option v-for="m in dummyMakes" :key="m.id" :value="m.id">{{ m.name }}</option>
                                            </select>
                                        </div>
                                        <div><InputLabel value="Model Year" /><TextInput type="number" v-model="form.model_year" class="w-full text-sm py-1.5 mt-1" placeholder="YYYY" /></div>
                                        <div><InputLabel value="Plate No." /><TextInput v-model="form.plate_number" class="w-full text-sm py-1.5 mt-1" /></div>
                                        
                                        <div><InputLabel value="Motor No." /><TextInput v-model="form.motor_number" class="w-full text-sm py-1.5 mt-1" /></div>
                                        <div><InputLabel value="Chassis No." /><TextInput v-model="form.chassis_number" class="w-full text-sm py-1.5 mt-1" /></div>
                                        <div><InputLabel value="CR No." /><TextInput v-model="form.cr_number" class="w-full text-sm py-1.5 mt-1" /></div>
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

                            <div v-if="selectedType === 'renewal'" class="bg-blue-50 p-4 rounded-lg text-sm text-blue-800 border border-blue-100">
                                <p class="font-bold mb-1">Renewal Process</p>
                                <p class="text-xs">Please upload the required evaluation documents below. A physical inspection schedule will be provided by the admin upon successful evaluation of your documents.</p>
                            </div>

                            <div class="pt-2">
                                <h3 class="text-sm font-bold text-gray-800 mb-1 border-b border-gray-100 pb-2">Evaluation Requirements</h3>
                                <p class="text-xs text-gray-500 mb-4 leading-tight">Please upload clear copies (PDF, JPG, PNG) of the following mandatory documents. Physical inspection is scheduled separately.</p>
                                
                                <div class="space-y-3">
                                    <div v-for="req in evaluationRequirementsConfig[selectedType]" :key="req.id" 
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
                                </div>
                            </div>

                        </div>

                        <div v-if="currentStep === 3" class="space-y-4">
                            <div class="bg-gray-50 p-6 rounded-xl text-sm space-y-4 border border-gray-200">
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Transaction Type</p>
                                    <p class="font-bold text-gray-900 text-base">{{ applicationTypes.find(t => t.id === selectedType).name }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Selected Franchise to Modify</p>
                                    <p class="font-medium text-gray-900">{{ getSelectedFranchiseLabel() }}</p>
                                </div>
                                
                                <div v-if="isOwnerRequired" class="pt-4 border-t border-gray-200">
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Proposed New Owner</p>
                                    <p class="font-medium text-gray-900" v-if="ownerMode === 'existing'">{{ getSelectedOwnerLabel() }} (Existing Profile)</p>
                                    <div v-else>
                                        <p class="font-bold text-gray-900">{{ form.first_name }} {{ form.middle_name }} {{ form.last_name }}</p>
                                        <p class="text-xs text-gray-600 mt-1">{{ form.contact_number }} | {{ form.email }}</p>
                                        <p class="text-xs text-gray-600">{{ form.street_address }}, {{ form.barangay }}, {{ form.city }}</p>
                                    </div>
                                </div>

                                <div v-if="isUnitRequired" class="pt-4 border-t border-gray-200">
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Proposed New Unit</p>
                                    <p class="font-medium text-gray-900" v-if="unitMode === 'existing'">{{ getSelectedUnitLabel() }} (Existing Record)</p>
                                    <div v-else class="grid grid-cols-2 gap-x-4 gap-y-2 mt-1">
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">Make:</span> {{ getMakeName(form.make_id) }}</p>
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">Model Year:</span> {{ form.model_year }}</p>
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">Plate No:</span> {{ form.plate_number }}</p>
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">CR No:</span> {{ form.cr_number }}</p>
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">Motor No:</span> {{ form.motor_number }}</p>
                                        <p class="text-xs text-gray-700"><span class="font-semibold text-gray-500">Chassis No:</span> {{ form.chassis_number }}</p>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-200">
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold mb-2">Documents Ready for Upload</p>
                                    <ul class="space-y-1.5">
                                        <li v-for="req in evaluationRequirementsConfig[selectedType]" :key="req.id" class="flex items-center gap-2 text-xs font-medium text-gray-700 bg-white p-2 rounded border border-gray-100 shadow-sm">
                                            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            {{ req.name }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <p class="text-xs text-gray-500 flex items-start gap-1.5 bg-blue-50 p-3 rounded-md border border-blue-100 mt-4">
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                By confirming, you agree that all information provided is accurate. Physical inspection will be coordinated by the admin separately.
                            </p>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between flex-shrink-0">
                        <SecondaryButton @click="currentStep === 1 ? closeModal() : currentStep--">
                            {{ currentStep === 1 ? 'Cancel' : 'Back' }}
                        </SecondaryButton>
                        
                        <PrimaryButton v-if="currentStep < 3" @click="currentStep++" :disabled="currentStep === 2 && (!form.selected_franchise_id || !areAllDocsUploaded)">
                            Next Review
                        </PrimaryButton>
                        
                        <PrimaryButton v-else @click="submitApplication" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                            Confirm Submission
                        </PrimaryButton>
                    </div>

                </div>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="showComplyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeComplyModal"></div>
                
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[450px] flex flex-col overflow-hidden">
                    
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-start bg-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 leading-tight">Application Returned</h2>
                                <p class="text-xs text-gray-500">{{ selectedReturnedApp?.ref_no }}</p>
                            </div>
                        </div>
                        <button @click="closeComplyModal" class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <h3 class="text-xs font-bold text-red-800 uppercase mb-1 tracking-wide">Reason for Return:</h3>
                            <p class="text-sm text-red-700 italic">"{{ selectedReturnedApp?.remarks }}"</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <InputLabel value="Upload Corrected Document" />
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors relative bg-gray-50/50">
                                    <div class="space-y-2 text-center">
                                        <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div v-if="compliancePreview" class="text-sm text-green-600 font-bold truncate max-w-[200px] mx-auto bg-green-50 px-2 py-1 rounded" :title="compliancePreview">
                                            {{ compliancePreview }}
                                        </div>
                                        <div v-else class="flex text-sm text-gray-600 justify-center">
                                            <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload a file</span>
                                                <input id="file-upload" name="file-upload" type="file" class="sr-only" @change="handleComplianceFile" accept="image/*,.pdf" />
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Additional Remarks / Explanation" />
                                <TextInput v-model="complianceForm.remarks" class="w-full text-sm" placeholder="I have uploaded the clear copy..." />
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                        <SecondaryButton @click="closeComplyModal">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitCompliance" class="bg-blue-600 hover:bg-blue-700" :disabled="!complianceForm.file && !complianceForm.remarks">
                            Resubmit Application
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>