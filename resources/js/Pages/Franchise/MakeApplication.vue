<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MakeApplicationModal from '@/Components/Modals/MakeApplicationModal.vue';
import ComplyApplicationModal from '@/Components/Modals/ComplyApplicationModal.vue';
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
    barangays: { 
        type: Array, 
        default: () => [] 
    },
    unitMakes: { 
        type: Array, 
        default: () => [] 
    },
    operators: { 
        type: Array, 
        default: () => [] 
    },
    units: { 
        type: Array, 
        default: () => [] 
    },
    applications: {
        type: Array,
        default: () => []
    }
});

const processSteps = [{ id: 1, label: 'Sub' }, { id: 2, label: 'Rev' }, { id: 3, label: 'Insp/Pay' }, { id: 4, label: 'Done' }];

// --- VIEW STATE ---
const activeTab = ref('active'); 
const showNewAppModal = ref(false);
const showComplyModal = ref(false);
const showCancelModal = ref(false);

const selectedReturnedApp = ref(null); 
const appToCancel = ref(null);

const activeApplications = computed(() => props.applications.filter(app => app.is_active));
const pastApplications = computed(() => props.applications.filter(app => !app.is_active));

// --- ACTIONS ---
const handleCardClick = (app) => {
    if (app.status === 'Returned') {
        selectedReturnedApp.value = app; 
        showComplyModal.value = true;
    }
};

const handleNewApplicationSubmit = () => {
    showNewAppModal.value = false;
};

const handleComplianceSubmit = () => {
    showComplyModal.value = false;
    selectedReturnedApp.value = null;
};

// Open the cancellation modal
const confirmCancelApplication = (app) => {
    appToCancel.value = app;
    showCancelModal.value = true;
};

// Execute the cancellation
const executeCancelApplication = () => {
    if (appToCancel.value) {
        router.post(`/franchise/applications/${appToCancel.value.id}/cancel`, {}, { 
            preserveScroll: true,
            onSuccess: () => {
                closeCancelModal();
            }
        });
    }
};

// Close the cancellation modal
const closeCancelModal = () => {
    showCancelModal.value = false;
    appToCancel.value = null;
};

const getStepPercentage = (app) => ((app.current_step) / processSteps.length) * 100;

const approvalStages = [
    { key: 'evaluator_status', label: 'EVL', tooltip: 'Evaluator' },
    { key: 'inspector_status', label: 'INS', tooltip: 'Inspector' },
    { key: 'capo_status', label: 'CPO', tooltip: 'CAPO' },
    { key: 'reviewer_status', label: 'REV', tooltip: 'Reviewer' },
    { key: 'sp_status', label: 'SP', tooltip: 'Sanggunian Panlungsod' },
    { key: 'tab_status', label: 'TAB', tooltip: 'Tricycle Adjudication Board' }
];

const getBadgeStyle = (status) => {
    if (status === 'Approved' || status === 'Completed') return 'bg-green-100 text-green-700 border-green-200';
    if (status === 'Rejected' || status === 'Returned') return 'bg-red-100 text-red-700 border-red-200';
    return 'bg-gray-100 text-gray-400 border-gray-200'; // Pending or default
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
                                <tr v-for="app in activeApplications" :key="app.id" 
                                    @click="handleCardClick(app)"
                                    class="transition-colors group"
                                    :class="{ 'hover:bg-red-50 cursor-pointer': app.status === 'Returned', 'hover:bg-gray-50': app.status !== 'Returned' }">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col"><span class="text-sm font-bold text-gray-900 font-mono">{{ app.ref_no }}</span><span class="text-xs text-gray-500">{{ app.date }}</span></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-700">{{ app.type }}</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded border"
                                            :class="{
                                                'bg-yellow-50 text-yellow-700 border-yellow-200': app.status === 'Pending',
                                                'bg-blue-50 text-blue-700 border-blue-200': ['Under Review', 'Inspection', 'Processing', 'For Payment'].includes(app.status),
                                                'bg-red-100 text-red-700 border-red-200 animate-pulse': app.status === 'Returned'
                                            }">
                                            {{ app.status }}
                                        </span>
                                    </td>
<td class="px-6 py-4 align-middle">
    <div class="flex items-center gap-1.5 flex-wrap">
        <div v-for="stage in approvalStages" :key="stage.key" 
             :title="`${stage.tooltip}: ${app[stage.key]}`"
             class="px-2 py-1 text-[10px] font-bold rounded border cursor-help text-center min-w-[36px] transition-colors"
             :class="getBadgeStyle(app[stage.key])">
            {{ stage.label }}
        </div>
    </div>
</td>
                                    <td class="px-6 py-4"><div class="text-xs text-gray-500 max-w-xs truncate" :title="app.remarks">"{{ app.remarks }}"</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-3 ml-auto">
                                            <button v-if="app.status === 'Returned'" class="text-blue-600 hover:text-blue-900 font-bold text-xs uppercase flex items-center gap-1">
                                                Comply <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                            </button>

                                            <button 
                                                v-if="app.type !== 'Renewal'" 
                                                @click.stop="confirmCancelApplication(app)" 
                                                class="text-red-500 hover:text-red-700 font-bold text-xs uppercase"
                                                title="Cancel Application"
                                            >
                                                Cancel
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">No application history found.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref No. & Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Progress</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Latest Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="app in pastApplications" :key="app.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col"><span class="text-sm font-bold text-gray-900 font-mono">{{ app.ref_no }}</span><span class="text-xs text-gray-500">{{ app.date }}</span></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-700">{{ app.type }}</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-bold rounded border"
                                            :class="{
                                                'bg-green-50 text-green-700 border-green-200': app.status === 'Completed' || app.status === 'Approved',
                                                'bg-red-50 text-red-700 border-red-200': app.status === 'Rejected',
                                                'bg-gray-100 text-gray-600 border-gray-200': app.status === 'Cancelled'
                                            }">
                                            {{ app.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-middle">
                                        <div class="w-full">
                                            <div class="flex justify-between items-end mb-1">
                                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Final</span>
                                                <span class="text-[10px] text-gray-400 font-mono">100%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full" 
                                                    :class="{
                                                        'bg-green-500': app.status === 'Approved' || app.status === 'Completed',
                                                        'bg-red-500': app.status === 'Rejected',
                                                        'bg-gray-400': app.status === 'Cancelled'
                                                    }"
                                                    style="width: 100%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><div class="text-xs text-gray-500 max-w-xs truncate" :title="app.remarks">"{{ app.remarks }}"</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <MakeApplicationModal 
            :show="showNewAppModal" 
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
            @close="showComplyModal = false; selectedReturnedApp = null"
            @submit="handleComplianceSubmit"
        />

        <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeCancelModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Cancel Application
                                </h3>
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
        </div>
    </AuthenticatedLayout>
</template>