<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    application: Object,
    requirements: Array,
});

// Access globally shared settings for dynamic theme color
const page = usePage();
const settings = computed(() => page.props.settings || {});
const currentThemeColor = computed(() => settings.value.theme_color || '#2563eb'); // Default fallback blue

const form = useForm({
    documents: {},
    _method: 'POST', 
});

const filePreviews = ref({});

const handleFileChange = (event, reqId) => {
    const file = event.target.files[0];
    if (file) {
        form.documents[reqId] = file;
        filePreviews.value[reqId] = file.name;
        form.clearErrors(`documents.${reqId}`);
    }
};

const areAllRequirementsMet = computed(() => {
    if (props.requirements.length === 0) return true;
    return props.requirements.every(req => form.documents[req.id]);
});

const submit = () => {
    const urlParams = new URLSearchParams(window.location.search);

    form.post(route('application.complete.submit', {
        application: props.application.id,
        expires: urlParams.get('expires'),
        signature: urlParams.get('signature')
    }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Complete Application - TRICYSYS" />

    <div class="min-h-screen bg-slate-50 font-sans selection:bg-black selection:text-white">
        
        <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg theme-bg flex items-center justify-center text-white font-black text-lg shadow-sm">
                            T
                        </div>
                        <span class="font-extrabold text-xl tracking-tight text-slate-900">TRICYSYS</span>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Complete Your Application</h1>
                <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto">
                    You're almost done. Securely upload the required documents below to finalize your <span class="font-semibold text-slate-700">{{ application.application_type }}</span> application.
                </p>
            </div>

            <div class="bg-white shadow-2xl shadow-slate-200/50 rounded-3xl overflow-hidden border border-slate-100 relative">
                
                <div class="theme-bg px-8 py-6 flex flex-col md:flex-row md:items-center justify-between relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-transparent to-transparent"></div>
                    
                    <div class="relative z-10">
                        <p class="text-white/80 text-xs font-bold uppercase tracking-widest mb-1">Reference Number</p>
                        <p class="text-white font-mono font-bold text-2xl tracking-tight">{{ application.reference_number }}</p>
                    </div>
                    <div class="mt-5 md:mt-0 md:text-right relative z-10">
                        <p class="text-white/80 text-xs font-bold uppercase tracking-widest mb-1">Applicant</p>
                        <p class="text-white font-bold text-xl">{{ application.first_name }} {{ application.last_name }}</p>
                    </div>
                </div>

                <div class="p-8 sm:p-10">
                    <form @submit.prevent="submit">
                        
                        <div v-if="form.errors.error" class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-red-700 text-sm font-medium">{{ form.errors.error }}</span>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-8">
                            <h3 class="text-xl font-bold text-slate-900">Required Documents</h3>
                            <span class="text-sm font-medium text-slate-500">{{ Object.keys(filePreviews).length }} of {{ requirements.length }} Uploaded</span>
                        </div>

                        <div v-if="requirements.length === 0" class="text-center py-16 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50">
                            <div class="mx-auto w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <p class="text-slate-600 font-medium">No evaluation documents are needed for this application type.</p>
                        </div>

                        <div v-else class="space-y-5">
                            <div v-for="req in requirements" :key="req.id" 
                                class="relative p-5 rounded-2xl transition-all duration-300 border-2 overflow-hidden group"
                                :class="filePreviews[req.id] ? 'border-emerald-500 bg-emerald-50/30' : 'border-slate-200 bg-white hover:border-slate-300'">
                                
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 relative z-10">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <div v-if="filePreviews[req.id]" class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            </div>
                                            <div v-else class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0">
                                                <span class="text-xs font-bold">{{ requirements.indexOf(req) + 1 }}</span>
                                            </div>
                                            <p class="text-base font-bold text-slate-900">{{ req.name }} <span class="text-red-500">*</span></p>
                                        </div>
                                        
                                        <div v-if="filePreviews[req.id]" class="mt-2 ml-8 flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-emerald-100 shadow-sm w-fit max-w-full">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                            <span class="text-sm text-emerald-700 font-medium truncate">{{ filePreviews[req.id] }}</span>
                                        </div>
                                        <p v-else class="mt-1 ml-8 text-sm text-slate-500">Please upload a clear PDF or Image file.</p>
                                    </div>
                                    
                                    <div class="flex-shrink-0 sm:ml-4 pl-8 sm:pl-0">
                                        <label class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border shadow-sm text-sm font-semibold rounded-xl transition-all active:scale-95"
                                            :class="filePreviews[req.id] ? 'border-emerald-200 text-emerald-700 bg-emerald-100 hover:bg-emerald-200' : 'border-slate-300 text-slate-700 bg-white hover:bg-slate-50 hover:shadow'">
                                            <span>{{ filePreviews[req.id] ? 'Replace File' : 'Browse File' }}</span>
                                            <input type="file" @change="e => handleFileChange(e, req.id)" class="hidden" accept=".pdf,image/*">
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors[`documents.${req.id}`]" class="mt-3 ml-8" />
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-slate-100">
                            <button 
                                type="submit" 
                                :disabled="form.processing || !areAllRequirementsMet"
                                :class="{'opacity-50 grayscale cursor-not-allowed': form.processing || !areAllRequirementsMet}"
                                class="w-full flex justify-center items-center py-4 px-4 rounded-xl shadow-lg text-lg font-bold text-white theme-btn transition-all duration-300">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Submitting Application...' : 'Submit Application' }}
                            </button>
                            <p v-if="!areAllRequirementsMet" class="text-center text-sm text-red-500 mt-4 font-medium flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Upload all required documents to proceed.
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
            <p class="text-center text-sm text-slate-400 mt-8 font-medium">
                &copy; {{ new Date().getFullYear() }} TRICYSYS Franchise Management System. All rights reserved.
            </p>
        </div>
    </div>
</template>

<style scoped>
/* Inject dynamic theme styling natively */
.theme-bg { background-color: v-bind(currentThemeColor); }
.theme-text { color: v-bind(currentThemeColor); }
.theme-ring { --tw-ring-color: v-bind(currentThemeColor); box-shadow: 0 0 0 2px var(--tw-ring-color); }
.theme-btn { 
    background-color: v-bind(currentThemeColor); 
}
.theme-btn:hover:not(:disabled) { 
    filter: brightness(0.90); 
    box-shadow: 0 10px 20px -5px v-bind('currentThemeColor + "60"'); 
    transform: translateY(-1px);
}
</style>