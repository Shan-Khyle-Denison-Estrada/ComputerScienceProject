<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- ADDED: Import Pagination Component
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    franchises: Object,
    operators: Array,
    units: Array,
    drivers: Array,
    zones: Array,
    filters: Object
});

// --- MODAL STATES ---
const showAddModal = ref(false);
const showDriversModal = ref(false);

// --- SEARCH STATE ---
const search = ref(props.filters.search || '');

// --- SELECTED DATA FOR MODALS ---
const selectedDrivers = ref([]);

// --- FORM ---
const form = useForm({
    operator_id: '',
    unit_id: '',
    driver_id: '',
    zone_id: '',
    date_issued: new Date().toISOString().split('T')[0],
});

// --- HELPERS ---
const getDriverName = (driver) => {
    if (driver.user) {
        return `${driver.user.last_name}, ${driver.user.first_name}`;
    }
    if (driver.first_name && driver.last_name) {
        return `${driver.last_name}, ${driver.first_name}`;
    }
    return driver.name || 'Unknown Driver';
};

// NEW: Helper to resolve image paths correctly
const getDriverPhoto = (driver) => {
    // 1. Check for specific driver photo uploaded manually
    if (driver.user_photo) {
        // If it's already a full URL (e.g. S3), return it
        if (driver.user_photo.startsWith('http')) {
            return driver.user_photo;
        }
        // Otherwise, assume local storage and prepend /storage/
        return `/storage/${driver.user_photo}`;
    }
    
    // 2. Fallback to the User model's profile photo (Jetstream/default)
    if (driver.user?.profile_photo_url) {
        return driver.user.profile_photo_url;
    }

    return null;
};

const openDriversModal = (assignments) => {
    // Extract the driver object from the pivot/assignment relationship
    selectedDrivers.value = assignments.map(a => a.driver);
    showDriversModal.value = true;
};

// --- ACTIONS ---
const submitForm = () => {
    form.post(route('admin.franchises.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        }
    });
};

const handleSearch = () => {
    router.get(route('admin.franchises.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Franchises" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Franchises</h1>
                <p class="text-gray-600 text-sm">Manage franchises, assignments, and history.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input v-model="search" @keyup.enter="handleSearch" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" placeholder="Search Franchise ID..." />
                </div>
                <PrimaryButton @click="showAddModal = true" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Franchise
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Franchise ID</th>
                            <th class="px-6 py-4">Current Owner</th>
                            <th class="px-6 py-4">Assigned Unit</th>
                            <th class="px-6 py-4">Assigned Drivers</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="franchise in franchises.data" :key="franchise.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600 text-base">#{{ franchise.id }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase"
                                    :class="{
                                        'bg-green-100 text-green-800': franchise.status === 'renewed',
                                        'bg-orange-100 text-orange-800': franchise.status === 'pending renewal',
                                        'bg-red-100 text-red-800': franchise.status === 'terminated',
                                        'bg-gray-100 text-gray-800': !['renewed', 'pending renewal', 'terminated'].includes(franchise.status)
                                    }">
                                    {{ franchise.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="franchise.current_ownership?.new_owner">
                                    <div class="font-medium text-gray-900">
                                        {{ franchise.current_ownership.new_owner.user.last_name }}, {{ franchise.current_ownership.new_owner.user.first_name }}
                                    </div>
                                    <div class="text-xs text-gray-500">Since: {{ franchise.current_ownership.date_transferred }}</div>
                                </div>
                                <div v-else class="text-gray-400 italic">No Owner</div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="franchise.current_active_unit?.new_unit">
                                    <span class="font-mono bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200 font-bold">
                                        {{ franchise.current_active_unit.new_unit.plate_number }}
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">{{ franchise.current_active_unit.new_unit.make.name }}</div>
                                </div>
                                <div v-else class="text-gray-400 italic">No Unit</div>
                            </td>
                            
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button 
                                    v-if="franchise.driver_assignments && franchise.driver_assignments.length > 0"
                                    @click="openDriversModal(franchise.driver_assignments)"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
                                >
                                    <svg class="mr-2 -ml-0.5 h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    View Drivers ({{ franchise.driver_assignments.length }})
                                </button>
                                <span v-else class="text-gray-400 italic text-xs">Unassigned</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <Link :href="route('admin.franchises.show', franchise.id)" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">
                                    View Details &rarr;
                                </Link>
                            </td>
                        </tr>
                         <tr v-if="franchises.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No franchises found. Create one to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="franchises.links && franchises.links.length > 3">
                <div class="text-xs text-gray-500">
                    Showing {{ franchises.from }} to {{ franchises.to }} of {{ franchises.total }} results
                </div>
                
                <Pagination :links="franchises.links" />
            </div>
        </div>

        <Modal :show="showAddModal" @close="showAddModal = false">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Issue New Franchise</h2>
                    <p class="text-sm text-gray-500">Create a new record and assign initial ownership.</p>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel>Franchise Owner (Operator)<span class="text-red-600"> *</span></InputLabel>
                        <select v-model="form.operator_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Select Operator</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">
                                {{ op.user.last_name }}, {{ op.user.first_name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel>Assign Unit<span class="text-red-600"> *</span></InputLabel>
                        <select v-model="form.unit_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Select Unit</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                {{ u.plate_number }} - {{ u.make.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Driver</InputLabel>
                            <select v-model="form.driver_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Select Driver</option>
                                <option v-for="d in drivers" :key="d.id" :value="d.id">
                                    {{ getDriverName(d) }}
                                </option>
                            </select>
                            <p v-if="drivers.length === 0" class="text-xs text-red-500 mt-1">No drivers available.</p>
                        </div>
                        <div>
                            <InputLabel>Zone<span class="text-red-600"> *</span></InputLabel>
                            <select v-model="form.zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Select Zone</option>
                                <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.description }}</option>
                            </select>
                             <p v-if="zones.length === 0" class="text-xs text-red-500 mt-1">No zones available.</p>
                        </div>
                    </div>

                    <div>
                        <InputLabel>Date Issued<span class="text-red-600"> *</span></InputLabel>
                        <TextInput type="date" v-model="form.date_issued" class="mt-1 block w-full" required />
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Issue Franchise</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showDriversModal" @close="showDriversModal = false">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Assigned Drivers</h2>
                        <p class="text-sm text-gray-500">Currently assigned drivers for this franchise.</p>
                    </div>
                    <button @click="showDriversModal = false" class="text-gray-400 hover:text-gray-600 transition">
                         <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto p-1">
                    <div 
                        v-for="driver in selectedDrivers" 
                        :key="driver.id" 
                        class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 flex flex-col gap-2"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-lg overflow-hidden border border-blue-200">
                                <img 
                                    v-if="getDriverPhoto(driver)" 
                                    :src="getDriverPhoto(driver)" 
                                    class="h-full w-full object-cover" 
                                    alt="Driver Photo" 
                                />
                                <span v-else>
                                    {{ getDriverName(driver).charAt(0) }}
                                </span>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 truncate">
                                    {{ getDriverName(driver) }}
                                </h3>
                                <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    {{ driver.contact_number || 'No contact info' }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-2 mt-1 space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">License:</span>
                                <span class="font-mono font-medium text-gray-700">{{ driver.license_number || 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Status:</span>
                                <span class="uppercase font-bold text-[10px] px-1.5 py-0.5 rounded"
                                    :class="{
                                        'bg-green-100 text-green-700': driver.status === 'active',
                                        'bg-gray-100 text-gray-600': driver.status !== 'active'
                                    }">
                                    {{ driver.status || 'Unknown' }}
                                </span>
                            </div>
                             <div class="flex justify-between text-xs" v-if="driver.city || driver.barangay">
                                <span class="text-gray-500">Location:</span>
                                <span class="text-gray-700 truncate max-w-[120px] text-right" :title="`${driver.barangay || ''}, ${driver.city || ''}`">
                                    {{ driver.barangay ? driver.barangay + ',' : '' }} {{ driver.city }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end border-t pt-4">
                    <SecondaryButton @click="showDriversModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>