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
const activeTab = ref('payments'); // 'payments' | 'driver'

// --- COMPUTED ---
const unit = computed(() => props.franchise?.current_active_unit?.new_unit);
const driver = computed(() => props.franchise?.driver);
const zone = computed(() => props.franchise?.zone);

const getDriverName = (driver) => {
    if (!driver) return 'No Driver Assigned';
    
    // Check if the user relation is loaded
    if (driver.user) {
        return `${driver.user.first_name} ${driver.user.last_name}`;
    }
    
    // Fallback if user object is missing but names exist directly on driver
    if (driver.first_name) {
        return `${driver.first_name} ${driver.last_name}`;
    }
    
    return 'Unknown Driver';
};

// Helper for currency
const formatCurrency = (val) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(val);

// Helper for status color
const statusColor = computed(() => {
    if (!props.franchise) return 'bg-gray-100 text-gray-800';
    switch (props.franchise.status) {
        case 'renewed': return 'bg-green-100 text-green-800 border-green-200';
        case 'pending renewal': return 'bg-orange-100 text-orange-800 border-orange-200';
        case 'terminated': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-blue-100 text-blue-800 border-blue-200';
    }
});
</script>

<template>
    <Head title="My Franchise Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Franchise Portal
            </h2>
        </template>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div v-if="!hasFranchise" class="bg-white rounded-xl shadow-sm p-10 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">No Active Franchise Found</h3>
                <p class="text-gray-500 mt-2">There is no active franchise record associated with your account. Please contact the administration office.</p>
            </div>

            <div v-else class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-5 relative overflow-hidden">
                         <div class="absolute top-0 right-0 p-4 opacity-5">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div class="h-20 w-20 rounded-full bg-blue-600 flex-shrink-0 flex items-center justify-center text-white text-2xl font-bold shadow-md border-4 border-white z-10">
                            <img v-if="operator.user.user_photo" :src="`/storage/${operator.user.user_photo}`" class="h-full w-full object-cover rounded-full" />
                            <span v-else>{{ operator.user.first_name.charAt(0) }}</span>
                        </div>
                        <div class="z-10">
                            <h3 class="text-lg font-bold text-gray-900">{{ operator.user.first_name }} {{ operator.user.last_name }}</h3>
                            <p class="text-sm text-gray-500">{{ operator.user.email }}</p>
                            <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                TIN: {{ operator.tin_number }}
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 bg-gradient-to-br from-blue-900 to-slate-900 rounded-2xl shadow-lg text-white p-6 relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 opacity-10">
                            <svg class="w-48 h-48 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm0 4c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm6 12H6v-1.4c0-2 4-3.1 6-3.1s6 1.1 6 3.1V19z"/></svg>
                        </div>
                        
                        <div class="flex justify-between items-start relative z-10">
                            <div>
                                <p class="text-blue-200 text-sm font-medium uppercase tracking-wider">Franchise ID</p>
                                <h1 class="text-4xl font-bold tracking-tight mt-1">FR-{{ franchise.id }}</h1>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border border-white/20 bg-white/10 backdrop-blur-sm">
                                {{ franchise.status }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-8 relative z-10">
                            <div>
                                <p class="text-blue-300 text-xs uppercase">Date Issued</p>
                                <p class="font-medium text-lg">{{ franchise.date_issued }}</p>
                            </div>
                            <div>
                                <p class="text-blue-300 text-xs uppercase">Zone</p>
                                <p class="font-medium text-lg">{{ zone?.description || 'Unassigned' }}</p>
                            </div>
                             <div>
                                <p class="text-blue-300 text-xs uppercase">Driver</p>
                                <p class="font-medium text-lg">{{ driver ? getDriverName(driver) : 'No Driver' }}</p>
                            </div>
                             <div class="flex justify-end items-end">
                                 <img v-if="franchise.qr_code" :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${franchise.qr_code}`" class="w-12 h-12 bg-white p-1 rounded" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                             <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                             Active Unit Details
                        </h3>
                         <span v-if="unit" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                             Assigned: {{ franchise.current_active_unit.date_changed }}
                         </span>
                    </div>

                    <div v-if="unit" class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold">Plate Number</p>
                                    <div class="text-3xl font-mono font-black text-gray-900">{{ unit.plate_number }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                     <div>
                                        <p class="text-xs text-gray-400 uppercase">Make / Model</p>
                                        <p class="font-medium text-gray-800">{{ unit.make?.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase">Model Year</p>
                                        <p class="font-medium text-gray-800">{{ unit.model_year }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase">Motor No.</p>
                                        <p class="font-mono text-sm text-gray-600">{{ unit.motor_number }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase">Chassis No.</p>
                                        <p class="font-mono text-sm text-gray-600">{{ unit.chassis_number }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="(photo, label) in { 'Front': unit.unit_front_photo, 'Back': unit.unit_back_photo, 'Left': unit.unit_left_photo, 'Right': unit.unit_right_photo }" :key="label" class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                    <img v-if="photo" :src="`/storage/${photo}`" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-2">
                                        <span class="text-white text-xs font-bold uppercase tracking-wider">{{ label }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-8 text-center text-gray-400 italic">
                        No active unit assigned to this franchise.
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden min-h-[400px]">
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6" aria-label="Tabs">
                            <button 
                                @click="activeTab = 'payments'"
                                :class="[ activeTab === 'payments' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors' ]"
                            >
                                Payment History
                            </button>
                             <button 
                                @click="activeTab = 'driver'"
                                :class="[ activeTab === 'driver' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors' ]"
                            >
                                Driver History
                            </button>
                        </nav>
                    </div>

                    <div v-if="activeTab === 'payments'" class="p-0">
                         <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-100">
                                    <tr>
                                        <th class="px-6 py-4">Date Paid</th>
                                        <th class="px-6 py-4">Assessment ID</th>
                                        <th class="px-6 py-4">Particulars</th>
                                        <th class="px-6 py-4 text-right">Amount Paid</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="pay in payments" :key="pay.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 text-gray-600">{{ new Date(pay.created_at).toLocaleDateString() }}</td>
                                        <td class="px-6 py-4 text-blue-600 font-medium">#{{ pay.assessment_id }}</td>
                                        <td class="px-6 py-4 text-gray-500 truncate max-w-xs">{{ pay.particulars }}</td>
                                        <td class="px-6 py-4 text-right font-mono font-bold text-gray-800">{{ formatCurrency(pay.amount_paid) }}</td>
                                    </tr>
                                    <tr v-if="payments.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">No payments recorded.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="activeTab === 'driver'" class="p-8 text-center">
                        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                            <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-gray-900 font-medium">Driver History</h3>
                        <p class="text-gray-500 text-sm mt-1">
                            Currently showing active driver only. Historical data module coming soon.
                        </p>
                        <div v-if="driver" class="mt-6 bg-gray-50 p-4 rounded-lg inline-block text-left border border-gray-200">
                             <div class="text-xs text-gray-400 uppercase font-bold mb-1">Current Active Driver</div>
                             <div class="font-bold text-gray-800 text-lg">{{ getDriverName(driver) }}</div>
                             <div class="text-sm text-gray-500">License: {{ driver.license_number || 'N/A' }}</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>