<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ChangeOfOwnerModal from '@/Components/Modals/ChangeOfOwnerModal.vue';

const props = defineProps({
    application: Object,
});

// --- STATE ---
const activeTab = ref('evaluation');
const showChangeOwnerModal = ref(false); 

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    return {
        ...app,
        type: 'Change of Owner',
        status: app.status || 'Approved', // Defaulted to Approved so you can see finalize
        reference_no: app.reference_no || 'COO-2026-015',
        applicant: app.applicant || {
            first_name: 'Andres', last_name: 'Bonifacio', email: 'andres@example.com',
            contact: '09991234567', address: 'Bonifacio St., Molave'
        },
        evaluation_requirements: app.evaluation_requirements || [
            { id: 1, name: 'Notarized Deed of Sale / Assumption', status: 'Approved', remarks: 'Clear copy' },
            { id: 2, name: 'Valid ID of Previous Owner', status: 'Approved', remarks: '' }
        ],
        // DUMMY DATA FOR CHANGE OF OWNER
        receipt: app.receipt || {
            or_number: 'OR-COO-551', date: '2026-02-19', total_amount_due: 2000,
            particulars: [ { name: 'Transfer of Franchise Fee', amount: 2000 } ]
        }
    };
});

// --- ACTIONS ---
const confirmApproveApplication = () => {
    if (!confirm("Approve Change of Owner?")) return;
    router.post(route('admin.applications.approve', application.value.id));
};

const formatCurrency = (value) => {
    if(!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};
</script>

<template>
    <Head title="Application Details - Change of Owner" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Change of Owner Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Approved'">
                        <PrimaryButton @click="showChangeOwnerModal = true" class="flex items-center gap-2">
                            Finalize Ownership Transfer
                        </PrimaryButton>
                    </template>
                    <template v-else>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 text-white text-xs font-bold uppercase rounded-lg">Approve Transfer</button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex gap-4 h-full min-h-0">
                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">
                    
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6">
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Evaluation</button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6">
                        <div v-if="activeTab === 'evaluation'" class="space-y-6">
                             <div v-for="req in application.evaluation_requirements" :key="req.id" class="p-4 bg-white border border-gray-200 rounded flex justify-between">
                                <p class="font-bold">{{ req.name }}</p>
                                <span class="font-bold text-green-600">{{ req.status }}</span>
                            </div>
                        </div>

                        <div v-if="activeTab === 'receipt'" class="bg-white p-6 border border-gray-200 rounded">
                            <h3 class="font-bold">OR #{{ application.receipt.or_number }}</h3>
                             <div v-for="item in application.receipt.particulars" :key="item.name" class="flex justify-between mt-2">
                                <span>{{ item.name }}</span>
                                <span>{{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between font-bold">
                                <span>Total</span>
                                <span>{{ formatCurrency(application.receipt.total_amount_due) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ChangeOfOwnerModal :show="showChangeOwnerModal" :application="application" @close="showChangeOwnerModal = false" />

    </AuthenticatedLayout>
</template>