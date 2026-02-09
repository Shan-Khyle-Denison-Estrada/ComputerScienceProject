<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
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
const showZoneModal = ref(false);
const showUnitDetailsModal = ref(false);
const activeTab = ref('ownership'); 

// NEW: State for Full Screen Image Viewer
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
};

const closeImage = () => {
    selectedImage.value = null;
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
                                    Assigned Drivers
                                </button>
                                <button 
                                    @click="activeTab = 'financials'"
                                    :class="[
                                        activeTab === 'financials' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Assessments & Payments
                                </button>
                                <button 
                                    @click="activeTab = 'unit'"
                                    :class="[
                                        activeTab === 'unit' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
                                    ]"
                                >
                                    Unit History
                                </button>
                            </nav>
                        </div>

                        <div class="flex-grow bg-white min-h-[300px]">
                            <div v-if="activeTab === 'ownership'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                        <tr>
                                            <th class="px-6 py-3">Date</th>
                                            <th class="px-6 py-3">New Owner</th>
                                            <th class="px-6 py-3">Previous Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="hist in franchise.ownership_history" :key="hist.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-gray-600 font-mono">{{ hist.date_transferred }}</td>
                                            <td class="px-6 py-4 font-medium text-gray-900">{{ hist.new_owner.user.last_name }}, {{ hist.new_owner.user.first_name }}</td>
                                            <td class="px-6 py-4 text-gray-500">
                                                {{ hist.previous_owner ? `${hist.previous_owner.user.last_name}, ${hist.previous_owner.user.first_name}` : 'Initial Issue' }}
                                            </td>
                                        </tr>
                                        <tr v-if="franchise.ownership_history.length === 0"><td colspan="3" class="px-6 py-8 text-center text-gray-400">No history available</td></tr>
                                    </tbody>
                                </table>
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

                            <div v-if="activeTab === 'financials'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                        <tr>
                                            <th class="px-6 py-3">Date</th>
                                            <th class="px-6 py-3">Description</th>
                                            <th class="px-6 py-3">OR Number</th>
                                            <th class="px-6 py-3 text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr>
                                            <td colspan="4" class="px-6 py-12 text-center">
                                                <div class="text-gray-400 italic mb-2">No payment records found.</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="activeTab === 'unit'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-semibold">
                                        <tr>
                                            <th class="px-6 py-3">Date</th>
                                            <th class="px-6 py-3">Plate No.</th>
                                            <th class="px-6 py-3">Details</th>
                                            <th class="px-6 py-3">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="uh in franchise.unit_history" :key="uh.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-gray-600 font-mono">{{ uh.date_changed }}</td>
                                            <td class="px-6 py-4 font-bold text-gray-800">{{ uh.new_unit.plate_number }}</td>
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-gray-900">{{ uh.new_unit.make.name }} {{ uh.new_unit.model_year }}</div>
                                                <div class="text-xs text-gray-500 font-mono">
                                                    C: {{ uh.new_unit.chassis_number }} | M: {{ uh.new_unit.motor_number }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-500 italic">{{ uh.remarks || '-' }}</td>
                                        </tr>
                                        <tr v-if="franchise.unit_history.length === 0"><td colspan="4" class="px-6 py-8 text-center text-gray-400">No history available</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <Modal :show="showUnitDetailsModal" @close="showUnitDetailsModal = false" maxWidth="lg">
                <div class="p-0 overflow-hidden" v-if="currentUnit">
                    <div class="bg-gray-900 p-6 text-white flex justify-between items-start">
                        <div>
                            <div class="text-xs uppercase tracking-widest text-gray-400 mb-1">Active Unit Details</div>
                            <h2 class="text-3xl font-mono font-bold">{{ currentUnit.plate_number }}</h2>
                            <p class="text-gray-300">{{ currentUnit.make.name }} {{ currentUnit.model_year }}</p>
                        </div>
                        <button @click="showUnitDetailsModal = false" class="text-gray-400 hover:text-white">
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
                                
                                <div @click="currentUnit.unit_front_photo ? openImage(`/storage/${currentUnit.unit_front_photo}`) : null"
                                     class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                    <img v-if="currentUnit.unit_front_photo" :src="`/storage/${currentUnit.unit_front_photo}`" class="w-full h-full object-cover" alt="Front View" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span class="text-[10px] uppercase font-bold">Front</span>
                                    </div>
                                </div>

                                <div @click="currentUnit.unit_back_photo ? openImage(`/storage/${currentUnit.unit_back_photo}`) : null"
                                     class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                    <img v-if="currentUnit.unit_back_photo" :src="`/storage/${currentUnit.unit_back_photo}`" class="w-full h-full object-cover" alt="Back View" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span class="text-[10px] uppercase font-bold">Back</span>
                                    </div>
                                </div>

                                <div @click="currentUnit.unit_left_photo ? openImage(`/storage/${currentUnit.unit_left_photo}`) : null"
                                     class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                    <img v-if="currentUnit.unit_left_photo" :src="`/storage/${currentUnit.unit_left_photo}`" class="w-full h-full object-cover" alt="Left View" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span class="text-[10px] uppercase font-bold">Left</span>
                                    </div>
                                </div>

                                <div @click="currentUnit.unit_right_photo ? openImage(`/storage/${currentUnit.unit_right_photo}`) : null"
                                     class="aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative group cursor-pointer hover:opacity-90 transition-opacity">
                                    <img v-if="currentUnit.unit_right_photo" :src="`/storage/${currentUnit.unit_right_photo}`" class="w-full h-full object-cover" alt="Right View" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-6 h-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
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
                            <p v-if="transferForm.errors.new_operator_id" class="text-red-500 text-xs mt-1">{{ transferForm.errors.new_operator_id }}</p>
                        </div>
                        <div>
                            <InputLabel>Date</InputLabel>
                            <TextInput type="date" v-model="transferForm.date_transferred" class="mt-1 block w-full" required />
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <SecondaryButton @click="showTransferModal = false">Cancel</SecondaryButton>
                            <PrimaryButton :disabled="transferForm.processing">Confirm Transfer</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal :show="showChangeUnitModal" @close="showChangeUnitModal = false" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Change Active Unit</h2>
                    <p class="text-sm text-gray-500 mb-6">Update the vehicle associated with this franchise.</p>
                    <form @submit.prevent="submitUnitChange" class="space-y-4">
                        <div>
                            <InputLabel>New Unit</InputLabel>
                            <select v-model="unitForm.new_unit_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Choose Unit...</option>
                                <option v-for="u in units" :key="u.id" :value="u.id">{{ u.plate_number }} — {{ u.make.name }}</option>
                            </select>
                            <p v-if="unitForm.errors.new_unit_id" class="text-red-500 text-xs mt-1">{{ unitForm.errors.new_unit_id }}</p>
                        </div>
                        <div>
                            <InputLabel>Date</InputLabel>
                            <TextInput type="date" v-model="unitForm.date_changed" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel>Remarks</InputLabel>
                            <TextInput v-model="unitForm.remarks" class="mt-1 block w-full" placeholder="e.g. Upgrade" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <SecondaryButton @click="showChangeUnitModal = false">Cancel</SecondaryButton>
                            <PrimaryButton :disabled="unitForm.processing">Update</PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal :show="showAddDriverModal" @close="showAddDriverModal = false" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">Assign Driver</h2>
                    <p class="text-sm text-gray-500 mb-6">Select a driver for this franchise.</p>
                    <form @submit.prevent="submitDriverAssignment" class="space-y-4">
                        <div>
                            <InputLabel>Select Driver</InputLabel>
                            <select v-model="driverForm.driver_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Choose driver...</option>
                                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ getDriverName(d) }}</option>
                            </select>
                             <p v-if="driverForm.errors.driver_id" class="text-red-500 text-xs mt-1">{{ driverForm.errors.driver_id }}</p>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <SecondaryButton @click="showAddDriverModal = false">Cancel</SecondaryButton>
                            <PrimaryButton :disabled="driverForm.processing">Assign</PrimaryButton>
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
                        <div v-else class="text-gray-400 italic text-center py-4">
                            This franchise is not currently assigned to a zone.
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <SecondaryButton @click="showZoneModal = false">Close</SecondaryButton>
                    </div>
                </div>
            </Modal>

        </div>
        
        <Teleport to="body">
            <div v-if="selectedImage" 
                 style="z-index: 2147483647 !important;" 
                 class="fixed inset-0 bg-black bg-opacity-95 flex items-center justify-center p-4 backdrop-blur-sm transition-all duration-300"
                 @click.self="closeImage">
                
                <button @click="closeImage" class="absolute top-6 right-6 text-white text-opacity-70 hover:text-white focus:outline-none transition-colors">
                    <svg class="w-12 h-12 drop-shadow-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                
                <img :src="selectedImage" class="max-h-[90vh] max-w-[90vw] object-contain rounded-sm shadow-2xl" />
            </div>
        </Teleport>

        <div id="print-area">
            <div class="relative bg-white rounded-xl overflow-hidden shadow-none max-w-[400px] mx-auto text-center border-4"
                 :style="{ borderColor: zoneColor }">

                <div class="py-4 text-white font-bold tracking-widest uppercase"
                     :style="{ backgroundColor: zoneColor }">
                    Franchise Authority
                </div>

                <div class="p-8">
                    <div class="text-xs font-bold uppercase tracking-widest mb-1 text-gray-400">Zone Assignment</div>
                    <div class="text-lg font-black uppercase mb-6" :style="{ color: zoneColor }">
                         {{ franchise.zone?.description || 'No Zone' }}
                    </div>

                    <div class="text-6xl font-black text-gray-900 mb-6 leading-none">{{ franchise.id }}</div>
                    
                    <div class="mb-6 flex justify-center items-center h-48 w-48 mx-auto">
                        <img v-if="franchise.qr_code" :src="`/storage/qrcodes/${franchise.qr_code}`" class="block max-w-full max-h-full" loading="eager" alt="Franchise QR Code" />
                        <div v-else class="text-gray-400 text-sm">No QR Code Available</div>
                    </div>
                    
                    <div class="border-t-2 border-dashed border-gray-200 pt-6">
                        <div class="text-xs uppercase text-gray-400 mb-1">Plate Number</div>
                        <div class="text-4xl font-mono font-bold text-gray-800">{{ currentUnit ? currentUnit.plate_number : 'N/A' }}</div>
                    </div>
                </div>

                <div class="py-2 bg-gray-50 text-[10px] text-gray-400 border-t border-gray-100">
                    Issued {{ franchise.date_issued }}
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style>
/* Default: Hide print area on screen */
#print-area {
    display: none;
}

/* Print Specific Styles */
@media print {
    /* 1. Hide everything visually */
    body * {
        visibility: hidden;
    }

    /* 2. Collapse the layout of the main screen content to prevent scroll/layout issues */
    .screen-content, nav, header, footer {
        display: none !important;
    }

    /* 3. Make print area visible and taking up layout */
    #print-area, #print-area * {
        visibility: visible;
    }

    /* 4. Position fixed ensures it starts at 0,0 of the paper, regardless of screen scroll */
    #print-area {
        display: flex !important;
        align-items: center;
        justify-content: center;
        position: fixed;
        left: 0;
        top: 0;
        width: 100vw;
        height: 100vh;
        margin: 0;
        padding: 0;
        background: white;
        z-index: 9999;
    }

    /* FORCE background colors and images to print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>