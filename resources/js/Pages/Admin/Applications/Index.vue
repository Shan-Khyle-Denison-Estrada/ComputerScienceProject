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
    requirements: Object, // Grouped by application_type
    auth: Object
});

// --- STATE ---
const showFilterModal = ref(false);
const showAddModal = ref(false);
const showDetailsModal = ref(false);
const showRequirementsModal = ref(false);
const selectedApp = ref(null);
const activeTab = ref('assessment'); // Tabs: assessment, evaluation, inspection

// Search & Filter State
const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');

// Requirements Management State
const activeRequirementTab = ref('renewal'); // Default to renewal since that's the focus
const requirementTypes = [
    { value: 'new_franchise', label: 'New Franchise' },
    { value: 'renewal', label: 'Renewal' },
    { value: 'transfer_ownership', label: 'Transfer Ownership' },
    { value: 'change_unit', label: 'Change Unit' },
    { value: 'new_operator', label: 'New Operator' },
];

// --- FORMS ---

// 1. Create Application Form
const createForm = useForm({
    type: '',
    franchise_id: '',
    remarks: '',
    proposed_data: {} 
});

// 2. Inspection Results Form
const inspectionForm = useForm({
    inspections: [] // Array of { requirement_id, score, remarks }
});

// 3. New Requirement Form
const requirementForm = useForm({
    application_type: '',
    category: 'evaluation', // Default category
    name: ''
});

// --- COMPUTED HELPERS ---

// Get requirements for the currently selected application in the modal
const currentAppRequirements = computed(() => {
    if (!selectedApp.value) return { evaluation: [], inspection: [] };
    
    const allReqs = props.requirements[selectedApp.value.type] || [];
    
    return {
        evaluation: allReqs.filter(r => r.category === 'evaluation'),
        inspection: allReqs.filter(r => r.category === 'inspection')
    };
});

// Get requirements list for the "Manage Requirements" modal based on selected tab
const activeRequirementsList = computed(() => {
    return props.requirements[activeRequirementTab.value] || [];
});

// --- ACTIONS ---

// Fetch Data (Unified Search/Filter)
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

const handleSearch = debounce(() => fetchApplications(), 300);

watch(search, handleSearch);

const applyFilters = () => {
    fetchApplications();
    showFilterModal.value = false;
};

const clearFilters = () => {
    typeFilter.value = '';
    statusFilter.value = '';
    fetchApplications();
    showFilterModal.value = false;
};

// Create Application
const submitApplication = () => {
    createForm.post(route('admin.applications.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            createForm.reset();
        }
    });
};

// Open Details Modal & Initialize Inspection Form
const openDetails = (app) => {
    selectedApp.value = app;
    activeTab.value = 'assessment'; // Reset to first tab
    showDetailsModal.value = true;

    // Prepare Inspection Form Data
    // Map the Inspection Requirements to the form, pre-filling with existing results if any
    const inspectionReqs = props.requirements[app.type]?.filter(r => r.category === 'inspection') || [];
    
    inspectionForm.inspections = inspectionReqs.map(req => {
        // Check if there is already a saved result for this requirement
        const existing = app.inspections?.find(i => i.application_requirement_id === req.id);
        return {
            requirement_id: req.id,
            name: req.name, // For display purposes in the form
            score: existing ? existing.score : '',
            remarks: existing ? existing.remarks : ''
        };
    });
};

// Save Inspection Results
const saveInspection = () => {
    if (!selectedApp.value) return;
    
    inspectionForm.post(route('admin.applications.inspection.save', selectedApp.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show notification
        }
    });
};

// Approve Application
const approveApp = () => {
    if (!selectedApp.value) return;
    
    if (confirm('Are you sure you want to approve this application?')) {
        router.post(route('admin.applications.approve', selectedApp.value.id), {}, {
            onSuccess: () => {
                showDetailsModal.value = false;
                selectedApp.value = null;
            }
        });
    }
};

// Add Requirement
const submitRequirement = () => {
    requirementForm.application_type = activeRequirementTab.value;
    requirementForm.post(route('admin.applications.requirements.store'), {
        onSuccess: () => requirementForm.reset('name'),
        preserveScroll: true
    });
};

// Delete Requirement
const deleteRequirement = (id) => {
    if(!confirm('Remove this requirement?')) return;
    router.delete(route('admin.applications.requirements.destroy', id), {
        preserveScroll: true
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
</script>

<template>
    <Head title="Applications" />

    <AuthenticatedLayout>
        <div>
            <div>
                
                <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Applications</h1>
                        <p class="text-gray-600 text-sm">Create and manage franchise applications.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <input 
                                v-model="search" 
                                type="text" 
                                class="pl-3 pr-4 py-2 border-gray-300 rounded-lg block w-full sm:w-64 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" 
                                placeholder="Search Applicant or ID..." 
                            />
                        </div>
                        
                        <button @click="showFilterModal = true" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm relative transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span v-if="typeFilter || statusFilter" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full animate-pulse"></span>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">App ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Filed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
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
                                            @click="openDetails(app)"
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
                                        No applications found.
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
                            class="px-3 py-1 rounded text-sm border transition-colors"
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
                            <option value="transfer_ownership">Transfer Ownership</option>
                            <option value="change_unit">Change Unit</option>
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

        <Modal :show="showDetailsModal" @close="showDetailsModal = false" maxWidth="2xl">
            <div class="p-6" v-if="selectedApp">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Application #{{ selectedApp.id }}</h2>
                        <span class="text-sm text-gray-500">{{ getTypeLabel(selectedApp.type) }}</span>
                    </div>
                    
                    <div class="text-right">
                         <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border" :class="getStatusColor(selectedApp.status)">
                            {{ selectedApp.status }}
                        </span>
                    </div>
                </div>

                <div class="flex border-b border-gray-200 mb-6">
                    <button 
                        @click="activeTab = 'assessment'"
                        class="px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'assessment' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Assessment
                    </button>
                    <button 
                        @click="activeTab = 'evaluation'"
                        class="px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'evaluation' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Evaluation (Files)
                    </button>
                    <button 
                        v-if="currentAppRequirements.inspection.length > 0"
                        @click="activeTab = 'inspection'"
                        class="px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'inspection' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Inspection
                    </button>
                </div>

                <div v-if="activeTab === 'assessment'" class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Payment Details</h3>
                        <div v-if="selectedApp.assessment">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm text-gray-600">Total Due:</span>
                                <span class="text-lg font-bold text-gray-900">₱{{ selectedApp.assessment.total_amount_due }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Status:</span>
                                <span class="text-sm font-bold uppercase" :class="selectedApp.assessment.assessment_status === 'paid' ? 'text-green-600' : 'text-red-500'">
                                    {{ selectedApp.assessment.assessment_status }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 italic">No assessment generated.</div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100" v-if="selectedApp.remarks">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Remarks</h3>
                        <p class="text-sm text-gray-700">{{ selectedApp.remarks }}</p>
                    </div>
                </div>

                <div v-if="activeTab === 'evaluation'" class="space-y-4">
                    <div v-if="currentAppRequirements.evaluation.length === 0" class="text-gray-500 italic text-sm text-center py-4">
                        No document requirements defined for this application type.
                    </div>

                    <div v-for="req in currentAppRequirements.evaluation" :key="req.id" 
                        class="flex items-center justify-between p-3 border rounded bg-white shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gray-100 rounded text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                    <path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ req.name }}</p>
                                <p class="text-xs text-gray-400">Required Document</p>
                            </div>
                        </div>
                        
                        <div v-if="selectedApp.attachments && selectedApp.attachments.find(a => a.application_requirement_id === req.id)">
                            <a 
                                :href="'/storage/' + selectedApp.attachments.find(a => a.application_requirement_id === req.id).file_path" 
                                target="_blank"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline flex items-center gap-1"
                            >
                                View
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3">
                                    <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                            </a>
                        </div>
                        <span v-else class="text-red-500 text-xs font-bold italic bg-red-50 px-2 py-1 rounded">Missing</span>
                    </div>
                </div>

                <div v-if="activeTab === 'inspection'" class="space-y-4">
                    <p class="text-sm text-gray-500 mb-2">Check the unit condition and enter scores.</p>

                    <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2">
                        <div v-for="(item, index) in inspectionForm.inspections" :key="item.requirement_id" 
                            class="p-4 border rounded-lg border-gray-200 bg-white"
                        >
                            <div class="mb-3 font-semibold text-sm text-gray-800 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ index + 1 }}</span>
                                {{ item.name }}
                            </div>
                            
                            <div class="grid grid-cols-4 gap-3">
                                <div class="col-span-1">
                                    <label class="block text-[10px] uppercase text-gray-500 font-bold mb-1">Score</label>
                                    <input 
                                        v-model="item.score" 
                                        type="number" 
                                        class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="0-10"
                                    >
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-[10px] uppercase text-gray-500 font-bold mb-1">Remarks</label>
                                    <input 
                                        v-model="item.remarks" 
                                        type="text" 
                                        class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter observations..."
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <PrimaryButton @click="saveInspection" :disabled="inspectionForm.processing">
                            Save Results
                        </PrimaryButton>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                    <SecondaryButton @click="showDetailsModal = false">Close</SecondaryButton>
                    <PrimaryButton 
                        v-if="selectedApp.status === 'pending'"
                        @click="approveApp" 
                        :disabled="selectedApp.assessment?.assessment_status !== 'paid'"
                        :class="{'opacity-50 cursor-not-allowed': selectedApp.assessment?.assessment_status !== 'paid'}"
                    >
                        Approve Application
                    </PrimaryButton>
                </div>
                 <p v-if="selectedApp.assessment && selectedApp.assessment.assessment_status !== 'paid'" class="text-[10px] text-red-500 text-right mt-2 font-medium">
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
                    <form @submit.prevent="submitRequirement" class="flex flex-col gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <InputLabel value="Requirement Name" class="text-xs mb-1" />
                                <input 
                                    v-model="requirementForm.name"
                                    type="text" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    placeholder="e.g. Official Receipt"
                                    required
                                />
                            </div>
                            <div class="w-1/3">
                                <InputLabel value="Category" class="text-xs mb-1" />
                                <select v-model="requirementForm.category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="evaluation">Evaluation (File)</option>
                                    <option value="inspection">Inspection (Admin)</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <PrimaryButton size="sm" :disabled="requirementForm.processing">Add Requirement</PrimaryButton>
                        </div>
                    </form>

                    <div class="divide-y divide-gray-100 border rounded-lg">
                        <div v-if="activeRequirementsList.length === 0" class="p-4 text-center text-gray-400 text-sm italic">
                            No requirements defined for this type.
                        </div>
                        <div v-for="req in activeRequirementsList" :key="req.id" class="p-3 flex justify-between items-center group bg-white">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-700">{{ req.name }}</span>
                                <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded font-bold" 
                                    :class="req.category === 'inspection' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700'">
                                    {{ req.category }}
                                </span>
                            </div>
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