<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

// Import Component strictly for Franchise Owner Account
import CreateFranchiseAccountModal from '@/Components/Modals/CreateFranchiseAccountModal.vue';

const props = defineProps({
    application: Object,
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
});

// --- STATE ---
const activeTab = ref('evaluation');
const showInspectionModal = ref(false);
const showFranchiseModal = ref(false); 
const showRequirementModal = ref(false);
const showMediaModal = ref(false); 

const selectedItemIndex = ref(null);
const selectedFranchise = ref(null);
const selectedRequirementIndex = ref(null);

const mediaUrl = ref('');
const mediaTitle = ref('');

// Track parent modal to reopen when child modal closes
const parentModal = ref(null);

// Modal States
const showCreateAccountModal = ref(false);
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

// --- HELPER: Identify PDF files ---
const isPdf = (path) => {
    if (!path) return false;
    return path.toLowerCase().endsWith('.pdf');
};

// --- ACTIONS: Media Viewer (Child Modal Logic) ---
const viewMedia = (path, title) => {
    if (!path) return;
    
    // Check if Franchise Modal is currently open to set it as parent
    if (showFranchiseModal.value) {
        parentModal.value = 'franchise';
        showFranchiseModal.value = false; // Hide parent modal
    } else {
        parentModal.value = null;
    }

    // Ensure path has correct prefix if not an absolute URL
    mediaUrl.value = (path.startsWith('http') || path.startsWith('/storage')) ? path : '/storage/' + path;
    mediaTitle.value = title;
    showMediaModal.value = true;
};

const closeMediaModal = () => {
    showMediaModal.value = false;
    mediaUrl.value = '';
    mediaTitle.value = '';

    // Reopen parent modal if it exists
    if (parentModal.value === 'franchise') {
        // Slight delay to allow fade out of media modal before fading in parent
        setTimeout(() => {
            showFranchiseModal.value = true;
            parentModal.value = null;
        }, 200);
    }
};

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    return {
        ...app,
        inspection_requirements: app.inspection_requirements || [],
        evaluation_requirements: app.evaluation_requirements || [],
        complaints: app.complaints || [],
        franchises: app.franchises || [], 
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

const confirmApproveApplication = () => {
    if (!confirm("Are you sure you want to APPROVE this application?")) return;
    
    router.post(route('admin.applications.approve', props.application.id), {}, {
        onSuccess: () => {}
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

                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Address</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.street }}, {{ application.applicant.barangay_name }}, {{ application.applicant.city }}</p></div>
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
                                        class="p-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors group"
                                        @click="openRequirementModal(index)">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-100 transition-colors">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ req.name }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" 
                                                        :class="{ 'bg-green-100 text-green-700': req.status === 'Approved', 'bg-red-100 text-red-700': req.status === 'Rejected', 'bg-gray-100 text-gray-600': req.status === 'Pending' }">
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
                                <div class="divide-y divide-gray-100">
                                    <div v-for="franchise in application.franchises" :key="franchise.id" 
                                        class="p-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors"
                                        @click="openFranchiseModal(franchise)">
                                        <div class="flex items-center gap-4">
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">Make: {{ franchise.make_name }}</p>
                                                <p class="text-xs text-gray-500">Model: {{ franchise.model_year }} | Plate: {{ franchise.plate_number }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'inspection' && showInspectionTab" class="space-y-6">
                            <div v-if="application.inspection_requirements.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                                                :class="{ 'bg-green-100 text-green-700': ['Working', 'Good', 'Pass', 'Excellent', 'Present'].includes(item.status), 'bg-red-100 text-red-700': ['Defective', 'Fail', 'Needs Repair', 'Missing'].includes(item.status) }">
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
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="complaint.status === 'Resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                                    {{ complaint.status }}
                                                </span>
                                                <span class="text-xs text-gray-400">{{ complaint.incident_date }} at {{ complaint.incident_time }}</span>
                                            </div>
                                            <h3 class="font-bold text-gray-900">{{ complaint.nature_of_complaint }}</h3>
                                        </div>
                                        <button v-if="complaint.status === 'Pending'" @click="resolveComplaint(index)" class="text-xs text-blue-600 hover:underline">Mark Resolved</button>
                                    </div>
                                    <p class="text-sm text-gray-700">{{ complaint.details }}</p>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No complaints recorded.</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'receipt' && showReceiptTab" class="space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <div class="text-center py-12">
                                    <p class="text-gray-500 text-sm">No receipt generated yet.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <Transition name="fade">
                <div v-if="showRequirementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeRequirementModal">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6 h-[85vh] flex flex-col" v-if="selectedRequirement">
                            <div class="flex-none flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900">{{ selectedRequirement.name }}</h2>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase" 
                                            :class="{
                                                'bg-green-100 text-green-700': selectedRequirement.status === 'Approved',
                                                'bg-red-100 text-red-700': selectedRequirement.status === 'Rejected',
                                                'bg-gray-100 text-gray-600': selectedRequirement.status === 'Pending'
                                            }">
                                            {{ selectedRequirement.status }}
                                        </span>
                                    </div>
                                </div>
                                <button @click="closeRequirementModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            
                            <div class="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center mb-4 relative overflow-hidden">
                                <iframe v-if="isPdf(selectedRequirement.file_url)" :src="selectedRequirement.file_url" class="w-full h-full border-0"></iframe>
                                <img v-else-if="selectedRequirement.file_url && selectedRequirement.file_url !== '#'" :src="selectedRequirement.file_url" class="max-w-full max-h-full object-contain" />
                                <div v-else class="text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                    <p class="text-sm font-medium">No Document Available</p>
                                </div>
                            </div>

                            <div class="flex-none pt-4 border-t border-gray-100">
                                <div class="mb-4">
                                    <InputLabel for="req_remarks" value="Remarks (Required for Rejection)" />
                                    <textarea id="req_remarks" v-model="requirementForm.remarks" rows="2" class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" placeholder="Provide reason if rejecting..."></textarea>
                                </div>
                                <div class="flex justify-between items-center">
                                    <SecondaryButton @click="closeRequirementModal">Close</SecondaryButton>
                                    <div class="flex gap-2">
                                        <PrimaryButton @click="saveRequirementStatus('Rejected')" class="bg-red-600 hover:bg-red-700 focus:ring-red-500">
                                            Reject
                                        </PrimaryButton>
                                        <PrimaryButton @click="saveRequirementStatus('Approved')" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                                            Approve
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showMediaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeMediaModal">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6 h-[85vh] flex flex-col">
                            <div class="flex-none flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-gray-900">{{ mediaTitle }}</h2>
                                <button @click="closeMediaModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            
                            <div class="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center relative overflow-hidden">
                                <iframe v-if="isPdf(mediaUrl)" :src="mediaUrl" class="w-full h-full border-0"></iframe>
                                <img v-else-if="mediaUrl && mediaUrl !== '#'" :src="mediaUrl" class="max-w-full max-h-full object-contain" />
                                <div v-else class="text-center text-gray-400">
                                    <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <p class="text-sm font-medium">No Media Available</p>
                                </div>
                            </div>

                            <div class="flex-none pt-4 flex justify-end mt-4">
                                <SecondaryButton @click="closeMediaModal">Close Viewer</SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showFranchiseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeFranchiseModal">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col" @click.stop>
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
                                        <SecondaryButton v-if="selectedFranchise.unit_front_photo" @click="viewMedia(selectedFranchise.unit_front_photo, 'Front Photo')" class="justify-center text-xs">View Front</SecondaryButton>
                                        <SecondaryButton v-if="selectedFranchise.unit_back_photo" @click="viewMedia(selectedFranchise.unit_back_photo, 'Back Photo')" class="justify-center text-xs">View Back</SecondaryButton>
                                        <SecondaryButton v-if="selectedFranchise.unit_left_photo" @click="viewMedia(selectedFranchise.unit_left_photo, 'Left Photo')" class="justify-center text-xs">View Left</SecondaryButton>
                                        <SecondaryButton v-if="selectedFranchise.unit_right_photo" @click="viewMedia(selectedFranchise.unit_right_photo, 'Right Photo')" class="justify-center text-xs">View Right</SecondaryButton>
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Attached Documents</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <SecondaryButton v-if="selectedFranchise.cr_photo" @click="viewMedia(selectedFranchise.cr_photo, 'Certificate of Registration')" class="justify-center text-xs">View CR</SecondaryButton>
                                        <SecondaryButton v-if="selectedFranchise.or_photo" @click="viewMedia(selectedFranchise.or_photo, 'Official Receipt')" class="justify-center text-xs">View OR</SecondaryButton>
                                        <SecondaryButton v-if="selectedFranchise.franchise_certificate_photo" @click="viewMedia(selectedFranchise.franchise_certificate_photo, 'Franchise Certificate')" class="col-span-2 justify-center text-xs">View Franchise Certificate</SecondaryButton>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end mt-6 pt-4 border-t border-gray-100">
                                <SecondaryButton @click="closeFranchiseModal">Close</SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="showReturnModal = false">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Return Application</h2>
                            <p class="text-sm text-gray-600 mb-4">Please provide a reason for returning this application. The applicant will be able to edit and resubmit.</p>
                            <div class="mb-4">
                                <InputLabel value="Remarks / Reason for Return" />
                                <textarea v-model="returnForm.remarks" rows="3" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm" placeholder="E.g., Missing back photo of the unit..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3">
                                <SecondaryButton @click="showReturnModal = false">Cancel</SecondaryButton>
                                <PrimaryButton class="bg-amber-500 hover:bg-amber-600 focus:ring-amber-500" @click="confirmReturnApplication" :disabled="!returnForm.remarks">
                                    Confirm Return
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="showRejectModal = false">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-red-600 mb-4">Reject Application</h2>
                            <p class="text-sm text-gray-600 mb-4">Are you sure you want to permanently reject this application? This action cannot be undone.</p>
                            <div class="mb-4">
                                <InputLabel value="Reason for Rejection" />
                                <textarea v-model="rejectForm.remarks" rows="3" class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" placeholder="E.g., Applicant does not meet residency requirements..."></textarea>
                            </div>
                            <div class="flex justify-end gap-3">
                                <SecondaryButton @click="showRejectModal = false">Cancel</SecondaryButton>
                                <PrimaryButton class="bg-red-600 hover:bg-red-700 focus:ring-red-500" @click="confirmRejectApplication" :disabled="!rejectForm.remarks">
                                    Confirm Rejection
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <CreateFranchiseAccountModal 
                :show="showCreateAccountModal" 
                :application="application" 
                :barangays="barangays"
                :zones="zones"
                :unitMakes="unitMakes"
                @close="showCreateAccountModal = false" 
            />

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

/* Transition styles for modals */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>