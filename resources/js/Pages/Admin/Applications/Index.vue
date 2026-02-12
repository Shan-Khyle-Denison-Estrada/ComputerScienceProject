<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    applications: Object,
    filters: Object,
    requirements: Object, // Passed from Controller
    auth: Object
});

// --- STATE ---
const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');
const showFilterModal = ref(false);
const showAddModal = ref(false);
const showDetailsModal = ref(false);
const showRequirementsModal = ref(false); // NEW MODAL STATE
const selectedApp = ref(null);

// Requirements Management State
const activeRequirementTab = ref('new_franchise');
const requirementTypes = [
    { value: 'new_franchise', label: 'New Franchise' },
    { value: 'renewal', label: 'Renewal' },
    { value: 'transfer_ownership', label: 'Transfer Ownership' },
    { value: 'change_unit', label: 'Change Unit' },
    { value: 'new_operator', label: 'New Operator' },
];

// --- FORMS ---
const filterForm = useForm({
    type: props.filters.type || '',
    status: props.filters.status || '',
});

const createForm = useForm({
    type: '',
    franchise_id: '',
    remarks: '',
    proposed_data: {} 
});

// NEW: Requirement Form
const requirementForm = useForm({
    application_type: '',
    name: ''
});

// 1. Search (Debounced)
const handleSearch = debounce(() => {
    fetchApplications();
}, 300);

watch(search, handleSearch);

// Single function to handle all data fetching
const fetchApplications = () => {
    router.get(route('applications.index'), { 
        search: search.value,
        type: typeFilter.value,
        status: statusFilter.value
    }, { 
        preserveState: true, 
        preserveScroll: true, 
        replace: true 
    });
};

watch(search, handleSearch);

// 2. Apply Filters
const applyFilters = () => {
    fetchApplications();
    showFilterModal.value = false;
};

// 3. Clear Filters
const clearFilters = () => {
    typeFilter.value = '';
    statusFilter.value = '';
    showFilterModal.value = false;
    fetchApplications();
};

const submitApplication = () => {
    createForm.post(route('admin.applications.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            createForm.reset();
        }
    });
};

// NEW: Add Requirement
const addRequirement = () => {
    requirementForm.application_type = activeRequirementTab.value;
    requirementForm.post(route('admin.applications.requirements.store'), {
        onSuccess: () => requirementForm.reset('name'),
        preserveScroll: true
    });
};

// NEW: Delete Requirement
const deleteRequirement = (id) => {
    if(!confirm('Remove this requirement?')) return;
    router.delete(route('admin.applications.requirements.destroy', id), {
        preserveScroll: true
    });
};

const openReview = (app) => {
    selectedApp.value = app;
    showDetailsModal.value = true;
};

const approveApp = () => {
    if(!confirm('Are you sure? This will execute database changes.')) return;
        router.post(route('admin.applications.approve', selectedApp.value.id), {}, {
        onSuccess: () => showDetailsModal.value = false
    });
};

// --- HELPERS ---
const getTypeLabel = (type) => {
    const map = {
        'new_operator': 'New Operator Account',
        'new_franchise': 'New Franchise',
        'renewal': 'Franchise Renewal',
        'change_of_owner': 'Transfer Ownership',
        'change_of_unit': 'Change Unit'
    };
    return map[type] || type;
};

const getStatusColor = (status) => {
    switch (status) {
        case 'approved': return 'bg-green-100 text-green-800 border-green-200';
        case 'rejected': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-blue-100 text-blue-800 border-blue-200';
    }
};

// Computed list for the active tab in requirements modal
const activeRequirements = computed(() => {
    return props.requirements[activeRequirementTab.value] || [];
});
</script>

<template>
    <Head title="Applications" />

    <AuthenticatedLayout>
        <div class="">
            <div class="">
                
                <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Applications</h1>
                        <p class="text-gray-600 text-sm">Create and manage franchise applications.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <input 
                                v-model="search" 
                                @keyup.enter="handleSearch" 
                                type="text" 
                                class="pl-3 pr-4 py-2 border-gray-300 rounded-lg block w-full sm:w-64 shadow-sm text-sm" 
                                placeholder="Search Applicant or ID..." 
                            />
                        </div>
                        
<button @click="showFilterModal = true" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm relative">
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
    </svg>
    <span v-if="typeFilter || statusFilter" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
</button>

                        <SecondaryButton @click="showRequirementsModal = true" class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                            </svg>
                            Requirements
                        </SecondaryButton>

                        <PrimaryButton @click="showAddModal = true">New Application</PrimaryButton>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Filed</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="app in applications.data" :key="app.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #{{ app.id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ getTypeLabel(app.type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ app.user.first_name }} {{ app.user.last_name }}</div>
                                        <div class="text-xs text-gray-500" v-if="app.franchise_id">MTOP: {{ app.franchise_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ new Date(app.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="app.assessment">
                                            <span class="text-xs font-bold" 
                                                :class="app.assessment.assessment_status === 'paid' ? 'text-green-600' : 'text-orange-500'">
                                                {{ app.assessment.assessment_status.toUpperCase() }}
                                            </span>
                                            <div class="text-xs text-gray-500">₱{{ app.assessment.total_amount_due }}</div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-[10px] leading-5 font-semibold rounded-full uppercase border"
                                            :class="getStatusColor(app.status)">
                                            {{ app.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button 
                                            @click="openReview(app)"
                                            class="text-indigo-600 hover:text-indigo-900 flex items-center justify-end gap-1 w-full"
                                        >
                                            Review
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="applications.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 text-sm">
                                        No applications found matching your criteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex justify-end" v-if="applications.links.length > 3">
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, key) in applications.links"
                            :key="key"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3 py-1 rounded text-sm border"
                            :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                            :preserve-scroll="true"
                        />
                    </div>
                </div>
            </div>
        </div>

<Modal :show="showFilterModal" @close="showFilterModal = false">
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Filter Applications</h2>
        
        <div class="space-y-4">
            <div>
                <InputLabel value="Application Type" />
                <select v-model="typeFilter" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">All Types</option>
                    <option value="new_operator">New Operator</option>
                    <option value="new_franchise">New Franchise</option>
                    <option value="renewal">Renewal</option>
                    <option value="change_of_owner">Transfer Ownership</option>
                    <option value="change_of_unit">Change Unit</option>
                </select>
            </div>

            <div>
                <InputLabel value="Status" />
                <select v-model="statusFilter" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <SecondaryButton @click="clearFilters">Clear</SecondaryButton>
            <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
        </div>
    </div>
</Modal>

        <Modal :show="showAddModal" @close="showAddModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Create New Application</h2>
                
                <form @submit.prevent="submitApplication">
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Application Type" />
                            <select 
                                v-model="createForm.type" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="" disabled>Select Type...</option>
                                <option value="new_operator">New Operator Account</option>
                                <option value="new_franchise">New Franchise</option>
                                <option value="renewal">Renewal</option>
                                <option value="transfer_ownership">Transfer Ownership</option>
                                <option value="change_unit">Change Unit</option>
                            </select>
                        </div>

                        <div v-if="['renewal', 'transfer_ownership', 'change_unit'].includes(createForm.type)">
                            <InputLabel value="Franchise Reference (MTOP)" />
                            <TextInput 
                                v-model="createForm.franchise_id" 
                                type="text" 
                                class="mt-1 block w-full" 
                                placeholder="Enter Franchise ID"
                            />
                            <p class="text-xs text-gray-500 mt-1">Search logic can be added here.</p>
                        </div>
                        
                        <div>
                            <InputLabel value="Remarks / Notes" />
                            <textarea 
                                v-model="createForm.remarks" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                            ></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="createForm.processing">Create Application</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDetailsModal" @close="showDetailsModal = false">
            <div class="p-6" v-if="selectedApp">
                <div class="flex justify-between items-start mb-6">
                    <div>
                         <h2 class="text-xl font-bold text-gray-900">Application #{{ selectedApp.id }}</h2>
                         <p class="text-sm text-gray-500">{{ getTypeLabel(selectedApp.type) }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border" :class="getStatusColor(selectedApp.status)">
                        {{ selectedApp.status }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Applicant</h3>
                            <p class="font-medium text-gray-900">{{ selectedApp.user.first_name }} {{ selectedApp.user.last_name }}</p>
                            <p class="text-sm text-gray-500">{{ selectedApp.user.email }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ selectedApp.user.contact_number || 'No contact info' }}</p>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Requirements</h3>
                            <div class="text-sm text-gray-500 italic">
                                No documents attached.
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                             <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Financial</h3>
                             <div v-if="selectedApp.assessment">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600">Total Due:</span>
                                    <span class="font-bold">₱{{ selectedApp.assessment.total_amount_due }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Payment Status:</span>
                                    <span class="font-bold" :class="selectedApp.assessment.assessment_status === 'paid' ? 'text-green-600' : 'text-red-500'">
                                        {{ selectedApp.assessment.assessment_status.toUpperCase() }}
                                    </span>
                                </div>
                             </div>
                             <div v-else class="text-sm text-gray-500 italic">No assessment required.</div>
                        </div>

                         <div class="bg-gray-50 p-4 rounded-lg border border-gray-100" v-if="selectedApp.remarks">
                             <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Remarks</h3>
                             <p class="text-sm text-gray-700">{{ selectedApp.remarks }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <SecondaryButton @click="showDetailsModal = false">Close</SecondaryButton>
                    <PrimaryButton 
                        v-if="selectedApp.status === 'pending'"
                        @click="approveApp" 
                        :disabled="selectedApp.assessment && selectedApp.assessment.assessment_status !== 'paid'"
                        :class="{'opacity-50 cursor-not-allowed': selectedApp.assessment && selectedApp.assessment.assessment_status !== 'paid'}"
                    >
                        Approve Application
                    </PrimaryButton>
                </div>
                 <p v-if="selectedApp.assessment && selectedApp.assessment.assessment_status !== 'paid'" class="text-xs text-red-500 text-right mt-2">
                    * Assessment must be settled before approval.
                </p>
            </div>
        </Modal>

        <Modal :show="showRequirementsModal" @close="showRequirementsModal = false">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-gray-900">Manage Requirements</h2>
                    <SecondaryButton size="sm" @click="showRequirementsModal = false">Done</SecondaryButton>
                </div>

                <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg mb-6 overflow-x-auto">
                    <button
                        v-for="type in requirementTypes"
                        :key="type.value"
                        @click="activeRequirementTab = type.value"
                        class="px-3 py-1.5 text-xs font-medium rounded-md whitespace-nowrap transition-colors"
                        :class="activeRequirementTab === type.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        {{ type.label }}
                    </button>
                </div>

                <div class="space-y-4 min-h-[300px]">
                    <form @submit.prevent="addRequirement" class="flex gap-2">
                        <input 
                            v-model="requirementForm.name"
                            type="text" 
                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Add new requirement (e.g. Deed of Sale)"
                            required
                        />
                        <PrimaryButton :disabled="requirementForm.processing">Add</PrimaryButton>
                    </form>

                    <div class="bg-gray-50 rounded-lg border border-gray-200 divide-y divide-gray-200">
                        <div v-if="activeRequirements.length === 0" class="p-4 text-center text-gray-400 text-sm italic">
                            No requirements defined for this type.
                        </div>
                        <div v-for="req in activeRequirements" :key="req.id" class="p-3 flex justify-between items-center group">
                            <span class="text-sm text-gray-700">{{ req.name }}</span>
                            <button @click="deleteRequirement(req.id)" class="text-gray-400 hover:text-red-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>