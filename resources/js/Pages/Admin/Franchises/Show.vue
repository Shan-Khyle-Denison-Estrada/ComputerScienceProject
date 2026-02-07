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
const showZoneModal = ref(false); // NEW: State for Zone Modal
const activeTab = ref('ownership'); 

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
// Fallback color if zone has no color defined
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Operator</h3>
                        <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                    </div>
                    
                    <div v-if="currentOwner" class="relative z-10">
                        <div class="flex items-center gap-4 mb-4">
                            <div class="h-16 w-16 rounded-full bg-gray-200 flex-shrink-0 overflow-hidden border-2 border-white shadow-sm">
                                <img v-if="currentOwner.user.user_photo" :src="`/storage/${currentOwner.user.user_photo}`" class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full flex items-center justify-center bg-blue-600 text-white text-xl font-bold">
                                    {{ currentOwner.user.first_name.charAt(0) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xl font-bold text-gray-900 leading-tight">
                                    {{ currentOwner.user.last_name }}
                                </div>
                                <div class="text-base text-gray-600">{{ currentOwner.user.first_name }}</div>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm border-t border-gray-100 pt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">TIN</span>
                                <span class="font-mono font-medium text-gray-700">{{ currentOwner.tin_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Since</span>
                                <span class="font-medium text-gray-700">{{ franchise.current_ownership?.date_transferred }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Contact</span>
                                <span class="font-medium text-gray-700">{{ currentOwner.user.email }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-400 italic py-4">No Active Owner Assigned</div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Active Unit</h3>
                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 012-2v0m12 0a2 2 0 012 2v0m-2-2h2" /></svg>
                        </div>
                    </div>

                    <div v-if="currentUnit" class="relative z-10">
                        <div class="text-center bg-gray-50 rounded-xl border border-gray-200 py-4 mb-4">
                            <div class="text-[10px] uppercase text-gray-400 tracking-wider mb-1">Plate Number</div>
                            <div class="text-3xl font-mono font-black text-gray-800 tracking-wider">
                                {{ currentUnit.plate_number }}
                            </div>
                        </div>
                        
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                                <span class="text-gray-500">Make</span>
                                <span class="font-bold text-gray-800">{{ currentUnit.make.name }}</span>
                            </div>
                            <div class="flex justify-between items-center border-b border-gray-50 pb-2">
                                <span class="text-gray-500">Model Year</span>
                                <span class="text-gray-700">{{ currentUnit.model_year }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Chassis</span>
                                <span class="font-mono text-gray-600 text-xs">{{ currentUnit.chassis_number }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-gray-400 italic py-4">No Unit Assigned</div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition-shadow flex flex-col h-full">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Operations</h3>
                        <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-xs text-gray-500 block">Assigned Zone</span>
                            <button @click="showZoneModal = true" class="text-[10px] text-blue-600 hover:text-blue-800 font-bold uppercase tracking-wide cursor-pointer hover:underline">
                                View Details
                            </button>
                        </div>
                        <div class="font-medium text-gray-800 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: zoneColor }"></span>
                            <span class="uppercase truncate">{{ franchise.zone ? franchise.zone.description : 'Unassigned' }}</span>
                        </div>
                    </div>

                    <div class="flex-grow mb-5">
                         <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-gray-500">Drivers</span>
                            <button @click="showAddDriverModal = true" class="text-[10px] font-bold text-blue-600 hover:underline uppercase tracking-wide">
                                + Assign
                            </button>
                        </div>
                        <div class="bg-gray-50 rounded-lg border border-gray-100 overflow-hidden">
                            <div v-if="franchise.driver_assignments && franchise.driver_assignments.length > 0" class="max-h-32 overflow-y-auto divide-y divide-gray-100">
                                <div v-for="assignment in franchise.driver_assignments" :key="assignment.id" class="p-2 flex justify-between items-center group hover:bg-white transition-colors">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                            {{ assignment.driver.first_name ? assignment.driver.first_name.charAt(0) : 'D' }}
                                        </div>
                                        <span class="text-sm text-gray-700 truncate max-w-[120px]" :title="getDriverName(assignment.driver)">
                                            {{ getDriverName(assignment.driver) }}
                                        </span>
                                    </div>
                                    <button @click="removeDriver(assignment.id)" class="text-gray-300 hover:text-red-500 p-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div v-else class="p-4 text-center text-xs text-gray-400 italic">No drivers assigned</div>
                        </div>
                    </div>

                    <div class="mt-auto border-t border-gray-100 pt-4 flex items-center gap-4">
                        <div class="bg-white p-1 rounded border shadow-sm flex-shrink-0">
                            <img v-if="franchise.qr_code" :src="`/storage/qrcodes/${franchise.qr_code}`" class="w-14 h-14" alt="QR Code" />
                            <div v-else class="w-14 h-14 bg-gray-50 flex items-center justify-center text-[8px] text-gray-400">NO QR</div>
                        </div>
                        <div class="flex-grow">
                            <div class="text-xs text-gray-400 mb-1">Identity Verification</div>
                            <button @click="triggerPrint" class="w-full flex items-center justify-center gap-2 bg-gray-900 hover:bg-black text-white text-xs font-bold py-2 rounded-lg transition shadow-sm">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                Print QR Badge
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
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
    /* Hide everything in body... */
    body * {
        visibility: hidden;
    }

    /* ...Except the print area and its children */
    #print-area, #print-area * {
        visibility: visible;
    }

    /* Position the print area absolutely to take up the page */
    #print-area {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        background: white;
    }

    /* FORCE background colors and images to print */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>