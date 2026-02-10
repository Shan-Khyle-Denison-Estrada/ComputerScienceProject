<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3'; // Added router
import { ref, computed } from 'vue';

const props = defineProps({
    franchise: Object,
    operators: Array,
    units: Array,
    drivers: Array, 
});

// --- STATE ---
const showTransferModal = ref(false);
const showChangeUnitModal = ref(false);
const showAddDriverModal = ref(false);
const showComplaintModal = ref(false);
const showZoneModal = ref(false);
const showUnitDetailsModal = ref(false);
const activeTab = ref('ownership'); 

const selectedImage = ref(null);

// --- FORMS ---
const transferForm = useForm({
    new_operator_id: '',
    date_transferred: new Date().toISOString().split('T')[0],
});

const unitForm = useForm({
    new_unit_id: '',
    date_changed: new Date().toISOString().split('T')[0],
    remarks: ''
});

const driverForm = useForm({
    driver_id: '',
});

const complaintOptions = [
    'Overcharging / Overpricing',
    'Refusal to Convey Passenger',
    'Reckless Driving',
    'Arrogance / Discourtesy',
    'Trip Cutting',
    'Operating without License',
    'Others'
];

const complaintForm = useForm({
    franchise_id: props.franchise.id,
    nature_of_complaint: '',
    remarks: '',
    fare_collected: '',
    pick_up_point: '',
    drop_off_point: '',
    complainant_contact: '',
    incident_date: new Date().toISOString().split('T')[0],
    incident_time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }),
});

// --- ACTIONS ---
const submitTransfer = () => {
    transferForm.post(route('admin.franchises.transfer', props.franchise.id), {
        onSuccess: () => { showTransferModal.value = false; transferForm.reset(); },
        onError: () => { showTransferModal.value = true; }
    });
};

const submitUnitChange = () => {
    unitForm.post(route('admin.franchises.change-unit', props.franchise.id), {
        onSuccess: () => { showChangeUnitModal.value = false; unitForm.reset(); },
        onError: () => { showChangeUnitModal.value = true; }
    });
};

const submitDriverAssignment = () => {
    driverForm.post(route('admin.franchises.assign-driver', props.franchise.id), {
        onSuccess: () => { showAddDriverModal.value = false; driverForm.reset(); }
    });
};

const submitComplaint = () => {
    complaintForm.post(route('admin.franchises.complaints.store', props.franchise.id), {
        onSuccess: () => { showComplaintModal.value = false; complaintForm.reset(); activeTab.value = 'complaints'; },
        onError: () => { showComplaintModal.value = true; }
    });
};

// NEW: Resolve Complaint Action
const resolveComplaint = (complaintId) => {
    if (confirm('Are you sure you want to mark this complaint as resolved?')) {
        router.patch(route('admin.complaints.resolve', complaintId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show toast notification
            }
        });
    }
};

const removeDriver = (assignmentId) => {
    if (confirm('Are you sure you want to remove this driver?')) {
        const form = useForm({});
        form.delete(route('admin.franchises.remove-driver', [props.franchise.id, assignmentId]));
    }
};

const triggerPrint = () => {
    window.print();
};

const openImage = (url) => {
    selectedImage.value = url;
    showUnitDetailsModal.value = false;
};

// --- HELPERS ---
const statusColor = computed(() => {
    switch (props.franchise.status) {
        case 'renewed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'pending renewal': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'terminated': return 'bg-rose-100 text-rose-800 border-rose-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
});

const currentOwner = computed(() => props.franchise.current_ownership?.new_owner);
const currentUnit = computed(() => props.franchise.current_active_unit?.new_unit);
const zoneColor = computed(() => props.franchise.zone?.color || '#374151'); 

const getDriverName = (driver) => {
    if (driver.user) return `${driver.user.last_name}, ${driver.user.first_name}`;
    if (driver.first_name && driver.last_name) return `${driver.last_name}, ${driver.first_name}`;
    return 'Unknown Name';
};
</script>

<template>
    <Head :title="`Franchise FR-${franchise.id}`" />

    <AuthenticatedLayout>
        <div class="screen-content">
            
            <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <nav class="flex mb-2" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-2 text-sm text-gray-500">
                            <li><Link :href="route('admin.franchises.index')" class="hover:text-blue-600 transition">Franchises</Link></li>
                            <li>/</li>
                            <li class="font-medium text-gray-900">Details</li>
                        </ol>
                    </nav>
                    <div class="flex items-center gap-4">
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Franchise #{{ franchise.id }}</h1>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border tracking-wide" :class="statusColor">
                            {{ franchise.status }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Issued on {{ franchise.date_issued }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <SecondaryButton @click="showComplaintModal = true" class="text-rose-600 border-rose-200 hover:bg-rose-50 hover:border-rose-300">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        Make Complaint
                    </SecondaryButton>
                    <SecondaryButton @click="showChangeUnitModal = true">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Change Unit
                    </SecondaryButton>
                    <PrimaryButton @click="showTransferModal = true" class="bg-gray-800 hover:bg-gray-700">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        Transfer Ownership
                    </PrimaryButton>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Operator</h3>
                            <div class="p-1.5 bg-blue-50 text-blue-600 rounded-lg">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                        </div>
                        
                        <div v-if="currentOwner" class="relative z-10">
                            <div class="flex flex-col items-center mb-4 text-center">
                                <div class="h-20 w-20 rounded-full bg-gray-200 mb-3 overflow-hidden border-4 border-white shadow-sm">
                                    <img v-if="currentOwner.user.user_photo" :src="`/storage/${currentOwner.user.user_photo}`" class="h-full w-full object-cover" />
                                    <div v-else class="h-full w-full flex items-center justify-center bg-blue-600 text-white text-2xl font-bold">
                                        {{ currentOwner.user.first_name.charAt(0) }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-gray-900 leading-tight">
                                        {{ currentOwner.user.last_name }}
                                    </div>
                                    <div class="text-sm text-gray-600">{{ currentOwner.user.first_name }}</div>
                                </div>
                            </div>
                            <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                                <div>
                                    <span class="text-xs text-gray-400 block uppercase">TIN</span>
                                    <span class="font-mono font-medium text-gray-700">{{ currentOwner.tin_number }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 block uppercase">Owned Since</span>
                                    <span class="font-medium text-gray-700">{{ franchise.current_ownership?.date_transferred }}</span>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-400 block uppercase">Contact</span>
                                    <span class="font-medium text-gray-700 truncate block" :title="currentOwner.user.email">{{ currentOwner.user.email }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-gray-400 italic py-4 text-center">No Active Owner</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="flex items-center gap-4 mb-4">
                             <div class="bg-gray-50 p-2 rounded border shadow-sm flex-shrink-0">
                                <img v-if="franchise.qr_code" :src="`/storage/qrcodes/${franchise.qr_code}`" class="w-12 h-12" alt="QR Code" />
                                <div v-else class="w-12 h-12 bg-gray-100 flex items-center justify-center text-[8px] text-gray-400">NO QR</div>
                            </div>
                            <div class="leading-tight">
                                <h4 class="font-bold text-gray-900 text-sm">QR Identity</h4>
                                <span class="text-xs text-gray-500">Scan for verification</span>
                            </div>
                        </div>
                        <button @click="triggerPrint" class="w-full flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white text-sm font-bold py-3 rounded-lg transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print QR Badge
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-3 flex flex-col gap-6">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div @click="showZoneModal = true" class="group cursor-pointer bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:border-blue-400 hover:shadow-md transition-all relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-16 h-16 rounded-bl-full opacity-10 transition-opacity group-hover:opacity-20" :style="{ backgroundColor: zoneColor }"></div>
                            
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Zone</span>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            
                            <div class="font-black text-lg text-gray-900 truncate mb-1">
                                {{ franchise.zone ? franchise.zone.description : 'Unassigned' }}
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: zoneColor }"></span>
                                View Coverage Area
                            </div>
                        </div>

                        <div @click="showUnitDetailsModal = true" class="group cursor-pointer bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:border-emerald-400 hover:shadow-md transition-all">
                             <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Unit</span>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </div>

                            <div v-if="currentUnit">
                                <div class="font-mono font-black text-2xl text-gray-800 tracking-wider mb-1">
                                    {{ currentUnit.plate_number }}
                                </div>
                                <div class="text-sm font-medium text-gray-600">
                                    {{ currentUnit.make.name }} {{ currentUnit.model_year }}
                                </div>
                            </div>
                            <div v-else class="text-gray-400 italic text-sm py-2">No Unit Assigned</div>
                        </div>

                        <div @click="activeTab = 'financials'" class="group cursor-pointer bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:border-amber-400 hover:shadow-md transition-all">
                             <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Latest Payment</span>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            
                            <div v-if="franchise.latest_payment">
                                <div class="font-black text-lg text-gray-900 mb-1">
                                    ₱ {{ Number(franchise.latest_payment.amount).toLocaleString() }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    OR# {{ franchise.latest_payment.or_number }} • {{ franchise.latest_payment.date }}
                                </div>
                            </div>
                            <div v-else class="py-1">
                                <div class="text-sm font-medium text-gray-500">No recent payments</div>
                                <div class="text-xs text-blue-500 mt-1 font-bold group-hover:underline">View History &rarr;</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex-grow flex flex-col">
                        <div class="border-b border-gray-200 bg-gray-50/50 px-6">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button 
                                    @click="activeTab = 'ownership'"
                                    :class="[
                                        activeTab === 'ownership' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Ownership History
                                </button>
                                <button 
                                    @click="activeTab = 'drivers'"
                                    :class="[
                                        activeTab === 'drivers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Authorized Drivers
                                </button>
                                <button 
                                    @click="activeTab = 'financials'"
                                    :class="[
                                        activeTab === 'financials' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Assessments & Payments
                                </button>
                                <button 
                                    @click="activeTab = 'complaints'"
                                    :class="[
                                        activeTab === 'complaints' ? 'border-rose-500 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Complaints
                                    <span v-if="franchise.complaints && franchise.complaints.length > 0" class="ml-2 bg-rose-100 text-rose-600 py-0.5 px-2 rounded-full text-xs font-bold">
                                        {{ franchise.complaints.length }}
                                    </span>
                                </button>
                            </nav>
                        </div>

                        <div v-if="activeTab === 'ownership'" class="p-6">
                            <h3 class="font-bold text-gray-700 mb-4">Transfer Log</h3>
                            <div class="overflow-x-auto border border-gray-100 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New Operator</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Previous Operator</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="history in franchise.ownership_history" :key="history.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ history.date_transferred }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ history.new_owner.user.last_name }}, {{ history.new_owner.user.first_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span v-if="history.previous_owner">
                                                    {{ history.previous_owner.user.last_name }}, {{ history.previous_owner.user.first_name }}
                                                </span>
                                                <span v-else class="text-gray-400 italic">-- Original --</span>
                                            </td>
                                        </tr>
                                        <tr v-if="!franchise.ownership_history || franchise.ownership_history.length === 0">
                                            <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500 italic">No transfer history recorded.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-if="activeTab === 'drivers'" class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700">Current Assignments</h3>
                                <button @click="showAddDriverModal = true" class="text-sm font-bold text-blue-600 hover:underline uppercase tracking-wide">
                                    + Assign Driver
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="assignment in franchise.driver_assignments" :key="assignment.id" class="flex items-center justify-between p-4 rounded-lg border border-gray-100 bg-gray-50 hover:bg-white hover:shadow-sm transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                            {{ assignment.driver.first_name ? assignment.driver.first_name.charAt(0) : 'D' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ getDriverName(assignment.driver) }}</div>
                                            <div class="text-xs text-gray-500">License: {{ assignment.driver.license_number || 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <button @click="removeDriver(assignment.id)" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition">
                                        <span class="sr-only">Remove</span>
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <div v-if="!franchise.driver_assignments || franchise.driver_assignments.length === 0" class="col-span-2 text-center py-8 text-gray-400 italic bg-gray-50 rounded-lg">
                                    No drivers currently assigned.
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'financials'" class="p-6">
                            <h3 class="font-bold text-gray-700 mb-4">Assessments & Payments</h3>
                            <div class="space-y-4">
                                <div v-for="assessment in franchise.assessments" :key="assessment.id" class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 p-4 flex justify-between items-center border-b border-gray-100">
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ assessment.description || 'Assessment' }}</div>
                                            <div class="text-xs text-gray-500">Due: {{ assessment.due_date }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-black text-gray-800">₱ {{ Number(assessment.amount).toLocaleString() }}</div>
                                            <span v-if="assessment.is_paid" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold uppercase">Paid</span>
                                            <span v-else class="text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold uppercase">Unpaid</span>
                                        </div>
                                    </div>
                                    <div v-if="assessment.payments && assessment.payments.length > 0" class="bg-white">
                                        <table class="min-w-full divide-y divide-gray-100">
                                            <thead class="bg-white">
                                                <tr>
                                                    <th class="px-6 py-2 text-left text-[10px] uppercase font-bold text-gray-400">OR Number</th>
                                                    <th class="px-6 py-2 text-left text-[10px] uppercase font-bold text-gray-400">Date Paid</th>
                                                    <th class="px-6 py-2 text-right text-[10px] uppercase font-bold text-gray-400">Amount Paid</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                <tr v-for="payment in assessment.payments" :key="payment.id">
                                                    <td class="px-6 py-2 text-sm text-gray-600 font-mono">{{ payment.or_number }}</td>
                                                    <td class="px-6 py-2 text-sm text-gray-600">{{ payment.date_paid }}</td>
                                                    <td class="px-6 py-2 text-sm text-gray-800 text-right font-medium">₱ {{ Number(payment.amount).toLocaleString() }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div v-else class="p-4 text-xs text-gray-400 italic text-center">
                                        No payments recorded for this assessment.
                                    </div>
                                </div>
                                <div v-if="!franchise.assessments || franchise.assessments.length === 0" class="text-center py-8 text-gray-400 italic">
                                    No financial records found.
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'complaints'" class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700">Reported Complaints</h3>
                            </div>
                            
                            <div class="overflow-x-auto border border-gray-100 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nature</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Complainant</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="complaint in franchise.complaints" :key="complaint.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ complaint.incident_date }}
                                                <div class="text-xs text-gray-400">{{ complaint.incident_time }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-bold leading-5 rounded-full bg-rose-100 text-rose-800">
                                                    {{ complaint.nature_of_complaint }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="[
                                                    'px-2 py-1 text-xs font-bold leading-5 rounded-full uppercase',
                                                    complaint.status === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                ]">
                                                    {{ complaint.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ complaint.complainant_contact }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" :title="complaint.remarks">
                                                {{ complaint.remarks || 'No details provided.' }}
                                                <div v-if="complaint.fare_collected" class="text-xs text-gray-400 mt-1">
                                                    Fare: ₱{{ complaint.fare_collected }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button 
                                                    v-if="complaint.status !== 'Resolved'"
                                                    @click="resolveComplaint(complaint.id)" 
                                                    class="text-blue-600 hover:text-blue-900 font-bold hover:underline"
                                                >
                                                    Mark Resolved
                                                </button>
                                                <div v-else class="text-green-600 font-bold flex items-center justify-end gap-1">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    Done
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="!franchise.complaints || franchise.complaints.length === 0">
                                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 italic">
                                                No complaints reported for this franchise.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div id="print-area">
                <div class="w-[300px] bg-white border border-gray-300 p-4 text-center font-sans">
                    <h1 class="text-lg font-bold mb-1">Franchise QR</h1>
                    <div class="mb-4">
                        <img v-if="franchise.qr_code" :src="`/storage/qrcodes/${franchise.qr_code}`" class="w-48 h-48 mx-auto" />
                    </div>
                    <div class="text-2xl font-black mb-1">FR #{{ franchise.id }}</div>
                    <div class="text-sm uppercase mb-4">{{ franchise.zone?.description }}</div>
                    
                    <div class="border-t border-b border-gray-200 py-2 mb-2 text-left text-xs">
                        <div class="flex justify-between mb-1">
                            <span class="font-bold">Operator:</span>
                            <span>{{ currentOwner ? `${currentOwner.user.last_name}, ${currentOwner.user.first_name}` : 'N/A' }}</span>
                        </div>
                         <div class="flex justify-between">
                            <span class="font-bold">Plate #:</span>
                            <span>{{ currentUnit ? currentUnit.plate_number : 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="text-[10px] text-gray-400">Scan to Verify Validity</div>
                </div>
            </div>

        </div>
        
        <Modal :show="showChangeUnitModal" @close="showChangeUnitModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Change Active Unit</h2>
                <form @submit.prevent="submitUnitChange" class="space-y-4">
                    <div>
                        <InputLabel>Select Unit</InputLabel>
                        <select v-model="unitForm.new_unit_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                             <option value="" disabled>Select a unit...</option>
                             <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.plate_number }} - {{ unit.make.name }}
                             </option>
                        </select>
                        <p v-if="unitForm.errors.new_unit_id" class="text-sm text-red-600 mt-1">{{ unitForm.errors.new_unit_id }}</p>
                    </div>
                     <div>
                        <InputLabel>Date Changed</InputLabel>
                        <TextInput v-model="unitForm.date_changed" type="date" class="mt-1 block w-full" required />
                    </div>
                     <div>
                        <InputLabel>Remarks (Optional)</InputLabel>
                        <TextInput v-model="unitForm.remarks" type="text" class="mt-1 block w-full" placeholder="Reason for change..." />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                         <SecondaryButton @click="showChangeUnitModal = false">Cancel</SecondaryButton>
                         <PrimaryButton :disabled="unitForm.processing">Save Changes</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showAddDriverModal" @close="showAddDriverModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Assign Driver</h2>
                 <form @submit.prevent="submitDriverAssignment" class="space-y-4">
                    <div>
                        <InputLabel>Select Driver</InputLabel>
                        <select v-model="driverForm.driver_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                             <option value="" disabled>Select driver...</option>
                             <option v-for="d in drivers" :key="d.id" :value="d.id">
                                {{ d.user.last_name }}, {{ d.user.first_name }} ({{ d.license_number }})
                             </option>
                        </select>
                         <p v-if="driverForm.errors.driver_id" class="text-sm text-red-600 mt-1">{{ driverForm.errors.driver_id }}</p>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                         <SecondaryButton @click="showAddDriverModal = false">Cancel</SecondaryButton>
                         <PrimaryButton :disabled="driverForm.processing">Assign Driver</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="selectedImage !== null" @close="selectedImage = null" maxWidth="4xl">
             <div class="p-1 bg-black rounded-lg overflow-hidden relative group">
                <button @click="selectedImage = null" class="absolute top-4 right-4 bg-black/50 hover:bg-black text-white p-2 rounded-full z-50 transition">
                     <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <img :src="selectedImage" class="w-full h-auto max-h-[90vh] object-contain mx-auto" />
             </div>
        </Modal>

        <Modal :show="showUnitDetailsModal" @close="showUnitDetailsModal = false" maxWidth="lg">
            <div class="bg-white">
                <div class="flex justify-between items-center p-4 border-b border-gray-100">
                    <h3 class="font-bold text-lg text-gray-900">Unit Details</h3>
                    <button @click="showUnitDetailsModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 flex justify-between items-center">
                            <span class="text-xs text-gray-500 uppercase font-bold">Chassis Number</span>
                            <span class="font-mono font-medium text-gray-900 break-all">{{ currentUnit.chassis_number }}</span>
                        </div>
                         <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 flex justify-between items-center">
                            <span class="text-xs text-gray-500 uppercase font-bold">Motor Number</span>
                            <span class="font-mono font-medium text-gray-900 break-all">{{ currentUnit.motor_number }}</span>
                        </div>
                         <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 flex justify-between items-center">
                            <span class="text-xs text-gray-500 uppercase font-bold">CR Number</span>
                            <span class="font-mono font-medium text-gray-900 break-all">{{ currentUnit.cr_number || 'N/A' }}</span>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-3">Vehicle Photos</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                             <div @click="currentUnit.unit_front_photo ? openImage(`/storage/${currentUnit.unit_front_photo}`) : null" class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                <img v-if="currentUnit.unit_front_photo" :src="`/storage/${currentUnit.unit_front_photo}`" class="w-full h-full object-cover" alt="Front View" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="text-[10px] uppercase font-bold">Front</span>
                                </div>
                            </div>
                            <div @click="currentUnit.unit_back_photo ? openImage(`/storage/${currentUnit.unit_back_photo}`) : null" class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                <img v-if="currentUnit.unit_back_photo" :src="`/storage/${currentUnit.unit_back_photo}`" class="w-full h-full object-cover" alt="Back View" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                     <span class="text-[10px] uppercase font-bold">Back</span>
                                </div>
                            </div>
                             <div @click="currentUnit.unit_left_photo ? openImage(`/storage/${currentUnit.unit_left_photo}`) : null" class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                <img v-if="currentUnit.unit_left_photo" :src="`/storage/${currentUnit.unit_left_photo}`" class="w-full h-full object-cover" alt="Side View" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                     <span class="text-[10px] uppercase font-bold">Side</span>
                                </div>
                            </div>
                             <div @click="currentUnit.unit_right_photo ? openImage(`/storage/${currentUnit.unit_right_photo}`) : null" class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                <img v-if="currentUnit.unit_right_photo" :src="`/storage/${currentUnit.unit_right_photo}`" class="w-full h-full object-cover" alt="Right View" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                     <span class="text-[10px] uppercase font-bold">Right</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <Modal :show="showTransferModal" @close="showTransferModal = false" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1">Transfer Ownership</h2>
                <p class="text-sm text-gray-500 mb-6">Assign this franchise to a new operator.</p>
                <form @submit.prevent="submitTransfer" class="space-y-4">
                    <div>
                        <InputLabel>New Operator</InputLabel>
                        <select v-model="transferForm.new_operator_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Search Operator...</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">{{ op.user.last_name }}, {{ op.user.first_name }}</option>
                        </select>
                        <p v-if="transferForm.errors.new_operator_id" class="text-sm text-red-600 mt-1">{{ transferForm.errors.new_operator_id }}</p>
                    </div>
                    <div>
                        <InputLabel>Date Transferred</InputLabel>
                        <TextInput v-model="transferForm.date_transferred" type="date" class="mt-1 block w-full" required />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <SecondaryButton @click="showTransferModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="transferForm.processing">Confirm Transfer</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showZoneModal" @close="showZoneModal = false" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-6 border-b border-gray-100 pb-4">
                    <div class="w-12 h-12 rounded-full shadow-sm border-2 border-white flex-shrink-0" :style="{ backgroundColor: zoneColor }"></div>
                    <div>
                        <h2 class="text-2xl font-black text-gray-900 uppercase leading-none mb-1">
                            {{ franchise.zone?.description || 'No Zone' }}
                        </h2>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Zone Details</span>
                    </div>
                </div>
                <div class="space-y-5">
                    <div v-if="franchise.zone">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Coverage Area</h4>
                        <div class="bg-gray-50 p-4 rounded-xl text-sm text-gray-700 border border-gray-100 max-h-60 overflow-y-auto">
                            <ul v-if="Array.isArray(franchise.zone.coverage)" class="list-disc list-inside space-y-1">
                                <li v-for="(area, index) in franchise.zone.coverage" :key="index">{{ area }}</li>
                            </ul>
                            <p v-else>{{ franchise.zone.coverage || 'No specific coverage area defined.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <Modal :show="showComplaintModal" @close="showComplaintModal = false" maxWidth="lg">
             <div class="p-6">
                <div class="mb-4">
                     <h2 class="text-lg font-bold text-red-600">File a Complaint</h2>
                     <p class="text-sm text-gray-500">Report an incident involving this franchise.</p>
                </div>

                <form @submit.prevent="submitComplaint" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Date of Incident *</InputLabel>
                            <TextInput v-model="complaintForm.incident_date" type="date" class="mt-1 block w-full" required />
                        </div>
                         <div>
                            <InputLabel>Time *</InputLabel>
                            <TextInput v-model="complaintForm.incident_time" type="time" class="mt-1 block w-full" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nature of Complaint *</label>
                        <select v-model="complaintForm.nature_of_complaint" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" required>
                            <option value="" disabled>Select nature of complaint...</option>
                            <option v-for="option in complaintOptions" :key="option" :value="option">{{ option }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Pick-up Point</InputLabel>
                            <TextInput v-model="complaintForm.pick_up_point" type="text" class="mt-1 block w-full" placeholder="e.g. City Hall" />
                        </div>
                         <div>
                            <InputLabel>Drop-off Point</InputLabel>
                            <TextInput v-model="complaintForm.drop_off_point" type="text" class="mt-1 block w-full" placeholder="e.g. Market" />
                        </div>
                    </div>

                     <div class="grid grid-cols-2 gap-4">
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-1">Fare Collected</label>
                             <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                  <span class="text-gray-500 sm:text-sm">₱</span>
                                </div>
                                <input type="number" v-model="complaintForm.fare_collected" step="0.01" class="focus:ring-red-500 focus:border-red-500 block w-full pl-7 sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                              </div>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complainant Contact *</label>
                             <input type="text" v-model="complaintForm.complainant_contact" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="09xxxxxxxxx" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Narrative / Remarks</label>
                        <textarea v-model="complaintForm.remarks" rows="3" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Describe what happened..."></textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                         <SecondaryButton @click="showComplaintModal = false">Cancel</SecondaryButton>
                         <button type="submit" :disabled="complaintForm.processing" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Submit Report
                        </button>
                    </div>
                </form>
             </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style>
/* Default: Hide print area on screen */
#print-area {
    display: none;
}

/* Print Specific Styles */
@media print {
    /* 1. Hide everything visually at the root */
    body > * {
        visibility: hidden;
    }

    /* 2. Make the print area visible and position it */
    #print-area, #print-area * {
        visibility: visible;
        display: block;
    }

    #print-area {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        background: white;
        z-index: 9999;
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    /* Force background colors/images */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>