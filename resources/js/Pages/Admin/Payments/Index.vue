<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- ADDED: Import Pagination Component
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- PROPS ---
const props = defineProps({
    payments: Object,       // Paginated Payment Records
    filters: Object,        // Search filters
    barangays: Array,       // List of Barangays for Address
    assessments: Array      // List of Pending/Overdue Assessments [ {id, label, balance, ...} ]
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showFilterModal = ref(false);
const search = ref(props.filters.search || '');

// --- DROPDOWN STATE ---
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);

const assessmentQuery = ref('');
const showAssessmentDropdown = ref(false);

// --- COMPUTED FILTERS ---
// 1. Filter Barangays locally
const filteredBarangays = computed(() => {
    if (!barangayQuery.value) return props.barangays;
    return props.barangays.filter(b => 
        b.name.toLowerCase().includes(barangayQuery.value.toLowerCase())
    );
});

// 2. Filter Assessments locally (Search by ID or Label)
const filteredAssessments = computed(() => {
    if (!assessmentQuery.value) return props.assessments;
    return props.assessments.filter(a => 
        a.label.toLowerCase().includes(assessmentQuery.value.toLowerCase())
    );
});

// --- SELECTION ACTIONS ---
const selectBarangay = (name) => {
    addForm.payee_barangay = name;
    barangayQuery.value = name;
    showBarangayDropdown.value = false;
};

const selectAssessment = (assessment) => {
    addForm.assessment_id = assessment.id;
    assessmentQuery.value = assessment.label;
    
    // Auto-fill the amount with the remaining balance
    // Ensure we handle cases where balance might be weird, defaulting to 0 if negative
    addForm.amount_paid = assessment.balance > 0 ? assessment.balance : 0; 
    
    showAssessmentDropdown.value = false;
};

// --- FORMS ---
const addForm = useForm({
    assessment_id: '',
    amount_paid: '',
    payee_first_name: '',
    payee_middle_name: '',
    payee_last_name: '',
    payee_contact_number: '',
    payee_street_address: '',
    payee_barangay: '',
    payee_city: '',
});

const filterForm = ref({
    city: props.filters.city || '',
});

// --- HELPERS ---
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value);
};

// --- ACTIONS ---
const openAddModal = () => {
    showAddModal.value = true;
    // Reset local dropdown states
    barangayQuery.value = '';
    assessmentQuery.value = '';
};

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
    showBarangayDropdown.value = false;
    showAssessmentDropdown.value = false;
};

const submitAdd = () => {
    // Sync barangay text if user typed manually instead of clicking dropdown
    if (!addForm.payee_barangay && barangayQuery.value) {
        addForm.payee_barangay = barangayQuery.value;
    }
    
    addForm.post(route('admin.payments.store'), {
        onSuccess: () => closeAddModal(),
    });
};

const handleSearch = () => {
    router.get(route('admin.payments.index'), { 
        search: search.value, 
        city: filterForm.value.city 
    }, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true
    });
};

const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;

const applyFilters = () => {
    handleSearch();
    closeFilterModal();
};

const resetFilters = () => {
    filterForm.value.city = '';
    search.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Manage Payments" />

    <AuthenticatedLayout>
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Payment History</h1>
                <p class="text-gray-600 text-sm">Track incoming payments. Records are immutable.</p>
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
                        placeholder="Search payee..." 
                    />
                </div>

                <button 
                    @click="openFilterModal"
                    class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm transition-colors relative"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span v-if="filterForm.city" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                </button>

                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Record Payment
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Payee Details</th>
                            <th class="px-6 py-4">Location</th>
                            <th class="px-6 py-4">Assessment ID</th>
                            <th class="px-6 py-4">Amount</th>
                            <th class="px-6 py-4">Date Recorded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-green-100 flex items-center justify-center border border-gray-200 text-green-700 font-bold text-lg">
                                        ₱
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900">
                                            {{ payment.payee_last_name }}, {{ payment.payee_first_name }}
                                        </div>
                                        <div class="text-gray-500 text-xs">{{ payment.payee_contact_number || 'No contact' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="text-sm">{{ payment.payee_barangay }}, {{ payment.payee_city }}</div>
                                <div class="text-xs text-gray-400">{{ payment.payee_street_address }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="payment.assessment_id" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    #{{ payment.assessment_id }}
                                </span>
                                <span v-else class="text-gray-400 text-xs italic">
                                    N/A
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono font-medium text-gray-900">
                                {{ formatCurrency(payment.amount_paid) }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-xs">
                                {{ new Date(payment.created_at).toLocaleDateString() }}
                            </td>
                        </tr>
                        <tr v-if="payments.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No payment records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="payments.links && payments.links.length > 3">
                <div class="text-xs text-gray-500">
                    Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} results
                </div>
                
                <Pagination :links="payments.links" />
            </div>
        </div>

        <Modal :show="showAddModal" @close="closeAddModal">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Record Payment</h2>
                        <p class="text-sm text-gray-500">Create a new official payment record.</p>
                    </div>
                    <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-6">
                    
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Payee Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="col-span-12 md:col-span-4">
                                <InputLabel>Last Name <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_last_name" placeholder="Dela Cruz" required />
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <InputLabel>First Name <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_first_name" placeholder="Juan" required />
                            </div>
                            <div class="col-span-12 md:col-span-4">
                                <InputLabel>Middle Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_middle_name" />
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <InputLabel>Contact Number</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_contact_number" placeholder="09xxxxxxxxx" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Address Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <InputLabel>Street Address <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_street_address" placeholder="House No., Street Name" required />
                            </div>
                            
                            <div class="col-span-12 md:col-span-6 relative">
                                <InputLabel>Barangay <span class="text-red-500">*</span></InputLabel>
                                <TextInput 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    v-model="barangayQuery" 
                                    @focus="showBarangayDropdown = true"
                                    @input="showBarangayDropdown = true"
                                    placeholder="Select Barangay..." 
                                    autocomplete="off"
                                    required
                                />
                                <div v-if="showBarangayDropdown && filteredBarangays.length > 0" class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded-md shadow-lg max-h-40 overflow-y-auto">
                                    <div 
                                        v-for="barangay in filteredBarangays" 
                                        :key="barangay.id"
                                        @click="selectBarangay(barangay.name)"
                                        class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-gray-700"
                                    >
                                        {{ barangay.name }}
                                    </div>
                                </div>
                                <div v-if="showBarangayDropdown" @click="showBarangayDropdown = false" class="fixed inset-0 z-0 bg-transparent cursor-default"></div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <InputLabel>City <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.payee_city" required />
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Payment Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            
                            <div class="col-span-12 md:col-span-6 relative">
                                <InputLabel>Assessment (Optional)</InputLabel>
                                <TextInput 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    v-model="assessmentQuery" 
                                    @focus="showAssessmentDropdown = true"
                                    @input="showAssessmentDropdown = true"
                                    placeholder="Search Pending Assessment..." 
                                    autocomplete="off"
                                />
                                <div v-if="showAssessmentDropdown && filteredAssessments.length > 0" class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                    <div 
                                        v-for="assessment in filteredAssessments" 
                                        :key="assessment.id"
                                        @click="selectAssessment(assessment)"
                                        class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm text-gray-700 border-b border-gray-100 last:border-0"
                                    >
                                        {{ assessment.label }}
                                    </div>
                                </div>
                                <div v-else-if="showAssessmentDropdown && assessmentQuery && filteredAssessments.length === 0" class="absolute z-10 w-full bg-white border border-gray-300 mt-1 rounded-md shadow-lg p-2 text-sm text-gray-500">
                                    No pending assessments found.
                                </div>
                                
                                <div v-if="showAssessmentDropdown" @click="showAssessmentDropdown = false" class="fixed inset-0 z-0 bg-transparent cursor-default"></div>
                            </div>

                            <div class="col-span-12 md:col-span-6">
                                <InputLabel>Amount Paid (PHP) <span class="text-red-500">*</span></InputLabel>
                                <div class="relative rounded-md shadow-sm mt-1">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <TextInput 
                                        type="number" 
                                        step="0.01" 
                                        class="block w-full pl-7 font-mono font-medium text-gray-900" 
                                        v-model="addForm.amount_paid" 
                                        placeholder="0.00" 
                                        required 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Save Payment Record</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showFilterModal" @close="closeFilterModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter Payments</h2>
                    <button @click="closeFilterModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel>Filter by City</InputLabel>
                        <TextInput type="text" class="mt-1 block w-full" v-model="filterForm.city" placeholder="e.g. Polomolok" />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="resetFilters">Reset</SecondaryButton>
                    <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>