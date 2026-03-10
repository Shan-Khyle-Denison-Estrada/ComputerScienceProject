<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object,
    zones: Array,
    isEncoder: { type: Boolean, default: false }
});

const activeTab = ref('applicant_info');
const showFinalizeModal = ref(false);

const finalizeForm = useForm({
    franchise_number: '',
    mtfrb_case_no: '',
    zone_id: props.application?.proposed_units[0]?.zone_id || '',
    plate_number: '',
    date_issued: '',
    valid_until: ''
});

// Evaluation logic
const evaluationsForm = useForm({
    evaluations: props.application?.evaluations?.map(e => ({
        id: e.id,
        is_compliant: e.is_compliant === 1 || e.is_compliant === true,
        remarks: e.remarks || ''
    })) || []
});

const submitEvaluation = () => {
    evaluationsForm.post(route('admin.applications.evaluate', props.application.id), {
        preserveScroll: true,
        onSuccess: () => alert('Evaluation saved!')
    });
};

const submitFinalization = () => {
    finalizeForm.post(route('admin.applications.new-franchise.finalize', props.application.id), {
        onSuccess: () => showFinalizeModal.value = false
    });
};

const proposedUnit = computed(() => props.application?.proposed_units[0] || null);

</script>

<template>
    <Head :title="`New Franchise: ${application.reference_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
                        App Ref: <span class="text-blue-600">{{ application.reference_number }}</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 font-medium">Type: New Franchise Application</p>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="{
                            'bg-yellow-100 text-yellow-800': application.status === 'Pending',
                            'bg-blue-100 text-blue-800': application.status === 'In Progress',
                            'bg-green-100 text-green-800': application.status === 'Completed',
                            'bg-red-100 text-red-800': application.status === 'Rejected',
                            'bg-gray-100 text-gray-800': application.status === 'Returned'
                        }" class="px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shadow-sm">
                        {{ application.status }}
                    </span>
                    
                    <PrimaryButton 
                        v-if="isEncoder && application.status !== 'Completed'" 
                        @click="showFinalizeModal = true" 
                        class="bg-green-600 hover:bg-green-700">
                        Finalize & Issue Franchise
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col min-h-[500px]">
                
                <div class="bg-gray-50/80 border-b border-gray-200 p-2 sm:px-6 flex gap-2 overflow-x-auto custom-scrollbar">
                    <button @click="activeTab = 'applicant_info'" :class="activeTab === 'applicant_info' ? 'bg-white text-blue-600 shadow-sm border border-gray-200' : 'text-gray-500 hover:bg-gray-100'" class="px-4 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap">Applicant Profile</button>
                    <button @click="activeTab = 'proposed_unit'" :class="activeTab === 'proposed_unit' ? 'bg-white text-blue-600 shadow-sm border border-gray-200' : 'text-gray-500 hover:bg-gray-100'" class="px-4 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap">Proposed Tricycle</button>
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'bg-white text-blue-600 shadow-sm border border-gray-200' : 'text-gray-500 hover:bg-gray-100'" class="px-4 py-2.5 rounded-xl font-bold text-sm transition-all whitespace-nowrap">Document Evaluation</button>
                </div>

                <div class="p-6 sm:p-8 flex-1 bg-white">
                    
                    <div v-show="activeTab === 'applicant_info'" class="space-y-6 animate-fade-in">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Personal Details</h3>
                                <div class="space-y-4">
                                    <div><p class="text-xs text-gray-500">Full Name</p><p class="font-bold text-gray-900">{{ application.first_name }} {{ application.middle_name }} {{ application.last_name }}</p></div>
                                    <div><p class="text-xs text-gray-500">Contact Number</p><p class="font-bold text-gray-900">{{ application.contact_number }}</p></div>
                                    <div><p class="text-xs text-gray-500">Email Address</p><p class="font-bold text-gray-900">{{ application.email }}</p></div>
                                    <div><p class="text-xs text-gray-500">TIN Number</p><p class="font-bold text-gray-900">{{ application.tin_number || 'N/A' }}</p></div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Location</h3>
                                <div class="space-y-4">
                                    <div class="col-span-2"><p class="text-xs text-gray-500">Street / House No.</p><p class="font-bold text-gray-900">{{ application.street_address }}</p></div>
                                    <div><p class="text-xs text-gray-500">Barangay</p><p class="font-bold text-gray-900">{{ application.barangay }}</p></div>
                                    <div><p class="text-xs text-gray-500">City / Municipality</p><p class="font-bold text-gray-900">{{ application.city }}</p></div>
                                    <div><p class="text-xs text-gray-500">Province</p><p class="font-bold text-gray-900">{{ application.province }}</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'proposed_unit'" class="space-y-6 animate-fade-in">
                        <div v-if="proposedUnit" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Motor Specs</h3>
                                <div><p class="text-xs text-gray-500">Brand / Make</p><p class="font-bold text-gray-900">{{ proposedUnit.make?.name }}</p></div>
                                <div><p class="text-xs text-gray-500">Motor / Engine No.</p><p class="font-bold text-gray-900">{{ proposedUnit.motor_number }}</p></div>
                                <div><p class="text-xs text-gray-500">Chassis No.</p><p class="font-bold text-gray-900">{{ proposedUnit.chassis_number }}</p></div>
                                <div><p class="text-xs text-gray-500">Model Year</p><p class="font-bold text-gray-900">{{ proposedUnit.model_year }}</p></div>
                                <div><p class="text-xs text-gray-500">Target Zone</p><p class="font-bold text-gray-900">{{ proposedUnit.zone?.name }}</p></div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2">Unit Imagery</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <a v-if="proposedUnit.unit_front_photo" :href="`/storage/${proposedUnit.unit_front_photo}`" target="_blank" class="border rounded-xl p-2 hover:bg-gray-50 text-center"><img :src="`/storage/${proposedUnit.unit_front_photo}`" class="h-20 mx-auto object-cover rounded-md mb-2"><span class="text-xs font-bold text-gray-600">Front View</span></a>
                                    <a v-if="proposedUnit.unit_back_photo" :href="`/storage/${proposedUnit.unit_back_photo}`" target="_blank" class="border rounded-xl p-2 hover:bg-gray-50 text-center"><img :src="`/storage/${proposedUnit.unit_back_photo}`" class="h-20 mx-auto object-cover rounded-md mb-2"><span class="text-xs font-bold text-gray-600">Back View</span></a>
                                    <a v-if="proposedUnit.unit_left_photo" :href="`/storage/${proposedUnit.unit_left_photo}`" target="_blank" class="border rounded-xl p-2 hover:bg-gray-50 text-center"><img :src="`/storage/${proposedUnit.unit_left_photo}`" class="h-20 mx-auto object-cover rounded-md mb-2"><span class="text-xs font-bold text-gray-600">Left View</span></a>
                                    <a v-if="proposedUnit.unit_right_photo" :href="`/storage/${proposedUnit.unit_right_photo}`" target="_blank" class="border rounded-xl p-2 hover:bg-gray-50 text-center"><img :src="`/storage/${proposedUnit.unit_right_photo}`" class="h-20 mx-auto object-cover rounded-md mb-2"><span class="text-xs font-bold text-gray-600">Right View</span></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="activeTab === 'evaluations'" class="space-y-6 animate-fade-in">
                        <form @submit.prevent="submitEvaluation">
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Requirement</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">File</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/3">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(evalRecord, index) in application.evaluations" :key="evalRecord.id">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ evalRecord.requirement?.name }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                <a :href="`/storage/${evalRecord.file_path}`" target="_blank" class="text-blue-600 hover:text-blue-800 underline font-medium">View File</a>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <select v-model="evaluationsForm.evaluations[index].is_compliant" :disabled="!isEncoder" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                    <option :value="true">Compliant (Passed)</option>
                                                    <option :value="false">Incomplete (Failed)</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4">
                                                <TextInput v-model="evaluationsForm.evaluations[index].remarks" :disabled="!isEncoder" class="w-full text-sm" placeholder="Add note..." />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 flex justify-end" v-if="isEncoder && application.status !== 'Completed'">
                                <PrimaryButton type="submit" :disabled="evaluationsForm.processing">Save Evaluation Status</PrimaryButton>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <Modal :show="showFinalizeModal" @close="showFinalizeModal = false" maxWidth="2xl">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-100">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Finalize & Issue New Franchise</h2>
                        <p class="text-sm text-gray-500">Assign the permanent MTOP Franchise ID to create the record.</p>
                    </div>
                </div>

                <form @submit.prevent="submitFinalization" class="space-y-5">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <InputLabel>Franchise Plate No. (MTOP) <span class="text-red-500">*</span></InputLabel>
                            <TextInput v-model="finalizeForm.franchise_number" class="mt-1 w-full" placeholder="e.g. F-1001" required />
                            <InputError v-if="finalizeForm.errors.franchise_number" :message="finalizeForm.errors.franchise_number" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel>MTFRB Case No. <span class="text-red-500">*</span></InputLabel>
                            <TextInput v-model="finalizeForm.mtfrb_case_no" class="mt-1 w-full" placeholder="e.g. 2024-XXXX" required />
                            <InputError v-if="finalizeForm.errors.mtfrb_case_no" :message="finalizeForm.errors.mtfrb_case_no" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel>Tricycle Plate Number <span class="text-red-500">*</span></InputLabel>
                            <TextInput v-model="finalizeForm.plate_number" class="mt-1 w-full" placeholder="e.g. ABC 1234" required />
                            <InputError v-if="finalizeForm.errors.plate_number" :message="finalizeForm.errors.plate_number" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel>Assigned Zone <span class="text-red-500">*</span></InputLabel>
                            <select v-model="finalizeForm.zone_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option disabled value="">Select Zone</option>
                                <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name }}</option>
                            </select>
                            <InputError v-if="finalizeForm.errors.zone_id" :message="finalizeForm.errors.zone_id" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel>Date Issued <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="date" v-model="finalizeForm.date_issued" class="mt-1 w-full" required />
                            <InputError v-if="finalizeForm.errors.date_issued" :message="finalizeForm.errors.date_issued" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel>Valid Until <span class="text-red-500">*</span></InputLabel>
                            <TextInput type="date" v-model="finalizeForm.valid_until" class="mt-1 w-full" required />
                            <InputError v-if="finalizeForm.errors.valid_until" :message="finalizeForm.errors.valid_until" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <SecondaryButton @click="showFinalizeModal = false" :disabled="finalizeForm.processing">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" class="bg-green-600 hover:bg-green-700" :disabled="finalizeForm.processing">
                            Finalize Application
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>