<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- ADDED: Import Pagination Component
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    redFlags: Object,
    natures: Array,
    filters: Object
});

// --- State Management ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const natureFilter = ref(props.filters.nature_id || '');

const showNatureModal = ref(false);
const showFilterModal = ref(false); // Controls the new Filter Modal

// --- Filtering Logic ---

// 1. Live Search (Debounced)
const handleSearch = debounce(() => {
    router.get(route('admin.red-flags.index'), { 
        search: search.value,
        status: statusFilter.value,
        nature_id: natureFilter.value
    }, { preserveState: true, replace: true });
}, 300);

watch(search, handleSearch);

// 2. Apply Filters (From Modal)
const applyFilters = () => {
    router.get(route('admin.red-flags.index'), {
        search: search.value,
        status: statusFilter.value,
        nature_id: natureFilter.value
    }, { 
        preserveState: true, 
        preserveScroll: true,
        onSuccess: () => showFilterModal.value = false 
    });
};

// 3. Reset Filters
const resetFilters = () => {
    statusFilter.value = '';
    natureFilter.value = '';
    applyFilters();
};

// --- Action Logic ---
const resolveRedFlag = (id) => {
    if (confirm('Mark this red flag as resolved?')) {
        router.patch(route('admin.red-flags.resolve', id), {}, { preserveScroll: true });
    }
};

// --- Nature Configuration Logic ---
const natureForm = useForm({ name: '' });

const submitNature = () => {
    natureForm.post(route('admin.red-flags.nature.store'), {
        onSuccess: () => { natureForm.reset(); },
    });
};

const deleteNature = (id) => {
    if(confirm('Are you sure?')) {
        router.delete(route('admin.red-flags.nature.destroy', id));
    }
};
</script>

<template>
    <Head title="Red Flags" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Red Flags</h1>
                <p class="text-gray-600 text-sm">Manage franchise alerts and warning types.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input 
                        v-model="search" 
                        type="text" 
                        class="pl-4 pr-10 py-2 border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 block w-full sm:w-64 shadow-sm text-sm" 
                        placeholder="Search Franchise ID..." 
                    />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <button 
                    @click="showFilterModal = true"
                    class="p-2 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    title="Filter Options"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                </button>
                
                <SecondaryButton @click="showNatureModal = true">
                    Manage Types
                </SecondaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Franchise</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nature</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="flag in redFlags.data" :key="flag.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                FR-{{ flag.franchise_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <span class="px-2 py-1 rounded-md bg-red-50 text-red-700 border border-red-200 text-xs font-bold">
                                    {{ flag.nature?.name || 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                {{ flag.remarks || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="flag.status === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full uppercase">
                                    {{ flag.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ new Date(flag.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button v-if="flag.status !== 'resolved'" @click="resolveRedFlag(flag.id)" class="text-blue-600 hover:text-blue-900 font-bold hover:underline">
                                    Resolve
                                </button>
                                <div v-else class="text-green-600 font-bold flex items-center justify-end gap-1">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Done
                                </div>
                            </td>
                        </tr>
                        <tr v-if="redFlags.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 italic">
                                No red flags found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="redFlags.links && redFlags.links.length > 3">
                <div class="text-xs text-gray-500">
                    Showing {{ redFlags.from }} to {{ redFlags.to }} of {{ redFlags.total }}
                </div>
                
                <Pagination :links="redFlags.links" />
            </div>
        </div>

        <Modal :show="showFilterModal" @close="showFilterModal = false" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter Red Flags</h2>
                    <button @click="showFilterModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel value="Filter by Nature" />
                        <select 
                            v-model="natureFilter" 
                            class="mt-1 block w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 shadow-sm text-sm"
                        >
                            <option value="">All Types</option>
                            <option v-for="nature in natures" :key="nature.id" :value="nature.id">
                                {{ nature.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Filter by Status" />
                        <select 
                            v-model="statusFilter" 
                            class="mt-1 block w-full border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 shadow-sm text-sm"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="resetFilters">Reset</SecondaryButton>
                    <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showNatureModal" @close="showNatureModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Manage Red Flag Types</h2>
                
                <form @submit.prevent="submitNature" class="flex gap-2 mb-6">
                    <TextInput v-model="natureForm.name" placeholder="Enter new type (e.g. For Impound)" class="flex-1" />
                    <PrimaryButton :disabled="natureForm.processing">Add</PrimaryButton>
                </form>

                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <div v-for="nature in natures" :key="nature.id" class="flex justify-between items-center p-3 bg-gray-50 rounded border">
                        <span class="text-sm text-gray-700">{{ nature.name }}</span>
                        <button @click="deleteNature(nature.id)" class="text-red-500 hover:text-red-700 text-xs font-medium">Delete</button>
                    </div>
                    <div v-if="natures.length === 0" class="text-center text-gray-400 text-sm py-4">No types defined yet.</div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showNatureModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>