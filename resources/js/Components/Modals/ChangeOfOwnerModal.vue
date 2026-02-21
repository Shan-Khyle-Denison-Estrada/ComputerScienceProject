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
    application: Object
});

const emit = defineEmits(['close']);

// --- STATE ---
const mode = ref('new'); // We default to 'new' since we are autofilling the proposed new operator

const form = useForm({
    franchise_id: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    contact_number: '',
    email: '',
    tin_number: '',
    street_address: '',
    barangay: '',
    city: 'Zamboanga City',
    password: '',
    password_confirmation: '',
    change_date: '',
    remarks: 'Approved and Finalized Change of Owner.',
});

// Watch for the modal opening to Auto-fill the inputs
watch(() => props.show, (isOpen) => {
    if (isOpen && props.application?.raw_proposed_owner) {
        mode.value = 'new';
        
        form.franchise_id = props.application.franchise_details?.id || '';
        
        form.first_name = props.application.raw_proposed_owner.first_name;
        form.middle_name = props.application.raw_proposed_owner.middle_name;
        form.last_name = props.application.raw_proposed_owner.last_name;
        form.email = props.application.raw_proposed_owner.email;
        form.contact_number = props.application.raw_proposed_owner.contact_number;
        form.tin_number = props.application.raw_proposed_owner.tin_number;
        form.street_address = props.application.raw_proposed_owner.street_address;
        form.barangay = props.application.raw_proposed_owner.barangay;
        form.city = props.application.raw_proposed_owner.city || 'Zamboanga City';
        
        // Ensure passwords remain empty for security
        form.password = '';
        form.password_confirmation = '';
        
        form.change_date = new Date().toISOString().split('T')[0];
    } else if (!isOpen) {
        form.reset();
    }
});

const submit = () => {
    form.post(route('admin.applications.change-of-owner.finalize', props.application.id), {
        onSuccess: () => emit('close')
    });
};
</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="emit('close')">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b pb-4 border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">Finalize Transfer of Ownership</h2>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                
                <div class="mb-6">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <p class="text-sm font-medium text-blue-800 mb-2">Notice: Form Autofilled</p>
                        <p class="text-xs text-blue-600">This form has been populated with the proposed new operator details. If an account does not yet exist for this user, you must set a password below.</p>
                    </div>
                </div>

                <div class="space-y-4 mb-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div><InputLabel value="First Name" class="text-xs mb-0" /><TextInput v-model="form.first_name" class="block w-full text-sm py-1.5" required /></div>
                        <div><InputLabel value="Middle Name" class="text-xs mb-0" /><TextInput v-model="form.middle_name" class="block w-full text-sm py-1.5" /></div>
                        <div><InputLabel value="Last Name" class="text-xs mb-0" /><TextInput v-model="form.last_name" class="block w-full text-sm py-1.5" required /></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div><InputLabel value="Contact Number" class="text-xs mb-0" /><TextInput v-model="form.contact_number" class="block w-full text-sm py-1.5" required /></div>
                        <div><InputLabel value="Email Address" class="text-xs mb-0" /><TextInput type="email" v-model="form.email" class="block w-full text-sm py-1.5" required /></div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div><InputLabel value="TIN Number" class="text-xs mb-0" /><TextInput v-model="form.tin_number" class="block w-full text-sm py-1.5" required /></div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div><InputLabel value="Street Address" class="text-xs mb-0" /><TextInput v-model="form.street_address" class="block w-full text-sm py-1.5" required /></div>
                        <div><InputLabel value="Barangay" class="text-xs mb-0" /><TextInput v-model="form.barangay" class="block w-full text-sm py-1.5" required /></div>
                        <div><InputLabel value="City" class="text-xs mb-0" /><TextInput v-model="form.city" class="block w-full text-sm py-1.5 bg-gray-100" readonly /></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100 mt-4">
                        <div>
                            <InputLabel value="Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password" class="block w-full text-sm py-1.5" placeholder="Set a password for new operators" />
                            <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <InputLabel value="Confirm Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password_confirmation" class="block w-full text-sm py-1.5" />
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