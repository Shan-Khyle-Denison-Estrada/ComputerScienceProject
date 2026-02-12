<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    application: Object
});

const emit = defineEmits(['close']);

// --- STATE ---
const mode = ref('existing'); // 'existing' or 'new'
const unitQuery = ref('');
const showUnitDropdown = ref(false);
const previews = ref({ front: null, back: null, left: null, right: null });

// --- DUMMY DATA ---
const dummyUnits = [
    { id: 1, plate: 'ABC-123', make: 'Honda', motor: 'H123456', chassis: 'C123456' },
    { id: 2, plate: 'XYZ-789', make: 'Kawasaki', motor: 'K987654', chassis: 'K987654' },
    { id: 3, plate: 'DBP-001', make: 'Suzuki', motor: 'S112233', chassis: 'S445566' },
    { id: 4, plate: 'NEW-999', make: 'Yamaha', motor: 'Y998877', chassis: 'Y665544' },
];

const dummyMakes = [
    { id: 1, name: 'Honda' },
    { id: 2, name: 'Kawasaki' },
    { id: 3, name: 'Suzuki' },
    { id: 4, name: 'Yamaha' },
    { id: 5, name: 'Bajaj' },
    { id: 6, name: 'TVS' },
];

// --- FORM ---
const form = useForm({
    // Transaction Details
    change_date: new Date().toISOString().split('T')[0],
    remarks: '',

    // Mode: Existing
    existing_unit_id: '',
    
    // Mode: New
    make_id: '',
    plate_number: '',
    motor_number: '',
    chassis_number: '',
    model_year: '',
    cr_number: '', // Certificate of Registration
    
    // Photos
    unit_front_photo: null,
    unit_back_photo: null,
    unit_left_photo: null,
    unit_right_photo: null,
});

// --- COMPUTED ---
const filteredUnits = computed(() => {
    if (!unitQuery.value) return dummyUnits;
    const query = unitQuery.value.toLowerCase();
    return dummyUnits.filter(u => 
        u.plate.toLowerCase().includes(query) || 
        u.make.toLowerCase().includes(query)
    );
});

// --- ACTIONS ---
const selectUnit = (unit) => {
    form.existing_unit_id = unit.id;
    unitQuery.value = `${unit.plate} - ${unit.make}`;
    showUnitDropdown.value = false;
};

const handleFileChange = (event, side) => {
    const file = event.target.files[0];
    if (file) {
        form[`unit_${side}_photo`] = file;
        previews.value[side] = URL.createObjectURL(file);
    }
};

const submit = () => {
    console.log(`Finalizing Change Unit [Mode: ${mode.value}]`, form.data());
    emit('close');
    setTimeout(() => {
        form.reset();
        unitQuery.value = '';
        previews.value = { front: null, back: null, left: null, right: null };
    }, 500);
};

// Watchers
watch(mode, () => {
    form.reset('existing_unit_id', 'make_id', 'plate_number', 'motor_number', 'chassis_number', 'model_year', 'cr_number', 'unit_front_photo', 'unit_back_photo', 'unit_left_photo', 'unit_right_photo');
    unitQuery.value = '';
    previews.value = { front: null, back: null, left: null, right: null };
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Finalize Change of Unit</h2>
                    <p class="text-xs text-gray-500">Assign a different tricycle unit to this franchise.</p>
                </div>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                
                <div class="flex p-1 bg-gray-100 rounded-lg mb-6">
                    <button 
                        type="button"
                        @click="mode = 'existing'"
                        class="flex-1 py-1.5 text-sm font-medium rounded-md transition-all duration-200"
                        :class="mode === 'existing' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Select Existing Unit
                    </button>
                    <button 
                        type="button"
                        @click="mode = 'new'"
                        class="flex-1 py-1.5 text-sm font-medium rounded-md transition-all duration-200"
                        :class="mode === 'new' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Register New Unit
                    </button>
                </div>

                <div v-if="mode === 'existing'" class="space-y-4 mb-6 min-h-[180px]">
                    <div class="relative">
                        <InputLabel value="Search Unit" class="mb-1" />
                        <div class="relative">
                            <TextInput 
                                v-model="unitQuery" 
                                @focus="showUnitDropdown = true"
                                class="w-full pl-9" 
                                placeholder="Search by Plate No. or Make..." 
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        
                        <div v-if="showUnitDropdown" class="absolute z-10 w-full bg-white border border-gray-200 mt-1 rounded-md shadow-lg max-h-40 overflow-y-auto">
                            <div 
                                v-for="unit in filteredUnits" 
                                :key="unit.id" 
                                @click="selectUnit(unit)"
                                class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-50 last:border-0"
                            >
                                <div class="flex justify-between">
                                    <span class="font-bold text-gray-800">{{ unit.plate }}</span>
                                    <span class="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">{{ unit.make }}</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">Motor: {{ unit.motor }} • Chassis: {{ unit.chassis }}</div>
                            </div>
                            <div v-if="filteredUnits.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">No units found.</div>
                        </div>
                        <div v-if="showUnitDropdown" @click="showUnitDropdown = false" class="fixed inset-0 z-0"></div>
                    </div>
                    
                    <div v-if="form.existing_unit_id" class="p-4 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-blue-900">{{ unitQuery }}</div>
                                <div class="text-xs text-blue-600">Ready to assign</div>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-blue-600 bg-blue-100 px-2 py-1 rounded">Selected</span>
                    </div>
                </div>

                <div v-if="mode === 'new'" class="space-y-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Plate Number" class="text-xs mb-0" />
                            <TextInput type="text" v-model="form.plate_number" class="block w-full text-sm py-1.5 font-bold uppercase" placeholder="ABC-123" />
                        </div>
                        <div>
                            <InputLabel value="Make / Brand" class="text-xs mb-0" />
                            <select v-model="form.make_id" class="block w-full text-sm py-1.5 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="" disabled>Select Make</option>
                                <option v-for="make in dummyMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Motor Number" class="text-xs mb-0" />
                            <TextInput type="text" v-model="form.motor_number" class="block w-full text-sm py-1.5 uppercase" />
                        </div>
                        <div>
                            <InputLabel value="Chassis Number" class="text-xs mb-0" />
                            <TextInput type="text" v-model="form.chassis_number" class="block w-full text-sm py-1.5 uppercase" />
                        </div>
                        <div>
                            <InputLabel value="CR Number" class="text-xs mb-0" />
                            <TextInput type="text" v-model="form.cr_number" class="block w-full text-sm py-1.5" />
                        </div>
                        <div>
                            <InputLabel value="Model Year" class="text-xs mb-0" />
                            <TextInput type="number" v-model="form.model_year" class="block w-full text-sm py-1.5" placeholder="YYYY" />
                        </div>
                        
                        <div class="col-span-2 mt-2">
                            <InputLabel value="Unit Photos" class="text-xs mb-2" />
                            <div class="grid grid-cols-4 gap-3">
                                <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="relative group">
                                    <div class="aspect-square bg-white border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden hover:bg-blue-50 hover:border-blue-300 transition-colors cursor-pointer relative">
                                        <img v-if="previews[side]" :src="previews[side]" class="absolute inset-0 w-full h-full object-cover" />
                                        <div v-else class="text-center p-1">
                                            <svg class="w-5 h-5 mx-auto text-gray-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="text-[10px] text-gray-500 uppercase font-bold">{{ side }}</span>
                                        </div>
                                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" accept="image/*" @change="(e) => handleFileChange(e, side)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Change Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Effectivity Date" class="text-xs mb-0" />
                            <TextInput type="date" v-model="form.change_date" class="block w-full text-sm py-1.5" required />
                        </div>
                        <div>
                             <InputLabel value="Remarks" class="text-xs mb-0" />
                             <TextInput v-model="form.remarks" class="block w-full text-sm py-1.5" placeholder="Reason for change..." />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="emit('close')" class="text-xs h-9">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="text-xs h-9 px-6">Save Changes</PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>