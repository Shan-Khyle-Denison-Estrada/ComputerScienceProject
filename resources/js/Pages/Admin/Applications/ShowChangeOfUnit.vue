<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

import ChangeOfUnitModal from '@/Components/Modals/ChangeOfUnitModal.vue';

const props = defineProps({
    application: Object,
});

// --- STATE ---
const activeTab = ref('evaluation');
const showInspectionModal = ref(false);
const showChangeUnitModal = ref(false); 
const showRequirementModal = ref(false);

const selectedItemIndex = ref(null);
const selectedRequirementIndex = ref(null);

const inspectionForm = reactive({ item: '', status: '', remarks: '', options: [] });
const requirementForm = reactive({ remarks: '' });

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    return {
        ...app,
        type: 'Change of Unit',
        status: app.status || 'Pending', // Defaulted to Approved so you can see the finalize button
        reference_no: app.reference_no || 'COU-2026-042',
        applicant: app.applicant || {
            first_name: 'Jose', last_name: 'Rizal', email: 'pepe@example.com',
            contact: '09123456789', address: 'Rizal St., Molave'
        },
        evaluation_requirements: app.evaluation_requirements || [
            { id: 1, name: 'Deed of Sale of New Unit', status: 'Approved', remarks: '' }
        ],
        // DUMMY DATA FOR CHANGE OF UNIT
        inspection_requirements: app.inspection_requirements || [
            { id: 1, name: 'Engine verification', status: 'Pass', remarks: 'Matches papers', options: 'Pass, Fail' },
            { id: 2, name: 'Chassis verification', status: 'Pass', remarks: '', options: 'Pass, Fail' }
        ],
        receipt: app.receipt || {
            or_number: 'OR-COU-912', date: '2026-02-18', total_amount_due: 1500,
            particulars: [ { name: 'Change of Unit Fee', amount: 1200 }, { name: 'Inspection Fee', amount: 300 } ]
        }
    };
});

// --- ACTIONS ---
const confirmApproveApplication = () => {
    if (!confirm("Approve Change of Unit?")) return;
    router.post(route('admin.applications.approve', application.value.id));
};

const formatCurrency = (value) => {
    if(!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};
</script>

<template>
    <Head title="Application Details - Change of Unit" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Change of Unit Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Approved'">
                        <PrimaryButton @click="showChangeUnitModal = true" class="flex items-center gap-2">
                            Finalize Unit Change
                        </PrimaryButton>
                    </template>
                    <template v-else>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-yellow-500 text-white text-xs font-bold uppercase rounded-lg">Return</button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-red-600 text-white text-xs font-bold uppercase rounded-lg">Reject</button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 text-white text-xs font-bold uppercase rounded-lg">Approve</button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex gap-4 h-full min-h-0">
                <div class="w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-blue-100 text-blue-800">{{ application.status }}</span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.type }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4">
                         <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold text-gray-400 mb-3 overflow-hidden">
                                <span>{{ application.applicant.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold">{{ application.applicant.first_name }} {{ application.applicant.last_name }}</h2>
                        </div>
                    </div>
                </div>
                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">  
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6">
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Evaluation</button>
                        <button @click="activeTab = 'inspection'" :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Inspection</button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6">
                        <div v-if="activeTab === 'inspection'" class="space-y-6">
                             <div v-for="(item, index) in application.inspection_requirements" :key="item.id" class="p-4 bg-white border border-gray-200 rounded flex justify-between">
                                <div><p class="font-bold">{{ item.name }}</p><p class="text-xs text-gray-500">{{ item.remarks }}</p></div>
                                <span class="font-bold">{{ item.status }}</span>
                            </div>
                        </div>

                        <div v-if="activeTab === 'receipt'" class="bg-white p-6 border border-gray-200 rounded">
                            <h3 class="font-bold">OR #{{ application.receipt.or_number }}</h3>
                            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between font-bold">
                                <span>Total</span>
                                <span>{{ formatCurrency(application.receipt.total_amount_due) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ChangeOfUnitModal :show="showChangeUnitModal" :application="application" @close="showChangeUnitModal = false" />

    </AuthenticatedLayout>
</template>