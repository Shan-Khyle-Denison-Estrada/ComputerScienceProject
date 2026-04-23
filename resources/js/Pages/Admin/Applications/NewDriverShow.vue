<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import FinalizeDriverModal from '@/Components/Modals/FinalizeDriverModal.vue'; // <-- Added Import
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    application: Object
});

// --- STATE ---
const activeTab = ref('uploads');
const showRejectModal = ref(false);
const rejectRemarks = ref('');
const rejectError = ref('');

const showDocumentModal = ref(false);
const currentDocumentUrl = ref(null);

// State for the new Finalize Driver Modal
const isFinalizeDriverModalOpen = ref(false); // <-- Added State

// --- ACTIONS ---
const confirmApproveApplication = () => {
    if (!confirm("Are you sure you want to APPROVE this New Driver application?")) return;
    
    router.post(route('admin.applications.approve', props.application.id), {}, {
        onSuccess: () => {}
    });
};

const confirmRejectApplication = () => {
    rejectError.value = '';
    if(!rejectRemarks.value.trim()){
        rejectError.value = 'Reason for rejection is required.';
        return;
    }

    if (!confirm("Are you sure you want to completely REJECT this application? This action cannot be undone.")) return;

    router.post(route('admin.applications.reject', props.application.id), {
        remarks: rejectRemarks.value
    }, {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectRemarks.value = '';
        }
    });
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    rejectRemarks.value = '';
    rejectError.value = '';
};

const openDocumentModal = (url) => {
    if(!url) return;
    currentDocumentUrl.value = url;
    showDocumentModal.value = true;
};

const closeDocumentModal = () => {
    showDocumentModal.value = false;
    currentDocumentUrl.value = null;
};

const isImageUrl = (url) => {
    if (!url) return false;
    const cleanUrl = url.split('?')[0]; 
    return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(cleanUrl);
};
</script>

<template>
    <Head title="Application Details" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">New Driver Application</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_number }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Approved' && can('finalize_applications')">
                        <button @click="isFinalizeDriverModalOpen = true" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Finalize Driver
                        </button>
                    </template>
                    <template v-else-if="application.status === 'Rejected'">
                        <p class="text-red-600 font-bold italic pr-4 mt-2">Rejected</p>
                    </template>
                    <template v-else-if="application.status === 'Completed'">
                        <p class="text-green-600 font-bold italic pr-4 mt-2">Completed</p>
                    </template>
                    <template v-else>
                        <button @click="showRejectModal = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Reject
                        </button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            Approve
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex gap-4 h-full min-h-0">
                
                <div class="w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide" 
                                :class="{
                                    'bg-yellow-100 text-yellow-800': application.status === 'Pending',
                                    'bg-green-100 text-green-800': application.status === 'Approved',
                                    'bg-red-100 text-red-800': application.status === 'Rejected'
                                }">
                                {{ application.status }}
                            </span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.application_type }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                         <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-gray-200 border-2 border-white shadow-md flex items-center justify-center text-2xl font-bold text-gray-400 mb-3 overflow-hidden cursor-pointer" @click="openDocumentModal(`/storage/${application.driver_user_photo}`)">
                                <img v-if="application.driver_user_photo" :src="`/storage/${application.driver_user_photo}`" class="w-full h-full object-cover hover:opacity-80 transition-opacity" />
                                <span v-else>{{ application.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">
                                {{ application.first_name }} 
                                <span v-if="application.middle_name">{{ application.middle_name }}</span>
                                {{ application.last_name }}
                            </h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Contact No.</p>
                                <p class="text-sm font-medium text-gray-800">{{ application.contact_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">License Number</p>
                                <p class="text-sm font-bold text-blue-600 font-mono">{{ application.driver_license_number }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">License Expiry</p>
                                <p class="text-sm font-medium text-gray-800">{{ application.driver_license_expiration_date }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Address</p>
                                <p class="text-sm font-medium text-gray-800">{{ application.street_address }}, {{ application.barangay }}, {{ application.city }}, {{ application.province }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">
                    
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6 shrink-0">
                        <button @click="activeTab = 'uploads'" :class="activeTab === 'uploads' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">License Images</button>
                        <button @click="activeTab = 'franchise'" :class="activeTab === 'franchise' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Franchise Details</button>
                        <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Evaluation Requirements</button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar bg-gray-50 p-6">
                        
                        <div v-show="activeTab === 'uploads'" class="space-y-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-4">Driver License Photos</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                
                                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col hover:border-blue-300 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg></div>
                                            <div><h4 class="text-sm font-bold text-gray-900">License (Front)</h4></div>
                                        </div>
                                    </div>
                                    <div class="relative w-full h-48 bg-gray-100 rounded border overflow-hidden flex items-center justify-center cursor-pointer" @click="openDocumentModal(`/storage/${application.driver_license_front_photo}`)">
                                        <img v-if="application.driver_license_front_photo" :src="`/storage/${application.driver_license_front_photo}`" class="w-full h-full object-cover hover:opacity-90 transition-opacity" />
                                        <span v-else class="text-xs text-gray-400">No Image</span>
                                    </div>
                                </div>

                                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col hover:border-blue-300 transition-colors">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-blue-50 text-blue-600 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg></div>
                                            <div><h4 class="text-sm font-bold text-gray-900">License (Back)</h4></div>
                                        </div>
                                    </div>
                                    <div class="relative w-full h-48 bg-gray-100 rounded border overflow-hidden flex items-center justify-center cursor-pointer" @click="openDocumentModal(`/storage/${application.driver_license_back_photo}`)">
                                        <img v-if="application.driver_license_back_photo" :src="`/storage/${application.driver_license_back_photo}`" class="w-full h-full object-cover hover:opacity-90 transition-opacity" />
                                        <span v-else class="text-xs text-gray-400">No Image</span>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div v-show="activeTab === 'franchise'" class="space-y-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-4">Target Franchise Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-5 rounded-xl border border-gray-200 shadow-sm" v-if="application.franchise">
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Franchise No.</p><p class="text-sm font-bold text-gray-900">{{ application.franchise.franchise_number || 'N/A' }}</p></div>
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Zone Assigned</p><p class="text-sm font-medium text-gray-900">{{ application.franchise.zone?.description || application.zone?.description || 'N/A' }}</p></div>
                                <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Original Issue Date</p><p class="text-sm font-medium text-gray-900">{{ application.franchise.date_issued || 'N/A' }}</p></div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic p-4 bg-white border rounded">
                                No franchise details are attached to this application.
                            </div>
                        </div>

                        <div v-show="activeTab === 'evaluations'" class="space-y-6">
                            <div class="flex items-center justify-between border-b pb-2 mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Evaluation Requirements</h3>
                                <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-1 rounded">Evaluator Assessment View</span>
                            </div>
                            
                            <div v-if="application.evaluations && application.evaluations.length > 0" class="space-y-4">
                                <div v-for="evalRecord in application.evaluations" :key="evalRecord.id" 
                                     class="p-4 border rounded-xl shadow-sm" 
                                     :class="evalRecord.is_compliant === 1 ? 'bg-green-50 border-green-200' : (evalRecord.is_compliant === 0 ? 'bg-red-50 border-red-200' : 'bg-white border-gray-200')">
                                    
                                    <div class="flex justify-between items-start mb-2">
                                        <strong class="text-gray-900">{{ evalRecord.requirement?.name || 'Uploaded Document' }}</strong>
                                        <span v-if="evalRecord.is_compliant === 1" class="px-2 py-1 text-[10px] font-bold bg-green-200 text-green-800 rounded uppercase tracking-wide">Compliant</span>
                                        <span v-else-if="evalRecord.is_compliant === 0" class="px-2 py-1 text-[10px] font-bold bg-red-200 text-red-800 rounded uppercase tracking-wide">Deficient</span>
                                        <span v-else class="px-2 py-1 text-[10px] font-bold bg-gray-200 text-gray-600 rounded uppercase tracking-wide">Pending Review</span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <button v-if="evalRecord.file_path" @click.prevent="openDocumentModal(`/storage/${evalRecord.file_path}`)" class="text-sm text-blue-600 hover:text-blue-800 hover:underline font-medium inline-flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            View Attached File
                                        </button>
                                        <span v-else class="text-sm text-gray-500 italic">No file attached</span>
                                    </div>
                                    
                                    <div class="text-sm text-gray-700 bg-white p-3 rounded border">
                                        <span class="font-bold text-gray-900 block mb-1">Evaluator Remarks:</span> 
                                        <span class="italic">{{ evalRecord.remarks || 'No remarks provided by the evaluator.' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic p-6 border border-dashed border-gray-300 rounded text-center bg-white">
                                No evaluation requirements were attached to this application.
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <Transition name="fade">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden" @click.stop>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-2 border-b pb-2 text-red-600">Reject Application</h3>
                            <p class="text-sm text-gray-500 mb-4">Please provide a reason for completely rejecting this New Driver application.</p>
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks / Reason</label>
                                <textarea v-model="rejectRemarks" rows="3" class="w-full text-sm border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm" placeholder="Applicant does not meet requirements..."></textarea>
                                <p v-if="rejectError" class="text-red-500 text-xs mt-1 font-semibold">{{ rejectError }}</p>
                            </div>
                            <div class="flex justify-end gap-3 pt-2">
                                <SecondaryButton @click="closeRejectModal">Cancel</SecondaryButton>
                                <PrimaryButton class="bg-red-600 hover:bg-red-700 focus:ring-red-500" @click="confirmRejectApplication">
                                    Confirm Rejection
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showDocumentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                    <div class="absolute inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeDocumentModal"></div>
                    <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl overflow-hidden flex flex-col h-[90vh]">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 flex-shrink-0">
                            <h3 class="text-lg font-bold text-gray-800">Document Viewer</h3>
                            <button @click="closeDocumentModal" class="text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="flex-1 bg-gray-200 relative w-full h-full flex items-center justify-center overflow-auto p-4">
                            <img v-if="isImageUrl(currentDocumentUrl)" :src="currentDocumentUrl" class="max-w-full max-h-full object-contain drop-shadow-md rounded" />
                            <iframe v-else-if="currentDocumentUrl" :src="currentDocumentUrl" class="w-full h-full bg-white shadow-sm rounded" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>
            </Transition>

            <FinalizeDriverModal 
                v-if="application.franchise"
                :show="isFinalizeDriverModalOpen" 
                :franchise-id="application.franchise.id" 
                :application-data="application" 
                @close="isFinalizeDriverModalOpen = false" 
            />

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }

/* Transition styles for modals */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>