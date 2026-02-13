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
    remarks: ''
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
        documents: [
            { name: 'Barangay Clearance', status: 'Verified' },
            { name: 'Police Clearance', status: 'Verified' },
            { name: 'Valid ID (Govt Issued)', status: 'Pending Review' },
            { name: 'Cedula', status: 'Pending Review' },
        ],
        inspection: [],
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
        documents: [
            { name: 'Previous Franchise Permit', status: 'Verified' },
            { name: 'Emission Test Result', status: 'Verified' },
            { name: 'Vehicle OR/CR', status: 'Verified' },
            { name: 'Insurance Policy', status: 'Verified' },
        ],
        inspection: [
            { item: 'Headlights (Low/High)', status: 'Passed', remarks: 'OK' },
            { item: 'Signal Lights', status: 'Passed', remarks: 'OK' },
            { item: 'Brakes', status: 'Passed', remarks: 'OK' },
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
        documents: [
            { name: 'Barangay Clearance', status: 'Verified' },
            { name: 'Police Clearance', status: 'Verified' },
            { name: 'Valid ID', status: 'Verified' },
        ],
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
    return ['Renewal', 'Change of Unit', 'Change of Owner', 'Franchise Owner Account'].includes(application.value.type);
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
    if (selectedItemIndex.value !== null) {
        const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
        if (appIndex !== -1) {
             dummyApplications.value[appIndex].inspection[selectedItemIndex.value] = {
                item: inspectionForm.item,
                status: inspectionForm.status,
                remarks: inspectionForm.remarks
             };
        }
    }
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
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/2">Document Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(doc, index) in application.documents" :key="index" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" /></svg>
                                                {{ doc.name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            <span 
                                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium border"
                                                :class="{
                                                    'text-green-700 bg-green-50 border-green-100': doc.status === 'Verified',
                                                    'text-yellow-700 bg-yellow-50 border-yellow-100': doc.status === 'Pending Review',
                                                    'text-red-700 bg-red-50 border-red-100': doc.status === 'Invalid',
                                                    'text-amber-700 bg-amber-50 border-amber-100': doc.status === 'Returned for Correction'
                                                }"
                                            >
                                                <span 
                                                    class="w-1.5 h-1.5 rounded-full" 
                                                    :class="{
                                                        'bg-green-500': doc.status === 'Verified',
                                                        'bg-yellow-500': doc.status === 'Pending Review',
                                                        'bg-red-500': doc.status === 'Invalid',
                                                        'bg-amber-500': doc.status === 'Returned for Correction'
                                                    }"
                                                ></span>
                                                {{ doc.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium">
                                            <button class="text-blue-600 hover:text-blue-900 hover:underline">View</button>
                                        </td>
                                    </tr>
                                    <tr v-if="application.documents.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400 text-sm italic">No documents available</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-show="activeTab === 'inspection' && showInspectionTab" class="flex-1 overflow-y-auto p-0 custom-scrollbar">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Item</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in application.inspection" :key="index" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                            {{ item.item }}
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            <button 
                                                @click="openInspectionModal(index, item)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium border cursor-pointer hover:shadow-sm hover:scale-105 transition-all focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500"
                                                :class="{
                                                    'text-green-700 bg-green-50 border-green-100': item.status === 'Passed',
                                                    'text-red-700 bg-red-50 border-red-100': item.status === 'Failed',
                                                    'text-gray-700 bg-gray-50 border-gray-200': item.status === 'Pending'
                                                }"
                                            >
                                                <span 
                                                    class="w-2 h-2 rounded-full" 
                                                    :class="{
                                                        'bg-green-500': item.status === 'Passed',
                                                        'bg-red-500': item.status === 'Failed',
                                                        'bg-gray-400': item.status === 'Pending'
                                                    }"
                                                ></span>
                                                {{ item.status }}
                                                <svg class="w-3 h-3 ml-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-500 truncate max-w-xs">
                                            {{ item.remarks }}
                                        </td>
                                    </tr>
                                    <tr v-if="application.inspection.length === 0">
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-400 text-sm italic">No inspection items found</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="activeTab === 'receipt' && showReceiptTab && application.receipt" class="flex-1 overflow-y-auto p-4 custom-scrollbar bg-gray-100 flex items-center justify-center">
                            
                            <div class="bg-white w-full max-w-4xl shadow-md border border-gray-300 rounded-sm relative flex flex-col md:flex-row min-h-[350px]">
                                <div class="absolute top-0 left-0 w-full h-1.5 bg-blue-600 z-10"></div>
                                
                                <div class="w-full md:w-1/3 bg-gray-50 border-r border-gray-200 p-6 flex flex-col">
                                    <div class="mb-6">
                                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Official Receipt</h3>
                                        <div class="text-2xl font-mono text-red-600 font-bold tracking-tight">#{{ application.receipt.or_number }}</div>
                                        <div class="text-sm text-gray-500 mt-1">{{ application.receipt.date }}</div>
                                    </div>

                                    <div class="mb-auto">
                                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1.5">Received from</div>
                                        <div class="font-bold text-gray-900 text-base leading-tight">{{ application.receipt.payee.first_name }} {{ application.receipt.payee.last_name }}</div>
                                        <div class="text-xs text-gray-500 mt-1 leading-snug">{{ application.receipt.payee.address }}</div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t border-gray-200">
                                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-2">Payment Method</div>
                                        <span class="inline-block px-3 py-1 bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded shadow-sm uppercase">
                                            {{ application.receipt.payment.method }}
                                        </span>
                                    </div>
                                </div>

                                <div class="w-full md:w-2/3 p-6 flex flex-col">
                                    <div class="flex-1">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="text-xs text-gray-400 border-b border-gray-100">
                                                    <th class="text-left font-medium py-2 uppercase tracking-wide">Particulars</th>
                                                    <th class="text-right font-medium py-2 uppercase tracking-wide">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                <tr v-for="(item, i) in application.receipt.particulars" :key="i">
                                                    <td class="py-2.5 text-gray-700">{{ item.name }}</td>
                                                    <td class="py-2.5 text-right text-gray-900 font-medium">{{ formatCurrency(item.amount) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-4 pt-4 border-t-2 border-gray-100">
                                        <div class="flex justify-between items-end">
                                            <span class="text-sm font-medium text-gray-500 uppercase tracking-wide pb-1">Total Amount Due</span>
                                            <span class="text-3xl font-bold text-gray-900 leading-none">{{ formatCurrency(application.receipt.total_amount_due) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div v-else-if="activeTab === 'receipt' && showReceiptTab" class="flex-1 flex items-center justify-center bg-gray-50">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">No Receipt Available</h3>
                                <p class="text-gray-500 text-sm mt-1 max-w-xs mx-auto">No official receipt has been generated for this application yet.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showInspectionModal" @close="closeInspectionModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1">Update Inspection Status</h2>
                <p class="text-sm text-gray-500 mb-6">Evaluating: <span class="font-medium text-gray-800">{{ inspectionForm.item }}</span></p>

                <div class="grid grid-cols-3 gap-3 mb-6">
                    <button type="button" @click="selectStatus('Passed')" class="flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all" :class="inspectionForm.status === 'Passed' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 hover:border-green-200 hover:bg-green-50/50'">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2" :class="inspectionForm.status === 'Passed' ? 'bg-green-200' : 'bg-gray-100'">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <span class="text-xs font-bold">Passed</span>
                    </button>
                    <button type="button" @click="selectStatus('Pending')" class="flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all" :class="inspectionForm.status === 'Pending' ? 'border-gray-500 bg-gray-50 text-gray-700' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/50'">
                         <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2" :class="inspectionForm.status === 'Pending' ? 'bg-gray-300' : 'bg-gray-100'">
                            <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <span class="text-xs font-bold">Pending</span>
                    </button>
                    <button type="button" @click="selectStatus('Failed')" class="flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all" :class="inspectionForm.status === 'Failed' ? 'border-red-500 bg-red-50 text-red-700' : 'border-gray-200 hover:border-red-200 hover:bg-red-50/50'">
                         <div class="w-8 h-8 rounded-full flex items-center justify-center mb-2" :class="inspectionForm.status === 'Failed' ? 'bg-red-200' : 'bg-gray-100'">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </div>
                        <span class="text-xs font-bold">Failed</span>
                    </button>
                </div>

                <div class="mb-6">
                    <InputLabel for="remarks" value="Remarks / Observations" />
                    <TextInput id="remarks" type="text" v-model="inspectionForm.remarks" class="mt-1 block w-full" placeholder="Enter specific details about the finding..." />
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeInspectionModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="saveInspection">Save Update</PrimaryButton>
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