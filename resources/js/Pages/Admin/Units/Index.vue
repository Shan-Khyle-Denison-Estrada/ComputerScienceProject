<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- PROPS ---
const props = defineProps({
    units: Object,
    unitMakes: Array,
    filters: Object
});

// --- STATE ---
const showAddModal = ref(false);
const showEditModal = ref(false);
const showMakesModal = ref(false);
const showPhotoModal = ref(false); 
const search = ref(props.filters.search || '');

// --- PHOTO PREVIEWS ---
const previews = ref({ front: null, back: null, left: null, right: null });

// --- SELECTED UNIT PHOTOS ---
const selectedUnitPhotos = ref({ plate: '', front: null, back: null, left: null, right: null });

// --- FORMS ---
const form = useForm({
    id: null,
    make_id: '',
    plate_number: '',
    motor_number: '',
    chassis_number: '',
    model_year: '',
    unit_front_photo: null,
    unit_back_photo: null,
    unit_left_photo: null,
    unit_right_photo: null,
    _method: 'POST'
});

const makeForm = useForm({ id: null, name: '', description: '' });
const isEditingMake = ref(false);

// --- ACTIONS: PHOTO VIEWING ---
const openPhotoModal = (unit) => {
    selectedUnitPhotos.value = {
        plate: unit.plate_number,
        front: unit.unit_front_photo ? `/storage/${unit.unit_front_photo}` : null,
        back: unit.unit_back_photo ? `/storage/${unit.unit_back_photo}` : null,
        left: unit.unit_left_photo ? `/storage/${unit.unit_left_photo}` : null,
        right: unit.unit_right_photo ? `/storage/${unit.unit_right_photo}` : null,
    };
    // Prevent background scrolling when custom modal is open
    document.body.style.overflow = 'hidden';
    showPhotoModal.value = true;
};

const closePhotoModal = () => {
    showPhotoModal.value = false;
    document.body.style.overflow = 'auto'; // Restore background scrolling
    setTimeout(() => {
        selectedUnitPhotos.value = { plate: '', front: null, back: null, left: null, right: null };
    }, 300); // clear after transition
};

// --- ACTIONS: UNIT MAKES ---
const resetMakeForm = () => { makeForm.reset(); makeForm.clearErrors(); isEditingMake.value = false; };
const editMake = (make) => { makeForm.id = make.id; makeForm.name = make.name; makeForm.description = make.description; isEditingMake.value = true; };
const submitMake = () => {
    const routeName = isEditingMake.value ? 'admin.unit-makes.update' : 'admin.unit-makes.store';
    const routeParams = isEditingMake.value ? makeForm.id : undefined;
    const method = isEditingMake.value ? 'put' : 'post';
    makeForm[method](route(routeName, routeParams), { onSuccess: () => resetMakeForm(), preserveScroll: true });
};
const deleteMake = (id) => {
    if (confirm('Delete this Unit Make?')) router.delete(route('admin.unit-makes.destroy', id), { preserveScroll: true });
};

// --- ACTIONS: UNITS ---
const resetForm = () => { form.reset(); form.clearErrors(); form._method = 'POST'; previews.value = { front: null, back: null, left: null, right: null }; };
const openAddModal = () => { resetForm(); showAddModal.value = true; };
const openEditModal = (unit) => {
    resetForm();
    form.id = unit.id;
    form.make_id = unit.make_id;
    form.plate_number = unit.plate_number;
    form.motor_number = unit.motor_number;
    form.chassis_number = unit.chassis_number;
    form.model_year = unit.model_year;
    form._method = 'PUT';
    if(unit.unit_front_photo) previews.value.front = `/storage/${unit.unit_front_photo}`;
    if(unit.unit_back_photo) previews.value.back = `/storage/${unit.unit_back_photo}`;
    if(unit.unit_left_photo) previews.value.left = `/storage/${unit.unit_left_photo}`;
    if(unit.unit_right_photo) previews.value.right = `/storage/${unit.unit_right_photo}`;
    showEditModal.value = true;
};
const closeModals = () => { showAddModal.value = false; showEditModal.value = false; resetForm(); };
const handleFileChange = (event, side) => {
    const file = event.target.files[0];
    if (file) { form[`unit_${side}_photo`] = file; previews.value[side] = URL.createObjectURL(file); }
};
const submitForm = () => {
    const routeName = showEditModal.value ? 'admin.units.update' : 'admin.units.store';
    const routeParams = showEditModal.value ? form.id : undefined;
    form.post(route(routeName, routeParams), { onSuccess: () => closeModals(), forceFormData: true });
};
const handleSearch = () => { router.get(route('admin.units.index'), { search: search.value }, { preserveState: true, replace: true }); };
</script>

<template>
    <Head title="Manage Units" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Tricycles</h1>
                <p class="text-gray-600 text-sm">Inventory of all registered units.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input v-model="search" @keyup.enter="handleSearch" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" placeholder="Search Plate, Motor, Chassis..." />
                </div>
                <SecondaryButton @click="showMakesModal = true" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                    Manage Makes
                </SecondaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider text-xs">
                        <tr>
                            <th class="px-6 py-4">Make & Model</th>
                            <th class="px-6 py-4">Plate No.</th> 
                            <th class="px-6 py-4">Motor No.</th> 
                            <th class="px-6 py-4">Chassis No.</th> 
                            <th class="px-6 py-4 text-center">Photos</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="unit in units.data" :key="unit.id" class="hover:bg-gray-50 transition-colors align-middle">
                            
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 text-base">{{ unit.make ? unit.make.name : 'Unknown Make' }}</span>
                                    <span class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Model Year: <span class="font-medium text-gray-700">{{ unit.model_year }}</span>
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-800 font-medium bg-gray-100 px-2 py-0.5 rounded border border-gray-200 whitespace-nowrap">{{ unit.plate_number }}</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-600 whitespace-nowrap">{{ unit.motor_number }}</span>
                            </td>

                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-600 whitespace-nowrap">{{ unit.chassis_number }}</span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <button @click="openPhotoModal(unit)" class="inline-flex items-center justify-center p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors border border-blue-100" title="View Photos">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    <span class="ml-1 text-xs font-semibold">View</span>
                                </button>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <button @click="openEditModal(unit)" class="text-gray-400 hover:text-blue-600 font-medium transition-colors p-1" title="Edit Unit">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="units.data.length === 0">
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                No active units found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="units.links && units.links.length > 3">
                <div class="text-xs text-gray-500">
                    Showing {{ units.from }} to {{ units.to }} of {{ units.total }} results
                </div>
                <Pagination :links="units.links" />
            </div>
        </div>

        <transition 
            enter-active-class="transition duration-300 ease-out" 
            enter-from-class="opacity-0" 
            enter-to-class="opacity-100" 
            leave-active-class="transition duration-200 ease-in" 
            leave-from-class="opacity-100" 
            leave-to-class="opacity-0"
        >
            <div v-if="showPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center sm:p-4">
                
                <div class="absolute inset-0 bg-gray-900/75 backdrop-blur-sm" @click="closePhotoModal"></div>

                <div 
                    class="bg-white rounded-xl shadow-2xl z-10 w-full max-w-4xl max-h-full sm:max-h-[90vh] flex flex-col transform transition-all"
                    @click.stop
                >
                    <div class="flex justify-between items-start px-6 py-5 border-b border-gray-200 bg-white rounded-t-xl shrink-0">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Unit Photos</h2>
                            <p class="text-sm text-gray-500 mt-1">Plate Number: <span class="font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">{{ selectedUnitPhotos.plate }}</span></p>
                        </div>
                        <button @click="closePhotoModal" class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg p-2 transition-colors focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                    <h3 class="text-xs font-bold text-gray-600 uppercase tracking-wider text-center">{{ side }} View</h3>
                                </div>
                                <div class="aspect-[4/3] w-full bg-gray-100 flex items-center justify-center p-2 relative">
                                    <img 
                                        v-if="selectedUnitPhotos[side]" 
                                        :src="selectedUnitPhotos[side]" 
                                        class="w-full h-full object-contain rounded drop-shadow-sm" 
                                    />
                                    <div v-else class="text-gray-400 flex flex-col items-center">
                                        <svg class="h-10 w-10 mb-2 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span class="text-sm font-medium opacity-80">No photo uploaded</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-xl flex justify-end shrink-0">
                        <PrimaryButton @click="closePhotoModal">Done Viewing</PrimaryButton>
                    </div>
                </div>
            </div>
        </transition>

        <Modal :show="showAddModal || showEditModal" @close="closeModals" maxWidth="2xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ showEditModal ? 'Edit Unit' : 'Add New Unit' }}</h2>
                        <p class="text-sm text-gray-500">Enter tricycle specifications and identity numbers.</p>
                    </div>
                    <button @click="closeModals" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-5">
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Unit Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel>Make/Brand<span class="text-red-600"> *</span></InputLabel>
                                <select v-model="form.make_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="" disabled>Select Make...</option>
                                    <option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel>Model Year<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="number" class="mt-1 block w-full" v-model="form.model_year" placeholder="YYYY" min="1990" max="2100" required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-2">Identity Numbers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel>Plate Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full font-mono uppercase" v-model="form.plate_number" required />
                            </div>
                            <div>
                                <InputLabel>Motor Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full font-mono uppercase" v-model="form.motor_number" required />
                            </div>
                            <div>
                                <InputLabel>Chassis Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full font-mono uppercase" v-model="form.chassis_number" required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 mt-2">Unit Photos</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="side in ['front', 'back', 'left', 'right']" :key="side">
                                <InputLabel class="capitalize mb-1 text-center">{{ side }}</InputLabel>
                                <div class="relative aspect-square w-full bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-colors cursor-pointer overflow-hidden flex items-center justify-center group">
                                    <img v-if="previews[side]" :src="previews[side]" class="absolute inset-0 w-full h-full object-cover" />
                                    <div v-else class="text-gray-400 flex flex-col items-center">
                                        <svg class="h-6 w-6 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <span class="text-[10px] uppercase font-bold">{{ side }}</span>
                                    </div>
                                    <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" @change="(e) => handleFileChange(e, side)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="closeModals">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Save Unit</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="showMakesModal" @close="showMakesModal = false" maxWidth="xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Manage Unit Makes</h2>
                    <button @click="showMakesModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex flex-col md:flex-row gap-6">
                    <div class="w-full md:w-1/3">
                        <form @submit.prevent="submitMake" class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <h3 class="text-sm font-bold text-gray-700 mb-3">{{ isEditingMake ? 'Edit Make' : 'New Make' }}</h3>
                            <div class="space-y-3">
                                <div>
                                    <InputLabel>Brand Name<span class="text-red-600"> *</span></InputLabel>
                                    <TextInput type="text" class="mt-1 block w-full text-sm" v-model="makeForm.name" required />
                                </div>
                                <div>
                                    <InputLabel>Description</InputLabel>
                                    <TextInput type="text" class="mt-1 block w-full text-sm" v-model="makeForm.description" />
                                </div>
                                <div class="pt-2 flex flex-col gap-2">
                                    <PrimaryButton class="w-full justify-center" :disabled="makeForm.processing">
                                        {{ isEditingMake ? 'Update' : 'Add' }}
                                    </PrimaryButton>
                                    <SecondaryButton v-if="isEditingMake" @click="resetMakeForm" class="w-full justify-center text-xs">Cancel Edit</SecondaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="w-full md:w-2/3 max-h-96 overflow-y-auto border border-gray-200 rounded-xl">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-100 text-gray-600 font-semibold sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2">Make / Brand</th>
                                    <th class="px-4 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="make in unitMakes" :key="make.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        <div class="font-medium">{{ make.name }}</div>
                                        <div class="text-xs text-gray-500">{{ make.description || 'No description' }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="editMake(make)" class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button @click="deleteMake(make.id)" class="p-1.5 text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="unitMakes.length === 0">
                                    <td colspan="2" class="px-4 py-6 text-center text-gray-500 italic">No makes added yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>