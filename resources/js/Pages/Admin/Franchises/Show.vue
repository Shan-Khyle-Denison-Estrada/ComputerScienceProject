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
});

// --- STATE ---
const showTransferModal = ref(false);
const showChangeUnitModal = ref(false);
const activeTab = ref('ownership'); // 'ownership' | 'unit'

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

// --- ACTIONS ---
const submitTransfer = () => {
    transferForm.post(route('admin.franchises.transfer', props.franchise.id), {
        onSuccess: () => {
            showTransferModal.value = false;
            transferForm.reset();
        }
    });
};

const submitUnitChange = () => {
    unitForm.post(route('admin.franchises.change-unit', props.franchise.id), {
        onSuccess: () => {
            showChangeUnitModal.value = false;
            unitForm.reset();
        }
    });
};

// --- COMPUTED HELPERS ---
const statusColor = computed(() => {
    switch (props.franchise.status) {
        case 'renewed': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending renewal': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'terminated': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
});

const currentOwner = computed(() => props.franchise.current_ownership?.new_owner);
const currentUnit = computed(() => props.franchise.current_active_unit?.new_unit);

// FIX: Robust Driver Name Resolution
const driverName = computed(() => {
    const d = props.franchise.driver;
    if (!d) return 'Unassigned';
    
    // If driver is linked to a user account
    if (d.user) {
        return `${d.user.last_name}, ${d.user.first_name}`;
    }
    
    // Fallback if driver has direct name columns
    if (d.first_name && d.last_name) {
        return `${d.last_name}, ${d.first_name}`;
    }
    
    return d.name || 'Unknown Name';
});

</script>

<template>
    <Head :title="`Franchise FR-${franchise.id}`" />

    <AuthenticatedLayout>
        <div class="mb-8">
            <nav class="flex mb-3" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <Link :href="route('admin.franchises.index')" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors">
                            Franchises
                        </Link>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                            </svg>
                            <span class="text-gray-900 text-sm font-medium ml-1">Details</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg text-white">
                         <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">
                            Franchise #{{ franchise.id }}
                        </h1>
                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                            <span>Issued: <span class="font-medium text-gray-700">{{ franchise.date_issued }}</span></span>
                            <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border" :class="statusColor">
                                {{ franchise.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <SecondaryButton @click="showChangeUnitModal = true" class="shadow-sm">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Change Unit
                    </SecondaryButton>
                    <PrimaryButton @click="showTransferModal = true" class="shadow-sm bg-gray-900 hover:bg-gray-800">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Transfer Ownership
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-24 h-24 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Current Operator</h3>
                
                <div v-if="currentOwner" class="flex items-start gap-4 relative z-10">
                    <div class="h-14 w-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold shadow-md border-2 border-white">
                        <img v-if="currentOwner.user.user_photo" :src="`/storage/${currentOwner.user.user_photo}`" class="h-full w-full object-cover rounded-full" />
                        <span v-else>{{ currentOwner.user.first_name.charAt(0) }}</span>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-gray-900 leading-tight">
                            {{ currentOwner.user.last_name }}, {{ currentOwner.user.first_name }}
                        </div>
                        <div class="text-sm text-gray-500 mb-2">{{ currentOwner.user.email }}</div>
                        <div class="inline-flex items-center px-2 py-1 bg-gray-50 rounded text-xs text-gray-600 border border-gray-200">
                            TIN: <span class="font-mono ml-1 font-semibold">{{ currentOwner.tin_number }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-gray-400 italic">No Active Owner Assigned</div>
                
                <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                    <span>Active Since</span>
                    <span class="font-medium text-gray-700">{{ franchise.current_ownership?.date_transferred }}</span>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-24 h-24 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-13c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5z"/></svg>
                </div>

                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Assigned Unit</h3>

                <div v-if="currentUnit" class="relative z-10">
                    <div class="inline-block bg-white border-4 border-emerald-600 rounded-lg px-4 py-1 shadow-sm mb-3">
                        <div class="text-[10px] text-center uppercase tracking-widest text-gray-400 leading-none mb-0.5">Region 12</div>
                        <div class="text-3xl font-mono font-black text-gray-800 tracking-wider leading-none">
                            {{ currentUnit.plate_number }}
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-2">
                        <div>
                            <div class="text-sm font-bold text-gray-900">{{ currentUnit.make.name }}</div>
                            <div class="text-xs text-gray-500">Model {{ currentUnit.model_year }}</div>
                        </div>
                        <div class="text-right">
                             <div class="text-xs text-gray-400 uppercase">Chassis No.</div>
                             <div class="text-xs font-mono font-medium text-gray-700">{{ currentUnit.chassis_number }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-gray-400 italic">No Unit Assigned</div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Operations</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Assigned Driver</span>
                            <span class="text-sm font-bold text-gray-900">{{ driverName }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Designated Zone</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
                                {{ franchise.zone?.description || 'No Zone Assigned' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between">
                     <div class="text-xs text-gray-400">Scan for Verification</div>
                     <div class="bg-white p-1 rounded border shadow-sm">
                        <img v-if="franchise.qr_code" :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${franchise.qr_code}`" class="w-16 h-16" alt="QR Code" />
                        <div v-else class="w-16 h-16 bg-gray-100 flex items-center justify-center text-[8px] text-gray-400">NO QR</div>
                     </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex" aria-label="Tabs">
                    <button 
                        @click="activeTab = 'ownership'"
                        :class="[
                            activeTab === 'ownership' ? 'border-blue-500 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50',
                            'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                        ]"
                    >
                        Ownership History
                    </button>
                    <button 
                        @click="activeTab = 'unit'"
                        :class="[
                            activeTab === 'unit' ? 'border-blue-500 text-blue-600 bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50',
                            'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                        ]"
                    >
                        Unit Replacements
                    </button>
                </nav>
            </div>

            <div v-if="activeTab === 'ownership'" class="min-h-[300px]">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-4 w-1/4">Date Transferred</th>
                            <th class="px-8 py-4 w-1/3">New Owner</th>
                            <th class="px-8 py-4 w-1/3">Previous Owner</th>
                            <th class="px-8 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="hist in franchise.ownership_history" :key="hist.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-4 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ hist.date_transferred }}
                                </div>
                            </td>
                            <td class="px-8 py-4">
                                <span class="font-bold text-gray-900">{{ hist.new_owner.user.last_name }}, {{ hist.new_owner.user.first_name }}</span>
                            </td>
                            <td class="px-8 py-4 text-gray-500">
                                <span v-if="hist.previous_owner">{{ hist.previous_owner.user.last_name }}, {{ hist.previous_owner.user.first_name }}</span>
                                <span v-else class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">Initial Issue</span>
                            </td>
                            <td class="px-8 py-4 text-right">
                                <span class="text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded border border-green-100">Success</span>
                            </td>
                        </tr>
                        <tr v-if="franchise.ownership_history.length === 0">
                            <td colspan="4" class="px-8 py-12 text-center text-gray-400 italic">No ownership history found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="activeTab === 'unit'" class="min-h-[300px]">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-4 w-1/4">Date Changed</th>
                            <th class="px-8 py-4 w-1/3">New Unit Plate</th>
                            <th class="px-8 py-4 w-1/3">Remarks</th>
                            <th class="px-8 py-4 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="uh in franchise.unit_history" :key="uh.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-4 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ uh.date_changed }}
                                </div>
                            </td>
                            <td class="px-8 py-4">
                                <span class="font-mono font-bold text-gray-800 bg-gray-100 border border-gray-200 px-2 py-0.5 rounded">{{ uh.new_unit.plate_number }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ uh.new_unit.make.name }}</span>
                            </td>
                            <td class="px-8 py-4 text-gray-600 italic">
                                {{ uh.remarks || 'No remarks' }}
                            </td>
                            <td class="px-8 py-4 text-right">
                                <span class="text-xs text-gray-400">ID: {{ uh.new_unit.id }}</span>
                            </td>
                        </tr>
                        <tr v-if="franchise.unit_history.length === 0">
                            <td colspan="4" class="px-8 py-12 text-center text-gray-400 italic">No unit replacement history found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showTransferModal" @close="showTransferModal = false" maxWidth="md">
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Transfer Ownership</h2>
                    <p class="text-sm text-gray-500">Assign this franchise to a new operator. This action cannot be undone.</p>
                </div>
                
                <form @submit.prevent="submitTransfer" class="space-y-5">
                    <div>
                        <InputLabel>Select New Owner</InputLabel>
                        <select v-model="transferForm.new_operator_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Search Operator...</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">
                                {{ op.user.last_name }}, {{ op.user.first_name }} ({{ op.tin_number }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel>Date of Transfer</InputLabel>
                        <TextInput type="date" v-model="transferForm.date_transferred" class="mt-1 block w-full" required />
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-3 flex gap-3">
                         <svg class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                         <p class="text-xs text-yellow-700 leading-relaxed">
                            The current owner will be moved to history. The new owner will have full control over this franchise immediately.
                         </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="showTransferModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="transferForm.processing">Confirm Transfer</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showChangeUnitModal" @close="showChangeUnitModal = false" maxWidth="md">
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Change Active Unit</h2>
                    <p class="text-sm text-gray-500">Associate a different tricycle unit with this franchise.</p>
                </div>

                <form @submit.prevent="submitUnitChange" class="space-y-5">
                    <div>
                        <InputLabel>Select New Unit</InputLabel>
                        <select v-model="unitForm.new_unit_id" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Choose Unit...</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                {{ u.plate_number }} — {{ u.make.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel>Date Changed</InputLabel>
                        <TextInput type="date" v-model="unitForm.date_changed" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <InputLabel>Remarks</InputLabel>
                        <TextInput type="text" v-model="unitForm.remarks" class="mt-1 block w-full" placeholder="Reason for change (e.g. Upgrade)" />
                    </div>
                    <div class="mt-6 flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="showChangeUnitModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="unitForm.processing">Update Unit</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>