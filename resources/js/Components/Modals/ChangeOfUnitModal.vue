<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    application: Object,
    unitMakes: {
        type: Array,
        default: () => []
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

// Watch for the modal opening to Auto-fill the inputs and image previews
watch(() => props.show, (isOpen) => {
    if (isOpen && props.application?.raw_proposed_unit) {
        
        form.franchise_id = props.application.franchise_details?.id || '';
        form.make_id = props.application.raw_proposed_unit.make_id;
        form.model_year = props.application.raw_proposed_unit.model_year;
        form.plate_number = props.application.raw_proposed_unit.plate_number;
        form.motor_number = props.application.raw_proposed_unit.motor_number;
        form.chassis_number = props.application.raw_proposed_unit.chassis_number;
        
        // Feed the existing photo strings to the backend so it doesn't force re-upload
        form.existing_front_photo = props.application.raw_proposed_unit.front_photo;
        form.existing_back_photo = props.application.raw_proposed_unit.back_photo;
        form.existing_left_photo = props.application.raw_proposed_unit.left_photo;
        form.existing_right_photo = props.application.raw_proposed_unit.right_photo;

        // Render the image previews on the frontend
        previews.value = {
            front: props.application.raw_proposed_unit.front_photo || null,
            back: props.application.raw_proposed_unit.back_photo || null,
            left: props.application.raw_proposed_unit.left_photo || null,
            right: props.application.raw_proposed_unit.right_photo || null,
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
    // NEW: Posts directly to the application finalize endpoint
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
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <p class="text-sm font-medium text-blue-800 mb-2">Notice: Form Autofilled</p>
                        <p class="text-xs text-blue-600">This form has been automatically populated with the proposed unit details and photos.</p>
                    </div>
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
                            <TextInput v-model="form.plate_number" class="block w-full text-sm py-1.5 uppercase" required />
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
                                        <span class="mt-1 block text-[10px] text-gray-500">Change</span>
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