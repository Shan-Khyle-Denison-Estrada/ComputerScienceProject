<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

// Import Components
import CreateFranchiseAccountModal from '@/Components/Modals/CreateFranchiseAccountModal.vue';
import ChangeOfUnitModal from '@/Components/Modals/ChangeOfUnitModal.vue';
import ChangeOfOwnerModal from '@/Components/Modals/ChangeOfOwnerModal.vue';

const props = defineProps({
    application: Object, // Receive the full application object from the backend
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
});

// --- STATE ---
const activeTab = ref('evaluation');
const showInspectionModal = ref(false);
const showFranchiseModal = ref(false); 
const showRequirementModal = ref(false);

const selectedItemIndex = ref(null);
const selectedFranchise = ref(null);
const selectedRequirementIndex = ref(null);

// Modal States
const showCreateAccountModal = ref(false);
const showChangeUnitModal = ref(false);
const showChangeOwnerModal = ref(false);
const showReturnModal = ref(false);
const showRejectModal = ref(false);

const inspectionForm = reactive({
    item: '',
    status: '',
    remarks: '',
    options: [] 
});

const returnForm = reactive({
    remarks: ''
});

const rejectForm = reactive({
    remarks: ''
});

const requirementForm = reactive({
    remarks: ''
});

// --- COMPUTED PROPERTIES ---

const application = computed(() => {
    // Safely handle missing arrays by defaulting to empty arrays
    const app = props.application || {};
    return {
        ...app,
        inspection_requirements: app.inspection_requirements || [],
        evaluation_requirements: app.evaluation_requirements || [],
        complaints: app.complaints || [],
        franchises: app.franchises || [], // Ensure this is never undefined
        applicant: app.applicant || {}
    };
});

const confirmRejectApplication = () => {
    if (!confirm("Are you sure you want to completely REJECT this application? This action cannot be undone.")) return;

    router.post(route('admin.applications.reject', application.value.id), {
        remarks: rejectForm.remarks
    }, {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.remarks = '';
        }
    });
};

// [!code ++] Add the approval action
const confirmApproveApplication = () => {
    if (!confirm("Are you sure you want to APPROVE this application?")) return;
    
    router.post(route('admin.applications.approve', props.application.id), {}, {
        onSuccess: () => {
            // Optional: You can auto-open the finalize modal here if desired
            // showCreateAccountModal.value = true;
        }
    });
};

const showInspectionTab = computed(() => {
    return ['Renewal', 'Change of Unit'].includes(application.value.type);
});

const showReceiptTab = computed(() => {
    return ['Renewal', 'Change of Unit', 'Change of Owner'].includes(application.value.type);
});

const showComplaintsTab = computed(() => {
    return application.value.type === 'Renewal';
});

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

// --- ACTIONS ---

const resolveComplaint = (index) => {
    if (confirm('Are you sure you want to mark this complaint as Resolved?')) {
        alert("Feature coming soon connected to backend.");
    }
};

const promptRejectDocument = (index) => {
    const reason = prompt("Please state the reason for rejecting this document (e.g. Blurred, Expired, Incorrect):");
    if (reason !== null) { 
        selectedRequirementIndex.value = index;
        requirementForm.remarks = reason;
        saveRequirementStatus('Rejected');
    }
};

const openRequirementModal = (index) => {
    selectedRequirementIndex.value = index;
    requirementForm.remarks = application.value.evaluation_requirements[index].remarks || '';
    showRequirementModal.value = true;
};

const closeRequirementModal = () => {
    showRequirementModal.value = false;
    selectedRequirementIndex.value = null;
    requirementForm.remarks = '';
};

// Backend Integration: Update Evaluation Status
const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value !== null) {
        const evaluation = application.value.evaluation_requirements[selectedRequirementIndex.value];
        
        router.post(route('admin.applications.evaluate', application.value.id), {
            evaluation_id: evaluation.id,
            status: status,
            remarks: requirementForm.remarks
        }, {
            preserveScroll: true,
            onSuccess: () => {
                closeRequirementModal();
            }
        });
    }
};

// Backend Integration: Return Application
const confirmReturnApplication = () => {
    router.post(route('admin.applications.return', application.value.id), {
        remarks: returnForm.remarks
    }, {
        onSuccess: () => {
            showReturnModal.value = false;
            returnForm.remarks = '';
        }
    });
};

const openInspectionModal = (index, itemData) => {
    selectedItemIndex.value = index;
    inspectionForm.item = itemData.name;
    inspectionForm.status = itemData.status === 'Pending' ? '' : itemData.status;
    inspectionForm.remarks = itemData.remarks;
    inspectionForm.options = itemData.options ? itemData.options.split(',').map(o => o.trim()) : ['Pass', 'Fail'];
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    selectedItemIndex.value = null;
    inspectionForm.item = '';
    inspectionForm.status = '';
    inspectionForm.remarks = '';
    inspectionForm.options = [];
};

const saveInspection = () => {
    // Placeholder logic
    if (selectedItemIndex.value !== null) {
         application.value.inspection_requirements[selectedItemIndex.value].status = inspectionForm.status;
         application.value.inspection_requirements[selectedItemIndex.value].remarks = inspectionForm.remarks;
    }
    closeInspectionModal();
};

const openFranchiseModal = (franchise) => {
    selectedFranchise.value = franchise;
    showFranchiseModal.value = true;
};

const closeFranchiseModal = () => {
    showFranchiseModal.value = false;
    selectedFranchise.value = null;
};

const formatCurrency = (value) => {
    if(!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};
</script>

<template>
    <Head title="Application Details" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Application Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Approved' && application.type === 'Franchise Owner Account'">
                        <PrimaryButton @click="showCreateAccountModal = true" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            Create Franchise Owner Account
                        </PrimaryButton>
                    </template>
                    <template v-else-if="application.status === 'Approved' && application.type === 'Change of Unit'">
                        <PrimaryButton @click="showChangeUnitModal = true" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                            Finalize Unit Change
                        </PrimaryButton>
                    </template>
                     <template v-else-if="application.status === 'Approved' && application.type === 'Change of Owner'">
                        <PrimaryButton @click="showChangeOwnerModal = true" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            Finalize Ownership Transfer
                        </PrimaryButton>
                    </template>
                    <template v-else-if="application.status === 'Rejected'">
                        </template>
                    <template v-else>
                        <button @click="showReturnModal = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            Return
                        </button>
                        <button @click="showRejectModal = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Reject
                        </button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Approve
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex gap-4 h-full min-h-0">
                
                <div class="w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide" 
                                :class="{
                                    'bg-yellow-100 text-yellow-800': application.status === 'Pending',
                                    'bg-green-100 text-green-800': application.status === 'Approved',
                                    'bg-red-100 text-red-800': application.status === 'Rejected',
                                    'bg-amber-100 text-amber-800': application.status === 'Returned',
                                }">
                                {{ application.status }}
                            </span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.type }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                         <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-gray-200 border-2 border-white shadow-md flex items-center justify-center text-2xl font-bold text-gray-400 mb-3 overflow-hidden">
                                <img v-if="application.applicant.photo" :src="application.applicant.photo" class="w-full h-full object-cover" />
                                <span v-else>{{ application.applicant.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">
                                {{ application.applicant.first_name }} 
                                <span v-if="application.applicant.middle_name">{{ application.applicant.middle_name }}</span>
                                {{ application.applicant.last_name }}
                            </h2>
                            <p class="text-sm text-gray-500">{{ application.applicant.email }}</p>
                        </div>

                        <div class="space-y-4">
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Contact No.</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.contact }}</p></div>
                             
                             <div v-if="application.type === 'Franchise Owner Account' && application.applicant.tin_number">
                                 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">TIN No.</p>
                                 <p class="text-sm font-medium text-gray-800 font-mono">{{ application.applicant.tin_number }}</p>
                             </div>

                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Address</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.address }}</p></div>
                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Civil Status</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.civil_status }}</p></div>
                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Date of Birth</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.birthdate }}</p></div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">
                    
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6">
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                        
                        <button v-if="application.type === 'Franchise Owner Account'" 
                                @click="activeTab = 'franchises'" 
                                :class="activeTab === 'franchises' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" 
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            Franchises
                        </button>

                        <button v-if="showInspectionTab" @click="activeTab = 'inspection'" :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Inspection</button>
                        <button v-if="showComplaintsTab" @click="activeTab = 'complaints'" :class="activeTab === 'complaints' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Complaints / History</button>
                        <button v-if="showReceiptTab" @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar bg-gray-50/50 p-6">
                        
                        <div v-if="activeTab === 'evaluation'" class="space-y-6">
                            <div v-if="application.evaluation_requirements.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                    <h3 class="font-bold text-gray-800">Submitted Requirements</h3>
                                    <span class="text-xs text-gray-500">Click row to review</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" 
                                         class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer group"
                                         @click="openRequirementModal(index)">
                                        <div class="flex items-center gap-4">
                                            <div class="p-2 bg-blue-50 rounded-lg text-blue-600 group-hover:bg-blue-100 transition-colors">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ req.name }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                     <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                        :class="{
                                                            'bg-green-100 text-green-700': req.status === 'Approved',
                                                            'bg-red-100 text-red-700': req.status === 'Rejected',
                                                            'bg-gray-100 text-gray-600': req.status === 'Pending'
                                                        }">
                                                        {{ req.status }}
                                                    </span>
                                                    <span v-if="req.remarks" class="text-xs text-red-500 italic">{{ req.remarks }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button class="text-gray-400 group-hover:text-blue-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No evaluation requirements found.</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'franchises' && application.type === 'Franchise Owner Account'" class="space-y-6">
                            <div v-if="application.franchises && application.franchises.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800">Franchise Units</h3>
                                    <span class="text-xs text-gray-500">{{ application.franchises.length }} units attached</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(franchise, index) in application.franchises" :key="index" 
                                         class="p-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors group"
                                         @click="openFranchiseModal(franchise)">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center">
                                                 <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path d="M5 10l7-7m0 0l7 7m-7-7v18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                 </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-bold text-gray-900">{{ franchise.id }}</p>
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold uppercase rounded font-mono">{{ franchise.plate_number || 'NO PLATE' }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500">{{ franchise.zone_name }} • {{ franchise.make_name }} {{ franchise.model_year }} • Issued: {{ franchise.date_issued }}</p>
                                            </div>
                                        </div>
                                        <div class="text-gray-400 group-hover:text-blue-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No franchise units listed for this application.</p>
                            </div>
                        </div>


                        <div v-if="activeTab === 'inspection' && showInspectionTab" class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800">Inspection Checklist</h3>
                                    <span class="text-xs text-gray-500">{{ application.inspection_requirements.length }} items</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(item, index) in application.inspection_requirements" :key="item.id" 
                                         class="p-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors"
                                         @click="openInspectionModal(index, item)">
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ item.name }}</p>
                                                <p class="text-xs text-gray-500">{{ item.remarks || 'No remarks' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span v-if="item.status" class="px-2 py-1 rounded text-xs font-bold uppercase"
                                                 :class="{
                                                    'bg-green-100 text-green-700': ['Working', 'Good', 'Pass', 'Excellent', 'Present'].includes(item.status),
                                                    'bg-red-100 text-red-700': ['Defective', 'Fail', 'Needs Repair', 'Missing'].includes(item.status)
                                                }">
                                                {{ item.status }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400 italic">Pending</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'complaints' && showComplaintsTab" class="space-y-6">
                            <div v-if="application.complaints.length > 0" class="space-y-4">
                                <div v-for="(complaint, index) in application.complaints" :key="complaint.id" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" 
                                                    :class="complaint.status === 'Resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                                    {{ complaint.status }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ complaint.incident_date }} at {{ complaint.incident_time }}</span>
                                            </div>
                                            <h3 class="font-bold text-gray-900">{{ complaint.nature_of_complaint }}</h3>
                                        </div>
                                        <button v-if="complaint.status === 'Pending'" @click="resolveComplaint(index)" class="text-xs text-blue-600 hover:underline">Mark as Resolved</button>
                                    </div>
                                    <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded mb-3">{{ complaint.remarks }}</p>
                                    <div class="grid grid-cols-2 gap-4 text-xs text-gray-500">
                                        <div><span class="font-bold">Complainant:</span> {{ complaint.complainant_contact }}</div>
                                        <div><span class="font-bold">Route:</span> {{ complaint.pick_up_point }} to {{ complaint.drop_off_point }}</div>
                                    </div>
                                </div>
                            </div>
                             <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No complaints found for this unit.</p>
                            </div>
                        </div>

                         <div v-if="activeTab === 'receipt' && showReceiptTab" class="h-full">
                            <div v-if="application.receipt" class="bg-white rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                                <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <div>
                                        <h3 class="font-bold text-gray-900">Official Receipt</h3>
                                        <p class="text-xs text-gray-500">OR #{{ application.receipt.or_number }} • {{ application.receipt.date }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900">{{ formatCurrency(application.receipt.total_amount_due) }}</p>
                                        <p class="text-xs text-green-600 font-bold uppercase">Paid</p>
                                    </div>
                                </div>
                                <div class="p-6 flex-1 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead>
                                            <tr class="text-xs text-gray-400 uppercase border-b border-gray-100">
                                                <th class="pb-3 font-medium">Particulars</th>
                                                <th class="pb-3 font-medium text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            <tr v-for="(item, i) in application.receipt.particulars" :key="i">
                                                <td class="py-3 text-gray-700">{{ item.name }}</td>
                                                <td class="py-3 text-gray-900 font-medium text-right">{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="border-t border-gray-100">
                                            <tr>
                                                <td class="pt-4 font-bold text-gray-900">Total</td>
                                                <td class="pt-4 font-bold text-gray-900 text-right">{{ formatCurrency(application.receipt.total_amount_due) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                             <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300 h-full flex items-center justify-center">
                                <p class="text-gray-500 text-sm">No receipt generated yet.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showFranchiseModal" @close="closeFranchiseModal">
            <div class="p-6" v-if="selectedFranchise">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Franchise Details</h2>
                        <p class="text-sm text-gray-500">{{ selectedFranchise.zone_name }}</p>
                    </div>
                    <div class="text-right">
                         <span class="px-3 py-1 bg-blue-100 text-blue-800 font-mono font-bold rounded-lg text-sm">{{ selectedFranchise.plate_number || 'NO PLATE' }}</span>
                    </div>
                </div>

                <div class="space-y-6 overflow-y-auto max-h-[70vh] custom-scrollbar pr-2">
                    
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                         <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Unit Information</h3>
                         <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><span class="block text-xs text-gray-500">Make</span><span class="font-medium text-gray-900">{{ selectedFranchise.make_name }}</span></div>
                            <div><span class="block text-xs text-gray-500">Model Year</span><span class="font-medium text-gray-900">{{ selectedFranchise.model_year }}</span></div>
                            <div><span class="block text-xs text-gray-500">Date Issued</span><span class="font-medium text-gray-900">{{ selectedFranchise.date_issued }}</span></div>
                            <div><span class="block text-xs text-gray-500">CR Number</span><span class="font-medium text-gray-900">{{ selectedFranchise.cr_number }}</span></div>
                         </div>
                    </div>

                    <div>
                         <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Technical Specifications</h3>
                         <div class="grid grid-cols-2 gap-4 text-sm bg-white border border-gray-200 p-4 rounded-lg">
                            <div><span class="block text-xs text-gray-500 uppercase">Motor Number</span><span class="font-mono text-gray-900">{{ selectedFranchise.motor_number }}</span></div>
                            <div><span class="block text-xs text-gray-500 uppercase">Chassis Number</span><span class="font-mono text-gray-900">{{ selectedFranchise.chassis_number }}</span></div>
                         </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Unit Photos</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="aspect-video bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center overflow-hidden relative group">
                                <img v-if="selectedFranchise[`unit_${side}_photo`]" :src="selectedFranchise[`unit_${side}_photo`]" class="w-full h-full object-cover" />
                                <div v-else class="text-center">
                                    <p class="text-[10px] text-gray-400 uppercase font-bold">{{ side }}</p>
                                    <p class="text-[9px] text-gray-300">No Photo</p>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-[10px] font-bold uppercase py-1 text-center">{{ side }} View</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-6" v-if="selectedFranchise && selectedFranchise.franchise_certificate_photo">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2">Franchise Certificate</h3>
                    <div class="border rounded p-2 bg-gray-50">
                        <img :src="selectedFranchise.franchise_certificate_photo" alt="Franchise Certificate" class="w-full h-auto rounded shadow-sm">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeFranchiseModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRequirementModal" @close="closeRequirementModal">
            <div class="p-6 h-[80vh] flex flex-col" v-if="selectedRequirement">
                
                <div class="flex justify-between items-center mb-4 flex-none">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">{{ selectedRequirement.name }}</h2>
                         <div class="flex items-center gap-2 mt-1">
                             <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                :class="{
                                    'bg-green-100 text-green-700': selectedRequirement.status === 'Approved',
                                    'bg-red-100 text-red-700': selectedRequirement.status === 'Rejected',
                                    'bg-gray-100 text-gray-600': selectedRequirement.status === 'Pending'
                                }">
                                {{ selectedRequirement.status }}
                            </span>
                            <span v-if="selectedRequirement.remarks" class="text-xs text-red-500 font-medium">{{ selectedRequirement.remarks }}</span>
                        </div>
                    </div>
                    <button @click="closeRequirementModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center mb-4 relative">
                    <img v-if="selectedRequirement.file_url && selectedRequirement.file_url !== '#'" :src="selectedRequirement.file_url" class="max-w-full max-h-full object-contain" />
                     <div v-else class="text-center text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                        <p class="text-sm font-medium">Document Preview</p>
                    </div>
                </div>

                <div class="flex-none pt-4 border-t border-gray-100">
                     <div class="mb-4">
                        <InputLabel for="req_remarks" value="Remarks (Required for Rejection)" />
                        <textarea 
                            id="req_remarks" 
                            v-model="requirementForm.remarks"
                            rows="2"
                            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" 
                            placeholder="Enter notes..."
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                         <button @click="saveRequirementStatus('Rejected')" :disabled="!requirementForm.remarks" 
                            class="px-4 py-2 bg-white border border-red-300 text-red-700 hover:bg-red-50 rounded-md text-sm font-bold shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Reject Document
                        </button>
                        <PrimaryButton @click="saveRequirementStatus('Approved')" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                            Approve Document
                        </PrimaryButton>
                    </div>
                </div>

            </div>
        </Modal>


        <Modal :show="showInspectionModal" @close="closeInspectionModal">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Update Inspection: {{ inspectionForm.item }}</h2>
                <div class="space-y-4 mb-6">
                    <div>
                        <InputLabel for="status" value="Status" />
                        <select id="status" v-model="inspectionForm.status" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            <option value="" disabled>Select Status</option>
                            <option v-for="opt in inspectionForm.options" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="remarks" value="Remarks / Observations (Optional)" />
                        <TextInput id="remarks" type="text" v-model="inspectionForm.remarks" class="mt-1 block w-full text-sm" placeholder="Enter specific details..." />
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <SecondaryButton @click="closeInspectionModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="saveInspection" :disabled="!inspectionForm.status">Save Update</PrimaryButton>
                </div>
            </div>
        </Modal>

         <Modal :show="showReturnModal" @close="showReturnModal = false">
             <div class="p-6">
                <h2 class="text-lg font-bold text-amber-600 mb-2">Return Application</h2>
                <p class="text-sm text-gray-500 mb-4">Please specify the corrections needed for this application. The applicant will be notified to update their submission.</p>
                
                <div class="mb-6">
                    <InputLabel for="return_remarks" value="Reason for Return / Remarks" />
                    <textarea 
                        id="return_remarks" 
                        v-model="returnForm.remarks"
                        rows="4"
                        class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm" 
                        placeholder="E.g., Incorrect photo format, missing signature on barangay clearance..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="showReturnModal = false">Cancel</SecondaryButton>
                    <PrimaryButton class="bg-amber-600 hover:bg-amber-700 focus:ring-amber-500" @click="confirmReturnApplication" :disabled="!returnForm.remarks">
                        Confirm Return
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

<Modal :show="showRejectModal" @close="showRejectModal = false">
    <div class="p-6">
        <h2 class="text-lg font-medium text-red-900 mb-4">Reject Application</h2>
        <p class="text-sm text-gray-600 mb-4">
            Are you sure you want to reject this application? This action is typically final.
        </p>
        <div class="mb-4">
            <InputLabel value="Reason for Rejection" />
            <textarea 
                v-model="rejectForm.remarks"
                rows="4" 
                class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" 
                placeholder="E.g., Applicant does not meet residency requirements..."
            ></textarea>
        </div>
        <div class="flex justify-end gap-3">
            <SecondaryButton @click="showRejectModal = false">Cancel</SecondaryButton>
            <PrimaryButton 
                class="bg-red-600 hover:bg-red-700 focus:ring-red-500" 
                @click="confirmRejectApplication" 
                :disabled="!rejectForm.remarks"
            >
                Confirm Rejection
            </PrimaryButton>
        </div>
    </div>
</Modal>

<CreateFranchiseAccountModal 
            :show="showCreateAccountModal" 
            :application="application" 
            :barangays="barangays"
            :zones="zones"
            :unitMakes="unitMakes"
            @close="showCreateAccountModal = false" 
        />
        <ChangeOfUnitModal :show="showChangeUnitModal" :application="application" @close="showChangeUnitModal = false" />
        <ChangeOfOwnerModal :show="showChangeOwnerModal" :application="application" @close="showChangeOwnerModal = false" />

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>