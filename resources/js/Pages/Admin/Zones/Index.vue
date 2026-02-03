<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    zones: Array,
    filters: Object,
    barangays: Array 
});

// --- STATE ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showBarangayModal = ref(false); 

// --- SEARCH ---
const search = ref(''); 

// Filter Zones by Search
const visibleZones = computed(() => {
    if (!search.value) return props.zones;
    const q = search.value.toLowerCase();
    return props.zones.filter(zone => 
        zone.description.toLowerCase().includes(q) || 
        zone.color.toLowerCase().includes(q)
    );
});

// --- BARANGAY MODAL STATE ---
const barangaySearch = ref('');
const selectedBarangayToAdd = ref('');
const editingBarangayId = ref(null);

// --- FORMS ---
const addForm = useForm({ description: '', color: '', coverage: [] });
const editForm = useForm({ id: null, description: '', color: '', coverage: [], _method: 'PUT' });
const barangayAddForm = useForm({ name: '' });
const barangayEditForm = useForm({ id: null, name: '', _method: 'PUT' });

// --- HELPERS ---
const toSentenceCase = (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
};

const applySentenceCase = (form, field) => {
    form[field] = toSentenceCase(form[field]);
};

// --- LOGIC: EXCLUSIVE COVERAGE ---
// Get a Set of all barangays currently used by ANY zone
const getGloballyUsedBarangays = (exceptZoneId = null) => {
    const used = new Set();
    props.zones.forEach(zone => {
        if (zone.id === exceptZoneId) return; 
        if (Array.isArray(zone.coverage)) {
            zone.coverage.forEach(name => used.add(name));
        }
    });
    return used;
};

// Filter the Dropdown List (Hide used + already selected)
const availableBarangays = (formType) => {
    const currentCoverage = formType === 'add' ? addForm.coverage : editForm.coverage;
    const currentZoneId = formType === 'edit' ? editForm.id : null;
    const globallyUsed = getGloballyUsedBarangays(currentZoneId);

    return props.barangays.filter(b => {
        return !globallyUsed.has(b.name) && !currentCoverage.includes(b.name);
    });
};

const addCoverageItem = (formType) => {
    if (!selectedBarangayToAdd.value) return;
    const val = selectedBarangayToAdd.value;
    if (formType === 'add') addForm.coverage.push(val);
    else editForm.coverage.push(val);
    selectedBarangayToAdd.value = ''; 
};

const removeCoverageItem = (formType, index) => {
    if (formType === 'add') addForm.coverage.splice(index, 1);
    else editForm.coverage.splice(index, 1);
};

// --- ACTIONS ---
const openAddModal = () => {
    addForm.reset();
    selectedBarangayToAdd.value = '';
    showAddModal.value = true;
};

const openEditModal = (zone) => {
    editForm.id = zone.id;
    editForm.description = zone.description;
    editForm.color = zone.color;
    editForm.coverage = Array.isArray(zone.coverage) ? [...zone.coverage] : [];
    selectedBarangayToAdd.value = '';
    showEditModal.value = true;
};

const submitAdd = () => {
    addForm.post(route('admin.zones.store'), { onSuccess: () => showAddModal.value = false });
};

const submitEdit = () => {
    editForm.post(route('admin.zones.update', editForm.id), { onSuccess: () => showEditModal.value = false });
};

const deleteZone = (id) => {
    if (confirm('Are you sure you want to delete this zone?')) {
        router.delete(route('admin.zones.destroy', id), { onSuccess: () => showEditModal.value = false });
    }
};

// --- BARANGAY ACTIONS ---
const openBarangayModal = () => {
    barangayAddForm.reset();
    barangaySearch.value = '';
    editingBarangayId.value = null;
    showBarangayModal.value = true;
};

const filteredBarangays = computed(() => {
    let list = props.barangays;
    if (barangaySearch.value) {
        const q = barangaySearch.value.toLowerCase();
        list = list.filter(b => b.name.toLowerCase().includes(q));
    }
    return list;
});

const addBarangay = () => {
    barangayAddForm.name = toSentenceCase(barangayAddForm.name); 
    barangayAddForm.post(route('admin.barangays.store'), {
        onSuccess: () => barangayAddForm.reset(),
        preserveScroll: true
    });
};

const startEditingBarangay = (brgy) => {
    editingBarangayId.value = brgy.id;
    barangayEditForm.id = brgy.id;
    barangayEditForm.name = brgy.name;
};

const updateBarangay = () => {
    barangayEditForm.name = toSentenceCase(barangayEditForm.name);
    barangayEditForm.put(route('admin.barangays.update', barangayEditForm.id), {
        onSuccess: () => { editingBarangayId.value = null; barangayEditForm.reset(); },
        preserveScroll: true
    });
};

const deleteBarangay = (id) => {
    if(confirm('Delete this Barangay?')) {
        router.delete(route('admin.barangays.destroy', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Manage Zones" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Zone Management</h1>
                <p class="text-gray-600 text-sm">Manage operational zones and coverage areas.</p>
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
                        class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm transition" 
                        placeholder="Search zones..." 
                    />
                </div>

                <SecondaryButton @click="openBarangayModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Barangays
                </SecondaryButton>

                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Zone
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Description</th>
                            <th class="px-6 py-4">Color</th>
                            <th class="px-6 py-4">Coverage (Barangays)</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="zone in visibleZones" :key="zone.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ zone.description }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                    {{ zone.color }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="(place, idx) in zone.coverage" :key="idx" class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded border border-blue-100">
                                        {{ place }}
                                    </span>
                                    <span v-if="!zone.coverage || zone.coverage.length === 0" class="text-gray-400 italic">No coverage assigned</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openEditModal(zone)" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="visibleZones.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                {{ search ? 'No zones match your search.' : 'No zones found.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showAddModal" @close="showAddModal = false">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b pb-4">Create New Zone</h2>
                <form @submit.prevent="submitAdd" class="space-y-5">
                    <div>
                        <InputLabel>Description / Name <span class="text-red-500">*</span></InputLabel>
                        <TextInput type="text" class="mt-1 block w-full" v-model="addForm.description" @blur="applySentenceCase(addForm, 'description')" required placeholder="e.g., Downtown zone" />
                    </div>
                    <div>
                        <InputLabel>Color Label <span class="text-red-500">*</span></InputLabel>
                        <TextInput type="text" class="mt-1 block w-full" v-model="addForm.color" @blur="applySentenceCase(addForm, 'color')" required placeholder="e.g., Red" />
                    </div>
                    <div>
                        <InputLabel>Add Coverage (Barangay) <span class="text-red-500">*</span></InputLabel>
                        <div class="flex gap-2 mt-1">
                            <select v-model="selectedBarangayToAdd" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="" disabled>Select a Barangay</option>
                                <option v-for="brgy in availableBarangays('add')" :key="brgy.id" :value="brgy.name">{{ brgy.name }}</option>
                            </select>
                            <SecondaryButton type="button" @click="addCoverageItem('add')">Add</SecondaryButton>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 p-3 bg-gray-50 rounded-md border border-gray-200 min-h-[60px] items-center">
                            <span v-if="addForm.coverage.length === 0" class="text-gray-400 text-sm italic w-full text-center">Add Barangay</span>
                            <span v-else v-for="(item, index) in addForm.coverage" :key="index" class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-300 text-sm shadow-sm">
                                {{ item }}
                                <button type="button" @click="removeCoverageItem('add', index)" class="ml-2 text-red-500 hover:text-red-700 font-bold">&times;</button>
                            </span>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="addForm.processing">Create Zone</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6 border-b pb-4">Edit Zone</h2>
                <form @submit.prevent="submitEdit" class="space-y-5">
                    <div>
                        <InputLabel>Description / Name <span class="text-red-500">*</span></InputLabel>
                        <TextInput type="text" class="mt-1 block w-full" v-model="editForm.description" @blur="applySentenceCase(editForm, 'description')" required />
                    </div>
                    <div>
                        <InputLabel>Color Label <span class="text-red-500">*</span></InputLabel>
                        <TextInput type="text" class="mt-1 block w-full" v-model="editForm.color" @blur="applySentenceCase(editForm, 'color')" required />
                    </div>
                    <div>
                        <InputLabel>Add Coverage (Barangay) <span class="text-red-500">*</span></InputLabel>
                        <div class="flex gap-2 mt-1">
                             <select v-model="selectedBarangayToAdd" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="" disabled>Select a Barangay</option>
                                <option v-for="brgy in availableBarangays('edit')" :key="brgy.id" :value="brgy.name">{{ brgy.name }}</option>
                            </select>
                            <SecondaryButton type="button" @click="addCoverageItem('edit')">Add</SecondaryButton>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2 p-3 bg-gray-50 rounded-md border border-gray-200 min-h-[60px] items-center">
                            <span v-if="editForm.coverage.length === 0" class="text-gray-400 text-sm italic w-full text-center">Add Barangay</span>
                            <span v-else v-for="(item, index) in editForm.coverage" :key="index" class="inline-flex items-center px-2 py-1 rounded bg-white border border-gray-300 text-sm shadow-sm">
                                {{ item }}
                                <button type="button" @click="removeCoverageItem('edit', index)" class="ml-2 text-red-500 hover:text-red-700 font-bold">&times;</button>
                            </span>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-between items-center border-t pt-4">
                        <button type="button" @click="deleteZone(editForm.id)" class="text-red-600 hover:text-red-800 text-sm font-medium underline">Delete Zone</button>
                        <div class="flex gap-3">
                            <SecondaryButton @click="showEditModal = false">Cancel</SecondaryButton>
                            <PrimaryButton :disabled="editForm.processing">Save Changes</PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showBarangayModal" @close="showBarangayModal = false">
            <div class="p-6 h-[70vh] flex flex-col">
                
                <div class="shrink-0 space-y-4 mb-2">
                    <div class="border-b pb-4 flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Manage Barangays</h2>
                            <p class="text-sm text-gray-500 mt-1">Add, edit, or remove city barangays.</p>
                        </div>
                        <button @click="showBarangayModal = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div class="flex gap-2 relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 z-10">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            <input 
                                type="text" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 pl-10" 
                                v-model="barangayAddForm.name" 
                                @blur="applySentenceCase(barangayAddForm, 'name')"
                                placeholder="Type new barangay name..." 
                                @keyup.enter="addBarangay"
                            />
                            <PrimaryButton @click="addBarangay" :disabled="barangayAddForm.processing" class="shrink-0">
                                Add New
                            </PrimaryButton>
                        </div>

                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 z-10">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input 
                                v-model="barangaySearch" 
                                type="text" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 pl-10 text-sm bg-gray-50" 
                                placeholder="Filter list..." 
                            />
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto border rounded-lg bg-white shadow-inner min-h-0 relative">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-100 text-gray-600 font-semibold sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 bg-gray-100">Barangay Name</th>
                                <th class="px-4 py-3 text-right w-24 bg-gray-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="brgy in filteredBarangays" :key="brgy.id" class="group hover:bg-blue-50 transition duration-150">
                                <td class="px-4 py-3">
                                    <div v-if="editingBarangayId === brgy.id">
                                        <input 
                                            type="text" 
                                            class="block w-full text-sm py-1 px-2 h-8 border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500" 
                                            v-model="barangayEditForm.name"
                                            @blur="applySentenceCase(barangayEditForm, 'name')" 
                                            @keyup.enter="updateBarangay"
                                        />
                                    </div>
                                    <div v-else class="flex items-center gap-3 text-gray-800 font-medium">
                                        <svg class="h-4 w-4 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        {{ brgy.name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="editingBarangayId === brgy.id" class="flex justify-end gap-1">
                                        <button @click="updateBarangay" class="p-1 bg-green-100 text-green-700 rounded hover:bg-green-200"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg></button>
                                        <button @click="editingBarangayId = null" class="p-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                                    </div>
                                    <div v-else class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="startEditingBarangay(brgy)" class="text-blue-500 hover:text-blue-700 p-1 hover:bg-blue-100 rounded"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></button>
                                        <button @click="deleteBarangay(brgy.id)" class="text-red-500 hover:text-red-700 p-1 hover:bg-red-100 rounded"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredBarangays.length === 0">
                                <td colspan="2" class="px-4 py-8 text-center text-gray-400 italic">No barangays found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-4 border-t flex justify-end shrink-0">
                    <SecondaryButton @click="showBarangayModal = false">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>