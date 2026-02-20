<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, required: true },
    application: { type: Object, default: null }
});

const emit = defineEmits(['close', 'submit']);

// Form object dynamically handles whatever was rejected
const form = useForm({
    documents: {}
});

// We only want the applicant to re-upload files that were rejected.
const rejectedEvaluations = computed(() => {
    if (!props.application || !props.application.evaluations) return [];
    return props.application.evaluations.filter(evalDoc => evalDoc.status === 'Rejected');
});

// When the modal opens, reset the form.
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.reset();
        form.documents = {};
        // Initialize empty document fields for rejected ones
        rejectedEvaluations.value.forEach(doc => {
            form.documents[doc.id] = null;
        });
    }
});

const handleFileUpload = (event, evaluationId) => {
    form.documents[evaluationId] = event.target.files[0];
};

const submit = () => {
    // Check if they uploaded all required corrections
    for (const doc of rejectedEvaluations.value) {
        if (!form.documents[doc.id]) {
            alert(`Please re-upload a file for: ${doc.name}`);
            return;
        }
    }

    form.post(route('franchise.applications.resubmit', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('submit');
        }
    });
};

const closeModal = () => {
    form.reset();
    emit('close');
};
</script>

<template>
    <transition name="fade">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm" @click="closeModal">
            
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[90vh] overflow-hidden" @click.stop>
                
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Application Returned
                        </h2>
                        <p class="text-sm text-gray-500 mt-0.5">Ref No. <span class="font-mono text-gray-800 font-bold">{{ application?.ref_no }}</span></p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-200 rounded-full">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar">
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-lg">
                        <h4 class="text-sm font-bold text-red-800 uppercase tracking-wide mb-1">Return Reason</h4>
                        <p class="text-sm text-red-700 leading-relaxed">{{ application?.remarks }}</p>
                    </div>

                    <div v-if="rejectedEvaluations.length > 0">
                        <h3 class="text-sm font-bold text-gray-900 border-b pb-2 mb-4">Required Corrections</h3>
                        <p class="text-xs text-gray-500 mb-4">The following documents were rejected. Please re-upload corrected versions.</p>

                        <div class="space-y-4">
                            <div v-for="doc in rejectedEvaluations" :key="doc.id" class="p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-bold text-gray-800 text-sm">{{ doc.name }}</h4>
                                </div>
                                
                                <div class="bg-gray-50 p-3 rounded text-xs text-gray-600 mb-4 border border-gray-100 italic">
                                    <span class="font-bold text-gray-800 block mb-1">Admin Feedback:</span>
                                    "{{ doc.remarks }}"
                                </div>

                                <InputLabel :value="`Upload New ${doc.name}`" />
                                <input type="file" 
                                    @change="(e) => handleFileUpload(e, doc.id)"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all cursor-pointer border border-gray-200 rounded-lg shadow-sm"
                                    required
                                >
                                <p class="text-[10px] text-gray-400 mt-1">Accepts PDF, JPG, PNG (Max 5MB)</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6">
                        <p class="text-gray-500 text-sm italic">No specific documents marked as rejected. Wait for further instructions or contact admin.</p>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                    <SecondaryButton @click="closeModal" :disabled="form.processing">Cancel</SecondaryButton>
                    <PrimaryButton 
                        @click="submit" 
                        :class="{ 'opacity-50': form.processing || rejectedEvaluations.length === 0 }" 
                        :disabled="form.processing || rejectedEvaluations.length === 0" 
                        class="bg-blue-600 hover:bg-blue-700 focus:ring-blue-500">
                        {{ form.processing ? 'Submitting...' : 'Submit Compliance' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>