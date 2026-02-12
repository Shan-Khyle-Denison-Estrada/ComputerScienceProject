<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    application: Object,
    requirements: Array
});

// Helper to check uploads
const getAttachment = (reqId) => {
    return props.application.attachments.find(a => a.application_requirement_id === reqId);
};

// Upload Logic
const uploadForm = useForm({
    requirement_id: null,
    file: null
});

const handleUpload = (reqId, event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploadForm.requirement_id = reqId;
    uploadForm.file = file;

    uploadForm.post(route('franchise.applications.upload', props.application.id), {
        preserveScroll: true,
        onSuccess: () => uploadForm.reset()
    });
};

const submitApplication = () => {
    if(!confirm("Submit application for review?")) return;
    router.post(route('franchise.applications.submit', props.application.id));
};
</script>

<template>
    <Head title="Upload Requirements" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Application #{{ application.id }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <nav class="mb-8">
                    <ol role="list" class="space-y-4 md:flex md:space-y-0 md:space-x-8">
                        <li class="md:flex-1">
                            <div class="pl-4 py-2 border-l-4 border-indigo-600 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">
                                <span class="text-xs text-indigo-600 font-semibold uppercase">Step 1</span>
                                <span class="text-sm font-medium">Details (Saved)</span>
                            </div>
                        </li>
                        <li class="md:flex-1">
                            <div class="pl-4 py-2 border-l-4 border-indigo-600 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4">
                                <span class="text-xs text-indigo-600 font-semibold uppercase">Step 2</span>
                                <span class="text-sm font-medium">Upload Requirements</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 space-y-4">
                        <div class="bg-white shadow sm:rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Required Documents</h3>
                            
                            <div class="space-y-6">
                                <div v-for="req in requirements" :key="req.id" class="border rounded-lg p-4" 
                                    :class="getAttachment(req.id) ? 'bg-green-50 border-green-200' : 'bg-gray-50'">
                                    
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-bold text-gray-800">{{ req.name }}</p>
                                            <p v-if="getAttachment(req.id)" class="text-xs text-green-600 font-bold mt-1">✓ Uploaded</p>
                                            <p v-else class="text-xs text-red-500 mt-1">* Pending Upload</p>
                                        </div>
                                        
                                        <div>
                                            <label class="cursor-pointer inline-flex items-center px-3 py-1 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                                {{ getAttachment(req.id) ? 'Replace' : 'Upload' }}
                                                <input type="file" class="hidden" @change="handleUpload(req.id, $event)">
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div v-if="uploadForm.processing && uploadForm.requirement_id === req.id" class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                        <div class="bg-indigo-600 h-1.5 rounded-full animate-pulse" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white shadow sm:rounded-lg p-6">
                            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Assessment Info</h3>
                            <div v-if="application.assessment">
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Total Due:</span>
                                    <span class="font-bold">₱{{ application.assessment.total_amount_due }}</span>
                                </div>
                                <div class="text-xs text-gray-500 italic">
                                    Generated automatically based on application type.
                                </div>
                            </div>
                        </div>

                        <div class="bg-white shadow sm:rounded-lg p-6">
                             <PrimaryButton @click="submitApplication" class="w-full justify-center">
                                Submit Application
                            </PrimaryButton>
                            <p class="text-center text-xs text-gray-500 mt-2">
                                Make sure all documents are uploaded.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>