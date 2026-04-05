<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900 bg-opacity-50 p-4">
        <div class="w-full max-w-3xl p-6 bg-white rounded-lg shadow-xl mt-10 mb-10 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-start mb-6">
                <h2 class="text-xl font-bold text-gray-800">Finalize & Assign Driver</h2>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div v-if="form.errors.global_error || form.hasErrors" class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            {{ form.errors.global_error ? form.errors.global_error : 'Please correct the validation errors in the form below.' }}
                        </p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                
                <div class="flex flex-col items-center justify-center mb-6">
                    <div class="w-28 h-28 rounded-full bg-gray-100 border-4 border-white shadow-lg overflow-hidden flex items-center justify-center mb-2">
                        <img v-if="applicationData.driver_user_photo" :src="`/storage/${applicationData.driver_user_photo}`" class="w-full h-full object-cover" />
                        <span v-else class="text-3xl font-bold text-gray-400">{{ applicationData.first_name?.charAt(0) }}</span>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">Applicant Photo</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" v-model="form.first_name" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.first_name ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.first_name" class="text-xs text-red-600 font-medium">{{ form.errors.first_name }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" v-model="form.middle_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <span v-if="form.errors.middle_name" class="text-xs text-red-600 font-medium">{{ form.errors.middle_name }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" v-model="form.last_name" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.last_name ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.last_name" class="text-xs text-red-600 font-medium">{{ form.errors.last_name }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="text" v-model="form.contact_number" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.contact_number ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.contact_number" class="text-xs text-red-600 font-medium">{{ form.errors.contact_number }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">License Number</label>
                        <input type="text" v-model="form.license_number" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm font-mono" :class="form.errors.license_number ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.license_number" class="text-xs text-red-600 font-medium">{{ form.errors.license_number }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expiration Date</label>
                        <input type="date" v-model="form.license_expiration_date" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.license_expiration_date ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.license_expiration_date" class="text-xs text-red-600 font-medium">{{ form.errors.license_expiration_date }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Street / Purok</label>
                        <input type="text" v-model="form.street" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.street ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.street" class="text-xs text-red-600 font-medium">{{ form.errors.street }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Barangay</label>
                        <input type="text" v-model="form.barangay" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.barangay ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.barangay" class="text-xs text-red-600 font-medium">{{ form.errors.barangay }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" v-model="form.city" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.city ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.city" class="text-xs text-red-600 font-medium">{{ form.errors.city }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Province</label>
                        <input type="text" v-model="form.province" required class="mt-1 block w-full rounded-md shadow-sm focus:ring-blue-500 text-sm" :class="form.errors.province ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500'">
                        <span v-if="form.errors.province" class="text-xs text-red-600 font-medium">{{ form.errors.province }}</span>
                    </div>
                </div>

                <div class="mt-6 border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">License Images on File</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col items-center">
                            <span class="text-xs font-medium text-gray-500 mb-1">Front</span>
                            <div class="relative w-full h-40 bg-gray-100 rounded border border-gray-200 overflow-hidden flex items-center justify-center">
                                <img v-if="applicationData.driver_license_front_photo" :src="`/storage/${applicationData.driver_license_front_photo}`" class="w-full h-full object-contain" />
                                <span v-else class="text-xs text-gray-400">No Image</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <span class="text-xs font-medium text-gray-500 mb-1">Back</span>
                            <div class="relative w-full h-40 bg-gray-100 rounded border border-gray-200 overflow-hidden flex items-center justify-center">
                                <img v-if="applicationData.driver_license_back_photo" :src="`/storage/${applicationData.driver_license_back_photo}`" class="w-full h-full object-contain" />
                                <span v-else class="text-xs text-gray-400">No Image</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" @click="$emit('close')" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-75 disabled:cursor-not-allowed flex items-center justify-center min-w-[140px]">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Saving Data...' : 'Finalize & Assign' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    franchiseId: {
        type: [Number, String],
        required: true
    },
    applicationData: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close']);

// Map the application fields perfectly
const form = useForm({
    application_id: props.applicationData.id || '', 
    first_name: props.applicationData.first_name || '',
    last_name: props.applicationData.last_name || '',
    middle_name: props.applicationData.middle_name || '',
    contact_number: props.applicationData.contact_number || '',
    street: props.applicationData.street_address || '', 
    province: props.applicationData.province || '',
    barangay: props.applicationData.barangay || '',
    city: props.applicationData.city || '',
    license_number: props.applicationData.driver_license_number || '',
    license_expiration_date: props.applicationData.driver_license_expiration_date || '',
    status: 'active',
    
    // Existing DB paths
    existing_user_photo: props.applicationData.driver_user_photo || '',
    existing_license_front_photo: props.applicationData.driver_license_front_photo || '',
    existing_license_back_photo: props.applicationData.driver_license_back_photo || '',
});

const submitForm = () => {
    // Clear global error on new submission attempt
    form.clearErrors('global_error');

    form.post(route('franchises.store_and_assign_driver', props.franchiseId), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
        onError: (errors) => {
            // Errors are automatically injected into `form.errors`
            console.error('Submission failed', errors);
        }
    });
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>