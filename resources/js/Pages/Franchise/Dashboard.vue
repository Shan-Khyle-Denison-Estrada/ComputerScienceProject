<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    hasFranchise: Boolean,
    franchise: Object,
    operator: Object,
    payments: Array
});

// --- STATE ---
const activeTab = ref('payments'); // 'payments' | 'ownership' | 'units' | 'driver'

// --- COMPUTED ---
const unit = computed(() => props.franchise?.current_active_unit?.new_unit);
const zone = computed(() => props.franchise?.zone);

// Helper for Names
const getOwnerName = (user) => {
    return user ? `${user.first_name} ${user.last_name}` : 'Unknown';
}

const getDriverName = (driver) => {
    if (!driver) return 'No Driver Assigned';
    if (driver.user) return `${driver.user.last_name}, ${driver.user.first_name}`;
    return `${driver.last_name}, ${driver.first_name}`;
};

// Helper for formatting dates
const formatDate = (dateString) => {
    if(!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

// Helper for currency
const formatCurrency = (val) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(val);

// Helper for status color
const statusColor = computed(() => {
    if (!props.franchise) return 'bg-gray-100 text-gray-800';
    switch (props.franchise.status) {
        case 'renewed': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'pending renewal': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'terminated': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
});
</script>

<template>
    <Head title="My Franchise" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Franchise Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="!hasFranchise" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 text-center">
                    <div class="mb-4 text-gray-400">
                        <svg class="w-16 h-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">No Active Franchise Found</h3>
                    <p class="text-gray-500 mt-2">There is no active franchise linked to your account. Please contact the MTFRB office.</p>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch max-h-[80vh]">

                    <div class="flex flex-col gap-6 overflow-y-auto pr-1">
                        
                        <div class="bg-white shadow rounded-2xl overflow-hidden border border-gray-100 shrink-0">
                            <div class="p-6 text-center border-b border-gray-100 bg-gradient-to-br from-blue-50 to-white">
                                <div class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-1">Franchise ID</div>
                                <div class="text-4xl font-black text-gray-800">{{ franchise.id }}</div>
                                <div class="mt-3 inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase border" :class="statusColor">
                                    {{ franchise.status }}
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-400 uppercase font-bold">Current Unit</div>
                                            <div class="font-bold text-gray-800">{{ unit ? unit.plate_number : 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ unit?.make?.name || 'Unknown Make' }}</div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-gray-400 uppercase font-bold">Zone</div>
                                            <div class="font-bold text-gray-800">{{ zone?.description || 'Unassigned' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow rounded-2xl overflow-hidden border border-gray-100 p-6 flex-1">
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide border-b border-gray-100 pb-2 mb-4">
                                Current Owner Info
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-[10px] uppercase text-gray-400 font-bold">Name</label>
                                    <div class="text-sm font-medium text-gray-800">{{ getOwnerName(operator.user) }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400 font-bold">Contact</label>
                                        <div class="text-sm text-gray-600">{{ operator.user.contact_number || 'N/A' }}</div>
                                    </div>
                                    <div>
                                        <label class="text-[10px] uppercase text-gray-400 font-bold">TIN</label>
                                        <div class="text-sm text-gray-600">{{ operator.tin_number || 'N/A' }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-[10px] uppercase text-gray-400 font-bold">Address</label>
                                    <div class="text-sm text-gray-600">
                                        {{ operator.user.street_address }} {{ operator.user.barangay }}, {{ operator.user.city }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 flex flex-col h-full gap-4">
                        
                        <div class="bg-white shadow rounded-xl p-1 flex gap-2 shrink-0">
                            <button 
                                @click="activeTab = 'payments'"
                                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'payments' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50'"
                            >
                                Payments
                            </button>
                            <button 
                                @click="activeTab = 'ownership'"
                                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'ownership' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50'"
                            >
                                Ownership
                            </button>
                            <button 
                                @click="activeTab = 'units'"
                                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'units' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50'"
                            >
                                Units
                            </button>
                             <button 
                                @click="activeTab = 'driver'"
                                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                                :class="activeTab === 'driver' ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:bg-gray-50'"
                            >
                                Drivers
                            </button>
                        </div>

                        <div class="bg-white shadow rounded-2xl border border-gray-100 flex-1 flex flex-col min-h-0 overflow-hidden">
                            
                            <template v-if="activeTab === 'payments'">
                                <div class="p-5 border-b border-gray-100 flex justify-between items-center shrink-0">
                                    <h3 class="font-bold text-gray-800">Payment History</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-500 font-medium border-b sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3">OR Number</th>
                                                <th class="px-6 py-3">Date</th>
                                                <th class="px-6 py-3">Particulars</th>
                                                <th class="px-6 py-3 text-right">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-if="payments.length === 0">
                                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">No payment records found.</td>
                                            </tr>
                                            <tr v-for="pay in payments" :key="pay.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ pay.or_number }}</td>
                                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ formatDate(pay.created_at) }}</td>
                                                <td class="px-6 py-4 text-gray-800">{{ pay.particulars }}</td>
                                                <td class="px-6 py-4 text-right font-bold text-gray-800">{{ formatCurrency(pay.amount) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <template v-if="activeTab === 'ownership'">
                                <div class="p-5 border-b border-gray-100 shrink-0">
                                    <h3 class="font-bold text-gray-800">Ownership History</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-500 font-medium border-b sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3">Date Transferred</th>
                                                <th class="px-6 py-3">New Owner</th>
                                                <th class="px-6 py-3">Previous Owner</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-if="!franchise.ownership_history || franchise.ownership_history.length === 0">
                                                <td colspan="3" class="px-6 py-8 text-center text-gray-400">No ownership history records.</td>
                                            </tr>
                                            <tr v-for="hist in franchise.ownership_history" :key="hist.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                                    {{ formatDate(hist.date_transferred || hist.created_at) }}
                                                </td>
                                                <td class="px-6 py-4 font-medium text-gray-900">
                                                    {{ getOwnerName(hist.new_owner?.user) }}
                                                </td>
                                                <td class="px-6 py-4 text-gray-500">
                                                    {{ hist.previous_owner ? getOwnerName(hist.previous_owner.user) : 'Original Owner' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                            <template v-if="activeTab === 'units'">
                                <div class="p-5 border-b border-gray-100 shrink-0">
                                    <h3 class="font-bold text-gray-800">Unit History</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-500 font-medium border-b sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3">Date Changed</th>
                                                <th class="px-6 py-3">Plate Number</th>
                                                <th class="px-6 py-3">Make/Model</th>
                                                <th class="px-6 py-3">Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-if="!franchise.unit_history || franchise.unit_history.length === 0">
                                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">No unit history found.</td>
                                            </tr>
                                            <tr v-for="history in franchise.unit_history" :key="history.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                                    {{ formatDate(history.date_changed) }}
                                                </td>
                                                <td class="px-6 py-4 font-bold text-gray-800 font-mono">
                                                    {{ history.new_unit?.plate_number }}
                                                </td>
                                                <td class="px-6 py-4 text-gray-600">
                                                    {{ history.new_unit?.make?.name }}
                                                </td>
                                                <td class="px-6 py-4 text-gray-500 text-xs italic">
                                                    {{ history.remarks || '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            
                            <template v-if="activeTab === 'driver'">
                                <div class="p-5 border-b border-gray-100 shrink-0">
                                    <h3 class="font-bold text-gray-800">Driver Assignment History</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-500 font-medium border-b sticky top-0">
                                            <tr>
                                                <th class="px-6 py-3">Date</th>
                                                <th class="px-6 py-3">Driver Name</th>
                                                <th class="px-6 py-3">License No.</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-if="!franchise.driver_assignments || franchise.driver_assignments.length === 0">
                                                <td colspan="3" class="px-6 py-8 text-center text-gray-400">No driver records found.</td>
                                            </tr>
                                            <tr v-for="assignment in franchise.driver_assignments" :key="assignment.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                                    {{ formatDate(assignment.created_at) }}
                                                </td>
                                                <td class="px-6 py-4 font-medium text-gray-900">
                                                    {{ getDriverName(assignment.driver) }}
                                                </td>
                                                <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                                    {{ assignment.driver?.license_number }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>