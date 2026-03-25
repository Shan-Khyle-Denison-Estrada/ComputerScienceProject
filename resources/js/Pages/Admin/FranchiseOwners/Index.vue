<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

// --- PROPS ---
const props = defineProps({
    users: Object, 
    filters: Object
});

// --- STATE MANAGEMENT ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showFilterModal = ref(false); 
const showFranchisesModal = ref(false); // New modal for listing franchises
const selectedUser = ref(null); // Keep track of the owner we clicked

const search = ref(props.filters.search || '');

// --- FILTER STATE ---
const filterForm = ref({
    status: props.filters.status || '', 
});

// --- PHOTO STATE ---
const addPhotoPreview = ref(null);
const editPhotoPreview = ref(null);

// --- API STATE FOR ADDRESSES ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

const activeDropdown = ref(null);

onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load locations:", error);
    }
});

// Contextually determine which form is active to filter lists against
const activeForm = computed(() => {
    if (showAddModal.value) return addForm;
    if (showEditModal.value) return editForm;
    return null;
});

const filteredProvinces = computed(() => {
    if (!activeForm.value || !activeForm.value.province) return provincesList.value;
    return provincesList.value.filter(p => p.name.toLowerCase().includes(activeForm.value.province.toLowerCase()));
});

const filteredCities = computed(() => {
    if (!activeForm.value || !activeForm.value.city) return citiesList.value;
    return citiesList.value.filter(c => c.name.toLowerCase().includes(activeForm.value.city.toLowerCase()));
});

const filteredBarangays = computed(() => {
    if (!activeForm.value || !activeForm.value.barangay) return barangaysList.value;
    return barangaysList.value.filter(b => b.name.toLowerCase().includes(activeForm.value.barangay.toLowerCase()));
});

const selectProvince = async (prov, formRef) => {
    formRef.province = prov.name;
    formRef.clearErrors('province');
    activeDropdown.value = null;
    
    // Reset dependents
    formRef.city = '';
    formRef.barangay = '';
    citiesList.value = [];
    barangaysList.value = [];

    try {
        if (prov.isNCR) {
            const res = await fetch(`https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        } else {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        }
    } catch (error) {
        console.error("Failed to load cities:", error);
    }
};

const selectCity = async (city, formRef) => {
    formRef.city = city.name;
    formRef.clearErrors('city');
    activeDropdown.value = null;
    
    formRef.barangay = '';
    barangaysList.value = [];

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
        barangaysList.value = await res.json();
    } catch (error) {
        console.error("Failed to load barangays:", error);
    }
};

const selectBarangay = (brgy, formRef) => {
    formRef.barangay = brgy.name;
    formRef.clearErrors('barangay');
    activeDropdown.value = null;
};

// --- NUMBER FORMATTERS ---
const formatContactNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 11) parts = parts.substring(0, 11); 

    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 4);
    if (parts.length >= 5) formatted += '-' + parts.substring(4, 7);
    if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) formatted += '-';

    return formatted;
};

const formatTinNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 14) parts = parts.substring(0, 14); 

    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 3);
    if (parts.length >= 4) formatted += '-' + parts.substring(3, 6);
    if (parts.length >= 7) formatted += '-' + parts.substring(6, 9);
    if (parts.length >= 10) formatted += '-' + parts.substring(9, 14);
    if (val.endsWith('-') && (parts.length === 3 || parts.length === 6 || parts.length === 9)) formatted += '-';

    return formatted;
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
    province: '',
    city: '',
    barangay: '',
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
    province: '',
    city: '',
    barangay: '',
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
    citiesList.value = [];
    barangaysList.value = [];
    addPhotoPreview.value = null;
};

const closeAddModal = () => {
    showAddModal.value = false;
    activeDropdown.value = null;
    addForm.reset();
    addPhotoPreview.value = null;
};

const submitAdd = () => {
    addForm.post(route('admin.franchise-owners.store'), {
        onSuccess: () => closeAddModal(),
        forceFormData: true 
    });
};

const openEditModal = async (user) => {
    editForm.id = user.id;
    editForm.first_name = user.first_name;
    editForm.middle_name = user.middle_name;
    editForm.last_name = user.last_name;
    editForm.email = user.email;
    editForm.contact_number = formatContactNumber(user.contact_number);
    editForm.street_address = user.street_address;
    editForm.province = user.province;
    editForm.city = user.city;
    editForm.barangay = user.barangay;
    editForm.status = user.status; 
    editForm.tin_number = user.operator && user.operator.tin_number ? formatTinNumber(user.operator.tin_number) : '';
    
    // Set preview to existing photo if available
    editPhotoPreview.value = user.user_photo ? `/storage/${user.user_photo}` : null;

    showEditModal.value = true;

    // Pre-fetch locations to prepopulate dropdowns for current values
    if (user.province && provincesList.value.length) {
        const prov = provincesList.value.find(p => p.name === user.province);
        if (prov) {
            try {
                let res = await fetch(prov.isNCR 
                    ? `https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities`
                    : `https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`
                );
                citiesList.value = await res.json();

                if (user.city) {
                    const city = citiesList.value.find(c => c.name === user.city);
                    if (city) {
                        const brgyRes = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
                        barangaysList.value = await brgyRes.json();
                    }
                }
            } catch (e) { console.error("Error pre-fetching location APIs", e); }
        }
    }
};

const closeEditModal = () => {
    showEditModal.value = false;
    activeDropdown.value = null;
    editForm.reset();
    editPhotoPreview.value = null;
};

const submitEdit = () => {
    editForm.post(route('admin.franchise-owners.update', editForm.id), { 
        onSuccess: () => closeEditModal(),
        forceFormData: true
    });
};

const openFranchisesModal = (user) => {
    selectedUser.value = user;
    showFranchisesModal.value = true;
};

const closeFranchisesModal = () => {
    showFranchisesModal.value = false;
    setTimeout(() => {
        selectedUser.value = null;
    }, 300);
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
        
        <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-10"></div>

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
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative z-0">
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
                        <tr 
                            v-for="user in users.data" 
                            :key="user.id" 
                            @click="openFranchisesModal(user)"
                            class="hover:bg-blue-50 cursor-pointer transition-colors group"
                        >
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
                                <div class="text-xs text-gray-400">{{ user.street_address }}, {{ user.barangay }}, {{ user.city }}, {{ user.province }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span v-if="user.operator && user.operator.tin_number" class="font-mono bg-gray-100 px-2 py-1 rounded text-gray-700 text-xs border">
                                    {{ formatTinNumber(user.operator.tin_number) }}
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
                            <td class="px-6 py-4 text-right relative">
                                <button 
                                    @click.stop="openEditModal(user)" 
                                    class="text-gray-400 hover:text-blue-600 font-medium transition-colors p-2 rounded hover:bg-white border border-transparent hover:border-gray-200"
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
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="users.links && users.links.length > 3">
                <div class="text-xs text-gray-500">
                    Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                </div>
                
                <Pagination :links="users.links" />
            </div>
        </div>

        <Modal :show="showFranchisesModal" @close="closeFranchisesModal" maxWidth="2xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ selectedUser?.first_name }} {{ selectedUser?.last_name }}'s Franchises</h2>
                        <p class="text-sm text-gray-500">Select a franchise card to view its full details.</p>
                    </div>
                    <button @click="closeFranchisesModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div v-if="selectedUser?.franchises?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Link 
                        v-for="franchise in selectedUser.franchises" 
                        :key="franchise.id" 
                        :href="route('admin.franchises.show', franchise.id)"
                        class="block border border-gray-200 rounded-xl p-5 hover:border-blue-500 hover:shadow-md transition-all group bg-white"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-1">Franchise Number</span>
                                <h3 class="text-xl font-extrabold text-blue-700 group-hover:text-blue-800">{{ franchise.franchise_number }}</h3>
                            </div>
                            <span 
                                class="px-2.5 py-1 text-[10px] font-semibold rounded-full uppercase tracking-wider border"
                                :class="{
                                    'bg-green-50 text-green-700 border-green-200': franchise.status === 'active',
                                    'bg-yellow-50 text-yellow-700 border-yellow-200': franchise.status === 'pending',
                                    'bg-red-50 text-red-700 border-red-200': franchise.status === 'expired' || franchise.status === 'dropped',
                                    'bg-gray-50 text-gray-700 border-gray-200': !['active', 'pending', 'expired', 'dropped'].includes(franchise.status)
                                }"
                            >
                                {{ franchise.status || 'Unknown' }}
                            </span>
                        </div>
                    </Link>
                </div>

                <div v-else class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <h3 class="text-sm font-medium text-gray-900">No Franchises Assigned</h3>
                    <p class="text-sm text-gray-500 mt-1">This operator does not currently own any active franchises.</p>
                </div>
            </div>
        </Modal>

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
                                <InputLabel>First Name<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.first_name" required />
                            </div>
                            <div>
                                <InputLabel>Middle Name</InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.middle_name" />
                            </div>
                            <div>
                                <InputLabel>Last Name<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.last_name" required />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Email Address<span class="text-red-600"> *</span></InputLabel>
                            <TextInput type="email" class="mt-1 block w-full" v-model="addForm.email" required />
                        </div>
                        <div>
                            <InputLabel>Contact Number<span class="text-red-600"> *</span></InputLabel>
                            <TextInput 
                                v-model="addForm.contact_number" 
                                @input="addForm.contact_number = formatContactNumber($event.target.value); addForm.clearErrors('contact_number')" 
                                placeholder="09XX-XXX-XXXX" 
                                maxlength="13" 
                                class="mt-1 block w-full" 
                            />
                        </div>
                        <div class="md:col-span-2">
                             <InputLabel>TIN Number <span class="text-red-600"> *</span></InputLabel>
                             <TextInput 
                                v-model="addForm.tin_number" 
                                @input="addForm.tin_number = formatTinNumber($event.target.value); addForm.clearErrors('tin_number')" 
                                placeholder="XXX-XXX-XXX-00000" 
                                maxlength="17" 
                                class="mt-1 block w-full font-mono" 
                            />
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-2">Address</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-3">
                                <InputLabel>Street Address<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full" v-model="addForm.street_address" required />
                            </div>
                            
                            <div class="relative">
                                <InputLabel>Province<span class="text-red-600"> *</span></InputLabel>
                                <div class="relative z-30">
                                    <TextInput 
                                        v-model="addForm.province" 
                                        @focus="activeDropdown = 'province'"
                                        @input="addForm.clearErrors('province')"
                                        class="mt-1 block w-full"
                                        placeholder="Search Province..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov, addForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ prov.name }}
                                            </li>
                                            <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <InputLabel>City/Municipality<span class="text-red-600"> *</span></InputLabel>
                                <div class="relative z-20">
                                    <TextInput 
                                        v-model="addForm.city" 
                                        @focus="activeDropdown = 'city'"
                                        @input="addForm.clearErrors('city')"
                                        :disabled="!citiesList.length"
                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Search City..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city, addForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ city.name }}
                                            </li>
                                            <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <InputLabel>Barangay<span class="text-red-600"> *</span></InputLabel>
                                <div class="relative z-10">
                                    <TextInput 
                                        v-model="addForm.barangay" 
                                        @focus="activeDropdown = 'barangay'"
                                        @input="addForm.clearErrors('barangay')"
                                        :disabled="!barangaysList.length"
                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Search Barangay..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy, addForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ brgy.name }}
                                            </li>
                                            <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel>Password<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="password" class="mt-1 block w-full" v-model="addForm.password" required />
                            </div>
                            <div>
                                <InputLabel>Confirm Password<span class="text-red-600"> *</span></InputLabel>
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
                            <TextInput 
                                v-model="editForm.contact_number" 
                                @input="editForm.contact_number = formatContactNumber($event.target.value); editForm.clearErrors('contact_number')" 
                                placeholder="09XX-XXX-XXXX" 
                                maxlength="13" 
                                class="mt-1 block w-full" 
                            />
                        </div>
                        <div class="md:col-span-2">
                             <InputLabel>TIN Number</InputLabel>
                             <TextInput 
                                v-model="editForm.tin_number" 
                                @input="editForm.tin_number = formatTinNumber($event.target.value); editForm.clearErrors('tin_number')" 
                                placeholder="XXX-XXX-XXX-00000" 
                                maxlength="17" 
                                class="mt-1 block w-full font-mono" 
                            />
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
                                <InputLabel>Province</InputLabel>
                                <div class="relative z-30">
                                    <TextInput 
                                        v-model="editForm.province" 
                                        @focus="activeDropdown = 'province'"
                                        @input="editForm.clearErrors('province')"
                                        class="mt-1 block w-full"
                                        placeholder="Search Province..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov, editForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ prov.name }}
                                            </li>
                                            <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <InputLabel>City/Municipality</InputLabel>
                                <div class="relative z-20">
                                    <TextInput 
                                        v-model="editForm.city" 
                                        @focus="activeDropdown = 'city'"
                                        @input="editForm.clearErrors('city')"
                                        :disabled="!citiesList.length"
                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Search City..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city, editForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ city.name }}
                                            </li>
                                            <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="relative">
                                <InputLabel>Barangay</InputLabel>
                                <div class="relative z-10">
                                    <TextInput 
                                        v-model="editForm.barangay" 
                                        @focus="activeDropdown = 'barangay'"
                                        @input="editForm.clearErrors('barangay')"
                                        :disabled="!barangaysList.length"
                                        class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                        placeholder="Search Barangay..."
                                        autocomplete="off"
                                        required
                                    />
                                    <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-40 overflow-y-auto z-50">
                                        <ul class="py-1">
                                            <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy, editForm)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50">
                                                {{ brgy.name }}
                                            </li>
                                            <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                        </ul>
                                    </div>
                                </div>
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