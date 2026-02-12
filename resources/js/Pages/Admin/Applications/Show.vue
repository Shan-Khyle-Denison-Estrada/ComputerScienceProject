<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

// Import Components (Assumed to be created as per previous instruction)
import CreateFranchiseAccountModal from '@/Components/Modals/CreateFranchiseAccountModal.vue';
import ChangeOfUnitModal from '@/Components/Modals/ChangeOfUnitModal.vue';
import ChangeOfOwnerModal from '@/Components/Modals/ChangeOfOwnerModal.vue';

const props = defineProps({
    id: [String, Number],
});

// --- STATE ---
const activeTab = ref('evaluation');
const showInspectionModal = ref(false);
const selectedItemIndex = ref(null);

// Modal States
const showCreateAccountModal = ref(false);
const showChangeUnitModal = ref(false);
const showChangeOwnerModal = ref(false);

const inspectionForm = reactive({
    item: '',
    status: '',
    remarks: ''
});

// --- DUMMY DATA (Synced with Index.vue) ---
const dummyApplications = ref([
    {
        id: 1,
        reference_no: 'APP-2024-001',
        type: 'Franchise Owner Account',
        date_submitted: 'Oct 25, 2024',
        applicant: {
            first_name: 'Juan',
            last_name: 'Dela Cruz',
            email: 'juan.delacruz@example.com',
            photo: null,
            contact: '0917-123-4567',
            address: 'San Jose, Zamboanga City',
            civil_status: 'Married',
            birthdate: '1985-05-20'
        },
        status: 'Pending',
        documents: [{ name: 'Barangay Clearance', status: 'Verified' }],
        inspection: [],
        receipt: null
    },
    {
        id: 2,
        reference_no: 'APP-2024-002',
        type: 'Renewal',
        date_submitted: 'Oct 23, 2024',
        applicant: {
            first_name: 'Maria',
            last_name: 'Santos',
            email: 'maria.santos@example.com',
            photo: null,
            contact: '0918-987-6543',
            address: 'Tetuan, Zamboanga City',
            civil_status: 'Single',
            birthdate: '1990-11-15'
        },
        status: 'Approved',
        documents: [],
        inspection: [],
        receipt: null
    },
    {
        id: 3,
        reference_no: 'APP-2024-003',
        type: 'Change of Owner',
        date_submitted: 'Oct 20, 2024',
        applicant: {
            first_name: 'Pedro',
            last_name: 'Penduko',
            email: 'pedro.p@example.com',
            photo: null,
            contact: '0920-555-4444',
            address: 'Putik, Zamboanga City',
            civil_status: 'Married',
            birthdate: '1978-03-10'
        },
        status: 'Rejected',
        documents: [],
        inspection: [],
        receipt: null
    },
    {
        id: 4,
        reference_no: 'APP-2024-004',
        type: 'Change of Unit',
        date_submitted: 'Oct 18, 2024',
        applicant: {
            first_name: 'Anna',
            last_name: 'Reyes',
            email: 'anna.reyes@example.com',
            photo: null,
            contact: '0917-777-8888',
            address: 'Pasonanca, Zamboanga City',
            civil_status: 'Widowed',
            birthdate: '1965-08-30'
        },
        status: 'Pending',
        documents: [],
        inspection: [],
        receipt: null
    },
    {
        id: 5,
        reference_no: 'APP-2024-005',
        type: 'Renewal',
        date_submitted: 'Oct 15, 2024',
        applicant: {
            first_name: 'Carlos',
            last_name: 'Garcia',
            email: 'carlos.g@example.com',
            photo: null,
            contact: '0999-111-2222',
            address: 'Sta. Maria, Zamboanga City',
            civil_status: 'Married',
            birthdate: '1980-02-14'
        },
        status: 'Returned',
        documents: [],
        inspection: [],
        receipt: null
    },
    // --- APPROVED INSTANCES ---
    {
        id: 6,
        reference_no: 'APP-2024-006',
        type: 'Franchise Owner Account',
        date_submitted: 'Oct 10, 2024',
        applicant: {
            first_name: 'Elena',
            last_name: 'Torres',
            email: 'elena.t@example.com',
            photo: null,
            contact: '0922-333-4444',
            address: 'Tumaga, Zamboanga City',
            civil_status: 'Single',
            birthdate: '1995-12-05'
        },
        status: 'Approved', 
        documents: [],
        inspection: [],
        receipt: null 
    },
    {
        id: 7,
        reference_no: 'APP-2024-007',
        type: 'Change of Unit',
        date_submitted: 'Oct 05, 2024',
        applicant: {
            first_name: 'Roberto',
            last_name: 'Gomez',
            email: 'robert.g@example.com',
            photo: null,
            contact: '0915-888-9999',
            address: 'Baliwasan, Zamboanga City',
            civil_status: 'Widower',
            birthdate: '1960-09-21'
        },
        status: 'Approved',
        documents: [],
        inspection: [],
        receipt: null
    },
    {
        id: 8,
        reference_no: 'APP-2024-008',
        type: 'Change of Owner',
        date_submitted: 'Oct 01, 2024',
        applicant: {
            first_name: 'Mario',
            last_name: 'Bros',
            email: 'mario.b@example.com',
            photo: null,
            contact: '0911-222-3333',
            address: 'Calarian, Zamboanga City',
            civil_status: 'Single',
            birthdate: '1988-01-01'
        },
        status: 'Approved',
        documents: [],
        inspection: [],
        receipt: null
    }
]);

// --- COMPUTED PROPERTIES ---

const application = computed(() => {
    const found = dummyApplications.value.find(app => String(app.id) === String(props.id));
    const app = found || dummyApplications.value[0]; 
    if (!app.inspection) app.inspection = [];
    if (!app.documents) app.documents = [];
    return app;
});

const showInspectionTab = computed(() => {
    return ['Renewal', 'Change of Unit'].includes(application.value.type);
});

const showReceiptTab = computed(() => {
    return ['Renewal', 'Change of Unit', 'Change of Owner'].includes(application.value.type);
});

// --- ACTIONS ---

const openInspectionModal = (index, itemData) => {
    selectedItemIndex.value = index;
    inspectionForm.item = itemData.item;
    inspectionForm.status = itemData.status;
    inspectionForm.remarks = itemData.remarks;
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    selectedItemIndex.value = null;
    inspectionForm.item = '';
    inspectionForm.status = '';
    inspectionForm.remarks = '';
};

const saveInspection = () => {
    closeInspectionModal();
};

const selectStatus = (status) => {
    inspectionForm.status = status;
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

                    <template v-else>
                        <button v-if="application.type !== 'Franchise Owner Account'" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            Return
                        </button>
                        <button class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Reject
                        </button>
                        <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Approve
                        </button>
                    </template>

                </div>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-4 gap-4">
                
                <div class="lg:col-span-1 h-full min-h-0">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 h-full flex flex-col">
                        <h2 class="text-base font-bold text-gray-900 mb-4 flex-none">Applicant Information</h2>
                        
                        <div class="flex-1 overflow-y-auto pr-1 custom-scrollbar">
                            <div class="flex flex-col items-center mb-5">
                                <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-xl font-bold text-gray-500 mb-3 overflow-hidden border-2 border-white shadow-md flex-none">
                                    <img v-if="application.applicant.photo" :src="application.applicant.photo" class="h-full w-full object-cover" />
                                    <span v-else>{{ application.applicant.first_name.charAt(0) }}</span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 text-center leading-tight break-words w-full">{{ application.applicant.first_name }} {{ application.applicant.last_name }}</h3>
                                <p class="text-sm text-gray-500 text-center break-all">{{ application.applicant.email }}</p>
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Contact No.</span>
                                    <span class="font-medium text-gray-900">{{ application.applicant.contact }}</span>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Civil Status</span>
                                    <span class="font-medium text-gray-900">{{ application.applicant.civil_status }}</span>
                                </div>
                                <div class="flex justify-between border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Birthdate</span>
                                    <span class="font-medium text-gray-900">{{ application.applicant.birthdate }}</span>
                                </div>
                                <div class="pt-1">
                                    <span class="text-gray-500 block mb-1">Address</span>
                                    <span class="font-medium text-gray-900 leading-tight">{{ application.applicant.address }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3 h-full min-h-0 flex flex-col gap-4">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex-none">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-center">
                            <div class="border-r border-gray-100 last:border-0 md:pr-4">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Reference</span>
                                <span class="font-mono text-sm font-bold text-gray-800">{{ application.reference_no }}</span>
                            </div>
                            <div class="border-r border-gray-100 last:border-0 md:px-4">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Type</span>
                                <span class="text-sm font-bold text-blue-600 truncate block" :title="application.type">{{ application.type }}</span>
                            </div>
                            <div class="border-r border-gray-100 last:border-0 md:px-4">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Submitted</span>
                                <span class="text-sm font-medium text-gray-700">{{ application.date_submitted }}</span>
                            </div>
                            <div class="md:pl-4">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">Status</span>
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                    :class="{
                                        'bg-green-50 text-green-700 border-green-200': application.status === 'Approved',
                                        'bg-yellow-50 text-yellow-700 border-yellow-200': application.status === 'Pending',
                                        'bg-red-50 text-red-700 border-red-200': application.status === 'Rejected',
                                        'bg-amber-100 text-amber-800 border-amber-200': application.status === 'Returned',
                                    }"
                                >
                                    {{ application.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col flex-1 min-h-0 overflow-hidden">
                        
                        <div class="flex-none border-b border-gray-200 flex">
                            <button 
                                @click="activeTab = 'evaluation'"
                                class="px-6 py-3 text-sm font-bold border-b-2 transition-colors duration-150 focus:outline-none"
                                :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                            >
                                Evaluation
                                <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ application.documents.length }}</span>
                            </button>
                            
                            <button 
                                v-if="showInspectionTab"
                                @click="activeTab = 'inspection'"
                                class="px-6 py-3 text-sm font-bold border-b-2 transition-colors duration-150 focus:outline-none"
                                :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                            >
                                Inspection
                                <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ application.inspection.length }}</span>
                            </button>

                            <button 
                                v-if="showReceiptTab"
                                @click="activeTab = 'receipt'"
                                class="px-6 py-3 text-sm font-bold border-b-2 transition-colors duration-150 focus:outline-none"
                                :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                            >
                                Official Receipt
                            </button>
                        </div>
                        
                        <div v-show="activeTab === 'evaluation'" class="flex-1 overflow-y-auto p-0 custom-scrollbar">
                             <div class="p-8 text-center text-gray-400 italic">Documents View</div>
                        </div>

                        <div v-show="activeTab === 'inspection' && showInspectionTab" class="flex-1 overflow-y-auto p-0 custom-scrollbar">
                             <div class="p-8 text-center text-gray-400 italic">Inspection View</div>
                        </div>

                        <div v-if="activeTab === 'receipt' && showReceiptTab" class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-100 flex items-center justify-center">
                             <div class="p-8 text-center text-gray-400 italic">Receipt View</div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showInspectionModal" @close="closeInspectionModal" maxWidth="md">
            <div class="p-6"><h2 class="text-lg font-bold">Inspection Update</h2></div>
        </Modal>

        <CreateFranchiseAccountModal 
            :show="showCreateAccountModal" 
            :application="application"
            @close="showCreateAccountModal = false"
        />

        <ChangeOfUnitModal 
            :show="showChangeUnitModal" 
            :application="application"
            @close="showChangeUnitModal = false"
        />

        <ChangeOfOwnerModal 
            :show="showChangeOwnerModal" 
            :application="application"
            @close="showChangeOwnerModal = false"
        />

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>