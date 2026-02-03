<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// --- PROPS ---
const props = defineProps({
    users: Object, 
    filters: Object,
    barangays: Array 
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showFilterModal = ref(false); // New
const search = ref(props.filters.search || '');

// --- FILTER STATE ---
const filterForm = ref({
    status: props.filters.status || '', // New
});

// --- PHOTO STATE ---
const addPhotoPreview = ref(null);
const editPhotoPreview = ref(null);

// --- BARANGAY SEARCH STATE ---
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);

const filteredBarangays = computed(() => {
    if (!barangayQuery.value) return props.barangays;
    return props.barangays.filter(b => 
        b.name.toLowerCase().includes(barangayQuery.value.toLowerCase())
    );
});

const selectBarangay = (name) => {
    if (showAddModal.value) addForm.barangay = name;
    if (showEditModal.value) editForm.barangay = name;
    
    barangayQuery.value = name;
    showBarangayDropdown.value = false;
};

// --- PHOTO HANDLERS ---
const handleAddPhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        addForm.user_photo = file;
        addPhotoPreview.value = URL.createObjectURL(file);
    }
};

const handleEditPhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        editForm.user_photo = file; 
        editPhotoPreview.value = URL.createObjectURL(file);
    }
};

// --- FORMS ---
const addForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    street_address: '',
    barangay: '',
    city: '',
    tin_number: '',
    password: '',
    password_confirmation: '',
    user_photo: null, 
});

const editForm = useForm({
    id: null,
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    street_address: '',
    barangay: '',
    city: '',
    tin_number: '',
    status: '', 
    password: '',
    password_confirmation: '',
    user_photo: null, 
    _method: 'PUT' 
});

// --- ACTIONS ---
const openAddModal = () => {
    showAddModal.value = true;
    barangayQuery.value = '';
    addPhotoPreview.value = null;
};

const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
    addPhotoPreview.value = null;
};

const submitAdd = () => {
    if (!addForm.barangay && barangayQuery.value) {
        addForm.barangay = barangayQuery.value;
    }
    addForm.post(route('admin.franchise-owners.store'), {
        onSuccess: () => closeAddModal(),
        forceFormData: true 
    });
};

const openEditModal = (user) => {
    editForm.id = user.id;
    editForm.first_name = user.first_name;
    editForm.middle_name = user.middle_name;
    editForm.last_name = user.last_name;
    editForm.email = user.email;
    editForm.contact_number = user.contact_number;
    editForm.street_address = user.street_address;
    editForm.barangay = user.barangay;
    editForm.city = user.city;
    editForm.status = user.status; 
    editForm.tin_number = user.operator ? user.operator.tin_number : '';
    
    // Set preview to existing photo if available
    editPhotoPreview.value = user.user_photo ? `/storage/${user.user_photo}` : null;
    barangayQuery.value = user.barangay; 

    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
    editPhotoPreview.value = null;
};

const submitEdit = () => {
    if (!editForm.barangay && barangayQuery.value) {
        editForm.barangay = barangayQuery.value;
    }
    editForm.post(route('admin.franchise-owners.update', editForm.id), { 
        onSuccess: () => closeEditModal(),
        forceFormData: true
    });
};

// --- SEARCH & FILTER ACTIONS ---
const handleSearch = () => {
    router.get(route('admin.franchise-owners.index'), { 
        search: search.value,
        status: filterForm.value.status 
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
    filterForm.value.status = '';
    search.value = '';
    applyFilters();
};
</script>

<template>
    <Head title="Manage Franchise Owners" />

    <AuthenticatedLayout>
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Franchise Owners</h1>
                <p class="text-gray-600 text-sm">Manage operators and ownership profiles.</p>
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
                        placeholder="Search owners..." 
                    />
                </div>

                <button 
                    @click="openFilterModal"
                    class="p-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600 shadow-sm transition-colors relative"
                    title="Filter Owners"
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
                    Add Owner
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Owner Name</th>
                            <th class="px-6 py-4">Contact & Location</th>
                            <th class="px-6 py-4">TIN Number</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold border border-gray-300 overflow-hidden">
                                        <img v-if="user.user_photo" :src="'/storage/' + user.user_photo" class="h-full w-full object-cover" />
                                        <span v-else>{{ user.first_name.charAt(0) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900">{{ user.last_name }}, {{ user.first_name }}</div>
                                        <div class="text-gray-500 text-xs">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="text-sm font-medium">{{ user.contact_number || 'N/A' }}</div>
                                <div class="text-xs text-gray-400">{{ user.barangay }}, {{ user.city }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="user.operator && user.operator.tin_number" class="font-mono bg-gray-100 px-2 py-1 rounded text-gray-700 text-xs border">
                                    {{ user.operator.tin_number }}
                                </span>
                                <span v-else class="text-gray-400 text-xs italic">N/A</span>
                            </td>
                            <td class="px-6 py-4">
                                <span 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                >
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    @click="openEditModal(user)" 
                                    class="text-gray-400 hover:text-blue-600 font-medium transition-colors"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No franchise owners found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="users.links && users.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ users.meta.from }} to {{ users.meta.to }} of {{ users.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="(link, key) in users.meta.links" 
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

        <Modal :show="showAddModal" @close="closeAddModal">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Add Franchise Owner</h2>
                    <p class="text-sm text-gray-500">Create a new operator account.</p>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-5">
                    <div class="flex justify-center mb-4">
                        <div class="relative h-24 w-24">
                            <div class="h-24 w-24 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center overflow-hidden">
                                <img v-if="addPhotoPreview" :src="addPhotoPreview" class="h-full w-full object-cover" />
                                <svg v-else class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <label class="absolute bottom-0 right-0 bg-blue-600 rounded-full p-1.5 cursor-pointer shadow-md hover:bg-blue-700 transition-colors">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <input type="file" class="hidden" @change="handleAddPhotoChange" accept="image/*" />
                            </label>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel>First Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.first_name" required />
                            </div>
                            <div>
                                <InputLabel>Middle Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.middle_name" />
                            </div>
                            <div>
                                <InputLabel>Last Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.last_name" required />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Email Address</InputLabel>
                            <TextInput type="email" class="mt-1 block w-full" v-model="addForm.email" required />
                        </div>
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.contact_number" />
                        </div>
                        <div class="md:col-span-2">
                             <InputLabel>TIN Number <span class="text-gray-400 text-xs">(Operator ID)</span></InputLabel>
                             <TextInput type="text" class="mt-1 block w-full font-mono" v-model="addForm.tin_number" placeholder="000-000-000-000" />
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-2">Address</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <InputLabel>Street Address</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.street_address" required />
                            </div>
                            
                            <div class="relative">
                                <InputLabel>Barangay</InputLabel>
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

                            <div class="md:col-span-2">
                                <InputLabel>City</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.city" required />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel>Password</InputLabel>
                                <TextInput type="password" class="mt-1 block w-full" v-model="addForm.password" required />
                            </div>
                            <div>
                                <InputLabel>Confirm Password</InputLabel>
                                <TextInput type="password" class="mt-1 block w-full" v-model="addForm.password_confirmation" required />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Create Owner</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Owner</h2>
                    <p class="text-sm text-gray-500">Update operator details.</p>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-5">
                    
                    <div class="flex justify-center mb-4">
                        <div class="relative h-24 w-24">
                            <div class="h-24 w-24 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center overflow-hidden">
                                <img v-if="editPhotoPreview" :src="editPhotoPreview" class="h-full w-full object-cover" />
                                <svg v-else class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <label class="absolute bottom-0 right-0 bg-blue-600 rounded-full p-1.5 cursor-pointer shadow-md hover:bg-blue-700 transition-colors">
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <input type="file" class="hidden" @change="handleEditPhotoChange" accept="image/*" />
                            </label>
                        </div>
                    </div>

                     <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel>First Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.first_name" required />
                            </div>
                            <div>
                                <InputLabel>Middle Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.middle_name" />
                            </div>
                            <div>
                                <InputLabel>Last Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.last_name" required />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Email Address</InputLabel>
                            <TextInput type="email" class="mt-1 block w-full" v-model="editForm.email" required />
                        </div>
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.contact_number" />
                        </div>
                        <div class="md:col-span-2">
                             <InputLabel>TIN Number</InputLabel>
                             <TextInput type="text" class="mt-1 block w-full font-mono" v-model="editForm.tin_number" />
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-2">Address</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <InputLabel>Street Address</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.street_address" required />
                            </div>
                            
                            <div class="relative">
                                <InputLabel>Barangay</InputLabel>
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

                            <div class="md:col-span-2">
                                <InputLabel>City</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.city" required />
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel>Account Status</InputLabel>
                        <select 
                            v-model="editForm.status" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <h4 class="text-xs font-bold text-gray-500 mb-2">Change Password (Leave blank to keep current)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel>New Password</InputLabel>
                                <TextInput type="password" class="mt-1 block w-full" v-model="editForm.password" />
                            </div>
                            <div>
                                <InputLabel>Confirm Password</InputLabel>
                                <TextInput type="password" class="mt-1 block w-full" v-model="editForm.password_confirmation" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeEditModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing">Update Owner</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showFilterModal" @close="closeFilterModal" maxWidth="sm">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h2 class="text-lg font-bold text-gray-900">Filter Owners</h2>
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