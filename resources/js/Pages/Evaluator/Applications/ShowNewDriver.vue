<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object
});

const activeTab = ref('driver_details'); 

// Modals State
const showRequirementModal = ref(false);
const selectedRequirementIndex = ref(null);
const requirementForm = reactive({ remarks: '' });

const showRejectModal = ref(false);
const rejectForm = reactive({ remarks: '', processing: false, error: '' });

const showReturnModal = ref(false);
const returnForm = reactive({ remarks: '', processing: false, error: '' });

const showApproveModal = ref(false);
const approveProcessing = ref(false);

const showDocumentModal = ref(false);
const currentDocumentUrl = ref(null);

const application = computed(() => {
    const app = props.application || {};
    const franchise = app.franchise || {};

    return {
        id: app.id,
        type: app.application_type || 'New Driver',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        driver: {
            name: `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''}`.replace(/\s+/g, ' ').trim() || 'N/A',
            contact: app.contact_number || 'N/A',
            license_no: app.driver_license_number || 'N/A',
            license_expiry: app.driver_license_expiration_date || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}, ${app.province || ''}`.replace(/^[,\s]+|[,\s]+$/g, '').replace(/,\s*,/g, ',') || 'N/A',
            user_photo: app.driver_user_photo ? `/storage/${app.driver_user_photo}` : null,
            license_front: app.driver_license_front_photo ? `/storage/${app.driver_license_front_photo}` : null,
            license_back: app.driver_license_back_photo ? `/storage/${app.driver_license_back_photo}` : null,
        },

        franchise_details: {
            franchise_no: franchise.franchise_number || 'N/A',
            zone: franchise.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A'
        },

        evaluation_requirements: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement ? evalDoc.requirement.name : 'Document',
            status: evalDoc.is_compliant === 1 ? 'Approved' : (evalDoc.is_compliant === 0 ? 'Rejected' : 'Pending'),
            remarks: evalDoc.remarks || 'Pending Review',
            file_url: evalDoc.file_path ? `/storage/${evalDoc.file_path}` : null,
        }))
    };
});

const openRequirementModal = (index) => {
    selectedRequirementIndex.value = index;
    requirementForm.remarks = application.value.evaluation_requirements[index].remarks;
    if (requirementForm.remarks === 'Pending Review') requirementForm.remarks = '';
    showRequirementModal.value = true;
};

const closeRequirementModal = () => showRequirementModal.value = false;

const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value === null) return;
    const evaluation = application.value.evaluation_requirements[selectedRequirementIndex.value];
    
    router.post(route('evaluator.applications.evaluate', application.value.id), {
        evaluation_id: evaluation.id,
        status: status,
        remarks: requirementForm.remarks || (status === 'Approved' ? 'Document accepted.' : 'Document rejected.')
    }, {
        onSuccess: () => closeRequirementModal()
    });
};

const submitApprove = () => {
    approveProcessing.value = true;
    router.post(route('evaluator.applications.approve', application.value.id), {}, {
        onFinish: () => approveProcessing.value = false
    });
};

const submitReturn = () => {
    returnForm.error = '';
    if(!returnForm.remarks.trim()) {
        returnForm.error = 'Reason for return is required.';
        return;
    }
    
    returnForm.processing = true;
    router.post(route('evaluator.applications.return', application.value.id), { remarks: returnForm.remarks }, {
        onFinish: () => returnForm.processing = false,
        onSuccess: () => {
            showReturnModal.value = false;
            returnForm.remarks = '';
        }
    });
};

const submitReject = () => {
    rejectForm.error = '';
    if(!rejectForm.remarks.trim()) {
        rejectForm.error = 'Reason for rejection is required.';
        return;
    }

    rejectForm.processing = true;
    router.post(route('evaluator.applications.reject', application.value.id), { remarks: rejectForm.remarks }, {
        onFinish: () => rejectForm.processing = false,
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.remarks = '';
        }
    });
};

const openDocumentModal = (url) => {
    currentDocumentUrl.value = url;
    showDocumentModal.value = true;
};

const closeDocumentModal = () => {
    showDocumentModal.value = false;
    currentDocumentUrl.value = null;
};

const closeReturnModal = () => {
    showReturnModal.value = false;
    returnForm.error = '';
};

const closeRejectModal = () => {
    showRejectModal.value = false;
    rejectForm.error = '';
};

const isImageUrl = (url) => {
    if (!url) return false;
    const cleanUrl = url.split('?')[0]; 
    return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(cleanUrl);
};
</script>

<template>
    <Head title="Evaluate New Driver" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Evaluate New Driver Application</h2>
                    <p class="text-sm text-gray-500 mt-1">Application Ref: {{ application.reference_no }}</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-semibold border"
                     :class="application.status === 'Approved' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-yellow-100 text-yellow-700 border-yellow-200'">
                    {{ application.status }}
                </div>
            </div>
        </template>
        
        <div class="w-full flex flex-row gap-0 h-[calc(100vh-100px)] overflow-hidden relative rounded-lg">
            
            <div class="w-2/3 bg-white shadow-sm border-r border-gray-200 p-6 flex flex-col h-full flex-shrink-0">
                
                <div class="border-b border-gray-200 mb-6 flex gap-6 overflow-x-auto pb-1 flex-shrink-0">
                    <button @click="activeTab = 'driver_details'" :class="activeTab === 'driver_details' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Driver Details</button>
                    <button @click="activeTab = 'franchise_overview'" :class="activeTab === 'franchise_overview' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Franchise Info</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'driver_details'" class="space-y-8">
                            
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg> 
                                    Proposed Driver Information
                                </h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Full Name</p><p class="font-medium text-gray-900">{{ application.driver.name }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Contact No.</p><p class="font-medium text-gray-900">{{ application.driver.contact }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">License Number</p><p class="font-medium text-gray-900 font-mono">{{ application.driver.license_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Expiration Date</p><p class="font-medium text-gray-900">{{ application.driver.license_expiry }}</p></div>
                                    <div class="col-span-2"><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Address</p><p class="font-medium text-gray-900">{{ application.driver.address }}</p></div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-6">
                                <h3 class="font-bold text-blue-900 mb-6 flex items-center gap-2 text-lg">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    Driver Uploads
                                </h3>
                                
                                <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 relative">
                                    
                                    <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2 text-center">
                                            2x2 Photo
                                        </div>
                                        <div class="aspect-square bg-gray-50 rounded flex items-center justify-center overflow-hidden border">
                                            <img v-if="application.driver.user_photo" :src="application.driver.user_photo" class="object-cover w-full h-full cursor-pointer hover:opacity-90" @click="openDocumentModal(application.driver.user_photo)" />
                                            <span v-else class="text-[10px] text-gray-400">No Image</span>
                                        </div>
                                    </div>

                                    <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm xl:col-span-2">
                                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2 text-center">
                                            Driver's License Images
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="border border-gray-200 rounded p-1 bg-gray-50">
                                                <p class="text-[10px] text-gray-500 mb-1 text-center">Front View</p>
                                                <div class="aspect-video bg-white rounded flex items-center justify-center overflow-hidden border">
                                                    <img v-if="application.driver.license_front" :src="application.driver.license_front" class="object-cover w-full h-full cursor-pointer hover:opacity-90" @click="openDocumentModal(application.driver.license_front)" />
                                                    <span v-else class="text-[10px] text-gray-400">No Image</span>
                                                </div>
                                            </div>
                                            <div class="border border-gray-200 rounded p-1 bg-gray-50">
                                                <p class="text-[10px] text-gray-500 mb-1 text-center">Back View</p>
                                                <div class="aspect-video bg-white rounded flex items-center justify-center overflow-hidden border">
                                                    <img v-if="application.driver.license_back" :src="application.driver.license_back" class="object-cover w-full h-full cursor-pointer hover:opacity-90" @click="openDocumentModal(application.driver.license_back)" />
                                                    <span v-else class="text-[10px] text-gray-400">No Image</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'franchise_overview'" class="space-y-6">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Franchise Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Franchise No.</p><p class="font-bold text-gray-900">{{ application.franchise_details.franchise_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Zone Assigned</p><p class="font-medium text-gray-900">{{ application.franchise_details.zone }}</p></div>
                                    <!-- <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">MTFRB Case No.</p><p class="font-medium text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Original Issue Date</p><p class="font-medium text-gray-900">{{ application.franchise_details.date_issued }}</p></div> -->
                                </div>
                            </div>
                        </div>

                    </Transition>
                </div>
            </div>

            <div class="w-1/3 bg-gray-50 p-6 flex flex-col h-full flex-shrink-0 border-l border-gray-200">
                <h3 class="font-bold text-lg text-gray-800 border-b pb-3 mb-4 flex items-center gap-2 flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Evaluation Panel
                </h3>
                
                <p class="text-xs text-gray-500 mb-4 flex-shrink-0">Review the required documents below. Click to accept or reject them.</p>

                <div class="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-2 mb-6">
                    <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" 
                         @click="openRequirementModal(index)"
                         class="p-4 rounded-xl border-2 transition-all cursor-pointer bg-white shadow-sm"
                         :class="req.status === 'Approved' ? 'border-green-200 hover:border-green-400' : (req.status === 'Rejected' ? 'border-red-200 hover:border-red-400' : 'border-gray-200 hover:border-blue-400')">
                         <div class="flex justify-between items-start mb-2">
                             <p class="text-sm font-bold text-gray-800 pr-2">{{ req.name }}</p>
                             <svg v-if="req.status === 'Approved'" class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                             <svg v-else-if="req.status === 'Rejected'" class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                             <div v-else class="w-5 h-5 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                         </div>
                         <div v-if="req.file_url" class="mt-2 mb-2">
                             <button type="button" @click.stop="openDocumentModal(req.file_url)" class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                 <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg> View Attached Document
                             </button>
                         </div>
                         <p class="text-xs text-gray-500 bg-gray-50 p-2 rounded truncate mt-1">Note: {{ req.remarks }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-3 flex-shrink-0">
                    <PrimaryButton @click="showApproveModal = true" class="w-full justify-center py-3 bg-green-600 hover:bg-green-700 shadow text-sm">
                        Approve Evaluation
                    </PrimaryButton>
                    <SecondaryButton @click="showReturnModal = true" class="w-full justify-center py-3 !text-yellow-600 border-yellow-200 hover:bg-yellow-50 text-sm">
                        Return Application
                    </SecondaryButton>
                    <SecondaryButton @click="showRejectModal = true" class="w-full justify-center py-3 !text-red-600 border-red-200 hover:bg-red-50 text-sm">
                        Reject Application
                    </SecondaryButton>
                </div>
            </div>
        </div>

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

        <Modal :show="showRequirementModal" @close="closeRequirementModal" maxWidth="md">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b pb-4 mb-4">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 leading-tight">Evaluate Requirement</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ selectedRequirementIndex !== null ? application.evaluation_requirements[selectedRequirementIndex].name : '' }}</p>
                    </div>
                </div>

                <div class="mb-5">
                    <InputLabel value="Evaluation Remarks / Notes" class="text-xs mb-1" />
                    <TextInput v-model="requirementForm.remarks" class="block w-full text-sm py-2" placeholder="Ex: Blurred ID, Missing Signature..." />
                </div>

                <div class="flex gap-3">
                    <PrimaryButton @click="saveRequirementStatus('Approved')" class="flex-1 justify-center bg-green-600 hover:bg-green-700 py-2.5">
                        Accept Document
                    </PrimaryButton>
                    <PrimaryButton @click="saveRequirementStatus('Rejected')" class="flex-1 justify-center bg-red-600 hover:bg-red-700 py-2.5">
                        Reject Document
                    </PrimaryButton>
                </div>
                <div class="mt-3">
                    <SecondaryButton @click="closeRequirementModal" class="w-full justify-center py-2.5">Cancel</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showApproveModal" @close="showApproveModal = false" maxWidth="sm">
            <div class="p-6 text-center">
                <h3 class="text-lg font-bold text-gray-900 mb-2">Approve Evaluation?</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to approve this New Driver evaluation?</p>
                <div class="flex justify-center gap-3">
                    <SecondaryButton @click="showApproveModal = false" class="w-1/2 justify-center" :disabled="approveProcessing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitApprove" class="w-1/2 justify-center bg-green-600 hover:bg-green-700" :disabled="approveProcessing">
                        {{ approveProcessing ? 'Approving...' : 'Yes, Approve' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showReturnModal" @close="closeReturnModal" maxWidth="sm">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 text-yellow-600">Return Application</h3>
                <p class="text-sm text-gray-500 mb-4">Send this application back to the encoder/owner for corrections.</p>
                <div class="mb-5">
                    <InputLabel value="Reason for Return / Required Corrections" class="text-xs mb-1" />
                    <textarea v-model="returnForm.remarks" class="w-full border-gray-300 focus:border-yellow-500 focus:ring-yellow-500 rounded-md shadow-sm text-sm" rows="3" placeholder="What needs to be fixed?"></textarea>
                    <p v-if="returnForm.error" class="text-red-500 text-xs mt-1">{{ returnForm.error }}</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="closeReturnModal" :disabled="returnForm.processing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitReturn" class="bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500 text-white" :disabled="returnForm.processing">
                        Confirm Return
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRejectModal" @close="closeRejectModal" maxWidth="sm">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 text-red-600">Reject Application</h3>
                <div class="mb-5">
                    <InputLabel value="Reason for Rejection" class="text-xs mb-1" />
                    <textarea v-model="rejectForm.remarks" class="w-full border-gray-300 focus:border-red-500 rounded-md shadow-sm text-sm" rows="3"></textarea>
                    <p v-if="rejectForm.error" class="text-red-500 text-xs mt-1">{{ rejectForm.error }}</p>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="closeRejectModal" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitReject" class="bg-red-600 hover:bg-red-700" :disabled="rejectForm.processing">
                        Confirm Reject
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>