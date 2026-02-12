<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- DUMMY DATA ---
const dummyApplications = [
    {
        id: 1,
        reference_no: 'APP-2024-001',
        type: 'Franchise Owner Account',
        date_submitted: 'Oct 25, 2024',
        applicant: {
            first_name: 'Juan',
            last_name: 'Dela Cruz',
            email: 'juan.delacruz@example.com',
            photo: null,
            contact: '0917-123-4567',
            address: 'San Jose, Zamboanga City'
        },
        status: 'Pending'
    },
    {
        id: 2,
        reference_no: 'APP-2024-002',
        type: 'Renewal',
        date_submitted: 'Oct 23, 2024',
        applicant: {
            first_name: 'Maria',
            last_name: 'Santos',
            email: 'maria.santos@example.com',
            photo: null,
            contact: '0918-987-6543',
            address: 'Tetuan, Zamboanga City'
        },
        status: 'Approved'
    },
    {
        id: 3,
        reference_no: 'APP-2024-003',
        type: 'Change of Owner',
        date_submitted: 'Oct 20, 2024',
        applicant: {
            first_name: 'Pedro',
            last_name: 'Penduko',
            email: 'pedro.p@example.com',
            photo: null,
            contact: '0920-555-4444',
            address: 'Putik, Zamboanga City'
        },
        status: 'Rejected'
    },
    {
        id: 4,
        reference_no: 'APP-2024-004',
        type: 'Change of Unit',
        date_submitted: 'Oct 18, 2024',
        applicant: {
            first_name: 'Anna',
            last_name: 'Reyes',
            email: 'anna.reyes@example.com',
            photo: null,
            contact: '0917-777-8888',
            address: 'Pasonanca, Zamboanga City'
        },
        status: 'Pending'
    },
    {
        id: 5,
        reference_no: 'APP-2024-005',
        type: 'Renewal',
        date_submitted: 'Oct 15, 2024',
        applicant: {
            first_name: 'Carlos',
            last_name: 'Garcia',
            email: 'carlos.g@example.com',
            photo: null,
            contact: '0999-111-2222',
            address: 'Sta. Maria, Zamboanga City'
        },
        status: 'Returned'
    },
    // --- APPROVED INSTANCES FOR MODAL TESTING ---
    {
        id: 6,
        reference_no: 'APP-2024-006',
        type: 'Franchise Owner Account',
        date_submitted: 'Oct 10, 2024',
        applicant: {
            first_name: 'Elena',
            last_name: 'Torres',
            email: 'elena.t@example.com',
            photo: null,
            contact: '0922-333-4444',
            address: 'Tumaga, Zamboanga City'
        },
        status: 'Approved' // Triggers: Create Franchise Owner Account Modal
    },
    {
        id: 7,
        reference_no: 'APP-2024-007',
        type: 'Change of Unit',
        date_submitted: 'Oct 05, 2024',
        applicant: {
            first_name: 'Roberto',
            last_name: 'Gomez',
            email: 'robert.g@example.com',
            photo: null,
            contact: '0915-888-9999',
            address: 'Baliwasan, Zamboanga City'
        },
        status: 'Approved' // Triggers: Finalize Unit Change Modal
    },
    {
        id: 8,
        reference_no: 'APP-2024-008',
        type: 'Change of Owner',
        date_submitted: 'Oct 01, 2024',
        applicant: {
            first_name: 'Mario',
            last_name: 'Bros',
            email: 'mario.b@example.com',
            photo: null,
            contact: '0911-222-3333',
            address: 'Calarian, Zamboanga City'
        },
        status: 'Approved' // Triggers: Finalize Ownership Transfer Modal
    }
];

// --- STATE MANAGEMENT ---
const showFilterModal = ref(false);
const search = ref('');
const filterStatus = ref('');

// --- COMPUTED PROPERTIES (Frontend Filtering) ---
const filteredApplications = computed(() => {
    return dummyApplications.filter(app => {
        // Search Logic
        const searchLower = search.value.toLowerCase();
        const matchesSearch = 
            app.applicant.first_name.toLowerCase().includes(searchLower) ||
            app.applicant.last_name.toLowerCase().includes(searchLower) ||
            app.reference_no.toLowerCase().includes(searchLower) ||
            app.type.toLowerCase().includes(searchLower);

        // Filter Logic
        const matchesStatus = filterStatus.value 
            ? app.status.toLowerCase() === filterStatus.value.toLowerCase() 
            : true;

        return matchesSearch && matchesStatus;
    });
});

// --- ACTIONS ---
const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;

const applyFilters = () => {
    closeFilterModal();
};

const resetFilters = () => {
    filterStatus.value = '';
    search.value = '';
    closeFilterModal();
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
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="search"
                        type="text" 
                        class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" 
                        placeholder="Search applications..." 
                    />
                </div>

                <button 
                    @click="openFilterModal"
                    class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm transition-colors relative"
                    title="Filter Applications"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span v-if="filterStatus" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                </button>

                <PrimaryButton class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Application
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

                            <td class="px-6 py-4">
                                <span class="text-gray-600">{{ app.date_submitted }}</span>
                            </td>

                            <td class="px-6 py-4">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': app.status === 'Approved',
                                        'bg-yellow-100 text-yellow-800': app.status === 'Pending',
                                        'bg-red-100 text-red-800': app.status === 'Rejected',
                                        'bg-amber-100 text-amber-800': app.status === 'Returned',
                                    }"
                                >
                                    {{ app.status }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <Link 
                                    :href="route('admin.applications.show', app.id)"
                                    class="text-gray-400 hover:text-blue-600 font-medium transition-colors"
                                >
                                    View
                                </Link>
                            </td>
                        </tr>
                        
                        <tr v-if="filteredApplications.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No applications found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showFilterModal" @close="closeFilterModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter Applications</h2>
                    <button @click="closeFilterModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel>Status</InputLabel>
                        <select v-model="filterStatus" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Returned">Returned</option>
                        </select>
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