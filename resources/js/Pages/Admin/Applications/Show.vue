<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

// Import Components
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
    remarks: '',
    options: [] // Dynamic dropdown options
});

// --- DUMMY DATA ---
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
        evaluation_requirements: [
            { id: 1, name: 'Barangay Clearance', status: 'Approved', file_url: '#' },
            { id: 2, name: 'Police Clearance', status: 'Approved', file_url: '#' },
            { id: 3, name: 'Valid ID (Govt Issued)', status: 'Pending', file_url: '#' },
            { id: 4, name: 'Cedula', status: 'Pending', file_url: '#' },
        ],
        inspection_requirements: [],
        receipt: {
            or_number: '7894561',
            date: 'Oct 26, 2024',
            payee: { first_name: 'Juan', last_name: 'Dela Cruz', address: 'San Jose, Zamboanga City' },
            particulars: [
                { name: 'Filing Fee', amount: 500.00 },
                { name: 'Legal Research Fund', amount: 50.00 },
                { name: 'Documentary Stamp', amount: 30.00 }
            ],
            total_amount_due: 580.00,
            payment: { amount_paid: 580.00, change: 0.00, method: 'Cash' }
        }
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
        evaluation_requirements: [
            { id: 1, name: 'Previous Franchise Permit', status: 'Approved', file_url: '#' },
            { id: 2, name: 'Emission Test Result', status: 'Approved', file_url: '#' },
            { id: 3, name: 'Vehicle OR/CR', status: 'Approved', file_url: '#' },
            { id: 4, name: 'Insurance Policy', status: 'Approved', file_url: '#' },
        ],
        inspection_requirements: [
            { id: 1, name: 'Headlights (Low/High)', options: 'Working, Defective', status: 'Working', remarks: 'OK' },
            { id: 2, name: 'Signal Lights', options: 'Working, Defective', status: 'Working', remarks: 'OK' },
            { id: 3, name: 'Brakes', options: 'Excellent, Good, Needs Repair', status: 'Good', remarks: 'OK' },
        ],
        receipt: {
            or_number: '7894562',
            date: 'Oct 24, 2024',
            payee: { first_name: 'Maria', last_name: 'Santos', address: 'Tetuan, Zamboanga City' },
            particulars: [
                { name: 'Franchise Fee (Renewal)', amount: 1500.00 },
                { name: 'Mayor\'s Permit', amount: 1200.00 },
                { name: 'Garbage Fee', amount: 200.00 },
                { name: 'Sticker', amount: 50.00 }
            ],
            total_amount_due: 2950.00,
            payment: { amount_paid: 3000.00, change: 50.00, method: 'Cash' }
        }
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
        evaluation_requirements: [],
        inspection_requirements: [],
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
        evaluation_requirements: [],
        inspection_requirements: [],
        receipt: {
            or_number: '7894564',
            date: 'Oct 19, 2024',
            payee: { first_name: 'Anna', last_name: 'Reyes', address: 'Pasonanca, Zamboanga City' },
            particulars: [
                { name: 'Change Unit Fee', amount: 500.00 },
                { name: 'Processing Fee', amount: 100.00 },
            ],
            total_amount_due: 600.00,
            payment: { amount_paid: 600.00, change: 0.00, method: 'Cash' }
        }
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
        evaluation_requirements: [],
        inspection_requirements: [],
        receipt: null
    },
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
        evaluation_requirements: [
            { id: 1, name: 'Barangay Clearance', status: 'Approved', file_url: '#' },
            { id: 2, name: 'Police Clearance', status: 'Approved', file_url: '#' },
            { id: 3, name: 'Valid ID', status: 'Approved', file_url: '#' },
        ],
        inspection_requirements: [],
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
        evaluation_requirements: [],
        inspection_requirements: [],
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
        evaluation_requirements: [],
        inspection_requirements: [],
        receipt: null
    }
]);

// --- COMPUTED PROPERTIES ---

const application = computed(() => {
    const found = dummyApplications.value.find(app => String(app.id) === String(props.id));
    const app = found || dummyApplications.value[0]; 
    if (!app.inspection_requirements) app.inspection_requirements = [];
    if (!app.evaluation_requirements) app.evaluation_requirements = [];
    return app;
});

const showInspectionTab = computed(() => {
    return ['Renewal', 'Change of Unit'].includes(application.value.type);
});

const showReceiptTab = computed(() => {
    return ['Renewal', 'Change of Unit', 'Change of Owner', 'Franchise Owner Account'].includes(application.value.type);
});

// --- ACTIONS ---

// Evaluation Logic
const updateEvaluationStatus = (index, status) => {
    const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
    if (appIndex !== -1) {
        dummyApplications.value[appIndex].evaluation_requirements[index].status = status;
    }
};

// Inspection Logic
const openInspectionModal = (index, itemData) => {
    selectedItemIndex.value = index;
    inspectionForm.item = itemData.name;
    inspectionForm.status = itemData.status === 'Pending' ? '' : itemData.status;
    inspectionForm.remarks = itemData.remarks;
    
    // Parse dynamic options
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
        const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
        if (appIndex !== -1) {
             dummyApplications.value[appIndex].inspection_requirements[selectedItemIndex.value].status = inspectionForm.status;
             dummyApplications.value[appIndex].inspection_requirements[selectedItemIndex.value].remarks = inspectionForm.remarks;
        }
    }
    closeInspectionModal();
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
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ application.applicant.first_name }} {{ application.applicant.last_name }}</h2>
                            <p class="text-sm text-gray-500">{{ application.applicant.email }}</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Contact No.</p>
                                <p class="text-sm font-medium text-gray-800">{{ application.applicant.contact }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Address</p>
                                <p class="text-sm font-medium text-gray-800 leading-snug">{{ application.applicant.address }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Civil Status</p>
                                    <p class="text-sm font-medium text-gray-800">{{ application.applicant.civil_status }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Birthdate</p>
                                    <p class="text-sm font-medium text-gray-800">{{ application.applicant.birthdate }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Date Submitted</p>
                                <p class="text-sm font-medium text-gray-800">{{ application.date_submitted }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex-none bg-white border-b border-gray-100 flex px-2 pt-2">
                        <button 
                            @click="activeTab = 'evaluation'" 
                            class="px-5 py-2.5 text-sm font-bold border-b-2 transition-colors duration-150"
                            :class="activeTab === 'evaluation' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            Evaluation
                        </button>
                        
                        <button 
                            v-if="showInspectionTab"
                            @click="activeTab = 'inspection'" 
                            class="px-5 py-2.5 text-sm font-bold border-b-2 transition-colors duration-150"
                            :class="activeTab === 'inspection' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            Inspection
                        </button>

                        <button 
                            v-if="showReceiptTab"
                            @click="activeTab = 'receipt'" 
                            class="px-5 py-2.5 text-sm font-bold border-b-2 transition-colors duration-150"
                            :class="activeTab === 'receipt' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        >
                            Official Receipt
                        </button>
                    </div>

                    <div class="flex-1 overflow-hidden flex flex-col relative">
                        
                        <div v-if="activeTab === 'evaluation'" class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                            <h3 class="text-sm font-bold text-gray-800 mb-4">Required Documents Verification</h3>
                            <div class="space-y-3">
                                <div v-for="(doc, index) in application.evaluation_requirements" :key="index" class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:border-blue-200 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                            :class="doc.status === 'Approved' ? 'bg-green-100 text-green-600' : (doc.status === 'Rejected' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600')">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">{{ doc.name }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded"
                                                    :class="doc.status === 'Approved' ? 'bg-green-100 text-green-700' : (doc.status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')">
                                                    {{ doc.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">View File</button>
                                        
                                        <div class="flex border border-gray-200 rounded-md overflow-hidden bg-white ml-2">
                                            <button @click="updateEvaluationStatus(index, 'Approved')" class="px-3 py-1.5 hover:bg-green-50 text-gray-400 hover:text-green-600 transition-colors" :class="{'bg-green-50 text-green-600': doc.status === 'Approved'}" title="Approve">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            </button>
                                            <div class="w-px bg-gray-200"></div>
                                            <button @click="updateEvaluationStatus(index, 'Rejected')" class="px-3 py-1.5 hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors" :class="{'bg-red-50 text-red-600': doc.status === 'Rejected'}" title="Reject">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="application.evaluation_requirements.length === 0" class="text-center py-6 text-sm text-gray-400 italic">
                                    No documents required for this application type.
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'inspection' && showInspectionTab" class="flex-1 overflow-y-auto p-6 custom-scrollbar">
                            <h3 class="text-sm font-bold text-gray-800 mb-4">Physical Inspection Checklist</h3>
                            <div class="space-y-3">
                                <div v-for="(item, index) in application.inspection_requirements" :key="index" class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:border-blue-200 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                            :class="item.status === 'Pending' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600'">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-800">{{ item.name }}</p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded"
                                                    :class="item.status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'">
                                                    {{ item.status }}
                                                </span>
                                                <span v-if="item.remarks" class="text-[11px] text-gray-500 italic max-w-xs truncate" :title="item.remarks">
                                                    - {{ item.remarks }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <button @click="openInspectionModal(index, item)" class="px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors">
                                            Update Rating
                                        </button>
                                    </div>
                                </div>
                                <div v-if="application.inspection_requirements.length === 0" class="text-center py-6 text-sm text-gray-400 italic">
                                    No inspection required for this application type.
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'receipt' && showReceiptTab" class="flex-1 overflow-y-auto p-4 md:p-8 custom-scrollbar bg-gray-100 flex items-start justify-center">
                            
                            <div v-if="application.receipt" class="w-full max-w-4xl bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row overflow-hidden">
                                
                                <div class="md:w-1/3 bg-gray-50 p-6 md:p-8 border-b md:border-b-0 md:border-r border-gray-200 flex flex-col justify-between">
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900 uppercase tracking-widest">Official Receipt</h2>
                                        <p class="text-xs text-gray-500 mt-1">Republic of the Philippines<br>City of Zamboanga</p>

                                        <div class="mt-8 space-y-4">
                                            <div>
                                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">O.R. No.</p>
                                                <p class="font-mono font-bold text-red-600 text-xl">{{ application.receipt.or_number }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Date</p>
                                                <p class="font-medium text-gray-800">{{ application.receipt.date }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-8">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Received from</p>
                                            <p class="font-bold text-gray-900">{{ application.receipt.payee.first_name }} {{ application.receipt.payee.last_name }}</p>
                                            <p class="text-sm text-gray-600 leading-snug mt-1">{{ application.receipt.payee.address }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-8 pt-6 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-xs text-gray-500 font-bold uppercase tracking-wider">Paid via</span>
                                            <span class="font-medium text-gray-900 text-sm">{{ application.receipt.payment.method }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:w-2/3 p-6 md:p-8 flex flex-col">
                                    <div class="flex-1">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b-2 border-gray-800">
                                                    <th class="text-left py-3 text-gray-600 font-bold uppercase tracking-wider text-xs">Nature of Collection</th>
                                                    <th class="text-right py-3 text-gray-600 font-bold uppercase tracking-wider text-xs">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(item, i) in application.receipt.particulars" :key="i" class="border-b border-gray-100">
                                                    <td class="py-3 text-gray-800">{{ item.name }}</td>
                                                    <td class="py-3 text-right font-mono text-gray-900">{{ formatCurrency(item.amount) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-8 pt-4 flex justify-end">
                                        <div class="w-full sm:w-2/3 md:w-1/2 space-y-2">
                                            <div class="flex justify-between items-end border-b-2 border-gray-800 pb-2 mb-3">
                                                <span class="font-bold text-gray-900 uppercase tracking-wider text-sm">Total Due:</span>
                                                <span class="font-mono font-bold text-xl text-gray-900">{{ formatCurrency(application.receipt.total_amount_due) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Amount Paid:</span>
                                                <span class="font-mono font-medium text-gray-900">{{ formatCurrency(application.receipt.payment.amount_paid) }}</span>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Change:</span>
                                                <span class="font-mono font-medium text-gray-900">{{ formatCurrency(application.receipt.payment.change) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div v-else class="flex flex-col items-center justify-center text-gray-400 h-full w-full">
                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>No receipt issued for this application yet.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showInspectionModal" @close="closeInspectionModal" maxWidth="md">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-900">Update Inspection Rating</h2>
                    <button @click="closeInspectionModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 p-3 rounded-md border border-gray-200 mb-2">
                        <span class="text-xs text-gray-500 uppercase font-bold block mb-1">Requirement</span>
                        <span class="text-sm font-medium text-gray-900">{{ inspectionForm.item }}</span>
                    </div>

                    <div>
                        <InputLabel for="status" value="Rating / Status" />
                        <select id="status" v-model="inspectionForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                            <option value="" disabled>Select a rating...</option>
                            <option v-for="opt in inspectionForm.options" :key="opt" :value="opt">
                                {{ opt }}
                            </option>
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