<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    show: Boolean,
    application: Object,
    operatorExists: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

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
        
        form.franchise_id = props.application.franchise_details?.id || '';
        
        form.first_name = props.application.raw_proposed_owner.first_name;
        form.middle_name = props.application.raw_proposed_owner.middle_name;
        form.last_name = props.application.raw_proposed_owner.last_name;
        form.contact_number = props.application.raw_proposed_owner.contact_number;
        form.email = props.application.raw_proposed_owner.email;
        form.tin_number = props.application.raw_proposed_owner.tin_number;
        form.street_address = props.application.raw_proposed_owner.street_address;
        form.barangay = props.application.raw_proposed_owner.barangay;
        form.city = props.application.raw_proposed_owner.city || 'Zamboanga City';

        // Reset fields
        form.password = '';
        form.password_confirmation = '';
        form.change_date = '';
        form.remarks = 'Approved and Finalized Change of Owner.';
    }
});

const submit = () => {
    // Ensure passwords are wiped if it's an existing operator to prevent validation issues
    if (props.operatorExists) {
        form.password = '';
        form.password_confirmation = '';
    }

    form.post(route('admin.applications.change-of-owner.finalize', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Finalize Change of Owner</h2>
            
            <form @submit.prevent="submit">
                
                <div class="mb-6 p-4 rounded-lg border" :class="operatorExists ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">
                    <InputLabel value="System Detection" class="text-sm font-bold mb-1" :class="operatorExists ? 'text-green-900' : 'text-blue-900'" />
                    <div class="flex items-center gap-2">
                        <svg v-if="operatorExists" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold" :class="operatorExists ? 'text-green-800' : 'text-blue-800'">
                            {{ operatorExists ? 'Existing Operator Found' : 'New Operator Setup' }}
                        </span>
                    </div>
                    <p class="text-xs mt-2" :class="operatorExists ? 'text-green-700' : 'text-blue-700'">
                        {{ operatorExists ? 'The TIN matches an existing operator. Passwords are hidden, and this franchise will automatically link to their existing account.' : 'No existing operator found for this TIN. A new account will be created. Please assign a temporary password below.' }}
                    </p>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Proposed Owner Details</h3>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel value="First Name" class="text-xs mb-0" />
                            <TextInput v-model="form.first_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Middle Name" class="text-xs mb-0" />
                            <TextInput v-model="form.middle_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Last Name" class="text-xs mb-0" />
                            <TextInput v-model="form.last_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel value="Contact Number" class="text-xs mb-0" />
                            <TextInput v-model="form.contact_number" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Email Address" class="text-xs mb-0" />
                            <TextInput v-model="form.email" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="TIN Number" class="text-xs mb-0" />
                            <TextInput v-model="form.tin_number" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <InputLabel value="Street Address" class="text-xs mb-0" />
                            <TextInput v-model="form.street_address" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div class="col-span-1">
                            <InputLabel value="Barangay" class="text-xs mb-0" />
                            <TextInput v-model="form.barangay" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div class="col-span-1">
                            <InputLabel value="City" class="text-xs mb-0" />
                            <TextInput v-model="form.city" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                    </div>
                </div>

                <div v-if="!operatorExists" class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Account Setup</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Temporary Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password" class="block w-full text-sm py-1.5" :required="!operatorExists" />
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div>
                            <InputLabel value="Confirm Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password_confirmation" class="block w-full text-sm py-1.5" :required="!operatorExists" />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Change Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Effectivity Date" class="text-xs mb-0" />
                            <TextInput type="date" v-model="form.change_date" class="block w-full text-sm py-1.5" required />
                            <div v-if="form.errors.change_date" class="text-red-500 text-xs mt-1">{{ form.errors.change_date }}</div>
                        </div>
                        <div>
                             <InputLabel value="Remarks" class="text-xs mb-0" />
                             <TextInput v-model="form.remarks" class="block w-full text-sm py-1.5" placeholder="Reason for change..." />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="emit('close')" class="text-xs h-9" :disabled="form.processing">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="text-xs h-9 px-6">
                        {{ form.processing ? 'Finalizing...' : 'Save & Finalize' }}
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>