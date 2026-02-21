<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- ADDED: Import Pagination Component
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

// --- PROPS ---
const props = defineProps({
    assessments: Object,    
    filters: Object,
    particulars: Array,
    franchises: Array
});

// --- STATE ---
const showAddModal = ref(false);
const showParticularsModal = ref(false);
const showViewModal = ref(false);
const showFilterModal = ref(false);
const selectedAssessment = ref(null);

// --- FILTERS STATE ---
const search = ref(props.filters.search || '');
const filterForm = ref({
    status: props.filters.status || '',
    franchise_id: props.filters.franchise_id || ''
});

// --- COMPUTED: GROUPING ---
const renewalParticulars = computed(() => props.particulars.filter(p => p.group === 'renewal'));
const unitParticulars = computed(() => props.particulars.filter(p => p.group === 'change_of_unit'));
const ownerParticulars = computed(() => props.particulars.filter(p => p.group === 'change_of_owner'));
const otherParticulars = computed(() => props.particulars.filter(p => !p.group && !p.is_system)); 
// Note: We deliberately exclude 'penalty' group from manual selection now.

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

const getFranchiseLabel = (f) => {
    let label = `Franchise #${f.franchise_number || f.id}`;
    if (f.status) label += ` [${f.status.toUpperCase()}]`;
    if (f.last_name) label += ` - ${f.last_name}, ${f.first_name}`;
    else label += ' - (No Active Owner)';
    return label;
};

// --- SEARCH & FILTER ---
const handleSearch = () => {
    router.get(route('admin.assessments.index'), { 
        search: search.value,
        status: filterForm.value.status,
        franchise_id: filterForm.value.franchise_id
    }, { preserveState: true, replace: true, preserveScroll: true });
};
const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;
const applyFilters = () => { handleSearch(); closeFilterModal(); };
const resetFilters = () => {
    filterForm.value.status = '';
    filterForm.value.franchise_id = '';
    search.value = '';
    applyFilters();
};

// --- VIEW ACTION ---
const openViewModal = (assessment) => {
    selectedAssessment.value = assessment;
    showViewModal.value = true;
};

// --- FORM: ADD ASSESSMENT ---
const createLineItem = () => ({ particular_id: '', description: '', quantity: 1, amount: 0, subtotal: 0 });

const addForm = useForm({
    franchise_id: '',
    assessment_date: new Date().toISOString().split('T')[0],
    assessment_due: '',
    remarks: '',
    items: [createLineItem()],
    total_amount_due: 0 // Frontend Estimate
});

// Check if penalty applies (Visual feedback only)
const isLate = computed(() => {
    if (!addForm.assessment_date || !addForm.assessment_due) return false;
    return new Date(addForm.assessment_date) > new Date(addForm.assessment_due);
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
const removeItemRow = (index) => { if (addForm.items.length > 1) addForm.items.splice(index, 1); };

const submitAdd = () => {
    addForm.post(route('admin.assessments.store'), { onSuccess: () => closeAddModal() });
};
const closeAddModal = () => { showAddModal.value = false; addForm.reset(); };
const openAddModal = () => showAddModal.value = true;

// --- FORM: PARTICULARS ---
const particularForm = useForm({ id: null, name: '', amount: '', description: '', group: '', is_system: false });
const isEditingParticular = ref(false);
const resetParticularForm = () => { particularForm.reset(); particularForm.clearErrors(); isEditingParticular.value = false; };

const editParticular = (p) => {
    particularForm.id = p.id;
    particularForm.name = p.name;
    particularForm.amount = p.amount;
    particularForm.description = p.description;
    particularForm.group = p.group || '';
    particularForm.is_system = Boolean(p.is_system);
    isEditingParticular.value = true;
};

const submitParticular = () => {
    const routeName = isEditingParticular.value ? 'admin.particulars.update' : 'admin.particulars.store';
    const params = isEditingParticular.value ? particularForm.id : undefined;
    const method = isEditingParticular.value ? 'put' : 'post';
    particularForm[method](route(routeName, params), { onSuccess: () => resetParticularForm(), preserveScroll: true });
};

const deleteParticular = (p) => {
    if (p.is_system) return alert("System particulars cannot be deleted.");
    if (confirm('Delete this fee type?')) router.delete(route('admin.particulars.destroy', p.id), { preserveScroll: true });
};

const getGroupBadgeClass = (group) => {
    switch(group) {
        case 'renewal': return 'bg-blue-100 text-blue-800';
        case 'change_of_unit': return 'bg-purple-100 text-purple-800';
        case 'change_of_owner': return 'bg-orange-100 text-orange-800';
        case 'penalty': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
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

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <input 
                        v-model="search" @keyup.enter="handleSearch" type="text" 
                        class="pl-3 pr-4 py-2 border-gray-300 rounded-lg block w-full sm:w-64 shadow-sm text-sm" 
                        placeholder="Search ID or Remarks..." 
                    />
                </div>
                <button @click="openFilterModal" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm relative">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span v-if="filterForm.status || filterForm.franchise_id" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                </button>
                <SecondaryButton @click="showParticularsModal = true">Particulars</SecondaryButton>
                <PrimaryButton @click="openAddModal">New Assessment</PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col min-h-[500px]">
            <div class="overflow-x-auto flex-grow">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Application</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Due Date</th>
                            <th class="px-6 py-4 text-right">Balance</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="assessment in assessments.data" :key="assessment.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="text-blue-600 font-bold">#{{ assessment.id }}</span>
                                <div v-if="assessment.remarks" class="text-xs text-gray-400 italic truncate max-w-[150px]">{{ assessment.remarks }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ assessment.application_id ? `Application #${assessment.application_id}` : 'Unassigned' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium uppercase"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': assessment.assessment_status === 'pending',
                                        'bg-green-100 text-green-800': assessment.assessment_status === 'paid',
                                        'bg-red-100 text-red-800': assessment.assessment_status === 'overdue'
                                    }">
                                    {{ assessment.assessment_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4" :class="assessment.assessment_status === 'overdue' ? 'text-red-600 font-bold' : 'text-gray-600'">
                                {{ assessment.assessment_due }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-900">
                                {{ formatCurrency(getBalance(assessment)) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openViewModal(assessment)" class="text-gray-400 hover:text-blue-600 font-medium">View</button>
                            </td>
                        </tr>
                        <tr v-if="assessments.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">No assessments found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex items-center justify-between" v-if="assessments.links.length > 3">
                <div class="text-xs text-gray-500">
                    Page {{ assessments.current_page }} of {{ assessments.last_page }}
                </div>
                
                <Pagination :links="assessments.links" />
            </div>
        </div>

        <Modal :show="showAddModal" @close="closeAddModal" maxWidth="4xl">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">New Assessment</h2>
                    <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div>
                            <InputLabel>Franchise *</InputLabel>
                            <select v-model="addForm.franchise_id" class="mt-1 block w-full text-sm border-gray-300 rounded-md">
                                <option value="" disabled>Select</option>
                                <option v-for="f in franchises" :key="f.id" :value="f.id">{{ getFranchiseLabel(f) }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel>Issued *</InputLabel>
                            <TextInput type="date" class="mt-1 block w-full" v-model="addForm.assessment_date" required />
                        </div>
                        <div>
                            <InputLabel>Due Date *</InputLabel>
                            <TextInput type="date" class="mt-1 block w-full" v-model="addForm.assessment_due" required />
                        </div>
                        <div class="md:col-span-3">
                            <InputLabel>Remarks</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.remarks" placeholder="Description..." />
                        </div>
                    </div>

                    <div v-if="isLate" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <span class="font-bold">Notice:</span> 
                                    The "Date Issued" is after the "Due Date". 
                                    <span class="font-bold">Surcharge and Interest</span> 
                                    will be automatically calculated and applied upon saving if Renewal Fees are present.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-gray-700 uppercase">Particulars *</h3>
                            <button type="button" @click="addItemRow" class="text-xs text-blue-600 hover:text-blue-800 font-bold">+ Add Item</button>
                        </div>
                        
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in addForm.items" :key="index">
                                        <td class="px-4 py-2">
                                            <select v-model="item.particular_id" required class="block w-full text-sm border-gray-300 rounded-md">
                                                <option value="" disabled>Select Particular</option>
                                                <optgroup v-if="renewalParticulars.length" label="Renewal Fees">
                                                    <option v-for="p in renewalParticulars" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                </optgroup>
                                                <optgroup v-if="unitParticulars.length" label="Change of Unit">
                                                    <option v-for="p in unitParticulars" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                </optgroup>
                                                <optgroup v-if="ownerParticulars.length" label="Change of Owner">
                                                    <option v-for="p in ownerParticulars" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                </optgroup>
                                                <optgroup v-if="otherParticulars.length" label="Other Fees">
                                                    <option v-for="p in otherParticulars" :key="p.id" :value="p.id">{{ p.name }}</option>
                                                </optgroup>
                                            </select>
                                        </td>
                                        <td class="px-4 py-2 text-right text-sm text-gray-700 font-mono">{{ item.amount > 0 ? formatCurrency(item.amount) : '-' }}</td>
                                        <td class="px-4 py-2"><input type="number" min="1" v-model="item.quantity" required class="block w-full text-center text-sm border-gray-300 rounded-md" /></td>
                                        <td class="px-4 py-2 text-right font-medium">{{ formatCurrency(item.subtotal) }}</td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItemRow(index)" class="text-red-400 hover:text-red-600">✕</button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">Estimated Total:</td>
                                        <td class="px-4 py-3 text-right font-bold text-blue-600 text-lg">{{ formatCurrency(addForm.total_amount_due) }}</td>
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

        <Modal :show="showParticularsModal" @close="showParticularsModal = false" maxWidth="2xl">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Manage Particulars</h2>
                    <button @click="showParticularsModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form @submit.prevent="submitParticular" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase">{{ isEditingParticular ? 'Edit' : 'Add' }} Particular</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="md:col-span-2">
                            <InputLabel value="Name" />
                            <TextInput type="text" v-model="particularForm.name" class="w-full text-sm" required />
                        </div>
                        <div>
                            <InputLabel value="Amount" />
                            <TextInput type="number" step="0.01" v-model="particularForm.amount" class="w-full text-sm font-mono" :disabled="particularForm.is_system" :class="{'bg-gray-100': particularForm.is_system}" required />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Description" />
                            <TextInput type="text" v-model="particularForm.description" class="w-full text-sm" />
                        </div>
                        <div>
                            <InputLabel value="Group" />
                            <select v-model="particularForm.group" class="w-full text-sm border-gray-300 rounded-md" :disabled="particularForm.is_system" :class="{'bg-gray-100': particularForm.is_system}">
                                <option value="">None (Optional)</option>
                                <option value="renewal">Renewal Fee</option>
                                <option value="change_of_unit">Change of Unit</option>
                                <option value="change_of_owner">Change of Owner</option>
                                <option value="penalty" disabled>Penalty (System)</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2 md:col-span-3">
                            <PrimaryButton :disabled="particularForm.processing">{{ isEditingParticular ? 'Update' : 'Add' }}</PrimaryButton>
                            <SecondaryButton v-if="isEditingParticular" @click="resetParticularForm" type="button">Cancel</SecondaryButton>
                        </div>
                    </div>
                </form>
                <div class="overflow-y-auto max-h-64 border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-right w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="p in particulars" :key="p.id" class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ p.name }}</span>
                                        <span v-if="p.group" class="text-[10px] uppercase font-bold px-1.5 rounded" :class="getGroupBadgeClass(p.group)">{{ p.group.replace(/_/g, ' ') }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ p.description }}</div>
                                </td>
                                <td class="px-4 py-2 text-right font-mono font-bold">{{ p.group === 'penalty' ? 'Calculated' : formatCurrency(p.amount) }}</td>
                                <td class="px-4 py-2 text-right flex justify-end gap-2">
                                    <button @click="editParticular(p)" class="text-blue-600 hover:bg-blue-50 p-1 rounded">Edit</button>
                                    <button v-if="!p.is_system" @click="deleteParticular(p)" class="text-red-600 hover:bg-red-50 p-1 rounded">Del</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Modal>

        <Modal :show="showViewModal" @close="showViewModal = false" maxWidth="2xl">
            <div v-if="selectedAssessment" class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Assessment Details</h2>
                    <button @click="showViewModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
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

                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-6">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-500">Description</th>
                                <th class="px-4 py-2 text-center font-medium text-gray-500">Qty</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in selectedAssessment.particulars" :key="item.id">
                                <td class="px-4 py-2 text-gray-900 font-medium">{{ item.name }}</td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ item.pivot.quantity }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 font-mono font-bold">{{ formatCurrency(item.pivot.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-blue-50">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-right font-bold text-blue-900 uppercase text-xs tracking-wider pt-4">Total Amount Due</td>
                                <td class="px-4 py-3 text-right font-bold text-blue-700 text-xl font-mono pt-3">
                                    {{ formatCurrency(selectedAssessment.total_amount_due) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="flex justify-end">
                    <SecondaryButton @click="showViewModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showFilterModal" @close="closeFilterModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter</h2>
                    <button @click="closeFilterModal" class="text-gray-400">✕</button>
                </div>
                <div class="space-y-4">
                    <div>
                        <InputLabel>Status</InputLabel>
                        <select v-model="filterForm.status" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="resetFilters">Reset</SecondaryButton>
                    <PrimaryButton @click="applyFilters">Apply</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>