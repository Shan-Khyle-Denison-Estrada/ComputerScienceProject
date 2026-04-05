<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import AssessmentPrint from '@/Components/AssessmentPrint.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue';

// --- PROPS ---
const props = defineProps({
    assessments: Object,    
    filters: Object,
    particulars: Array,
    franchises: Array,
    userRole: String,
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
const newFranchiseParticulars = computed(() => props.particulars.filter(p => p.group === 'new_franchise'));
const otherParticulars = computed(() => props.particulars.filter(p => !p.group && !p.is_system));

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

// --- NEW HELPER: Get Operator Name ---
const getOperatorName = (assessment) => {
    if (!assessment || !assessment.franchise_id) return 'N/A';
    const franchise = props.franchises.find(f => f.id === assessment.franchise_id);
    return franchise?.owner_name || 'N/A';
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

// --- VIEW & PRINT ACTIONS ---
const openViewModal = (assessment) => {
    selectedAssessment.value = assessment;
    showViewModal.value = true;
};

const printAssessment = async (assessment) => {
    selectedAssessment.value = assessment;
    const wasViewModalOpen = showViewModal.value;
    
    if (wasViewModalOpen) {
        showViewModal.value = false;
    }
    
    await nextTick();
    
    setTimeout(() => {
        window.print();
        if (wasViewModalOpen) {
            showViewModal.value = true;
        }
    }, 300);
};

// --- FORM: ADD ASSESSMENT ---
const createLineItem = () => ({ particular_id: '', description: '', quantity: 1, amount: 0, subtotal: 0 });

const addForm = useForm({
    franchise_id: '',
    assessment_date: new Date().toISOString().split('T')[0],
    assessment_due: '',
    remarks: '',
    items: [createLineItem()],
    total_amount_due: 0 
});

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

const submitAdd = () => {
    addForm.post(route('admin.assessments.store'), { onSuccess: () => closeAddModal() });
};
const closeAddModal = () => { showAddModal.value = false; addForm.reset(); };
const openAddModal = () => {
    addForm.reset();
    franchiseQuery.value = ''; 
    showAddModal.value = true;
};

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
    const routeName = isEditingParticular.value ? 'admin.particulars.store' : 'admin.particulars.store';
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
        case 'new_franchise': return 'bg-teal-100 text-teal-800';
        case 'new_driver': return 'bg-green-100 text-green-800';
        case 'penalty': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const calculateSubtotal = (item) => {
    if (!item.particular_id) return 0;
    const particular = props.particulars.find(p => p.id === item.particular_id);
    return particular ? (particular.amount * (item.quantity || 1)) : 0;
};

const calculateTotal = () => {
    return addForm.items.reduce((total, item) => total + calculateSubtotal(item), 0);
};

// --- SEARCHABLE FRANCHISE DROPDOWN STATE ---
const franchiseQuery = ref('');
const showFranchiseDropdown = ref(false);
const dropdownContainer = ref(null); 

const filteredFranchises = computed(() => {
    if (!franchiseQuery.value) return props.franchises;
    const query = franchiseQuery.value.toLowerCase();
    
    return props.franchises.filter(f => {
        const mtop = String(f.id).toLowerCase();
        const owner = String(f.owner_name || '').toLowerCase();
        return owner.includes(query) || mtop.includes(query);
    });
});

const selectFranchise = (franchise) => {
    addForm.franchise_id = franchise.id;
    franchiseQuery.value = `${franchise.id} - ${franchise.owner_name}`;
    showFranchiseDropdown.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
        showFranchiseDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head title="Manage Assessments" />

    <div class="print:hidden">
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
                    <SecondaryButton v-if="userRole === 'admin'" @click="showParticularsModal = true">Particulars</SecondaryButton>
                    <PrimaryButton v-if="['evaluator', 'encoder', 'admin'].includes(userRole)" @click="openAddModal">New Assessment</PrimaryButton>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col min-h-[500px]">
                <div class="overflow-x-auto flex-grow">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Franchise</th>
                                <th class="px-6 py-4">Operator</th>
                                <th class="px-6 py-4">Application</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Due Date</th>
                                <th class="px-6 py-4 text-right">Balance</th>
                                <th class="px-6 py-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="assessment in assessments.data" :key="assessment.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="text-blue-600 font-bold">#{{ assessment.id }}</span>
                                    <div v-if="assessment.remarks" class="text-xs text-gray-400 italic truncate max-w-[150px]">{{ assessment.remarks }}</div>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <span v-if="assessment.franchise?.franchise_number">
                                        {{ assessment.franchise?.franchise_number }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs italic">N/A</span>
                                </td>
                                <td class="px-6 py-4 text-gray-900">
                                    {{ getOperatorName(assessment) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="assessment.application" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ assessment.application.reference_number }}
                                    </span>
                                    <span v-else class="text-gray-400 text-xs italic">N/A</span>
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
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="printAssessment(assessment)" class="text-gray-400 hover:text-green-600 font-medium">Print</button>
                                        <button @click="openViewModal(assessment)" class="text-gray-400 hover:text-blue-600 font-medium">View</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="assessments.data.length === 0">
                                <td colspan="8" class="px-6 py-10 text-center text-gray-500">No assessments found.</td>
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

            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    
                    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="closeAddModal"></div>

                    <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] md:max-h-[85vh] transform transition-all">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 shrink-0">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900" id="modal-title">Create Assessment</h2>
                                <p class="text-sm text-gray-500">Draft a new payment assessment for a franchise.</p>
                            </div>
                            <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-2 rounded-full transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1 min-h-0 bg-white">
                            <form @submit.prevent="submitAdd" class="space-y-6">
                                <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                                    <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        General Information
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="">
                                            <InputLabel>Target Franchise <span class="text-red-500">*</span></InputLabel>
                                            <div class="relative" ref="dropdownContainer">
                                                <input 
                                                    type="text" 
                                                    v-model="franchiseQuery" 
                                                    @focus="showFranchiseDropdown = true" 
                                                    class="mt-1 block w-full border-gray-300 rounded-md text-sm" 
                                                    placeholder="Search Franchise or Owner Name..."
                                                    required
                                                />
                                                <ul v-if="showFranchiseDropdown" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                                                    <li v-if="filteredFranchises.length === 0" class="p-2 text-sm text-gray-500">
                                                        No franchises found
                                                    </li>
                                                    <li 
                                                        v-for="franchise in filteredFranchises" 
                                                        :key="franchise.id" 
                                                        @click="selectFranchise(franchise)"
                                                        class="p-2 text-sm hover:bg-indigo-50 cursor-pointer text-gray-700"
                                                    >
                                                        {{ franchise.franchise_number }} - {{ franchise.owner_name }}
                                                    </li>
                                                </ul>
                                            </div>
                                            <input type="hidden" v-model="addForm.franchise_id" required />
                                        </div>

                                        <div>
                                            <InputLabel>Assessment Date <span class="text-red-500">*</span></InputLabel>
                                            <TextInput type="date" class="mt-1 block w-full bg-white" v-model="addForm.assessment_date" required />
                                        </div>

                                        <div class="col-span-1 md:col-span-2">
                                            <InputLabel>Remarks</InputLabel>
                                            <TextInput type="text" class="mt-1 block w-full bg-white" v-model="addForm.remarks" placeholder="e.g. Annual Renewal Assessment 2024" required />
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex justify-between items-center mb-3">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                            Particulars Breakdown
                                        </h4>
                                        <button type="button" @click="addForm.items.push({ particular_id: '', quantity: 1 })" class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            Add Item
                                        </button>
                                    </div>

                                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                        <div class="hidden md:grid grid-cols-12 gap-4 px-4 py-3 bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <div class="col-span-6">Particular / Fee</div>
                                            <div class="col-span-2">Quantity</div>
                                            <div class="col-span-3 text-right">Subtotal</div>
                                            <div class="col-span-1 text-center">Action</div>
                                        </div>
                                        
                                        <div class="divide-y divide-gray-100">
                                            <div v-for="(item, index) in addForm.items" :key="index" class="p-4 md:p-0">
                                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:items-center md:px-4 md:py-3">
                                                    
                                                    <div class="col-span-1 md:col-span-6">
                                                        <InputLabel class="md:hidden mb-1">Fee</InputLabel>
                                                        <select v-model="item.particular_id" class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm text-sm" required>
                                                            <option value="" disabled>Select particular...</option>
                                                            <optgroup label="Renewal Fees" v-if="renewalParticulars.length">
                                                                <option v-for="p in renewalParticulars" :key="p.id" :value="p.id">{{ p.name }} ({{ formatCurrency(p.amount) }})</option>
                                                            </optgroup>
                                                            <optgroup label="Change of Unit Fees" v-if="unitParticulars.length">
                                                                <option v-for="p in unitParticulars" :key="p.id" :value="p.id">{{ p.name }} ({{ formatCurrency(p.amount) }})</option>
                                                            </optgroup>
                                                            <optgroup label="Change of Owner Fees" v-if="ownerParticulars.length">
                                                                <option v-for="p in ownerParticulars" :key="p.id" :value="p.id">{{ p.name }} ({{ formatCurrency(p.amount) }})</option>
                                                            </optgroup>
                                                            <optgroup label="New Franchise Fees" v-if="newFranchiseParticulars.length">
                                                                <option v-for="p in newFranchiseParticulars" :key="p.id" :value="p.id">{{ p.name }} ({{ formatCurrency(p.amount) }})</option>
                                                            </optgroup>
                                                            <optgroup label="Other Fees" v-if="otherParticulars.length">
                                                                <option v-for="p in otherParticulars" :key="p.id" :value="p.id">{{ p.name }} ({{ formatCurrency(p.amount) }})</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>

                                                    <div class="col-span-1 md:col-span-2">
                                                        <InputLabel class="md:hidden mb-1">Quantity</InputLabel>
                                                        <TextInput type="number" min="1" v-model="item.quantity" class="block w-full text-center" required />
                                                    </div>

                                                    <div class="col-span-1 md:col-span-3 md:text-right font-mono text-gray-800 font-medium bg-gray-50 md:bg-transparent p-2 md:p-0 rounded border border-gray-200 md:border-0">
                                                        <span class="md:hidden text-xs text-gray-500 mr-2 uppercase">Subtotal:</span>
                                                        {{ formatCurrency(calculateSubtotal(item)) }}
                                                    </div>

                                                    <div class="col-span-1 md:col-span-1 flex justify-end md:justify-center">
                                                        <button type="button" @click="addForm.items.splice(index, 1)" :disabled="addForm.items.length === 1" class="text-red-400 hover:text-red-600 p-2 rounded-full hover:bg-red-50 transition-colors disabled:opacity-30 disabled:hover:bg-transparent">
                                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-100 px-6 py-4 border-t border-gray-200 flex justify-between items-center">
                                            <span class="font-bold text-gray-600 uppercase text-xs tracking-wider">Total Assessed Amount</span>
                                            <span class="text-xl font-bold font-mono text-blue-700">{{ formatCurrency(calculateTotal()) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 shrink-0">
                            <SecondaryButton type="button" @click="closeAddModal">Cancel</SecondaryButton>
                            <PrimaryButton @click="submitAdd" :disabled="addForm.processing || addForm.items.length === 0" class="flex items-center gap-2">
                                <span v-if="addForm.processing">Saving...</span>
                                <span v-else>Save Assessment</span>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </Transition>

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
                                    <option value="new_franchise">New Franchise</option>
                                    <option value="new_driver">New Driver</option>
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
                            <span class="block text-xs font-medium text-gray-500 uppercase">Franchise & Operator</span>
                            <span class="font-semibold text-gray-900 text-lg block">
                                {{ selectedAssessment.franchise_id ? `#${selectedAssessment.franchise_id}` : 'N/A' }}
                            </span>
                            <span class="text-gray-600">{{ getOperatorName(selectedAssessment) }}</span>
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
                    
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showViewModal = false">Close</SecondaryButton>
                        <PrimaryButton @click="printAssessment(selectedAssessment)">Print Assessment</PrimaryButton>
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
    </div>

    <div class="hidden print:block print:w-full">
        <AssessmentPrint 
            v-if="selectedAssessment" 
            :assessment="selectedAssessment" 
            :operator-name="getOperatorName(selectedAssessment)"
        />
    </div>
</template>