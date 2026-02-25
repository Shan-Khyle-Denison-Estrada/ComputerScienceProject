<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    application: { type: Object, default: null },
    evaluationRequirements: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['close', 'submit']);

const form = useForm({
    documents: {}
});

const docPreviews = ref({});

const requirements = computed(() => {
    return props.evaluationRequirements['Renewal'] || [];
});

const handleDocChange = (event, reqId) => {
    const file = event.target.files[0];
    if (file) {
        form.documents[reqId] = file;
        docPreviews.value[reqId] = file.name;
    }
};

const areAllDocsUploaded = computed(() => {
    return requirements.value.length > 0 && requirements.value.every(r => form.documents[r.id]);
});

const closeModal = () => {
    form.reset();
    form.clearErrors();
    docPreviews.value = {};
    emit('close');
};

const submit = () => {
    if (!props.application) return;
    
    form.post(route('franchise.applications.submit-renewal-documents', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit');
            closeModal();
        }
    });
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Submit Renewal Documents</h2>
                        <p class="text-xs text-gray-500 mt-0.5" v-if="application">
                            For Application: <span class="font-bold font-mono">{{ application.ref_no }}</span>
                        </p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                    <div class="bg-blue-50 text-blue-800 p-4 rounded-lg text-sm mb-6 border border-blue-200">
                        <p class="font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Auto-Generated Renewal
                        </p>
                        <p class="mt-1 ml-7">This is an auto-generated renewal application for the current fiscal year. Please upload all necessary documents to proceed with your evaluation.</p>
                    </div>

                    <h3 class="text-sm font-bold text-gray-800 mb-3 border-b pb-2">Evaluation Requirements</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="req in requirements" :key="req.id" class="border rounded-lg p-4 bg-gray-50 flex flex-col justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-800 mb-1 leading-tight">{{ req.name }}</p>
                            </div>
                            <div class="mt-3">
                                <label :for="`doc-${req.id}`" class="block w-full text-center px-3 py-1.5 border border-gray-300 rounded-md shadow-sm text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 cursor-pointer transition-colors">
                                    {{ docPreviews[req.id] ? 'Change File' : 'Browse File' }}
                                </label>
                                <input :id="`doc-${req.id}`" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png" @change="handleDocChange($event, req.id)" />
                                <p v-if="docPreviews[req.id]" class="mt-2 text-[10px] text-green-600 font-medium truncate flex items-center gap-1">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                    {{ docPreviews[req.id] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 rounded-b-2xl flex-shrink-0">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <PrimaryButton 
                        @click="submit" 
                        :disabled="form.processing || !areAllDocsUploaded"
                        :class="{'opacity-50 cursor-not-allowed': form.processing || !areAllDocsUploaded}">
                        Submit Documents
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>
</template>