<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MakeApplicationModal from '@/Components/Modals/MakeApplicationModal.vue';
import ComplyApplicationModal from '@/Components/Modals/ComplyApplicationModal.vue';
import SubmitRenewalModal from '@/Components/Modals/SubmitRenewalModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    hasFranchise: Boolean,
    franchises: Array,
    operator: Object,
    evaluationRequirements: {
        type: Object,
        default: () => ({})
    },
    barangays: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    operators: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
    applications: { type: Array, default: () => [] }
});

const processSteps = [{ id: 1, label: 'Sub' }, { id: 2, label: 'Rev' }, { id: 3, label: 'Insp/Pay' }, { id: 4, label: 'Done' }];

// --- VIEW STATE ---
const activeTab = ref('active'); 
const showNewAppModal = ref(false);
const showComplyModal = ref(false);
const showCancelModal = ref(false);
const showSubmitRenewalModal = ref(false);
const showInspectionFailedModal = ref(false);

const selectedReturnedApp = ref(null); 
const selectedRenewalApp = ref(null);
const appToCancel = ref(null);

const openSubmitRenewalModal = (app) => {
    selectedRenewalApp.value = app;
    showSubmitRenewalModal.value = true;
};

// Define the terminal statuses that should move an application to the History tab
const terminalStatuses = ['Completed', 'Rejected', 'Cancelled'];

const activeApplications = computed(() => props.applications.filter(app => !terminalStatuses.includes(app.status)));
const pastApplications = computed(() => props.applications.filter(app => terminalStatuses.includes(app.status)));

// --- PAGINATION STATE & LOGIC ---
const itemsPerPage = 6;
const activePage = ref(1);
const historyPage = ref(1);

const paginatedActiveApplications = computed(() => {
    const start = (activePage.value - 1) * itemsPerPage;
    return activeApplications.value.slice(start, start + itemsPerPage);
});
const activeTotalPages = computed(() => Math.ceil(activeApplications.value.length / itemsPerPage) || 1);

const paginatedPastApplications = computed(() => {
    const start = (historyPage.value - 1) * itemsPerPage;
    return pastApplications.value.slice(start, start + itemsPerPage);
});
const historyTotalPages = computed(() => Math.ceil(pastApplications.value.length / itemsPerPage) || 1);

// --- ACTIONS & HELPERS ---
const isUnsubmittedRenewal = (app) => {
    const isRenewal = app.type === 'Renewal' || app.application_type === 'Renewal';
    if (!isRenewal) return false;

    const activeStatuses = ['Pending', 'Under Review', 'Inspection', 'Processing', 'For Payment', 'Approved', 'Completed', 'Returned'];
    if (activeStatuses.includes(app.status)) return false;
    if (app.submitted_at) return false;

    return true;
};

const isRowInteractive = (app) => {
    if (app.status === 'Returned') return true;
    if (isUnsubmittedRenewal(app)) return true;
    return false;
};

const handleCardClick = (app) => {
    if (!isRowInteractive(app)) return;

    if (app.status === 'Returned') {
        selectedReturnedApp.value = app; 
        
        // CONDITIONAL MODAL LOGIC: Display inspection results if rejected by inspector
        if (app.inspector_status === 'Rejected') {
            showInspectionFailedModal.value = true;
        } else {
            showComplyModal.value = true; // Fallback to standard document compliance
        }
    } else if (isUnsubmittedRenewal(app)) { 
        selectedRenewalApp.value = app;
        showSubmitRenewalModal.value = true;
    }
};

const handleNewApplicationSubmit = () => showNewAppModal.value = false;

const handleComplianceSubmit = () => {
    showComplyModal.value = false;
    selectedReturnedApp.value = null;
};

const handleSubmitRenewalSubmit = () => { 
    showSubmitRenewalModal.value = false;
    selectedRenewalApp.value = null;
};

const confirmCancelApplication = (app) => {
    appToCancel.value = app;
    showCancelModal.value = true;
};

const executeCancelApplication = () => {
    if (appToCancel.value) {
        router.post(`/franchise/applications/${appToCancel.value.id}/cancel`, {}, { 
            preserveScroll: true,
            onSuccess: () => closeCancelModal()
        });
    }
};

const closeCancelModal = () => {
    showCancelModal.value = false;
    appToCancel.value = null;
};

const closeInspectionFailedModal = () => {
    showInspectionFailedModal.value = false;
    selectedReturnedApp.value = null;
};

const approvalStages = [
    { key: 'evaluator_status', label: 'EVL', tooltip: 'Evaluator' },
    { key: 'inspector_status', label: 'INS', tooltip: 'Inspector' },
    { key: 'capo_status', label: 'CPO', tooltip: 'City Anti-Pollution Officer' },
    { key: 'reviewer_status', label: 'REV', tooltip: 'Reviewer' },
    { key: 'sp_status', label: 'SP', tooltip: 'Sanggunian Panlungsod' },
    { key: 'tab_status', label: 'TAB', tooltip: 'Tricycle Adjudication Board' }
];

const getBadgeStyle = (status) => {
    if (status === 'Approved' || status === 'Completed') return 'bg-green-100 text-green-700 border-green-200';
    if (status === 'Rejected' || status === 'Returned') return 'bg-red-100 text-red-700 border-red-200';
    return 'bg-gray-100 text-gray-400 border-gray-200';
};

const getApprovalStages = (app) => {
    const appType = app.type || app.application_type;

    // Filter out stages based on the application type
    return approvalStages.filter(stage => {
        // NEW RULE: New Driver only displays Evaluator
        if (appType === 'New Driver') {
            return stage.key === 'evaluator_status';
        }

        // Rule 1: Change of Unit and Change of Owner both skip 'sp_status'
        if ((appType === 'Change of Unit' || appType === 'Change of Owner') && stage.key === 'sp_status') {
            return false;
        }

        // Rule 2: Change of Owner specifically skips inspector and capo
        if (appType === 'Change of Owner') {
            const excludeForOwner = ['inspector_status', 'capo_status']; 
            if (excludeForOwner.includes(stage.key)) {
                return false;
            }
        }

        return true;
    });
};

const resubmitForInspection = () => {
    if (!selectedReturnedApp.value) return;
    
    router.post(`/franchise/applications/${selectedReturnedApp.value.id}/resubmit-inspection`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeInspectionFailedModal();
        }
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My Applications" />

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">My Applications</h1>
                <p class="text-sm text-gray-500">Track current requests and view history.</p>
            </div>
            <PrimaryButton @click="showNewAppModal = true" class="shadow-md !text-xs !px-4 !py-2">
                + New Application
            </PrimaryButton>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden min-h-[500px] flex flex-col">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px px-6" aria-label="Tabs">
                    <button @click="activeTab = 'active'"
                        :class="[activeTab === 'active' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-4 border-b-2 font-bold text-sm transition-colors flex items-center gap-2']">
                        Active Applications
                        <span v-if="activeApplications.length > 0" class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs font-normal">
                            {{ activeApplications.length }}
                        </span>
                    </button>
                    <button @click="activeTab = 'history'"
                        :class="[activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-4 border-b-2 font-bold text-sm transition-colors']">
                        Application History
                    </button>
                </nav>
            </div>

            <div class="flex-1 flex flex-col">
                <div v-if="activeTab === 'active'">
                    <div v-if="activeApplications.length === 0" class="p-10 text-center text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">No active applications found.</p>
                        <button @click="showNewAppModal = true" class="text-blue-600 text-sm font-medium hover:underline mt-2">Start a new application</button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref No. & Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Progress</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Remarks</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="app in paginatedActiveApplications" :key="app.id" 
                                    @click="handleCardClick(app)"
                                    class="transition-colors group"
                                    :class="{ 
                                        'hover:bg-red-50 cursor-pointer': app.status === 'Returned', 
                                        'hover:bg-blue-50 cursor-pointer': isUnsubmittedRenewal(app),
                                        'hover:bg-gray-50': !isRowInteractive(app) 
                                    }">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col"><span class="text-sm font-bold text-gray-900 font-mono">{{ app.ref_no }}</span><span class="text-xs text-gray-500">{{ app.date }}</span></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-700">{{ app.type || app.application_type }}</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded border"
                                            :class="{
                                                'bg-yellow-50 text-yellow-700 border-yellow-200': app.status === 'Pending',
                                                'bg-green-50 text-green-700 border-green-200': app.status === 'Approved',
                                                'bg-blue-50 text-blue-700 border-blue-200': ['Under Review', 'Inspection', 'Processing', 'For Payment'].includes(app.status),
                                                'bg-red-100 text-red-700 border-red-200 animate-pulse': app.status === 'Returned'
                                            }">
                                            {{ app.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-middle">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <div v-for="stage in getApprovalStages(app)" :key="stage.key" :title="`${stage.tooltip}: ${app[stage.key]}`" 
                                                class="px-2 py-1 text-[10px] font-bold rounded border cursor-help text-center min-w-[36px] transition-colors"
                                                :class="getBadgeStyle(app[stage.key])">
                                                {{ stage.label }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><div class="text-xs text-gray-500 max-w-xs truncate" :title="app.remarks">"{{ app.remarks }}"</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-3 ml-auto">
                                            <button 
                                                v-if="app.application_type === 'Renewal' && app.status === 'Initial'"
                                                @click.prevent="openSubmitRenewalModal(app)"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase rounded hover:bg-blue-700"
                                            >
                                                Upload Renewal Requirements
                                            </button>
                                            <button v-if="app.status === 'Returned'" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase flex items-center gap-1">
                                                {{ app.inspector_status === 'Rejected' ? 'View Issues' : 'Comply' }}
                                            </button>
                                            
                                            <button v-if="isUnsubmittedRenewal(app)" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase flex items-center gap-1">
                                                Upload Docs
                                            </button>

                                            <button v-if="app.type !== 'Renewal' && app.application_type !== 'Renewal'" @click.stop="confirmCancelApplication(app)" class="text-red-500 hover:text-red-700 transition-colors p-1 rounded hover:bg-red-50" title="Cancel Application">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="activeTab === 'history'">
                    <div v-if="pastApplications.length === 0" class="p-10 text-center text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">No past applications found.</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref No. & Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="app in paginatedPastApplications" :key="app.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col"><span class="text-sm font-bold text-gray-900 font-mono">{{ app.ref_no }}</span><span class="text-xs text-gray-500">{{ app.date }}</span></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-700">{{ app.type || app.application_type }}</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded border"
                                            :class="{
                                                'bg-green-100 text-green-700 border-green-200': app.status === 'Approved' || app.status === 'Completed',
                                                'bg-red-100 text-red-700 border-red-200': app.status === 'Rejected',
                                                'bg-gray-100 text-gray-600 border-gray-200': app.status === 'Cancelled'
                                            }">
                                            {{ app.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><div class="text-xs text-gray-500 max-w-xs truncate" :title="app.remarks">"{{ app.remarks }}"</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 px-6 py-3 bg-gray-50 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-medium">{{ activeTab === 'active' ? ((activePage - 1) * itemsPerPage) + 1 : ((historyPage - 1) * itemsPerPage) + 1 }}</span> to 
                    <span class="font-medium">{{ activeTab === 'active' ? Math.min(activePage * itemsPerPage, activeApplications.length) : Math.min(historyPage * itemsPerPage, pastApplications.length) }}</span> of 
                    <span class="font-medium">{{ activeTab === 'active' ? activeApplications.length : pastApplications.length }}</span> results
                </div>
                <div class="flex gap-2">
                    <button v-if="activeTab === 'active'" :disabled="activePage === 1" @click="activePage--" class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">Prev</button>
                    <button v-if="activeTab === 'active'" :disabled="activePage === activeTotalPages" @click="activePage++" class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">Next</button>
                    
                    <button v-if="activeTab === 'history'" :disabled="historyPage === 1" @click="historyPage--" class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">Prev</button>
                    <button v-if="activeTab === 'history'" :disabled="historyPage === historyTotalPages" @click="historyPage++" class="px-3 py-1 border border-gray-300 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-colors">Next</button>
                </div>
            </div>
        </div>

        <MakeApplicationModal 
            :show="showNewAppModal" 
            :applications="applications"
            :evaluationRequirements="evaluationRequirements" 
            :franchises="franchises" 
            :barangays="barangays" 
            :unitMakes="unitMakes" 
            :operators="operators" 
            :units="units" 
            @close="showNewAppModal = false" 
            @submit="handleNewApplicationSubmit" 
        />

        <ComplyApplicationModal 
            :show="showComplyModal" 
            :application="selectedReturnedApp"
            :evaluationRequirements="evaluationRequirements"
            @close="showComplyModal = false"
            @submit="handleComplianceSubmit"
        />

        <SubmitRenewalModal 
            :show="showSubmitRenewalModal" 
            :application="selectedRenewalApp"
            :evaluationRequirements="evaluationRequirements"
            @close="showSubmitRenewalModal = false"
            @submit="handleSubmitRenewalSubmit"
        />

<transition name="fade">
            <div v-if="showInspectionFailedModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeInspectionFailedModal"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all flex flex-col max-h-[90vh]">
                    
                    <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center gap-3 flex-shrink-0">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-red-800">Unit Inspection Failed</h3>
                            <p class="text-xs text-red-600">Please fix the mechanical issues and return for re-inspection.</p>
                        </div>
                        <button @click="closeInspectionFailedModal" class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded-md hover:bg-red-100 focus:outline-none">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-4 overflow-y-auto custom-scrollbar flex-1 bg-white space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Inspector's Remarks</p>
                            <p class="text-sm font-medium text-gray-800">{{ selectedReturnedApp?.remarks }}</p>
                        </div>

                        <h4 class="font-bold text-gray-700 text-sm border-b pb-2">Itemized Inspection Results</h4>
                        
                        <div class="space-y-2">
                            <template v-if="selectedReturnedApp?.unit_inspections?.length > 0">
                                <div v-for="item in selectedReturnedApp.unit_inspections" :key="item.id" 
                                     class="flex flex-col p-3 rounded-lg border"
                                     :class="item.rating.toLowerCase() === 'fail' || item.rating.toLowerCase() === 'rejected' ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-sm" :class="item.rating.toLowerCase() === 'fail' || item.rating.toLowerCase() === 'rejected' ? 'text-red-800' : 'text-green-800'">
                                            {{ item.name }}
                                        </span>
                                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded border"
                                              :class="item.rating.toLowerCase() === 'fail' || item.rating.toLowerCase() === 'rejected' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-green-100 text-green-800 border-green-200'">
                                            {{ item.rating }}
                                        </span>
                                    </div>
                                    <span class="text-xs mt-1" :class="item.rating.toLowerCase() === 'fail' || item.rating.toLowerCase() === 'rejected' ? 'text-red-600 font-medium' : 'text-green-700'">
                                        "{{ item.remarks }}"
                                    </span>
                                </div>
                            </template>
                            <template v-else>
                                <p class="text-sm text-gray-500 italic text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-200">No specific inspection items recorded.</p>
                            </template>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end gap-3 flex-shrink-0">
                        <PrimaryButton type="button" @click="resubmitForInspection" class="bg-blue-600 hover:bg-blue-700">
                            Unit Fixed, Request Re-inspection
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeCancelModal"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Cancel Application</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Are you sure you want to cancel the application <span class="font-bold text-gray-700">{{ appToCancel?.ref_no }}</span>? This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" @click="executeCancelApplication" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Yes, Cancel it
                        </button>
                        <button type="button" @click="closeCancelModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            No, keep it
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>