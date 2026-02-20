<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    application: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'submit']);

const complianceForm = useForm({ file: null, remarks: '' });
const compliancePreview = ref(null); 

const handleComplianceFile = (event) => {
    const file = event.target.files[0];
    if (file) { 
        complianceForm.file = file; 
        compliancePreview.value = file.name; 
    }
};

const close = () => {
    complianceForm.reset();
    compliancePreview.value = null;
    emit('close');
};

const submit = () => {
    emit('submit', { 
        appId: props.application.id, 
        remarks: complianceForm.remarks 
    });
    close();
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="close"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-[450px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-start bg-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">Application Returned</h2>
                            <p class="text-xs text-gray-500">{{ application?.ref_no }}</p>
                        </div>
                    </div>
                    <button @click="close" class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <h3 class="text-xs font-bold text-red-800 uppercase mb-1 tracking-wide">Reason for Return:</h3>
                        <p class="text-sm text-red-700 italic">"{{ application?.remarks }}"</p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <InputLabel value="Upload Corrected Document" />
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors relative bg-gray-50/50">
                                <div class="space-y-2 text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div v-if="compliancePreview" class="text-sm text-green-600 font-bold truncate max-w-[200px] mx-auto bg-green-50 px-2 py-1 rounded">
                                        {{ compliancePreview }}
                                    </div>
                                    <div v-else class="flex text-sm text-gray-600 justify-center">
                                        <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                            <span>Upload a file</span>
                                            <input id="file-upload" type="file" class="sr-only" @change="handleComplianceFile" accept="image/*,.pdf" />
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Additional Remarks / Explanation" />
                            <TextInput v-model="complianceForm.remarks" class="w-full text-sm" placeholder="I have uploaded the clear copy..." />
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                    <SecondaryButton @click="close">Cancel</SecondaryButton>
                    <PrimaryButton @click="submit" class="bg-blue-600 hover:bg-blue-700" :disabled="!complianceForm.file && !complianceForm.remarks">
                        Resubmit Application
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>