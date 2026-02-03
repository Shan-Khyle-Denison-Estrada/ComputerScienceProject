<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- PROPS ---
const props = defineProps({
    drivers: Object,         // Paginated drivers
    franchiseOwners: Array,  // List of franchise owners for dropdown
    barangays: Array,        // List of barangays for dropdown
    filters: Object          // Search and filter state
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showFilterModal = ref(false);
const search = ref(props.filters.search || '');

// Photo Previews (Driver + License)
const addPhotos = ref({ user: null, front: null, back: null });
const editPhotos = ref({ user: null, front: null, back: null });

// Refs for file inputs
const addPhotoInput = ref(null);
const addFrontInput = ref(null);
const addBackInput = ref(null);
const editPhotoInput = ref(null);

// --- FORMS ---

// 1. ADD DRIVER FORM
const addForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    contact_number: '',
    street: '',
    barangay: '',
    city: 'Zamboanga City',
    license_number: '',
    license_expiration_date: '',
    user_id: '', // Franchise Owner ID
    status: 'active',
    user_photo: null,
    license_front_photo: null,
    license_back_photo: null,
});

// 2. EDIT DRIVER FORM
const editForm = useForm({
    id: null,
    first_name: '',
    middle_name: '',
    last_name: '',
    contact_number: '',
    street: '',
    barangay: '',
    city: '',
    license_number: '',
    license_expiration_date: '',
    user_id: '',
    status: '',
    user_photo: null,
    _method: 'PUT'
});

// 3. FILTER FORM
const filterForm = ref({
    status: props.filters.status || '',
});

// --- ACTIONS: HELPER FUNCTIONS ---

const handleFileChange = (event, formKey, previewRefKey, isEdit = false) => {
    const file = event.target.files[0];
    if (file) {
        if (isEdit) {
            editForm[formKey] = file;
            editPhotos.value[previewRefKey] = URL.createObjectURL(file);
        } else {
            addForm[formKey] = file;
            addPhotos.value[previewRefKey] = URL.createObjectURL(file);
        }
    }
};

/**
 * Automatically fill driver details when a Franchise Owner is selected.
 * Maps User attributes to Driver form fields.
 */
const fillFranchiseOwnerDetails = () => {
    const ownerId = addForm.user_id;
    if (!ownerId) return;

    const selectedOwner = props.franchiseOwners.find(owner => owner.id === ownerId);
    
    if (selectedOwner) {
        // Copy Name
        addForm.first_name = selectedOwner.first_name || '';
        addForm.middle_name = selectedOwner.middle_name || '';
        addForm.last_name = selectedOwner.last_name || '';
        
        // Copy Contact
        addForm.contact_number = selectedOwner.contact_number || '';
        
        // Copy Address (Note: User model usually uses street_address, Driver uses street)
        addForm.street = selectedOwner.street_address || selectedOwner.street || '';
        addForm.barangay = selectedOwner.barangay || '';
        addForm.city = selectedOwner.city || 'Zamboanga City';
    }
};

// --- ACTIONS: ADD DRIVER ---
const openAddModal = () => showAddModal.value = true;
const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
    addPhotos.value = { user: null, front: null, back: null };
};

const submitAdd = () => {
    addForm.post(route('admin.drivers.store'), {
        onSuccess: () => closeAddModal(),
    });
};

// --- ACTIONS: EDIT DRIVER ---
const openEditModal = (driver) => {
    editForm.id = driver.id;
    editForm.first_name = driver.first_name;
    editForm.middle_name = driver.middle_name;
    editForm.last_name = driver.last_name;
    editForm.contact_number = driver.contact_number;
    editForm.street = driver.street;
    editForm.barangay = driver.barangay;
    editForm.city = driver.city;
    editForm.license_number = driver.license_number;
    editForm.license_expiration_date = driver.license_expiration_date;
    editForm.user_id = driver.user_id || '';
    editForm.status = driver.status;

    // Previews
    editPhotos.value.user = driver.user_photo ? `/storage/${driver.user_photo}` : null;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
    editPhotos.value = { user: null, front: null, back: null };
};

const submitEdit = () => {
    editForm.post(route('admin.drivers.update', editForm.id), {
        onSuccess: () => closeEditModal(),
    });
};

// --- ACTIONS: FILTERS & SEARCH ---
const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;

const handleSearch = () => {
    router.get(route('admin.drivers.index'), {
        search: search.value,
        status: filterForm.value.status
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const applyFilters = () => {
    handleSearch();
    closeFilterModal();
};

const resetFilters = () => {
    filterForm.value.status = '';
    search.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Manage Drivers" />

    <AuthenticatedLayout>

        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Driver Management</h1>
                <p class="text-gray-600 text-sm">Manage registered drivers and licenses.</p>
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
                        placeholder="Search name or license..."
                    />
                </div>

                <button
                    @click="openFilterModal"
                    class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm transition-colors relative"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span v-if="filterForm.status" class="absolute top-1 right-1 h-2 w-2 bg-blue-500 rounded-full"></span>
                </button>

                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Driver
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Driver</th>
                            <th class="px-6 py-4">Address & Contact</th>
                            <th class="px-6 py-4">Franchise Owner</th>
                            <th class="px-6 py-4">License Exp.</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="driver in drivers.data" :key="driver.id" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden border border-gray-200">
                                        <img v-if="driver.user_photo" :src="`/storage/${driver.user_photo}`" class="h-full w-full object-cover" />
                                        <span v-else class="text-blue-600 font-bold text-lg">
                                            {{ driver.first_name.charAt(0) }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900">{{ driver.first_name }} {{ driver.last_name }}</div>
                                        <div class="text-gray-500 text-xs font-mono">Lic: {{ driver.license_number }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ driver.barangay || 'No Barangay' }}, {{ driver.city }}</div>
                                <div class="text-gray-500 text-xs">{{ driver.contact_number || '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="driver.user" class="text-gray-700 font-medium">
                                    {{ driver.user.first_name }} {{ driver.user.last_name }}
                                </span>
                                <span v-else class="text-gray-400 italic text-xs">Unassigned</span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ driver.license_expiration_date }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="driver.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                >
                                    {{ driver.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    @click="openEditModal(driver)"
                                    class="text-gray-400 hover:text-blue-600 font-medium transition-colors"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="drivers.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                No drivers found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="drivers.links && drivers.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ drivers.meta.from }} to {{ drivers.meta.to }} of {{ drivers.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="(link, key) in drivers.meta.links"
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border rounded-md text-xs transition-colors"
                        :class="[
                            link.active ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <Modal :show="showAddModal" @close="closeAddModal" maxWidth="2xl">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Register New Driver</h2>
                    <p class="text-sm text-gray-500">Enter driver details and license information.</p>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-5">

                    <div class="flex flex-col items-center gap-4">
                        <div class="relative group cursor-pointer h-24 w-24 rounded-full border-4 border-gray-100 bg-gray-50 overflow-hidden hover:border-blue-100 transition" @click="() => addPhotoInput.click()">
                            <img v-if="addPhotos.user" :src="addPhotos.user" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex flex-col items-center justify-center text-gray-400">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                        </div>
                        <input type="file" ref="addPhotoInput" class="hidden" accept="image/*" @change="(e) => handleFileChange(e, 'user_photo', 'user')" />
                        
                        <div class="w-full md:w-2/3">
                            <InputLabel>Assign Franchise Owner</InputLabel>
                            <select 
                                v-model="addForm.user_id" 
                                @change="fillFranchiseOwnerDetails"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">None (Unassigned)</option>
                                <option v-for="owner in franchiseOwners" :key="owner.id" :value="owner.id">
                                    {{ owner.first_name }} {{ owner.last_name }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1 italic">Selecting an owner will auto-fill name and address.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
                        <div>
                            <InputLabel>First Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.first_name" required />
                        </div>
                        <div>
                            <InputLabel>Middle Name</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.middle_name" />
                        </div>
                        <div>
                            <InputLabel>Last Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.last_name" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.contact_number" />
                        </div>
                        <div>
                            <InputLabel>Street Address</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.street" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Barangay</InputLabel>
                            <select v-model="addForm.barangay" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Barangay</option>
                                <option v-for="brgy in barangays" :key="brgy.id" :value="brgy.name">
                                    {{ brgy.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel>City</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.city" />
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-4">
                        <h3 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">License Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel>License Number <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.license_number" required />
                            </div>
                            <div>
                                <InputLabel>Expiration Date <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="date" class="mt-1 block w-full" v-model="addForm.license_expiration_date" required />
                            </div>
                        </div>

                        <div class="flex flex-col gap-4">
                            <InputLabel class="mb-1">License Photos (Front & Back)</InputLabel>
                            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                                
                                <div class="flex flex-col items-center group">
                                    <div class="w-64 h-40 bg-white border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-blue-400 hover:shadow-md transition overflow-hidden relative" @click="() => addFrontInput.click()">
                                        <img v-if="addPhotos.front" :src="addPhotos.front" class="h-full w-full object-cover" />
                                        <div v-else class="flex flex-col items-center justify-center p-4 text-center">
                                            <svg class="h-8 w-8 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span class="text-xs text-gray-400 font-medium">Front Side</span>
                                        </div>
                                    </div>
                                    <input type="file" ref="addFrontInput" class="hidden" accept="image/*" @change="(e) => handleFileChange(e, 'license_front_photo', 'front')" />
                                </div>

                                <div class="flex flex-col items-center group">
                                    <div class="w-64 h-40 bg-white border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center cursor-pointer hover:border-blue-400 hover:shadow-md transition overflow-hidden relative" @click="() => addBackInput.click()">
                                        <img v-if="addPhotos.back" :src="addPhotos.back" class="h-full w-full object-cover" />
                                        <div v-else class="flex flex-col items-center justify-center p-4 text-center">
                                            <svg class="h-8 w-8 text-gray-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span class="text-xs text-gray-400 font-medium">Back Side</span>
                                        </div>
                                    </div>
                                    <input type="file" ref="addBackInput" class="hidden" accept="image/*" @change="(e) => handleFileChange(e, 'license_back_photo', 'back')" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Register Driver</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showEditModal" @close="closeEditModal" maxWidth="2xl">
             <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Driver</h2>
                    <p class="text-sm text-gray-500">Update driver details.</p>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-5">
                    
                    <div class="flex flex-col items-center gap-4">
                        <div class="relative group cursor-pointer h-24 w-24 rounded-full border-4 border-gray-100 bg-gray-50 overflow-hidden hover:border-blue-100 transition" @click="() => editPhotoInput.click()">
                            <img v-if="editPhotos.user" :src="editPhotos.user" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center bg-blue-100 text-blue-500 font-bold text-2xl">
                                {{ editForm.first_name ? editForm.first_name.charAt(0) : 'D' }}
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </div>
                        </div>
                        <input type="file" ref="editPhotoInput" class="hidden" accept="image/*" @change="(e) => handleFileChange(e, 'user_photo', 'user', true)" />

                        <div class="w-full md:w-2/3">
                            <InputLabel>Franchise Owner</InputLabel>
                            <select v-model="editForm.user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">None (Unassigned)</option>
                                <option v-for="owner in franchiseOwners" :key="owner.id" :value="owner.id">
                                    {{ owner.first_name }} {{ owner.last_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-t pt-4">
                        <div>
                            <InputLabel>First Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.first_name" required />
                        </div>
                        <div>
                            <InputLabel>Middle Name</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.middle_name" />
                        </div>
                        <div>
                            <InputLabel>Last Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.last_name" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.contact_number" />
                        </div>
                        <div>
                            <InputLabel>Street Address</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.street" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Barangay</InputLabel>
                            <select v-model="editForm.barangay" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Barangay</option>
                                <option v-for="brgy in barangays" :key="brgy.id" :value="brgy.name">
                                    {{ brgy.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel>City</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.city" />
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mt-4">
                        <h3 class="text-sm font-bold text-gray-700 mb-3 border-b pb-2">License Information</h3>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel>License Number <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.license_number" required />
                            </div>
                            <div>
                                <InputLabel>Expiration Date <span class="text-red-500">*</span></InputLabel>
                                <TextInput type="date" class="mt-1 block w-full" v-model="editForm.license_expiration_date" required />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel>Account Status <span class="text-red-500">*</span></InputLabel>
                            <select v-model="editForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeEditModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing">Save Changes</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showFilterModal" @close="closeFilterModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter Drivers</h2>
                    <button @click="closeFilterModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <InputLabel>Account Status</InputLabel>
                        <select v-model="filterForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
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