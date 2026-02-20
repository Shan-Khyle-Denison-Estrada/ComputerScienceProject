<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- PROPS (Real Data from Backend) ---
const props = defineProps({
    applications: Array,
    evaluationRequirements: Array,
    inspectionRequirements: Array
});

// --- CONSTANTS ---
const applicationTypes = [
    'Franchise Owner Account',
    'Renewal',
    'Change of Owner',
    'Change of Unit'
];

// --- STATE MANAGEMENT ---
const showFilterModal = ref(false);
const showRequirementsModal = ref(false);
const search = ref('');
const filterStatus = ref('');
const filterType = ref('');

// Requirements Modal State
const activeReqTab = ref('evaluation');
const isEditingReq = ref(false);
const reqForm = ref({ id: null, name: '', options: '', type: '' });

// --- COMPUTED PROPERTIES ---
const filteredApplications = computed(() => {
    // [!code focus] Changed from dummyApplications to props.applications
    return props.applications.filter(app => {
        const searchLower = search.value.toLowerCase();
        
        // Safety check for null values
        const fName = app.applicant?.first_name || '';
        const lName = app.applicant?.last_name || '';
        const refNo = app.reference_no || '';
        const appType = app.type || '';

        const matchesSearch = 
            fName.toLowerCase().includes(searchLower) ||
            lName.toLowerCase().includes(searchLower) ||
            refNo.toLowerCase().includes(searchLower) ||
            appType.toLowerCase().includes(searchLower);

        const matchesStatus = filterStatus.value ? app.status.toLowerCase() === filterStatus.value.toLowerCase() : true;
        const matchesType = filterType.value ? app.type === filterType.value : true;

        return matchesSearch && matchesStatus && matchesType;
    });
});

const currentRequirementsList = computed(() => activeReqTab.value === 'evaluation' ? props.evaluationRequirements : props.inspectionRequirements);

// Group evaluation requirements by type
const groupedEvaluationRequirements = computed(() => {
    const groups = {};
    applicationTypes.forEach(type => groups[type] = []);
    
    props.evaluationRequirements.forEach(req => {
        if (groups[req.type]) {
            groups[req.type].push(req);
        } else {
            if (!groups['Other']) groups['Other'] = [];
            groups['Other'].push(req);
        }
    });
    return groups;
});

const getOptionsArray = (optionsStr) => {
    if (!optionsStr) return [];
    return optionsStr.split(',').map(o => o.trim()).filter(o => o.length > 0);
};

// --- ACTIONS ---
const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;
const applyFilters = () => closeFilterModal();
const resetFilters = () => { 
    filterStatus.value = ''; 
    filterType.value = ''; 
    search.value = ''; 
    closeFilterModal(); 
};

const openRequirementsModal = () => { showRequirementsModal.value = true; activeReqTab.value = 'evaluation'; resetReqForm(); };
const closeRequirementsModal = () => { showRequirementsModal.value = false; resetReqForm(); };
const setReqTab = (tab) => { activeReqTab.value = tab; resetReqForm(); };

const resetReqForm = () => { 
    reqForm.value = { id: null, name: '', options: '', type: applicationTypes[0] }; 
    isEditingReq.value = false; 
};

const editRequirement = (req) => { 
    reqForm.value = { 
        id: req.id, 
        name: req.name, 
        options: req.options || '',
        type: req.type || applicationTypes[0]
    }; 
    isEditingReq.value = true; 
};

// [!code focus] Backend Connection
const saveRequirement = () => {
    if (!reqForm.value.name.trim()) return;
    
    router.post(route('admin.requirements.store'), {
        category: activeReqTab.value,
        id: reqForm.value.id,
        name: reqForm.value.name,
        type: reqForm.value.type,
        options: reqForm.value.options
    }, {
        preserveScroll: true,
        onSuccess: () => resetReqForm()
    });
};

// [!code focus] Backend Connection
const deleteRequirement = (id) => {
    if (confirm("Are you sure you want to delete this requirement?")) {
        router.delete(route('admin.requirements.destroy', { type: activeReqTab.value, id: id }), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Applications" />

    <AuthenticatedLayout>
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Applications</h1>
                <p class="text-gray-600 text-sm">Manage franchise applications, renewals, and modifications.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input v-model="search" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" placeholder="Search applications..." />
                </div>

                <button @click="openFilterModal" class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm transition-colors relative" title="Filter Applications">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <span v-if="filterStatus || filterType" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                </button>

                <PrimaryButton @click="openRequirementsModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Manage Requirements
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Applicant</th>
                            <th class="px-6 py-4">Application Details</th>
                            <th class="px-6 py-4">Date Submitted</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="app in filteredApplications" :key="app.id" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold border border-gray-300 overflow-hidden">
                                        <img v-if="app.applicant.photo" :src="app.applicant.photo" class="h-full w-full object-cover" />
                                        <span v-else>{{ app.applicant.first_name.charAt(0) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900">{{ app.applicant.last_name }}, {{ app.applicant.first_name }}</div>
                                        <div class="text-gray-500 text-xs">{{ app.applicant.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="text-sm font-medium text-blue-600">{{ app.type }}</div>
                                <div class="text-xs text-gray-400 font-mono mt-0.5">{{ app.reference_no }}</div>
                            </td>
                            <td class="px-6 py-4"><span class="text-gray-600">{{ app.date_submitted }}</span></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{'bg-green-100 text-green-800': app.status === 'Approved', 'bg-yellow-100 text-yellow-800': app.status === 'Pending', 'bg-red-100 text-red-800': app.status === 'Rejected', 'bg-amber-100 text-amber-800': app.status === 'Returned', 'bg-blue-100 text-blue-800': app.status === 'processed'}">
                                    {{ app.status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('admin.applications.show', app.id)" class="text-gray-400 hover:text-blue-600 font-medium transition-colors">View</Link>
                            </td>
                        </tr>
                        <tr v-if="filteredApplications.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">No applications found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showFilterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeFilterModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                        <h2 class="text-lg font-bold text-gray-900">Filter Applications</h2>
                        <button @click="closeFilterModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <InputLabel>Application Type</InputLabel>
                            <select v-model="filterType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                <option value="">All Types</option>
                                <option v-for="type in applicationTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <InputLabel>Status</InputLabel>
                            <select v-model="filterStatus" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                                <option value="">All Statuses</option>
                                <option value="Pending">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Returned">Returned</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                        <SecondaryButton @click="resetFilters">Reset</SecondaryButton>
                        <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
                    </div>
                </div>
            </div>
        </transition>

        <transition name="fade">
            <div v-if="showRequirementsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeRequirementsModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[850px] max-h-[90vh] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Manage Requirements</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Configure mandatory items for the evaluation and inspection phases.</p>
                        </div>
                        <button @click="closeRequirementsModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">✕</button>
                    </div>

                    <div class="p-6 overflow-y-auto bg-gray-50/50 flex-1">
                        <div class="flex border-b border-gray-200 mb-6 w-full max-w-sm">
                            <button @click="setReqTab('evaluation')" :class="activeReqTab === 'evaluation' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="w-1/2 py-2 text-center border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                            <button @click="setReqTab('inspection')" :class="activeReqTab === 'inspection' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="w-1/2 py-2 text-center border-b-2 font-medium text-sm transition-colors">Inspection</button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 items-start">
                            <div class="md:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-200 transition-all duration-200" :class="isEditingReq ? 'ring-2 ring-blue-500/50' : ''">
                                <h3 class="text-sm font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <svg v-if="isEditingReq" class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <svg v-else class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    {{ isEditingReq ? 'Edit Requirement' : 'Add New Requirement' }}
                                </h3>
                                <div class="space-y-4">
                                    <div v-if="activeReqTab === 'evaluation'">
                                        <InputLabel value="Application Type" class="text-xs" />
                                        <select v-model="reqForm.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option v-for="type in applicationTypes" :key="type" :value="type">{{ type }}</option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel value="Requirement Name" class="text-xs" />
                                        <TextInput v-model="reqForm.name" type="text" class="mt-1 block w-full text-sm" placeholder="e.g. Police Clearance" />
                                    </div>
                                    
                                    <div v-if="activeReqTab === 'inspection'">
                                        <InputLabel value="Evaluation Options" class="text-xs" />
                                        <div class="text-[10px] text-gray-400 mb-1 leading-tight">Separate options with commas.</div>
                                        <TextInput v-model="reqForm.options" type="text" class="mt-1 block w-full text-sm" placeholder="e.g. Perfect, Good, Bad" />
                                    </div>

                                    <div class="flex flex-col gap-2 pt-3 border-t border-gray-100">
                                        <PrimaryButton @click="saveRequirement" class="justify-center py-2 text-xs w-full">{{ isEditingReq ? 'Update' : 'Save' }} Requirement</PrimaryButton>
                                        <SecondaryButton v-if="isEditingReq" @click="resetReqForm" class="justify-center py-2 text-xs w-full bg-gray-50 hover:bg-gray-100">Cancel Editing</SecondaryButton>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-3">
                                <div class="overflow-y-auto max-h-[400px] border border-gray-200 rounded-xl bg-white shadow-sm">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50 text-gray-600 font-medium sticky top-0 shadow-sm z-10 text-xs uppercase tracking-wider">
                                            <tr><th class="px-5 py-3">Name</th><th v-if="activeReqTab === 'inspection'" class="px-5 py-3">Options</th><th class="px-5 py-3 w-20 text-right">Actions</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            
                                            <template v-if="activeReqTab === 'evaluation'">
                                                <template v-for="(reqs, typeName) in groupedEvaluationRequirements" :key="typeName">
                                                    <tr class="bg-blue-50/50 border-y border-gray-100">
                                                        <td colspan="3" class="px-5 py-2 font-bold text-xs text-blue-700 uppercase tracking-wide">
                                                            {{ typeName }}
                                                        </td>
                                                    </tr>
                                                    <tr v-for="req in reqs" :key="req.id" class="hover:bg-gray-50 transition-colors">
                                                        <td class="px-5 py-3.5 font-medium text-gray-800">{{ req.name }}</td>
                                                        <td class="px-5 py-3.5 text-right">
                                                            <div class="flex justify-end gap-2">
                                                                <button @click="editRequirement(req)" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors" title="Edit"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                                                <button @click="deleteRequirement(req.id)" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors" title="Delete"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="reqs.length === 0" class="bg-white">
                                                       <td colspan="2" class="px-5 py-2 text-xs text-gray-400 italic pl-8">No requirements set for this type.</td>
                                                    </tr>
                                                </template>
                                            </template>

                                            <template v-else>
                                                <tr v-for="req in currentRequirementsList" :key="req.id" class="hover:bg-blue-50/30 transition-colors">
                                                    <td class="px-5 py-3.5 font-medium text-gray-800">{{ req.name }}</td>
                                                    <td class="px-5 py-3.5">
                                                        <div class="flex flex-wrap gap-1">
                                                            <span v-for="opt in getOptionsArray(req.options)" :key="opt" class="px-2 py-0.5 text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200 rounded-md">{{ opt }}</span>
                                                            <span v-if="getOptionsArray(req.options).length === 0" class="text-[10px] text-gray-400 italic">No options defined</span>
                                                        </div>
                                                    </td>
                                                    <td class="px-5 py-3.5 text-right">
                                                        <div class="flex justify-end gap-2">
                                                            <button @click="editRequirement(req)" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded transition-colors" title="Edit"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                                            <button @click="deleteRequirement(req.id)" class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors" title="Delete"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr v-if="currentRequirementsList.length === 0">
                                                    <td colspan="3" class="px-5 py-10 text-center text-gray-500 italic">No requirements found. Add one on the left panel.</td>
                                                </tr>
                                            </template>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>