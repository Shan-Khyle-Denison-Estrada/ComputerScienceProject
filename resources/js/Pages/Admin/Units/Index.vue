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
    cr_number: '',
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
    showPhotoModal.value = true;
};

const closePhotoModal = () => {
    showPhotoModal.value = false;
    selectedUnitPhotos.value = { plate: '', front: null, back: null, left: null, right: null };
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
    form.cr_number = unit.cr_number;
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
                <PrimaryButton @click="openAddModal" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Unit
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 w-1/4">Unit Information</th>
                            <th class="px-6 py-4 w-1/3">Identity Numbers</th> <th class="px-6 py-4 w-1/6">CR Number</th>
                            <th class="px-6 py-4 text-center w-1/6">Photos</th>
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
                                        Model Year: {{ unit.model_year }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="grid grid-cols-[80px_1fr] gap-y-2 items-center">
                                    
                                    <!-- <span class="text-xs text-gray-400 font-bold uppercase tracking-wider text-right pr-3">Plate</span>
                                    <div>
                                        <span class="bg-blue-50 text-blue-700 text-xs font-bold px-2 py-0.5 rounded border border-blue-100 font-mono tracking-wide">
                                            {{ unit.plate_number }}
                                        </span>
                                    </div> -->

                                    <span class="text-xs text-gray-400 uppercase tracking-wider text-right pr-3">Plate</span>
                                    <span class="text-xs text-gray-700 font-mono font-medium truncate" :title="unit.place_number">{{ unit.plate_number }}</span>

                                    <span class="text-xs text-gray-400 uppercase tracking-wider text-right pr-3">Motor</span>
                                    <span class="text-xs text-gray-700 font-mono font-medium truncate" :title="unit.motor_number">{{ unit.motor_number }}</span>

                                    <span class="text-xs text-gray-400 uppercase tracking-wider text-right pr-3">Chassis</span>
                                    <span class="text-xs text-gray-700 font-mono font-medium truncate" :title="unit.chassis_number">{{ unit.chassis_number }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div v-if="unit.cr_number" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                    <svg class="mr-1.5 h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ unit.cr_number }}
                                </div>
                                <div v-else class="text-xs text-gray-400 italic pl-1">
                                    Not Recorded
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <button 
                                    @click="openPhotoModal(unit)" 
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                >
                                    <svg class="h-4 w-4 mr-1.5 text-gray-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    View Photos
                                </button>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <button 
                                    @click="openEditModal(unit)" 
                                    class="text-gray-400 hover:text-blue-600 transition-colors p-2 rounded-full hover:bg-blue-50"
                                    title="Edit Unit"
                                >
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="units.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No active units found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="units.links && units.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ units.meta.from }} to {{ units.meta.to }} of {{ units.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="(link, key) in units.meta.links" 
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

        <Modal :show="showPhotoModal" @close="closePhotoModal" maxWidth="4xl">
             <div class="p-6">
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Unit Photos</h2>
                        <p class="text-sm text-gray-500">Plate Number: <span class="font-mono font-bold text-gray-800">{{ selectedUnitPhotos.plate }}</span></p>
                    </div>
                    <button @click="closePhotoModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="(photo, label) in { 'Front View': selectedUnitPhotos.front, 'Back View': selectedUnitPhotos.back, 'Left Side View': selectedUnitPhotos.left, 'Right Side View': selectedUnitPhotos.right }" :key="label" class="border rounded-lg p-2 bg-gray-50 text-center">
                        <span class="block text-xs font-bold text-gray-500 uppercase mb-2">{{ label }}</span>
                        <div class="relative w-full aspect-video bg-gray-200 rounded overflow-hidden flex items-center justify-center">
                            <img v-if="photo" :src="photo" class="absolute inset-0 w-full h-full object-contain bg-black" />
                            <span v-else class="text-gray-400 text-xs italic">No Image Available</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closePhotoModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showMakesModal" @close="showMakesModal = false" maxWidth="lg">
             <div class="p-6">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Manage Unit Makes</h2>
                        <p class="text-sm text-gray-500">Add or edit tricycle brands/models.</p>
                    </div>
                    <button @click="showMakesModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitMake" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase tracking-wide">
                        {{ isEditingMake ? 'Edit Make' : 'Add New Make' }}
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <TextInput type="text" v-model="makeForm.name" class="w-full text-sm" placeholder="Make Name (e.g. Honda TMX)" required />
                        </div>
                        <div>
                            <TextInput type="text" v-model="makeForm.description" class="w-full text-sm" placeholder="Description (Optional)" />
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <PrimaryButton :disabled="makeForm.processing" class="w-full justify-center">
                                {{ isEditingMake ? 'Update' : 'Add' }}
                            </PrimaryButton>
                            <SecondaryButton v-if="isEditingMake" @click="resetMakeForm" type="button" class="w-auto">
                                Cancel
                            </SecondaryButton>
                        </div>
                    </div>
                </form>

                <div class="overflow-y-auto max-h-64 border border-gray-200 rounded-md">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <tr v-for="make in unitMakes" :key="make.id" class="hover:bg-gray-50 transition-colors">
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
                            <tr v-if="unitMakes.length === 0"><td colspan="2" class="px-4 py-8 text-center text-sm text-gray-500 italic">No makes found. Add one above.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </Modal>

        <Modal :show="showAddModal || showEditModal" @close="closeModals" maxWidth="2xl">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ showEditModal ? 'Edit Unit' : 'Register New Unit' }}
                    </h2>
                    <p class="text-sm text-gray-500">Enter tricycle details and upload images.</p>
                </div>

                <form @submit.prevent="submitForm" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <InputLabel>Unit Make (Brand)<span class="text-red-600"> *</span></InputLabel>
                            <select v-model="form.make_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Select Make</option>
                                <option v-for="make in unitMakes" :key="make.id" :value="make.id">
                                    {{ make.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel>Model Year<span class="text-red-600"> *</span></InputLabel>
                            <TextInput type="number" class="mt-1 block w-full" v-model="form.model_year" placeholder="e.g. 2023" required />
                        </div>
                        <div>
                            <InputLabel>CR Number<span class="text-red-600"> *</span></InputLabel>
                            <TextInput type="text" class="mt-1 block w-full" v-model="form.cr_number" placeholder="Certificate of Reg." />
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Identification Numbers</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <InputLabel>Plate Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full font-mono uppercase" v-model="form.plate_number" required />
                            </div>
                            <div>
                                <InputLabel>Motor Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.motor_number" required />
                            </div>
                            <div>
                                <InputLabel>Chassis Number<span class="text-red-600"> *</span></InputLabel>
                                <TextInput type="text" class="mt-1 block w-full uppercase" v-model="form.chassis_number" required />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Unit Photos<span class="text-red-600"> *</span></h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="text-center">
                                <div class="relative w-full aspect-square bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center overflow-hidden hover:border-blue-400 transition-colors">
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
                        <PrimaryButton :disabled="form.processing">
                            {{ showEditModal ? 'Save Changes' : 'Register Unit' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>