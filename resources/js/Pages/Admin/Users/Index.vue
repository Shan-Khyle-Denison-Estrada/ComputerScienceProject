<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; // <-- Added Pagination Import
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- PROPS ---
const props = defineProps({
    users: Object, // The paginated users object
    filters: Object // Search and filter state
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showFilterModal = ref(false);
const search = ref(props.filters.search || '');

// Photo Previews
const addPhotoPreview = ref(null);
const addPhotoInput = ref(null);
const editPhotoPreview = ref(null);
const editPhotoInput = ref(null);

// --- FORMS ---

// 1. ADD USER FORM
const addForm = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',    
    street_address: '',    
    barangay: '',          
    city: '',              
    role: 'admin', 
    password: '',
    password_confirmation: '',
    photo: null,
});

// 2. EDIT USER FORM
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
    role: '',
    status: '',
    password: '', 
    password_confirmation: '',
    photo: null,
    _method: 'PUT'
});

// 3. FILTER FORM
const filterForm = ref({
    status: props.filters.status || '',
});

// --- ACTIONS: ADD USER ---
const openAddModal = () => showAddModal.value = true;
const closeAddModal = () => {
    showAddModal.value = false;
    addForm.reset();
    addPhotoPreview.value = null;
};

const triggerAddPhoto = () => addPhotoInput.value.click();
const handleAddPhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        addForm.photo = file;
        addPhotoPreview.value = URL.createObjectURL(file);
    }
};

const submitAdd = () => {
    addForm.post(route('admin.users.store'), {
        onSuccess: () => closeAddModal(),
        onFinish: () => addForm.reset('password', 'password_confirmation'),
    });
};

// --- ACTIONS: EDIT USER ---
const openEditModal = (user) => {
    editForm.id = user.id; 
    editForm.first_name = user.first_name;
    editForm.middle_name = user.middle_name;
    editForm.last_name = user.last_name;
    editForm.email = user.email;
    
    // Populate new fields
    editForm.contact_number = user.contact_number;
    editForm.street_address = user.street_address;
    editForm.barangay = user.barangay;
    editForm.city = user.city;

    editForm.role = user.role; 
    editForm.status = user.status;
    
    editPhotoPreview.value = user.user_photo ? `/storage/${user.user_photo}` : null;
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
    editPhotoPreview.value = null;
};

const triggerEditPhoto = () => editPhotoInput.value.click();
const handleEditPhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        editForm.photo = file;
        editPhotoPreview.value = URL.createObjectURL(file);
    }
};

const submitEdit = () => {
    editForm.post(route('admin.users.update', editForm.id), { 
        onSuccess: () => closeEditModal(),
    });
};

// --- ACTIONS: FILTERS & SEARCH ---
const openFilterModal = () => showFilterModal.value = true;
const closeFilterModal = () => showFilterModal.value = false;

// Debounced search or Enter key search
const handleSearch = () => {
    router.get(route('admin.users.index'), { 
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
    <Head title="Manage Users" />

    <AuthenticatedLayout>
        
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600 text-sm">Manage administrator accounts.</p>
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
                        placeholder="Search admins..." 
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
                    Add Admin
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4">Contact</th> <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 flex items-center justify-center overflow-hidden border border-gray-200">
                                        <img v-if="user.user_photo" :src="`/storage/${user.user_photo}`" class="h-full w-full object-cover" /> 
                                        <span v-else class="text-blue-600 font-bold text-lg">
                                            {{ user.first_name.charAt(0) }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900">{{ user.first_name }} {{ user.last_name }}</div>
                                        <div class="text-gray-500 text-xs">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                {{ user.contact_number || '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800 uppercase">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
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
                                No admin users found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between bg-gray-50" v-if="users.links || (users.meta && users.meta.links)">
                <div class="text-xs text-gray-500 mb-4 sm:mb-0">
                    Showing {{ users.meta ? users.meta.from : users.from }} to {{ users.meta ? users.meta.to : users.to }} of {{ users.meta ? users.meta.total : users.total }} results
                </div>
                <Pagination :links="users.meta ? users.meta.links : users.links" />
            </div>

        </div>

        <Modal :show="showAddModal" @close="closeAddModal">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Create New Admin</h2>
                    <p class="text-sm text-gray-500">Register a new administrator.</p>
                </div>

                <form @submit.prevent="submitAdd" class="space-y-5">
                    <div class="flex flex-col items-center justify-center mb-4">
                        <div class="relative group cursor-pointer h-24 w-24 rounded-full border-4 border-gray-100 bg-gray-50 overflow-hidden hover:border-blue-100 transition" @click="triggerAddPhoto">
                            <img v-if="addPhotoPreview" :src="addPhotoPreview" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex flex-col items-center justify-center text-gray-400">
                                <svg class="h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2">Profile Photo</span>
                        <input type="file" ref="addPhotoInput" class="hidden" accept="image/*" @change="handleAddPhotoChange" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel>First Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.first_name" required />
                        </div>
                        <div>
                            <InputLabel>Middle Name</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.middle_name" placeholder="(Optional)" />
                        </div>
                        <div>
                            <InputLabel>Last Name <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.last_name" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Email Address <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="email" class="mt-1 block w-full" v-model="addForm.email" required />
                        </div>
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="addForm.contact_number" />
                        </div>
                    </div>

                    <div class="border-t pt-2">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Address Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel>Street</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.street_address" />
                            </div>
                            <div>
                                <InputLabel>Barangay</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.barangay" />
                            </div>
                            <div>
                                <InputLabel>City</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.city" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel>Role <span class="text-red-500">*</span></InputLabel>
                        <select v-model="addForm.role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="admin">Administrator</option>
                            <option value="franchise_owner">Franchise Owner</option>
                            <option value="collector">Collector (CTO)</option>
                            <option value="evaluator">Evaluator</option>
                            <option value="inspector">Inspector</option>
                            <option value="city_anti_pollution_officer">City Anti-Pollution Officer</option>
                            <option value="reviewer">Reviewer (TAB Head)</option>
                            <option value="sp_approver">SP Approver</option>
                            <option value="tab_approver">TAB Approver</option>
                            <option value="releaser">Releaser</option>
                            <option value="encoder">Encoder</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Password <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="password" class="mt-1 block w-full" v-model="addForm.password" required />
                        </div>
                        <div>
                            <InputLabel>Confirm Password <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="password" class="mt-1 block w-full" v-model="addForm.password_confirmation" required />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeAddModal">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Create User</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showEditModal" @close="closeEditModal">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Admin</h2>
                    <p class="text-sm text-gray-500">Update account details.</p>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-5">
                    <div class="flex flex-col items-center justify-center mb-4">
                        <div class="relative group cursor-pointer h-24 w-24 rounded-full border-4 border-gray-100 bg-gray-50 overflow-hidden hover:border-blue-100 transition" @click="triggerEditPhoto">
                            <img v-if="editPhotoPreview" :src="editPhotoPreview" class="h-full w-full object-cover" />
                            <div v-else class="h-full w-full flex items-center justify-center bg-blue-100 text-blue-500 font-bold text-2xl">
                                {{ editForm.first_name ? editForm.first_name.charAt(0) : 'U' }}
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2">Change Photo</span>
                        <input type="file" ref="editPhotoInput" class="hidden" accept="image/*" @change="handleEditPhotoChange" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <InputLabel>Email Address <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="email" class="mt-1 block w-full" v-model="editForm.email" required />
                        </div>
                        <div>
                            <InputLabel>Contact Number</InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="editForm.contact_number" />
                        </div>
                    </div>

                    <div class="border-t pt-2">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Address Info</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel>Street</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.street_address" />
                            </div>
                            <div>
                                <InputLabel>Barangay</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.barangay" />
                            </div>
                            <div>
                                <InputLabel>City</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="editForm.city" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Role <span class="text-red-500">*</span></InputLabel>
                            <select v-model="editForm.role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="admin">Administrator</option>
                                <option value="franchise_owner">Franchise Owner</option>
                            </select>
                        </div>
                         <div>
                             <InputLabel>Account Status <span class="text-red-500">*</span></InputLabel>
                             <select v-model="editForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                 <option value="active">Active</option>
                                 <option value="inactive">Inactive</option>
                             </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-4">
                        <div>
                            <InputLabel>New Password</InputLabel>
                            <TextInput type="password" class="mt-1 block w-full" v-model="editForm.password" placeholder="Leave blank to keep" />
                        </div>
                        <div>
                            <InputLabel>Confirm New Password</InputLabel>
                            <TextInput type="password" class="mt-1 block w-full" v-model="editForm.password_confirmation" placeholder="Leave blank to keep" />
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
                    <h2 class="text-lg font-bold text-gray-900">Filter Admins</h2>
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