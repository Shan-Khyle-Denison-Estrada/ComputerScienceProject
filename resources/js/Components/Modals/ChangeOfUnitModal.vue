<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// Add the existingUnit prop
const props = defineProps({
    show: Boolean,
    application: Object,
    unitMakes: {
        type: Array,
        default: () => []
    },
    unitExists: {
        type: Boolean,
        default: false
    },
    existingUnit: { // Add this prop
        type: Object,
        default: () => null
    }
});

const emit = defineEmits(['close']);

// --- STATE ---
const previews = ref({ front: null, back: null, left: null, right: null });

const form = useForm({
    franchise_id: '',
    make_id: '',
    model_year: '',
    plate_number: '',
    motor_number: '',
    chassis_number: '',
    change_date: '',
    remarks: 'Approved and Finalized Change of Unit.',
    
    // Will hold new files if the admin changes them manually
    front_photo: null,
    back_photo: null,
    left_photo: null,
    right_photo: null,
    
    // Will hold the path to the previously uploaded proposed photos
    existing_front_photo: '',
    existing_back_photo: '',
    existing_left_photo: '',
    existing_right_photo: ''
});

// Update your watch function to handle the photo logic
watch(() => props.show, (isOpen) => {
    if (isOpen && props.application?.raw_proposed_unit) {
        
        form.franchise_id = props.application.franchise_details?.id || '';
        form.make_id = props.application.raw_proposed_unit.make_id;
        form.model_year = props.application.raw_proposed_unit.model_year;
        form.plate_number = props.application.raw_proposed_unit.plate_number;
        form.motor_number = props.application.raw_proposed_unit.motor_number;
        form.chassis_number = props.application.raw_proposed_unit.chassis_number;
        
        // Determine which unit to pull the photos from:
        // Prioritize the existing unit from the database if detected, otherwise fallback to the proposed unit
        const sourceUnit = (props.unitExists && props.existingUnit) 
            ? props.existingUnit 
            : props.application.raw_proposed_unit;

        // Note: Using || handles edge cases depending on your DB column naming (unit_front_photo vs front_photo)
        form.existing_front_photo = sourceUnit.unit_front_photo || sourceUnit.front_photo;
        form.existing_back_photo = sourceUnit.unit_back_photo || sourceUnit.back_photo;
        form.existing_left_photo = sourceUnit.unit_left_photo || sourceUnit.left_photo;
        form.existing_right_photo = sourceUnit.unit_right_photo || sourceUnit.right_photo;

        // Render the image previews on the frontend dynamically based on the source 
        previews.value = {
            front: form.existing_front_photo ? `/storage/${form.existing_front_photo}` : null,
            back: form.existing_back_photo ? `/storage/${form.existing_back_photo}` : null,
            left: form.existing_left_photo ? `/storage/${form.existing_left_photo}` : null,
            right: form.existing_right_photo ? `/storage/${form.existing_right_photo}` : null,
        };
        
        form.change_date = new Date().toISOString().split('T')[0];
    } else if (!isOpen) {
        form.reset();
        previews.value = { front: null, back: null, left: null, right: null };
    }
});

const handleFileChange = (event, side) => {
    const file = event.target.files[0];
    if (file) {
        form[`${side}_photo`] = file;
        
        // Nullify existing string so the backend knows to use the new file
        form[`existing_${side}_photo`] = null;

        const reader = new FileReader();
        reader.onload = (e) => previews.value[side] = e.target.result;
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    // Posts directly to the application finalize endpoint
    form.post(route('admin.applications.change-of-unit.finalize', props.application.id), {
        onSuccess: () => emit('close')
    });
};
</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="emit('close')">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-bold text-gray-900">Finalize Change of Unit</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                
                <div class="mb-6 p-4 rounded-lg border" :class="unitExists ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">
                    <InputLabel value="System Detection" class="text-sm font-bold mb-1" :class="unitExists ? 'text-green-900' : 'text-blue-900'" />
                    <div class="flex items-center gap-2">
                        <svg v-if="unitExists" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold" :class="unitExists ? 'text-green-800' : 'text-blue-800'">
                            {{ unitExists ? 'Existing Unit Found' : 'New Unit Setup' }}
                        </span>
                    </div>
                    <p class="text-xs mt-2" :class="unitExists ? 'text-green-700' : 'text-blue-700'">
                        {{ unitExists ? 'The Plate Number matches an existing unit in the master database. The system will safely update its details and attach it to this franchise.' : 'No existing unit found for this Plate Number. A brand new unit record will be created in the master database.' }}
                    </p>
                </div>

                <div class="space-y-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Unit Make" class="text-xs mb-0" />
                            <select v-model="form.make_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm py-1.5" required>
                                <option value="" disabled>Select Make</option>
                                <option v-for="make in unitMakes" :key="make.id" :value="make.id">
                                    {{ make.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Model Year" class="text-xs mb-0" />
                            <TextInput v-model="form.model_year" type="number" class="block w-full text-sm py-1.5" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Plate Number" class="text-xs mb-0" />
                            <TextInput v-model="form.plate_number" class="block w-full text-sm py-1.5 uppercase bg-gray-50 text-gray-500" readonly required />
                        </div>
                        <div>
                            <InputLabel value="Motor Number" class="text-xs mb-0" />
                            <TextInput v-model="form.motor_number" class="block w-full text-sm py-1.5 uppercase" required />
                        </div>
                        <div>
                            <InputLabel value="Chassis Number" class="text-xs mb-0" />
                            <TextInput v-model="form.chassis_number" class="block w-full text-sm py-1.5 uppercase" required />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Unit Photos</h3>
                    <div class="grid grid-cols-4 gap-4">
                        <div v-for="side in ['front', 'back', 'left', 'right']" :key="side">
                            <InputLabel :value="side" class="text-xs mb-1 capitalize" />
                            <div class="relative group">
                                <div class="w-full h-24 border-2 border-dashed border-gray-300 rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center transition-colors group-hover:border-blue-400">
                                    <img v-if="previews[side]" :src="previews[side]" class="w-full h-full object-cover" />
                                    <div v-else class="text-center p-2">
                                        <svg class="mx-auto h-6 w-6 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span class="mt-1 block text-[10px] text-gray-500">Upload</span>
                                    </div>
                                    <input type="file" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" accept="image/*" @change="(e) => handleFileChange(e, side)" />
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
                    <SecondaryButton @click="emit('close')" class="text-xs h-9" :disabled="form.processing">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="text-xs h-9 px-6">
                        {{ form.processing ? 'Finalizing...' : 'Save & Finalize' }}
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>