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
const showFranchiseModal = ref(false); 
const showRequirementModal = ref(false); // NEW: Requirement Modal State

const selectedItemIndex = ref(null);
const selectedFranchise = ref(null);
const selectedRequirementIndex = ref(null); // NEW: Selected Req Index

// Modal States
const showCreateAccountModal = ref(false);
const showChangeUnitModal = ref(false);
const showChangeOwnerModal = ref(false);
const showReturnModal = ref(false); 

const inspectionForm = reactive({
    item: '',
    status: '',
    remarks: '',
    options: [] 
});

const returnForm = reactive({
    remarks: ''
});

// NEW: Form for Requirement Review
const requirementForm = reactive({
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
            first_name: 'Juan', middle_name: 'Perez', last_name: 'Dela Cruz', email: 'juan.delacruz@example.com', photo: null,
            contact: '0917-123-4567', address: 'San Jose, Zamboanga City', civil_status: 'Married', birthdate: '1985-05-20',
            tin_number: '111-222-333-000'
        },
        status: 'Pending',
        evaluation_requirements: [
            { id: 1, name: 'Barangay Clearance', status: 'Approved', file_url: '#', remarks: '' },
            { id: 2, name: 'Police Clearance', status: 'Approved', file_url: '#', remarks: '' },
            { id: 3, name: 'Valid ID (Govt Issued)', status: 'Pending', file_url: '#', remarks: '' },
            { id: 4, name: 'Cedula', status: 'Pending', file_url: '#', remarks: '' },
        ],
        inspection_requirements: [],
        franchises: [
            {
                id: 101, zone_id: 1, zone_name: 'Zone 1 (Poblacion)', date_issued: '2024-10-01',
                make_id: 1, make_name: 'Honda', model_year: '2024', plate_number: 'ABC-123', cr_number: 'CR-NEW-001',
                motor_number: 'M-NEW-001', chassis_number: 'C-NEW-001',
                unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null
            }
        ],
        complaints: [],
        receipt: {
            or_number: '7894561', date: 'Oct 26, 2024', payee: { first_name: 'Juan', last_name: 'Dela Cruz', address: 'San Jose, Zamboanga City' },
            particulars: [ { name: 'Filing Fee', amount: 500.00 }, { name: 'Legal Research Fund', amount: 50.00 }, { name: 'Documentary Stamp', amount: 30.00 } ],
            total_amount_due: 580.00, payment: { amount_paid: 580.00, change: 0.00, method: 'Cash' }
        }
    },
    {
        id: 2,
        reference_no: 'APP-2024-002',
        type: 'Renewal',
        date_submitted: 'Oct 23, 2024',
        applicant: {
            first_name: 'Maria', middle_name: 'Cruz', last_name: 'Santos', email: 'maria.santos@example.com', photo: null,
            contact: '0918-987-6543', address: 'Tetuan, Zamboanga City', civil_status: 'Single', birthdate: '1990-11-15'
        },
        status: 'Approved',
        evaluation_requirements: [
            { id: 1, name: 'Previous Franchise Permit', status: 'Approved', file_url: '#', remarks: '' },
            { id: 2, name: 'Emission Test Result', status: 'Approved', file_url: '#', remarks: '' },
            { id: 3, name: 'Vehicle OR/CR', status: 'Approved', file_url: '#', remarks: '' },
            { id: 4, name: 'Insurance Policy', status: 'Approved', file_url: '#', remarks: '' },
        ],
        inspection_requirements: [
            { id: 1, name: 'Headlights (Low/High)', options: 'Working, Defective', status: 'Working', remarks: 'OK' },
            { id: 2, name: 'Signal Lights', options: 'Working, Defective', status: 'Working', remarks: 'OK' },
            { id: 3, name: 'Brakes', options: 'Excellent, Good, Needs Repair', status: 'Good', remarks: 'OK' },
            { id: 4, name: 'Trash Receptacle', options: 'Present, Missing', status: 'Present', remarks: 'OK' }
        ],
        franchises: [],
        complaints: [
            {
                id: 101,
                nature_of_complaint: 'Overcharging',
                remarks: 'Driver charged P50 for a P20 route.',
                status: 'Pending',
                fare_collected: 50.00,
                pick_up_point: 'KCC Mall',
                drop_off_point: 'Guiwan',
                complainant_contact: '0999-888-7777',
                incident_date: '2024-10-10',
                incident_time: '14:30'
            },
            {
                id: 102,
                nature_of_complaint: 'Refusal to Convey',
                remarks: 'Driver refused to take passenger to destination due to rain.',
                status: 'Resolved',
                fare_collected: 0.00,
                pick_up_point: 'City Hall',
                drop_off_point: 'Pasonanca',
                complainant_contact: '0922-333-1111',
                incident_date: '2024-09-15',
                incident_time: '18:00'
            }
        ],
        receipt: {
            or_number: '7894562', date: 'Oct 24, 2024', payee: { first_name: 'Maria', last_name: 'Santos', address: 'Tetuan, Zamboanga City' },
            particulars: [ { name: 'Franchise Fee (Renewal)', amount: 1500.00 }, { name: 'Mayor\'s Permit', amount: 1200.00 }, { name: 'Garbage Fee', amount: 200.00 }, { name: 'Sticker', amount: 50.00 } ],
            total_amount_due: 2950.00, payment: { amount_paid: 3000.00, change: 50.00, method: 'Cash' }
        }
    },
    {
        id: 3,
        reference_no: 'APP-2024-003', type: 'Change of Owner', date_submitted: 'Oct 20, 2024',
        applicant: { 
            first_name: 'Pedro', middle_name: 'A.', last_name: 'Penduko', email: 'pedro.p@example.com', photo: null, 
            contact: '0920-555-4444', address: 'Putik, Zamboanga City', civil_status: 'Married', birthdate: '1978-03-10' 
        },
        status: 'Rejected', 
        evaluation_requirements: [
             { id: 1, name: 'Deed of Sale', status: 'Rejected', file_url: '#', remarks: 'Notarization expired' },
             { id: 2, name: 'Valid ID (New Owner)', status: 'Approved', file_url: '#', remarks: '' },
             { id: 3, name: 'Transfer Fee Receipt', status: 'Approved', file_url: '#', remarks: '' }
        ], 
        inspection_requirements: [], 
        franchises: [],
        complaints: [], 
        receipt: null
    },
    {
        id: 4,
        reference_no: 'APP-2024-004', type: 'Change of Unit', date_submitted: 'Oct 18, 2024',
        applicant: { 
            first_name: 'Anna', middle_name: 'B.', last_name: 'Reyes', email: 'anna.reyes@example.com', photo: null, 
            contact: '0917-777-8888', address: 'Pasonanca, Zamboanga City', civil_status: 'Widowed', birthdate: '1965-08-30' 
        },
        status: 'Pending', 
        evaluation_requirements: [
            { id: 1, name: 'OR/CR of New Unit', status: 'Approved', file_url: '#', remarks: '' },
            { id: 2, name: 'Surrender of Old Plate', status: 'Pending', file_url: '#', remarks: 'Waiting for physical turn-over' }
        ], 
        inspection_requirements: [
            { id: 1, name: 'Headlights (Low/High)', options: 'Working, Defective', status: 'Working', remarks: 'Brand new unit' },
            { id: 2, name: 'Signal Lights', options: 'Working, Defective', status: 'Working', remarks: '' },
            { id: 3, name: 'Brakes', options: 'Excellent, Good, Needs Repair', status: 'Excellent', remarks: '' },
             { id: 4, name: 'Sidecar Integrity', options: 'Pass, Fail', status: 'Pass', remarks: '' }
        ], 
        franchises: [],
        complaints: [],
        receipt: {
            or_number: '7894564', date: 'Oct 19, 2024', payee: { first_name: 'Anna', last_name: 'Reyes', address: 'Pasonanca, Zamboanga City' },
            particulars: [ { name: 'Change Unit Fee', amount: 500.00 }, { name: 'Processing Fee', amount: 100.00 } ],
            total_amount_due: 600.00, payment: { amount_paid: 600.00, change: 0.00, method: 'Cash' }
        }
    },
    {
        id: 5,
        reference_no: 'APP-2024-005', type: 'Renewal', date_submitted: 'Oct 15, 2024',
        applicant: { 
            first_name: 'Carlos', middle_name: 'D.', last_name: 'Garcia', email: 'carlos.g@example.com', photo: null, 
            contact: '0999-111-2222', address: 'Sta. Maria, Zamboanga City', civil_status: 'Married', birthdate: '1980-02-14' 
        },
        status: 'Returned', 
        evaluation_requirements: [
             { id: 1, name: 'Previous Franchise Permit', status: 'Approved', file_url: '#', remarks: '' },
            { id: 2, name: 'Emission Test Result', status: 'Rejected', file_url: '#', remarks: 'Image too blurry, please re-upload' },
            { id: 3, name: 'Vehicle OR/CR', status: 'Approved', file_url: '#', remarks: '' },
        ], 
        inspection_requirements: [
             { id: 1, name: 'Headlights (Low/High)', options: 'Working, Defective', status: 'Working', remarks: '' },
            { id: 2, name: 'Brakes', options: 'Excellent, Good, Needs Repair', status: 'Needs Repair', remarks: 'Brake pads worn out' },
        ], 
        franchises: [],
        complaints: [
            {
                id: 103,
                nature_of_complaint: 'Reckless Driving',
                remarks: 'Swerving and overspeeding in school zone.',
                status: 'Pending',
                fare_collected: 0.00,
                pick_up_point: 'Sta. Maria',
                drop_off_point: 'Pasonanca',
                complainant_contact: '0915-123-9876',
                incident_date: '2024-10-12',
                incident_time: '07:30'
            }
        ],
        receipt: null
    },
    {
        id: 6,
        reference_no: 'APP-2024-006', type: 'Franchise Owner Account', date_submitted: 'Oct 10, 2024',
        applicant: { 
            first_name: 'Elena', 
            middle_name: 'Marie', 
            last_name: 'Torres', 
            email: 'elena.t@example.com', 
            photo: null, 
            contact: '0922-333-4444', 
            tin_number: '123-456-789-000', 
            address: 'Tumaga, Zamboanga City',
            civil_status: 'Single', 
            birthdate: '1995-12-05' 
        },
        franchises: [
            {
                id: 201, zone_id: 1, zone_name: 'Zone 1 (Poblacion)', date_issued: '2023-05-10',
                make_id: 1, make_name: 'Honda', model_year: '2023', plate_number: 'ZC-8888', cr_number: 'CR-12345',
                motor_number: 'M12345', chassis_number: 'C12345',
                unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null
            },
             {
                id: 202, zone_id: 2, zone_name: 'Zone 2 (East Coast)', date_issued: '2024-01-15',
                make_id: 2, make_name: 'Kawasaki', model_year: '2024', plate_number: 'ZC-9999', cr_number: 'CR-67890',
                motor_number: 'M67890', chassis_number: 'C67890',
                unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null
            }
        ],
        status: 'Approved', 
        evaluation_requirements: [
            { id: 1, name: 'Barangay Clearance', status: 'Approved', file_url: '#', remarks: '' }, 
            { id: 2, name: 'Police Clearance', status: 'Approved', file_url: '#', remarks: '' }, 
            { id: 3, name: 'Valid ID', status: 'Approved', file_url: '#', remarks: '' },
             { id: 4, name: 'TIN ID', status: 'Approved', file_url: '#', remarks: '' },
        ],
        inspection_requirements: [], complaints: [], receipt: null
    },
    {
        id: 7,
        reference_no: 'APP-2024-007', type: 'Change of Unit', date_submitted: 'Oct 05, 2024',
        applicant: { 
            first_name: 'Roberto', middle_name: 'S.', last_name: 'Gomez', email: 'robert.g@example.com', photo: null, 
            contact: '0915-888-9999', address: 'Baliwasan, Zamboanga City', civil_status: 'Widower', birthdate: '1960-09-21' 
        },
        status: 'Approved', 
        evaluation_requirements: [
             { id: 1, name: 'OR/CR of New Unit', status: 'Approved', file_url: '#', remarks: '' },
             { id: 2, name: 'Surrender of Old Plate', status: 'Approved', file_url: '#', remarks: '' }
        ], 
        inspection_requirements: [
            { id: 1, name: 'Headlights (Low/High)', options: 'Working, Defective', status: 'Working', remarks: '' },
            { id: 2, name: 'Signal Lights', options: 'Working, Defective', status: 'Working', remarks: '' },
             { id: 3, name: 'Brakes', options: 'Excellent, Good, Needs Repair', status: 'Good', remarks: '' },
        ], 
        franchises: [],
        complaints: [], 
        receipt: {
            or_number: '7894567', date: 'Oct 06, 2024', payee: { first_name: 'Roberto', last_name: 'Gomez', address: 'Baliwasan, Zamboanga City' },
            particulars: [ { name: 'Change Unit Fee', amount: 500.00 }, { name: 'Processing Fee', amount: 100.00 } ],
            total_amount_due: 600.00, payment: { amount_paid: 1000.00, change: 400.00, method: 'Cash' }
        }
    },
    {
        id: 8,
        reference_no: 'APP-2024-008', type: 'Change of Owner', date_submitted: 'Oct 01, 2024',
        applicant: { 
            first_name: 'Mario', middle_name: 'Luigi', last_name: 'Bros', email: 'mario.b@example.com', photo: null, 
            contact: '0911-222-3333', address: 'Calarian, Zamboanga City', civil_status: 'Single', birthdate: '1988-01-01' 
        },
        status: 'Approved', 
        evaluation_requirements: [
             { id: 1, name: 'Deed of Sale', status: 'Approved', file_url: '#', remarks: '' },
             { id: 2, name: 'Valid ID (New Owner)', status: 'Approved', file_url: '#', remarks: '' },
             { id: 3, name: 'Transfer Fee Receipt', status: 'Approved', file_url: '#', remarks: '' }
        ], 
        inspection_requirements: [], 
        franchises: [],
        complaints: [], 
        receipt: {
            or_number: '7894568', date: 'Oct 02, 2024', payee: { first_name: 'Mario', last_name: 'Bros', address: 'Calarian, Zamboanga City' },
            particulars: [ { name: 'Transfer Fee', amount: 1000.00 }, { name: 'Processing Fee', amount: 100.00 } ],
            total_amount_due: 1100.00, payment: { amount_paid: 1100.00, change: 0.00, method: 'Cash' }
        }
    }
]);

// --- COMPUTED PROPERTIES ---

const application = computed(() => {
    const found = dummyApplications.value.find(app => String(app.id) === String(props.id));
    const app = found || dummyApplications.value[0]; 
    if (!app.inspection_requirements) app.inspection_requirements = [];
    if (!app.evaluation_requirements) app.evaluation_requirements = [];
    if (!app.complaints) app.complaints = [];
    if (!app.franchises) app.franchises = []; // Ensure franchises array exists
    return app;
});

const showInspectionTab = computed(() => {
    return ['Renewal', 'Change of Unit'].includes(application.value.type);
});

const showReceiptTab = computed(() => {
    return ['Renewal', 'Change of Unit', 'Change of Owner'].includes(application.value.type);
});

const showComplaintsTab = computed(() => {
    return application.value.type === 'Renewal';
});

// NEW: Computed for current requirement Modal
const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

// --- ACTIONS ---

const resolveComplaint = (index) => {
    const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
    if (appIndex !== -1 && confirm('Are you sure you want to mark this complaint as Resolved?')) {
        dummyApplications.value[appIndex].complaints[index].status = 'Resolved';
    }
};

const promptRejectDocument = (index) => {
    const reason = prompt("Please state the reason for rejecting this document (e.g. Blurred, Expired, Incorrect):");
    if (reason !== null) { // if user didn't cancel
        updateEvaluationStatus(index, 'Rejected', reason);
    }
};

const updateEvaluationStatus = (index, status, remarks = '') => {
    const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
    if (appIndex !== -1) {
        dummyApplications.value[appIndex].evaluation_requirements[index].status = status;
        dummyApplications.value[appIndex].evaluation_requirements[index].remarks = remarks; // Save reason
    }
};

// NEW: Requirement Modal Actions
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

const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value !== null) {
        updateEvaluationStatus(selectedRequirementIndex.value, status, status === 'Rejected' ? requirementForm.remarks : '');
        closeRequirementModal();
    }
};

// Inspection Logic
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
        const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
        if (appIndex !== -1) {
             dummyApplications.value[appIndex].inspection_requirements[selectedItemIndex.value].status = inspectionForm.status;
             dummyApplications.value[appIndex].inspection_requirements[selectedItemIndex.value].remarks = inspectionForm.remarks;
        }
    }
    closeInspectionModal();
};

// Franchise Modal Logic
const openFranchiseModal = (franchise) => {
    selectedFranchise.value = franchise;
    showFranchiseModal.value = true;
};

const closeFranchiseModal = () => {
    showFranchiseModal.value = false;
    selectedFranchise.value = null;
};

const confirmReturnApplication = () => {
    const appIndex = dummyApplications.value.findIndex(app => app.id === application.value.id);
    if (appIndex !== -1) {
        dummyApplications.value[appIndex].status = 'Returned';
    }
    showReturnModal.value = false;
    returnForm.remarks = '';
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

        <CreateFranchiseAccountModal :show="showCreateAccountModal" :application="application" @close="showCreateAccountModal = false" />
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