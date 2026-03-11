<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue'; 
import ReceiptPrint from '@/Components/ReceiptPrint.vue'; 
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onMounted } from 'vue'; // <-- ADDED onMounted

// --- PROPS ---
const props = defineProps({
    payments: Object,       
    filters: Object,        
    barangays: Array,       
    assessments: Array,
    userRole: String,
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showFilterModal = ref(false);
const showViewModal = ref(false); 
const selectedPayment = ref(null); 
const search = ref(props.filters.search || '');

// --- DROPDOWN STATE ---
const assessmentQuery = ref('');
const showAssessmentDropdown = ref(false);
const selectedAssessmentDetails = ref(null); 

const activeDropdown = ref(null);

// --- API State for Addresses ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load locations:", error);
    }
});

// --- COMPUTED FILTERS ---
const filteredAssessments = computed(() => {
    if (!assessmentQuery.value) return props.assessments;
    const query = assessmentQuery.value.toLowerCase();
    
    return props.assessments.filter(a => {
        const ref = a.reference_number || a.application_reference_id || `ASM-${a.id}`;
        const matchRef = ref.toLowerCase().includes(query);
        const matchLabel = a.label && a.label.toLowerCase().includes(query);
        return matchRef || matchLabel;
    });
});

const filteredProvinces = computed(() => {
    if (!addForm.payee_province) return provincesList.value;
    return provincesList.value.filter(p => p.name.toLowerCase().includes(addForm.payee_province.toLowerCase()));
});

const filteredCities = computed(() => {
    if (!addForm.payee_city) return citiesList.value;
    return citiesList.value.filter(c => c.name.toLowerCase().includes(addForm.payee_city.toLowerCase()));
});

const filteredBarangays = computed(() => {
    if (!addForm.payee_barangay) return barangaysList.value;
    return barangaysList.value.filter(b => b.name.toLowerCase().includes(addForm.payee_barangay.toLowerCase()));
});

// --- WATCHERS ---
watch(assessmentQuery, (newVal) => {
    if (selectedAssessmentDetails.value) {
        const currentRef = selectedAssessmentDetails.value.reference_number 
            || selectedAssessmentDetails.value.application_reference_id 
            || `ASM-${selectedAssessmentDetails.value.id}`;
            
        if (newVal !== currentRef) {
            selectedAssessmentDetails.value = null;
            addForm.assessment_id = '';
            addForm.amount_paid = ''; 
        }
    }
});

// --- SELECTION ACTIONS ---
const selectAssessment = (assessment) => {
    addForm.assessment_id = assessment.id;
    assessmentQuery.value = assessment.reference_number 
        || assessment.application_reference_id 
        || `ASM-${assessment.id}`;
    
    addForm.amount_paid = ''; 
    selectedAssessmentDetails.value = assessment;
    showAssessmentDropdown.value = false;
};

const selectProvince = async (prov) => {
    addForm.payee_province = prov.name;
    addForm.clearErrors('payee_province');
    activeDropdown.value = null;
    
    addForm.payee_city = '';
    addForm.payee_barangay = '';
    citiesList.value = [];
    barangaysList.value = [];

    try {
        if (prov.isNCR) {
            const res = await fetch(`https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        } else {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        }
    } catch (error) {
        console.error("Failed to load cities:", error);
    }
};

const selectCity = async (city) => {
    addForm.payee_city = city.name;
    addForm.clearErrors('payee_city');
    activeDropdown.value = null;
    
    addForm.payee_barangay = '';
    barangaysList.value = [];

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
        barangaysList.value = await res.json();
    } catch (error) {
        console.error("Failed to load barangays:", error);
    }
};

const selectBarangay = (brgy) => {
    addForm.payee_barangay = brgy.name;
    addForm.clearErrors('payee_barangay');
    activeDropdown.value = null;
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
    payee_province: '',
    payee_city: '',
    payee_barangay: '',
});

const filterForm = ref({
    city: props.filters.city || '',
});

// --- HELPERS ---
const formatCurrency = (value) => {
    const numericValue = Number(value);
    if (isNaN(numericValue)) return '₱0.00';
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(numericValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const formatContactNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 11) parts = parts.substring(0, 11); 

    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 4);
    if (parts.length >= 5) formatted += '-' + parts.substring(4, 7);
    if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) formatted += '-';

    return formatted;
};

// --- ACTIONS ---
const openAddModal = () => {
    showAddModal.value = true;
    document.body.style.overflow = 'hidden'; 
    assessmentQuery.value = '';
    selectedAssessmentDetails.value = null;
};

const closeAddModal = () => {
    showAddModal.value = false;
    document.body.style.overflow = '';
    addForm.reset();
    showAssessmentDropdown.value = false;
    activeDropdown.value = null;
    selectedAssessmentDetails.value = null;
};

const submitAdd = () => {
    addForm.post(route('admin.payments.store'), {
        onSuccess: () => {
            closeAddModal();
            const newPayment = props.payments.data[0];
            
            if (newPayment) {
                printReceipt(newPayment);
            }
        },
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

const openFilterModal = () => {
    showFilterModal.value = true;
    document.body.style.overflow = 'hidden';
};

const closeFilterModal = () => {
    showFilterModal.value = false;
    document.body.style.overflow = '';
};

const applyFilters = () => {
    handleSearch();
    closeFilterModal();
};

const resetFilters = () => {
    filterForm.value.city = '';
    search.value = '';
    applyFilters();
};

// --- PRINT / VIEW ACTIONS ---
const openViewPaymentModal = (payment) => {
    selectedPayment.value = payment;
    showViewModal.value = true;
    document.body.style.overflow = 'hidden';
};

const closeViewModal = () => {
    showViewModal.value = false;
    document.body.style.overflow = '';
};

const printReceipt = async (payment) => {
    selectedPayment.value = payment;
    
    const wasViewModalOpen = showViewModal.value;
    if (wasViewModalOpen) {
        showViewModal.value = false;
        document.body.style.overflow = '';
    }
    
    await nextTick();
    
    setTimeout(() => {
        window.print();
        if (wasViewModalOpen) {
            showViewModal.value = true;
            document.body.style.overflow = 'hidden';
        }
    }, 300);
};
</script>

<template>
    <Head title="Manage Payments" />

    <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-10 hidden md:block"></div>

    <div class="print:hidden">
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
                    <PrimaryButton v-if="userRole === 'collector'" @click="openAddModal" class="flex items-center gap-2">
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
                                <th class="px-6 py-4">OR Number</th>
                                <th class="px-6 py-4">Payee Details</th>
                                <th class="px-6 py-4">Location</th>
                                <th class="px-6 py-4">Assessment / Ref ID</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Date Recorded</th>
                                <th class="px-6 py-4 text-center">Action</th> </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 text-gray-500 text-xs font-mono font-bold text-red-600">
                                    {{ payment.or_number || 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
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
                                    <div class="text-xs text-gray-400">{{ payment.payee_street_address }} <span v-if="payment.payee_province">, {{ payment.payee_province }}</span></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="payment.assessment_id" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        #{{ payment.application_reference_id || `ASM-${payment.assessment_id}` }}
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
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <button @click="printReceipt(payment)" class="text-gray-400 hover:text-green-600 font-medium transition-colors">Print</button>
                                        <button @click="openViewPaymentModal(payment)" class="text-gray-400 hover:text-blue-600 font-medium transition-colors">View</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="payments.data.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
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

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    
                    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" @click="closeAddModal"></div>

                    <div class="relative w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row max-h-[90vh] md:max-h-[85vh] transform transition-all">
                        
                        <div class="w-full md:w-2/3 flex flex-col min-h-0 bg-white relative z-20">
                            
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white shrink-0">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900" id="modal-title">Record Payment</h2>
                                    <p class="text-sm text-gray-500">Complete the form below to register a new payment.</p>
                                </div>
                                <button @click="closeAddModal" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors md:hidden">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 overflow-y-auto flex-1 min-h-0">
                                <form @submit.prevent="submitAdd" class="space-y-6">
                                    
                                    <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100">
                                        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            Search Assessment / Application
                                        </h4>
                                        <div class="relative">
                                            <InputLabel>Reference Number</InputLabel>
                                            <TextInput 
                                                type="text" 
                                                class="mt-1 block w-full bg-white" 
                                                v-model="assessmentQuery" 
                                                @focus="showAssessmentDropdown = true"
                                                @input="showAssessmentDropdown = true"
                                                placeholder="e.g. APP-2023-001 or ASM-000012..." 
                                                autocomplete="off"
                                            />
                                            <div v-if="showAssessmentDropdown && filteredAssessments.length > 0" class="absolute z-30 w-full bg-white border border-gray-200 mt-1 rounded-lg shadow-xl max-h-56 overflow-y-auto">
                                                <div 
                                                    v-for="assessment in filteredAssessments" 
                                                    :key="assessment.id"
                                                    @click="selectAssessment(assessment)"
                                                    class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                                                >
                                                    <div class="font-medium text-sm text-gray-900">
                                                        {{ assessment.reference_number || assessment.application_reference_id || `ASM-${assessment.id}` }}
                                                    </div>
                                                    <div class="text-xs text-gray-500">{{ assessment.label }} &bull; Balance: {{ formatCurrency(assessment.balance) }}</div>
                                                </div>
                                            </div>
                                            <div v-else-if="showAssessmentDropdown && assessmentQuery && filteredAssessments.length === 0" class="absolute z-30 w-full bg-white border border-gray-200 mt-1 rounded-lg shadow-xl p-4 text-sm text-center text-gray-500">
                                                No application found for this reference.
                                            </div>
                                            <div v-if="showAssessmentDropdown" @click="showAssessmentDropdown = false" class="fixed inset-0 z-20 bg-transparent cursor-default"></div>
                                        </div>
                                    </div>

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
                                                <TextInput 
                                                    type="text" 
                                                    class="mt-1 block w-full" 
                                                    v-model="addForm.payee_contact_number" 
                                                    @input="addForm.payee_contact_number = formatContactNumber($event.target.value)" 
                                                    maxlength="13" 
                                                    placeholder="09XX-XXX-XXXX" 
                                                />
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
                                            
                                            <div class="col-span-12 md:col-span-4 relative">
                                                <InputLabel>Province <span class="text-red-500">*</span></InputLabel>
                                                <div class="relative z-20">
                                                    <TextInput 
                                                        v-model="addForm.payee_province" 
                                                        @focus="activeDropdown = 'province'"
                                                        @input="addForm.clearErrors('payee_province')"
                                                        class="mt-1 block w-full"
                                                        placeholder="Search Province..."
                                                        autocomplete="off"
                                                        required
                                                    />
                                                    <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-48 overflow-y-auto ring-1 ring-black ring-opacity-5">
                                                        <ul class="py-1">
                                                            <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                                {{ prov.name }}
                                                            </li>
                                                            <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-span-12 md:col-span-4 relative">
                                                <InputLabel>City/Municipality <span class="text-red-500">*</span></InputLabel>
                                                <div class="relative z-20">
                                                    <TextInput 
                                                        v-model="addForm.payee_city" 
                                                        @focus="activeDropdown = 'city'"
                                                        @input="addForm.clearErrors('payee_city')"
                                                        :disabled="!citiesList.length"
                                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                                        placeholder="Search City..."
                                                        autocomplete="off"
                                                        required
                                                    />
                                                    <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-48 overflow-y-auto ring-1 ring-black ring-opacity-5">
                                                        <ul class="py-1">
                                                            <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                                {{ city.name }}
                                                            </li>
                                                            <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-span-12 md:col-span-4 relative">
                                                <InputLabel>Barangay <span class="text-red-500">*</span></InputLabel>
                                                <div class="relative z-20">
                                                    <TextInput 
                                                        v-model="addForm.payee_barangay" 
                                                        @focus="activeDropdown = 'barangay'"
                                                        @input="addForm.clearErrors('payee_barangay')"
                                                        :disabled="!barangaysList.length"
                                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                                        placeholder="Search Barangay..."
                                                        autocomplete="off"
                                                        required
                                                    />
                                                    <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-48 overflow-y-auto ring-1 ring-black ring-opacity-5">
                                                        <ul class="py-1">
                                                            <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                                {{ brgy.name }}
                                                            </li>
                                                            <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-gray-100 pb-2">
                                        <div class="w-full md:w-1/2">
                                            <InputLabel>Amount Paid (PHP) <span class="text-red-500">*</span></InputLabel>
                                            <div class="relative rounded-md shadow-sm mt-1">
                                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                    <span class="text-gray-500 sm:text-sm">₱</span>
                                                </div>
                                                <TextInput 
                                                    type="number" 
                                                    step="0.01" 
                                                    class="block w-full pl-7 font-mono text-lg font-bold text-gray-900 border-gray-300 focus:ring-green-500 focus:border-green-500" 
                                                    v-model="addForm.amount_paid" 
                                                    placeholder="0.00" 
                                                    required 
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end gap-3 shrink-0">
                                <SecondaryButton type="button" @click="closeAddModal">Cancel</SecondaryButton>
                                
                                <PrimaryButton @click="submitAdd" :disabled="addForm.processing" class="flex items-center gap-2">
                                    <svg v-if="addForm.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span v-if="addForm.processing">Saving...</span>
                                    <span v-else>Save Payment</span>
                                </PrimaryButton>
                            </div>
                        </div>

                        <div class="w-full md:w-1/3 bg-gray-50 border-t md:border-t-0 md:border-l border-gray-200 flex flex-col min-h-0 relative z-10">
                            
                            <button @click="closeAddModal" class="hidden md:flex absolute top-4 right-4 text-gray-400 hover:text-gray-700 bg-white hover:bg-gray-100 rounded-full p-2 shadow-sm transition-all border border-gray-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="p-6 flex-1 flex flex-col overflow-y-auto min-h-0">
                                <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-6 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Assessment Details
                                </h3>

                                <div v-if="selectedAssessmentDetails" class="flex-1 flex flex-col">
                                    
                                    <div class="mb-5 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                                        <p class="text-xs text-gray-500 mb-1">Reference Number</p>
                                        <p class="font-mono font-bold text-blue-600 text-lg">
                                            {{ selectedAssessmentDetails.reference_number || selectedAssessmentDetails.application_reference_id || `ASM-${selectedAssessmentDetails.id}` }}
                                        </p>
                                        
                                        <p class="text-xs text-gray-500 mt-3 mb-1">Description / Project</p>
                                        <p class="text-sm text-gray-800 font-medium leading-snug">{{ selectedAssessmentDetails.label || 'N/A' }}</p>
                                    </div>

                                    <div class="flex-1">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 border-b border-gray-200 pb-2">Particulars Breakdown</h4>
                                        <ul class="space-y-3 text-sm">
                                            <template v-if="selectedAssessmentDetails.particulars && selectedAssessmentDetails.particulars.length > 0">
                                                <li v-for="(item, index) in selectedAssessmentDetails.particulars" :key="index" class="flex justify-between items-start">
                                                    <div class="flex flex-col pr-4">
                                                        <span class="text-gray-700 font-medium">{{ item.name }}</span>
                                                        <span v-if="item.quantity && item.quantity > 0" class="text-xs text-gray-500 mt-0.5">
                                                            Qty: {{ item.quantity }}
                                                        </span>
                                                    </div>
                                                    <span class="font-mono font-medium text-gray-900">{{ formatCurrency(item.amount) }}</span>
                                                </li>
                                            </template>
                                            <li v-else class="text-gray-400 italic text-sm py-4 text-center bg-gray-100 rounded-lg border border-dashed border-gray-300">
                                                No itemized particulars available.
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="mt-6 pt-4 border-t border-gray-300">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-sm text-gray-600">Total Assessed</span>
                                            <span class="font-semibold text-gray-800">{{ formatCurrency(selectedAssessmentDetails.total_amount || selectedAssessmentDetails.balance) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center bg-red-50 p-3 rounded-lg border border-red-100 shadow-inner">
                                            <span class="text-sm font-bold text-red-800">Remaining Balance</span>
                                            <span class="font-bold text-red-700 font-mono text-lg">{{ formatCurrency(selectedAssessmentDetails.balance) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="flex-1 flex flex-col items-center justify-center text-center opacity-60">
                                    <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-600 max-w-[220px]">Search and select an application reference ID on the left to view its breakdown.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </Transition>

            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showFilterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="filter-modal-title" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="closeFilterModal"></div>

                    <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden transform transition-all">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                            <h2 class="text-lg font-bold text-gray-900" id="filter-modal-title">Filter Payments</h2>
                            <button @click="closeFilterModal" class="text-gray-400 hover:text-gray-600 hover:bg-gray-200 p-1.5 rounded-full transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div>
                                <InputLabel>Filter by City</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="filterForm.city" placeholder="e.g. Polomolok" @keyup.enter="applyFilters" />
                            </div>
                        </div>

                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
                            <SecondaryButton @click="resetFilters">Reset</SecondaryButton>
                            <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
                        </div>
                    </div>
                </div>
            </Transition>

            <Modal :show="showViewModal" @close="closeViewModal" maxWidth="lg">
                <div v-if="selectedPayment" class="p-6">
                    <div class="flex justify-between items-start mb-6 border-b pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Payment Details</h2>
                        <button @click="closeViewModal" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">OR Number:</span>
                            <span class="text-red-600 font-mono font-bold">{{ selectedPayment.or_number || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">Assessment Ref:</span>
                            <span class="text-gray-900">#{{ selectedPayment.application_reference_id || `ASM-${selectedPayment.assessment_id}` }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold text-gray-500">Date Paid:</span>
                            <span class="text-gray-900">{{ formatDate(selectedPayment.created_at) }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-4 mt-2">
                            <p class="font-bold text-gray-500 mb-1">Payee Information</p>
                            <p class="uppercase font-semibold text-gray-900">{{ selectedPayment.payee_first_name }} {{ selectedPayment.payee_middle_name || '' }} {{ selectedPayment.payee_last_name }}</p>
                            <p class="text-gray-600 mt-1">{{ selectedPayment.payee_street_address }}, {{ selectedPayment.payee_barangay }}, {{ selectedPayment.payee_city }}<span v-if="selectedPayment.payee_province">, {{ selectedPayment.payee_province }}</span></p>
                        </div>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg flex justify-between items-center mb-6">
                        <span class="font-bold text-blue-900 uppercase text-xs tracking-wider">Amount Paid</span>
                        <span class="text-xl font-bold font-mono text-blue-700">{{ formatCurrency(selectedPayment.amount_paid) }}</span>
                    </div>
                    
                    <div class="flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeViewModal">Close</SecondaryButton>
                        <PrimaryButton @click="printReceipt(selectedPayment)">Print Receipt</PrimaryButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    </div>

    <div class="hidden print:block print:w-full">
        <ReceiptPrint 
            v-if="selectedPayment" 
            :payment="selectedPayment" 
        />
    </div>

</template>