<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MakeApplicationModal from '@/Components/Modals/MakeApplicationModal.vue';
import ComplyApplicationModal from '@/Components/Modals/ComplyApplicationModal.vue';
import { Head } from '@inertiajs/vue3';
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
    // Added applications prop fetched from the backend
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
const selectedReturnedApp = ref(null); 

// Filter fetched applications instead of dummy data
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
    // The modal now handles the actual Inertia post natively. 
    // On success, Inertia reloads the page with updated props.
    showNewAppModal.value = false;
};

const handleComplianceSubmit = () => {
    // Similarly, rely on Inertia page visits to sync data
    showComplyModal.value = false;
    selectedReturnedApp.value = null;
};

const getStepPercentage = (app) => ((app.current_step) / processSteps.length) * 100;
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
                                        <div class="w-full">
                                            <div class="flex justify-between items-end mb-1">
                                                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Step {{ app.current_step }} of {{ processSteps.length }}</span>
                                                <span class="text-[10px] text-gray-400 font-mono">{{ Math.round((app.current_step / processSteps.length) * 100) }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-blue-500 h-1.5 rounded-full transition-all duration-500" 
                                                        :class="{'bg-red-500': app.status === 'Returned', 'bg-green-500': app.status === 'Approved'}"
                                                        :style="{ width: `${getStepPercentage(app)}%` }"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><div class="text-xs text-gray-500 max-w-xs truncate" :title="app.remarks">"{{ app.remarks }}"</div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button v-if="app.status === 'Returned'" class="text-red-600 hover:text-red-900 font-bold text-xs uppercase flex items-center justify-end gap-1 ml-auto">
                                            Comply <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                        </button>
                                        <span v-else class="text-gray-300 text-xs">View</span>
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
                                                'bg-green-50 text-green-700 border-green-200': app.status === 'Approved',
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
                                                        'bg-green-500': app.status === 'Approved',
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
    </AuthenticatedLayout>
</template>