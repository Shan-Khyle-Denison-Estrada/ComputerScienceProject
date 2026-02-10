<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    complaints: Object,
    natures: Array,
    filters: Object
});

// --- STATE ---
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const natureFilter = ref(props.filters.nature || '');
const showFilterModal = ref(false);

// --- ACTIONS ---

// 1. Search (Debounced)
const handleSearch = debounce(() => {
    applyFilters(false);
}, 300);

watch(search, handleSearch);

// 2. Apply Filters
const applyFilters = (closeModal = true) => {
    router.get(route('admin.complaints.index'), { 
        search: search.value,
        status: statusFilter.value,
        nature: natureFilter.value
    }, { 
        preserveState: true, 
        replace: true,
        preserveScroll: true
    });
    
    if (closeModal) {
        showFilterModal.value = false;
    }
};

// 3. Clear Filters
const clearFilters = () => {
    statusFilter.value = '';
    natureFilter.value = '';
    applyFilters();
};

// 4. Resolve Complaint
const resolveComplaint = (complaintId) => {
    if (confirm('Are you sure you want to mark this complaint as resolved?')) {
        router.patch(route('admin.complaints.resolve', complaintId), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Toast logic here if needed
            }
        });
    }
};
</script>

<template>
    <Head title="Complaints" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Complaints</h1>
                <p class="text-gray-600 text-sm">Monitor and manage reported violations.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input v-model="search" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 block w-full sm:w-64 shadow-sm text-sm" placeholder="Search..." />
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
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div v-if="statusFilter || natureFilter" class="bg-gray-50 px-6 py-2 border-b border-gray-100 flex items-center gap-2 text-xs">
                <span class="font-bold text-gray-500 uppercase tracking-wider">Active Filters:</span>
                <span v-if="statusFilter" class="bg-white border px-2 py-0.5 rounded text-gray-600">Status: {{ statusFilter }}</span>
                <span v-if="natureFilter" class="bg-white border px-2 py-0.5 rounded text-gray-600">Nature: {{ natureFilter }}</span>
                <button @click="clearFilters" class="text-red-600 hover:text-red-800 hover:underline ml-auto">Clear All</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Complaint ID</th>
                            <th class="px-6 py-4">Franchise</th>
                            <th class="px-6 py-4">Nature of Complaint</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4">Incident Details</th>
                            <th class="px-6 py-4">Complainant</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="complaint in complaints.data" :key="complaint.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-red-600 text-base">#{{ complaint.id }}</div>
                                <div class="text-xs text-gray-400">{{ new Date(complaint.created_at).toLocaleDateString() }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <Link :href="route('admin.franchises.show', complaint.franchise_id)" class="font-bold text-blue-600 hover:underline">
                                    Franchise #{{ complaint.franchise_id }}
                                </Link>
                                <div v-if="complaint.franchise?.current_active_unit?.new_unit" class="text-xs text-gray-500 mt-1">
                                    Plate: {{ complaint.franchise.current_active_unit.new_unit.plate_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                    {{ complaint.nature_of_complaint }}
                                </span>
                                <div class="text-xs text-gray-500 mt-2 max-w-xs truncate" :title="complaint.remarks">
                                    {{ complaint.remarks || 'No remarks provided.' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'px-2 py-1 text-xs font-bold leading-5 rounded-full uppercase',
                                    complaint.status === 'Resolved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                ]">
                                    {{ complaint.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1 text-xs">
                                    <div class="flex items-center gap-1 text-gray-700">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        {{ complaint.incident_date }} | {{ complaint.incident_time }}
                                    </div>
                                    <div v-if="complaint.pick_up_point || complaint.drop_off_point" class="text-gray-500 mt-1">
                                        <span class="font-semibold">Route:</span> {{ complaint.pick_up_point || '?' }} <span class="text-gray-400">→</span> {{ complaint.drop_off_point || '?' }}
                                    </div>
                                    <div v-if="complaint.fare_collected" class="text-gray-500">
                                        <span class="font-semibold">Fare:</span> ₱{{ complaint.fare_collected }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ complaint.complainant_contact }}</div>
                                <div class="text-xs text-gray-400">Contact Number</div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    v-if="complaint.status !== 'Resolved'"
                                    @click="resolveComplaint(complaint.id)" 
                                    class="text-blue-600 hover:text-blue-900 font-bold hover:underline text-xs uppercase"
                                >
                                    Mark Resolved
                                </button>
                                <div v-else class="text-green-600 font-bold flex items-center justify-end gap-1 text-xs uppercase">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Done
                                </div>
                            </td>
                        </tr>
                        <tr v-if="complaints.data.length === 0">
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                No complaints found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="complaints.links && complaints.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ complaints.meta.from }} to {{ complaints.meta.to }} of {{ complaints.meta.total }}
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="(link, key) in complaints.meta.links" 
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border rounded-md text-xs transition-colors"
                        :class="[
                            link.active ? 'bg-red-600 text-white border-red-600' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <Modal :show="showFilterModal" @close="showFilterModal = false" maxWidth="sm">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Filter Complaints</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Filter by Status" class="mb-1" />
                        <select v-model="statusFilter" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Filter by Nature" class="mb-1" />
                        <select v-model="natureFilter" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-red-500 focus:ring-red-500">
                            <option value="">All Types</option>
                            <option v-for="nature in natures" :key="nature" :value="nature">
                                {{ nature }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showFilterModal = false">Cancel</SecondaryButton>
                    <PrimaryButton @click="applyFilters(true)">Apply Filters</PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>