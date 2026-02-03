<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// --- PROPS ---
const props = defineProps({
    assessments: Object,    
    filters: Object,
    particulars: Array      
});

// --- STATE ---
const showAddModal = ref(false);
const showParticularsModal = ref(false);
const showViewModal = ref(false);
const search = ref(props.filters.search || '');
const selectedAssessment = ref(null);

// --- HELPERS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

const getBalance = (assessment) => {
    const totalPaid = assessment.payments 
        ? assessment.payments.reduce((sum, p) => sum + parseFloat(p.amount_paid), 0) 
        : 0;
    const balance = assessment.total_amount_due - totalPaid;
    return balance > 0 ? balance : 0;
};

// --- VIEW ACTION ---
const openViewModal = (assessment) => {
    selectedAssessment.value = assessment;
    showViewModal.value = true;
};

// --- FORM: ADD ASSESSMENT ---
const createLineItem = () => ({
    particular_id: '',
    description: '',
    quantity: 1,
    amount: 0,
    subtotal: 0
});

const addForm = useForm({
    franchise_id: '',
    assessment_date: new Date().toISOString().split('T')[0],
    assessment_due: '',
    remarks: '',
    items: [createLineItem()],
    total_amount_due: 0
});

watch(() => addForm.items, (items) => {
    let total = 0;
    items.forEach(item => {
        if (item.particular_id) {
            const masterItem = props.particulars.find(p => p.id === item.particular_id);
            if (masterItem) {
                if (item.amount === 0 || item.amount !== masterItem.amount) {
                     item.amount = masterItem.amount;
                }
                item.description = masterItem.description;
            }
        }
        item.subtotal = (item.quantity || 0) * (item.amount || 0);
        total += item.subtotal;
    });
    addForm.total_amount_due = total;
}, { deep: true });

const addItemRow = () => addForm.items.push(createLineItem());
const removeItemRow = (index) => {
    if (addForm.items.length > 1) addForm.items.splice(index, 1);
};

const submitAdd = () => {
    addForm.post(route('admin.assessments.store'), {
        onSuccess: () => closeAddModal(),
    });
};

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
};

const openAddModal = () => showAddModal.value = true;

// --- FORM: PARTICULARS MANAGEMENT ---
const particularForm = useForm({
    id: null,
    name: '',
    amount: '',
    description: ''
});
const isEditingParticular = ref(false);

const resetParticularForm = () => {
    particularForm.reset();
    particularForm.clearErrors();
    isEditingParticular.value = false;
};

const editParticular = (p) => {
    particularForm.id = p.id;
    particularForm.name = p.name;
    particularForm.amount = p.amount;
    particularForm.description = p.description;
    isEditingParticular.value = true;
};

const submitParticular = () => {
    const routeName = isEditingParticular.value ? 'admin.particulars.update' : 'admin.particulars.store';
    const routeParams = isEditingParticular.value ? particularForm.id : undefined;
    const method = isEditingParticular.value ? 'put' : 'post';

    particularForm[method](route(routeName, routeParams), {
        onSuccess: () => resetParticularForm(),
        preserveScroll: true
    });
};

const deleteParticular = (id) => {
    if (confirm('Are you sure you want to delete this fee type?')) {
        router.delete(route('admin.particulars.destroy', id), { preserveScroll: true });
    }
};

// --- SEARCH ---
const handleSearch = () => {
    router.get(route('admin.assessments.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Manage Assessments" />

    <AuthenticatedLayout>
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Assessments</h1>
                <p class="text-gray-600 text-sm">Create and manage fee assessments.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="search"
                        @keyup.enter="handleSearch"
                        type="text" 
                        class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" 
                        placeholder="Search ID..." 
                    />
                </div>

                <SecondaryButton @click="showParticularsModal = true" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Particulars
                </SecondaryButton>

                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H6a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z" />
                    </svg>
                    New Assessment
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Assessment ID</th>
                            <th class="px-6 py-4">Franchise</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Dates</th>
                            <th class="px-6 py-4 text-right">Balance / Total</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="assessment in assessments.data" :key="assessment.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-blue-600 font-bold">#{{ assessment.id }}</span>
                                <div v-if="assessment.remarks" class="text-xs text-gray-400 mt-1 italic">{{ assessment.remarks }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ assessment.franchise_id ? `Franchise #${assessment.franchise_id}` : 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wide"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': assessment.assessment_status === 'pending',
                                        'bg-green-100 text-green-800': assessment.assessment_status === 'paid',
                                        'bg-red-100 text-red-800': assessment.assessment_status === 'overdue'
                                    }"
                                >
                                    {{ assessment.assessment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">Issued: {{ assessment.assessment_date }}</div>
                                <div 
                                    class="text-xs font-medium"
                                    :class="assessment.assessment_status === 'overdue' ? 'text-red-600 font-bold' : 'text-gray-500'"
                                >
                                    Due: {{ assessment.assessment_due }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-gray-900 text-lg">
                                    {{ formatCurrency(getBalance(assessment)) }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    of {{ formatCurrency(assessment.total_amount_due) }} Total
                                </div>
                            </td>
                             <td class="px-6 py-4 text-right">
                                <button 
                                    @click="openViewModal(assessment)"
                                    class="text-gray-400 hover:text-blue-600 font-medium transition-colors"
                                >
                                    View
                                </button>
                            </td>
                        </tr>
                        <tr v-if="assessments.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                No assessments found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="assessments.links && assessments.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ assessments.meta.from }} to {{ assessments.meta.to }} of {{ assessments.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="(link, key) in assessments.meta.links" 
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border rounded-md text-xs transition-colors"
                        :class="[
                            link.active ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <Modal :show="showViewModal" @close="showViewModal = false" maxWidth="2xl">
            <div v-if="selectedAssessment" class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Assessment Details</h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm text-gray-500">ID: #{{ selectedAssessment.id }}</span>
                            <span 
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wide"
                                :class="{
                                    'bg-yellow-100 text-yellow-800': selectedAssessment.assessment_status === 'pending',
                                    'bg-green-100 text-green-800': selectedAssessment.assessment_status === 'paid',
                                    'bg-red-100 text-red-800': selectedAssessment.assessment_status === 'overdue'
                                }"
                            >
                                {{ selectedAssessment.assessment_status }}
                            </span>
                        </div>
                    </div>
                    <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <span class="block text-xs font-medium text-gray-500 uppercase">Franchise</span>
                        <span class="font-semibold text-gray-900 text-lg">
                            {{ selectedAssessment.franchise_id ? `#${selectedAssessment.franchise_id}` : 'N/A' }}
                        </span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <span class="block text-xs font-medium text-gray-500 uppercase">Dates</span>
                        <div class="flex justify-between mt-1">
                            <span class="text-gray-600">Issued: {{ selectedAssessment.assessment_date }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-red-600 font-medium">Due: {{ selectedAssessment.assessment_due }}</span>
                        </div>
                    </div>
                    <div class="col-span-2">
                         <span class="block text-xs font-medium text-gray-500 uppercase mb-1">Remarks</span>
                         <p class="text-gray-700 italic">{{ selectedAssessment.remarks || 'No remarks provided.' }}</p>
                    </div>
                </div>

                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-2 border-b border-gray-200 pb-1">
                    Bill of Particulars
                </h3>
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Description</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-500">Price</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in selectedAssessment.particulars" :key="item.id">
                                <td class="px-4 py-2 text-gray-900 font-medium">{{ item.name }}</td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ item.pivot.quantity }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 font-mono">{{ formatCurrency(item.amount) }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 font-mono font-bold">{{ formatCurrency(item.pivot.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-blue-50">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right font-bold text-blue-900 uppercase text-xs tracking-wider pt-4">Total Amount Due</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-700 text-xl font-mono pt-3">
                                    {{ formatCurrency(selectedAssessment.total_amount_due) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-2 border-b border-gray-200 pb-1 flex justify-between items-center">
                    <span>Payment History</span>
                    <span class="text-xs font-normal text-gray-500">
                        Paid: {{ formatCurrency(selectedAssessment.total_amount_due - getBalance(selectedAssessment)) }}
                    </span>
                </h3>
                <div class="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden mb-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Payee</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-500">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="pay in selectedAssessment.payments" :key="pay.id">
                                <td class="px-4 py-2 text-gray-600">{{ new Date(pay.created_at).toLocaleDateString() }}</td>
                                <td class="px-4 py-2 text-gray-600">{{ pay.payee_last_name }}, {{ pay.payee_first_name }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 font-mono font-medium">{{ formatCurrency(pay.amount_paid) }}</td>
                            </tr>
                            <tr v-if="!selectedAssessment.payments || selectedAssessment.payments.length === 0">
                                <td colspan="3" class="px-4 py-3 text-center text-gray-400 italic text-xs">No payments recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <SecondaryButton @click="showViewModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showParticularsModal" @close="showParticularsModal = false" maxWidth="2xl">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Manage Particulars</h2>
                        <p class="text-sm text-gray-500">Add or edit fees and charges available for assessments.</p>
                    </div>
                    <button @click="showParticularsModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitParticular" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wide">
                        {{ isEditingParticular ? 'Edit Particular' : 'Add New Particular' }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <TextInput type="text" v-model="particularForm.name" class="w-full text-sm" placeholder="Name (e.g. Registration Fee)" required />
                        </div>
                        <div>
                            <TextInput type="number" step="0.01" v-model="particularForm.amount" class="w-full text-sm font-mono" placeholder="Amount" required />
                        </div>
                        <div class="md:col-span-2">
                            <TextInput type="text" v-model="particularForm.description" class="w-full text-sm" placeholder="Description (Optional)" />
                        </div>
                        <div class="flex items-center gap-2">
                            <PrimaryButton :disabled="particularForm.processing" class="w-full justify-center">
                                {{ isEditingParticular ? 'Update' : 'Add' }}
                            </PrimaryButton>
                            <SecondaryButton v-if="isEditingParticular" @click="resetParticularForm" type="button">
                                Cancel
                            </SecondaryButton>
                        </div>
                    </div>
                </form>

                <div class="overflow-y-auto max-h-64 border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="p in particulars" :key="p.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <div class="font-medium">{{ p.name }}</div>
                                    <div class="text-xs text-gray-500">{{ p.description || 'No description' }}</div>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 text-right font-mono font-bold">
                                    {{ formatCurrency(p.amount) }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="editParticular(p)" 
                                            class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button 
                                            @click="deleteParticular(p.id)" 
                                            class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="particulars.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 italic">
                                    No particulars found. Add one above.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Modal>

        <Modal :show="showAddModal" @close="closeAddModal" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">New Assessment</h2>
                        <p class="text-sm text-gray-500">Create a new bill of particulars.</p>
                    </div>
                    <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-6">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div>
                            <InputLabel>Franchise ID <span class="text-gray-400 text-xs">(Optional)</span></InputLabel>
                            <TextInput type="number" class="mt-1 block w-full" v-model="addForm.franchise_id" placeholder="e.g. 101" />
                        </div>
                        <div>
                            <InputLabel>Date Issued <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="date" class="mt-1 block w-full" v-model="addForm.assessment_date" required />
                        </div>
                        <div>
                            <InputLabel>Due Date <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="date" class="mt-1 block w-full" v-model="addForm.assessment_due" required />
                        </div>
                        <div class="md:col-span-3">
                            <InputLabel>Remarks / Description</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.remarks" placeholder="e.g. Annual Renewal Fee" />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Particulars <span class="text-red-500">*</span></h3>
                            <button type="button" @click="addItemRow" class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Add Item
                            </button>
                        </div>
                        
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item <span class="text-red-500">*</span></th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Price</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Qty <span class="text-red-500">*</span></th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-32">Subtotal</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in addForm.items" :key="index">
                                        <td class="px-4 py-2">
                                            <select v-model="item.particular_id" required class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="" disabled>Select Particular</option>
                                                <option v-for="p in particulars" :key="p.id" :value="p.id">
                                                    {{ p.name }}
                                                </option>
                                            </select>
                                            <div v-if="item.description" class="text-xs text-gray-400 mt-1">{{ item.description }}</div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-right text-sm text-gray-700 font-mono py-2 bg-gray-50 rounded px-2 border border-transparent">
                                                {{ item.amount > 0 ? formatCurrency(item.amount) : '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" min="1" v-model="item.quantity" required class="block w-full text-center text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" />
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium text-gray-900">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItemRow(index)" class="text-red-400 hover:text-red-600" title="Remove Item">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">Total Amount Due:</td>
                                        <td class="px-4 py-3 text-right font-bold text-blue-600 text-lg">
                                            {{ formatCurrency(addForm.total_amount_due) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Save Assessment</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>