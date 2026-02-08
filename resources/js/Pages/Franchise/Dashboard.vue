<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    hasFranchise: Boolean,
    franchises: {
        type: Array,
        default: () => []
    },
    operator: Object
});

// --- STATE ---
const selectedFranchiseId = ref(null);
const activeTab = ref('payments'); 
const showCoverageModal = ref(false);

// --- INIT ---
onMounted(() => {
    if (props.franchises.length > 0) {
        selectedFranchiseId.value = props.franchises[0].id;
    }
});

// --- COMPUTED ---
const selectedFranchise = computed(() => {
    return props.franchises.find(f => f.id === selectedFranchiseId.value) || null;
});

const unit = computed(() => selectedFranchise.value?.current_active_unit?.new_unit);
const zone = computed(() => selectedFranchise.value?.zone);
const payments = computed(() => selectedFranchise.value?.payment_history || []);

// --- HELPERS ---
const getOwnerName = (user) => user ? `${user.first_name} ${user.last_name}` : 'Unknown';

const getDriverName = (driver) => {
    if (!driver) return 'No Driver Assigned';
    if (driver.user) return `${driver.user.last_name}, ${driver.user.first_name}`;
    return `${driver.last_name}, ${driver.first_name}`;
};

const formatDate = (dateString) => {
    if(!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const formatCurrency = (val) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(val);

const getStatusClasses = (status) => {
    switch (status) {
        case 'renewed': return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'pending renewal': return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'terminated': return 'bg-rose-100 text-rose-700 border-rose-200';
        default: return 'bg-gray-100 text-gray-600 border-gray-200';
    }
};

const getTabLabel = (tabKey) => {
    if (tabKey === 'driver') return 'Assigned Drivers';
    return tabKey.charAt(0).toUpperCase() + tabKey.slice(1) + ' History';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="flex flex-1 h-full overflow-hidden relative">
            
            <aside class="w-80 rounded-2xl bg-white flex flex-col z-10 shrink-0">
                <div class="p-5 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800 text-lg">My Franchises</h2>
                    <p class="text-xs text-gray-500 mt-1">{{ franchises.length }} franchise/s registered</p>
                </div>
                
                <div class="flex-1 overflow-y-auto p-3 space-y-2 custom-scrollbar bg-white">
                    <div v-if="!hasFranchise || franchises.length === 0" class="text-center p-6 text-gray-400">
                        <p class="text-sm">No active franchises found.</p>
                    </div>

                    <button 
                        v-for="fran in franchises" 
                        :key="fran.id"
                        @click="selectedFranchiseId = fran.id"
                        class="w-full text-left p-4 rounded-xl border transition-all duration-200 group relative"
                        :class="selectedFranchiseId === fran.id 
                            ? 'bg-blue-50 border-blue-200 shadow-sm ring-1 ring-blue-200' 
                            : 'bg-white border-gray-100 hover:border-blue-200 hover:bg-gray-50'"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">ID: {{ fran.id }}</span>
                            <div class="w-2 h-2 rounded-full shadow-sm" :class="{
                                'bg-emerald-500': fran.status === 'renewed',
                                'bg-amber-500': fran.status === 'pending renewal',
                                'bg-rose-500': fran.status === 'terminated'
                            }"></div>
                        </div>
                        <div class="font-bold text-gray-800 text-lg">
                            {{ fran.current_active_unit?.new_unit?.plate_number || 'NO UNIT' }}
                        </div>
                        <div class="text-xs text-gray-500 truncate mt-1 group-hover:text-blue-600 transition-colors">
                            {{ fran.zone?.description || 'No Zone Assigned' }}
                        </div>
                    </button>
                </div>

                <div v-if="operator" class="p-4 bg-white rounded-b-2xl border-t border-gray-200 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold shrink-0">
                            <img v-if="operator.user.user_photo" :src="'/storage/' + operator.user.user_photo" class="h-full w-full object-cover" />
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-gray-800 truncate">{{ getOwnerName(operator.user) }}</div>
                            <div class="text-xs text-gray-500 truncate">TIN: {{ operator.tin_number }}</div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="flex-1 flex flex-col h-full overflow-hidden space-y-3 relative">
                <div v-if="selectedFranchise" class="flex flex-col h-full max-w-6xl mx-auto w-full space-y-3">
                    
                    <div class="shrink-0 space-y-3">
                        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border border-gray-100 p-4 bg-white rounded-2xl shadow-sm">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border tracking-wide" :class="getStatusClasses(selectedFranchise.status)">
                                        {{ selectedFranchise.status }}
                                    </span>
                                    <span class="text-gray-400 text-sm font-medium">Franchise #{{ selectedFranchise.id }}</span>
                                </div>
                                
                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1">Plate Number</div>
                                <h1 class="text-4xl font-black text-gray-900 tracking-tight">
                                    {{ unit ? unit.plate_number : 'No Active Unit' }}
                                </h1>
                            </div>
                            <div class="flex gap-3">
                                <div class="text-right hidden md:block">
                                    <div class="text-xs text-gray-400 font-bold uppercase">Registration Date</div>
                                    <div class="text-gray-700 font-medium">{{ formatDate(selectedFranchise.date_issued) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            
                            <button 
                                @click="showCoverageModal = true"
                                class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-lg hover:border-gray-300 transition-all duration-300 w-full text-left cursor-pointer h-full"
                            >
                                <div class="relative z-10 transition-all duration-300 group-hover:blur-sm group-hover:opacity-50 h-full flex flex-col justify-center">
                                    <div 
                                        class="absolute -top-5 -right-5 w-32 h-32 opacity-10 rounded-bl-full pointer-events-none"
                                        :style="{ backgroundColor: zone?.color || '#a855f7' }"
                                    ></div>

                                    <div class="flex items-start gap-4">
                                        <div 
                                            class="p-3 rounded-xl shrink-0"
                                            :style="{ 
                                                backgroundColor: zone?.color ? zone.color + '20' : '#f3e8ff', 
                                                color: zone?.color ? zone.color : '#9333ea' 
                                            }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-[10px] font-bold uppercase text-gray-400 mb-1 tracking-wider">Assigned Zone</h4>
                                            <div class="font-bold text-gray-800 text-lg leading-tight">{{ zone?.description || 'N/A' }}</div>
                                            <div class="text-[11px] text-gray-500 mt-1 flex items-center gap-1">
                                                Color: <span class="font-bold text-gray-700">{{ zone?.color || 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                    <div class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-bold shadow-xl transform scale-90 group-hover:scale-100 transition-transform flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Coverage
                                    </div>
                                </div>
                            </button>

                            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-start gap-4 hover:shadow-md transition-shadow">
                                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-bold uppercase text-gray-400 mb-1 tracking-wider">Vehicle Make</h4>
                                    <div class="font-bold text-gray-800">{{ unit?.make?.name || 'N/A' }}</div>
                                    <div class="text-sm text-gray-500">{{ unit?.model_year || 'Year N/A' }}</div>
                                </div>
                            </div>

                            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-start gap-4 hover:shadow-md transition-shadow">
                                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-bold uppercase text-gray-400 mb-1 tracking-wider">Recent Payment</h4>
                                    <div class="font-bold text-gray-800">{{ payments.length > 0 ? formatCurrency(payments[0].amount_paid) : 'No Records' }}</div>
                                    <div class="text-sm text-gray-500">{{ payments.length > 0 ? formatDate(payments[0].created_at) : '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col min-h-0">
                        
                        <div class="flex border-b border-gray-200 bg-gray-50 px-6 pt-2 gap-6 overflow-x-auto shrink-0">
                            <button 
                                v-for="tab in ['payments', 'ownership', 'units', 'driver']" 
                                :key="tab"
                                @click="activeTab = tab"
                                class="pb-3 pt-3 text-sm font-bold border-b-2 transition-colors capitalize whitespace-nowrap outline-none focus:outline-none"
                                :class="activeTab === tab 
                                    ? 'border-blue-600 text-blue-700' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            >
                                {{ getTabLabel(tab) }}
                            </button>
                        </div>

                        <div class="flex-1 overflow-y-auto scroll-smooth custom-scrollbar">
                            
                            <div v-if="activeTab === 'payments'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Assessment Date</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Paid On</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Particulars</th>
                                            <th class="px-6 py-4 font-medium text-right bg-gray-50">Amount Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-if="payments.length === 0">
                                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">No payment history available.</td>
                                        </tr>
                                        <tr v-for="pay in payments" :key="pay.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-mono text-gray-600">{{ formatDate(pay.assessment_date) }}</td>
                                            <td class="px-6 py-4 text-gray-600">{{ formatDate(pay.created_at) }}</td>
                                            <td class="px-6 py-4 font-medium text-gray-800">{{ pay.particulars_string }}</td>
                                            <td class="px-6 py-4 text-right font-bold text-gray-800">{{ formatCurrency(pay.amount_paid) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="activeTab === 'ownership'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Date Transferred</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">New Owner</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Previous Owner</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-if="!selectedFranchise.ownership_history?.length">
                                            <td colspan="3" class="px-6 py-12 text-center text-gray-400">No ownership changes recorded.</td>
                                        </tr>
                                        <tr v-for="hist in selectedFranchise.ownership_history" :key="hist.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-gray-600">{{ formatDate(hist.date_transferred || hist.created_at) }}</td>
                                            <td class="px-6 py-4 font-bold text-gray-800">{{ getOwnerName(hist.new_owner?.user) }}</td>
                                            <td class="px-6 py-4 text-gray-500">{{ hist.previous_owner ? getOwnerName(hist.previous_owner.user) : 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="activeTab === 'units'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Date Changed</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Plate Number</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Make / Model</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-if="!selectedFranchise.unit_history?.length">
                                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">No unit changes recorded.</td>
                                        </tr>
                                        <tr v-for="h in selectedFranchise.unit_history" :key="h.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-gray-600">{{ formatDate(h.date_changed) }}</td>
                                            <td class="px-6 py-4 font-mono font-bold text-gray-800">{{ h.new_unit?.plate_number }}</td>
                                            <td class="px-6 py-4 text-gray-600">{{ h.new_unit?.make?.name }}</td>
                                            <td class="px-6 py-4 text-gray-500 italic">{{ h.remarks || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="activeTab === 'driver'" class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 sticky top-0">
                                        <tr>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Date Assigned</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">Driver Name</th>
                                            <th class="px-6 py-4 font-medium bg-gray-50">License Number</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-if="!selectedFranchise.driver_assignments?.length">
                                            <td colspan="3" class="px-6 py-12 text-center text-gray-400">No assigned drivers recorded.</td>
                                        </tr>
                                        <tr v-for="assign in selectedFranchise.driver_assignments" :key="assign.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-gray-600">{{ formatDate(assign.created_at) }}</td>
                                            <td class="px-6 py-4 font-bold text-gray-800">{{ getDriverName(assign.driver) }}</td>
                                            <td class="px-6 py-4 font-mono text-gray-600">{{ assign.driver?.license_number }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

                <div v-else class="h-full flex flex-col items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="text-lg font-medium">Select a franchise to view details</p>
                </div>
            </main>

            <Teleport to="body">
                <div 
                    v-if="showCoverageModal" 
                    class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm transition-all" 
                    @click.self="showCoverageModal = false"
                >
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-200 transform transition-all scale-100">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full shadow-sm" :style="{ backgroundColor: zone?.color || '#a855f7' }"></div>
                                <h3 class="font-bold text-gray-800">Zone Coverage Areas</h3>
                            </div>
                            <button @click="showCoverageModal = false" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-full hover:bg-red-50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div v-if="zone?.coverage && zone.coverage.length > 0">
                                <p class="text-sm text-gray-500 mb-4">
                                    The <span class="font-bold text-gray-700">{{ zone.description }}</span> covers the following {{ zone.coverage.length }} barangays:
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="(area, index) in zone.coverage" 
                                        :key="index"
                                        class="px-3 py-1.5 bg-gray-50 text-gray-700 text-sm font-medium rounded-lg border border-gray-200 hover:border-gray-300 transition-colors select-all"
                                    >
                                        {{ area }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-400">
                                <p>No coverage areas defined for this zone.</p>
                            </div>
                        </div>
                        <div class="p-4 border-t border-gray-100 bg-gray-50 text-right">
                            <button @click="showCoverageModal = false" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>