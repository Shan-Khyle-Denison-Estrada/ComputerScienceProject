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
    application: Object,
    // Kept in props in case the controller still sends them, but we won't use them in the UI anymore
    inspectionItems: { type: Array, default: () => [] },
    unitInspections: { type: Array, default: () => [] }
});

const activeTab = ref('operator_info'); 

// Modals State
const showRequirementModal = ref(false);
const selectedRequirementIndex = ref(null);
const requirementForm = reactive({ remarks: '' });

const showRejectModal = ref(false);
const rejectForm = reactive({ remarks: '', processing: false });

const showApproveModal = ref(false);
const approveProcessing = ref(false);

const showDocumentModal = ref(false);
const currentDocumentUrl = ref(null);

const unitViews = [
    { key: 'front', label: 'Front View' },
    { key: 'back', label: 'Back View' },
    { key: 'left', label: 'Left Side View' },
    { key: 'right', label: 'Right Side View' }
];

const application = computed(() => {
    const app = props.application || {};
    
    // Extract the latest proposed unit from the array
    const proposedUnits = app.proposed_units || [];
    const latestProposedUnit = proposedUnits.length > 0 ? proposedUnits[proposedUnits.length - 1] : {};

    return {
        id: app.id,
        type: app.application_type || 'New Franchise',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        // Applicant / Proposed Operator Info
        operator_info: {
            name: `${app.first_name || ''} ${app.middle_name || ''} ${app.last_name || ''}`.replace(/\s+/g, ' ').trim() || 'Not specified',
            contact: app.contact_number || 'N/A',
            email: app.email || 'N/A',
            tin_number: app.tin_number || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
            // Try reading zone from application directly, fallback to the latest proposed unit
            zone: app.zone?.description || latestProposedUnit.zone?.description || 'N/A'
        },

        // Support for Multiple Proposed Units
        proposed_units: (app.proposed_units || []).map(unit => ({
            make: unit.make?.name || 'Not specified',
            motor_no: unit.motor_number || 'Not specified',
            chassis_no: unit.chassis_number || 'Not specified',
            plate_no: unit.plate_number || 'Not specified',
            cr_no: unit.cr_number || 'Not specified',
            year: unit.model_year || 'Not specified',
            
            // Photos
            front_photo: unit.unit_front_photo ? `/storage/${unit.unit_front_photo}` : (unit.front_photo ? `/storage/${unit.front_photo}` : null),
            back_photo: unit.unit_back_photo ? `/storage/${unit.unit_back_photo}` : (unit.back_photo ? `/storage/${unit.back_photo}` : null),
            left_photo: unit.unit_left_photo ? `/storage/${unit.unit_left_photo}` : (unit.left_photo ? `/storage/${unit.left_photo}` : null),
            right_photo: unit.unit_right_photo ? `/storage/${unit.unit_right_photo}` : (unit.right_photo ? `/storage/${unit.right_photo}` : null),
            
            // Documents
            cr_photo: unit.cr_photo ? `/storage/${unit.cr_photo}` : null,
            or_photo: unit.or_photo ? `/storage/${unit.or_photo}` : null,
            franchise_certificate_photo: unit.franchise_certificate_photo ? `/storage/${unit.franchise_certificate_photo}` : null,
        })),

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

const submitReject = () => {
    if(!rejectForm.remarks) return;
    rejectForm.processing = true;
    router.post(route('evaluator.applications.reject', application.value.id), { remarks: rejectForm.remarks }, {
        onFinish: () => rejectForm.processing = false
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

const isImageUrl = (url) => {
    if (!url) return false;
    const cleanUrl = url.split('?')[0]; 
    return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(cleanUrl);
};
</script>

<template>
    <Head title="Evaluate New Franchise" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Evaluate New Franchise</h2>
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
                    <button @click="activeTab = 'operator_info'" :class="activeTab === 'operator_info' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Proposed Operator Info</button>
                    <button @click="activeTab = 'unit_details'" :class="activeTab === 'unit_details' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Proposed Unit(s)</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'operator_info'" class="space-y-6">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-6">
                                <h3 class="font-bold text-blue-900 mb-6 flex items-center gap-2 text-lg">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    Franchise Owner Details
                                </h3>
                                
                                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="grid grid-cols-2 gap-y-6 gap-x-8">
                                        <div><p class="text-xs text-gray-500 mb-1">Full Name</p><p class="font-medium text-gray-900 text-lg">{{ application.operator_info.name }}</p></div>
                                        <div><p class="text-xs text-gray-500 mb-1">Proposed Zone</p><p class="font-bold text-blue-700">{{ application.operator_info.zone }}</p></div>
                                        <div><p class="text-xs text-gray-500 mb-1">Contact No.</p><p class="font-medium text-gray-900">{{ application.operator_info.contact }}</p></div>
                                        <div><p class="text-xs text-gray-500 mb-1">Email Address</p><p class="font-medium text-gray-900">{{ application.operator_info.email }}</p></div>
                                        <div><p class="text-xs text-gray-500 mb-1">TIN Number</p><p class="font-medium text-gray-900">{{ application.operator_info.tin_number }}</p></div>
                                        <div class="col-span-2"><p class="text-xs text-gray-500 mb-1">Complete Address</p><p class="font-medium text-gray-900">{{ application.operator_info.address }}</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'unit_details'" class="space-y-6">
                            <div v-if="application.proposed_units.length === 0" class="p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                <p class="text-sm text-gray-500">No proposed units found for this application.</p>
                            </div>
                            
                            <div v-for="(unit, index) in application.proposed_units" :key="index" class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm relative mb-6">
                                <div class="text-[12px] font-bold text-blue-600 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2 flex justify-between items-center">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>
                                        Proposed Unit #{{ index + 1 }}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                                    <div><p class="text-xs text-gray-500 mb-1">Make / Model</p><p class="font-bold text-blue-900">{{ unit.make }}</p></div>
                                    <div><p class="text-xs text-gray-500 mb-1">Model Year</p><p class="font-medium text-gray-900">{{ unit.year }}</p></div>
                                    <div><p class="text-xs text-gray-500 mb-1">Plate Number</p><p class="font-medium text-gray-900">{{ unit.plate_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 mb-1">Motor No.</p><p class="font-medium text-gray-900">{{ unit.motor_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 mb-1">Chassis No.</p><p class="font-medium text-gray-900">{{ unit.chassis_no }}</p></div>
                                    <!-- <div><p class="text-xs text-gray-500 mb-1">CR No.</p><p class="font-medium text-gray-900">{{ unit.cr_no }}</p></div> -->
                                </div>

                                <div class="flex flex-wrap gap-3 mb-6">
                                    <SecondaryButton v-if="unit.cr_photo" @click="openDocumentModal(unit.cr_photo)" class="!text-xs py-1.5 flex items-center gap-1 border-blue-200 text-blue-700 hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        View CR Document
                                    </SecondaryButton>
                                    <SecondaryButton v-if="unit.or_photo" @click="openDocumentModal(unit.or_photo)" class="!text-xs py-1.5 flex items-center gap-1 border-blue-200 text-blue-700 hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        View OR Document
                                    </SecondaryButton>
                                    <SecondaryButton v-if="unit.franchise_certificate_photo" @click="openDocumentModal(unit.franchise_certificate_photo)" class="!text-xs py-1.5 flex items-center gap-1 border-blue-200 text-blue-700 hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Franchise Certificate
                                    </SecondaryButton>
                                </div>

                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <p class="text-sm font-bold text-gray-700 mb-4">Physical Unit Photos</p>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="view in unitViews" :key="view.key" class="border border-gray-200 rounded-lg p-2 bg-gray-50">
                                            <p class="text-xs font-semibold text-gray-500 mb-2 text-center">{{ view.label }}</p>
                                            <div class="aspect-video bg-white rounded flex items-center justify-center overflow-hidden shadow-sm">
                                                <img v-if="unit[`${view.key}_photo`]" :src="unit[`${view.key}_photo`]" class="object-cover w-full h-full cursor-pointer hover:opacity-90 transition-opacity" @click="openDocumentModal(unit[`${view.key}_photo`])" />
                                                <span v-else class="text-[10px] text-gray-400">No Image Attached</span>
                                            </div>
                                        </div>
                                    </div>
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

                <div v-if="can('evaluate_requirements')" class="flex-1 overflow-y-auto space-y-3 custom-scrollbar pr-2 mb-6">
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
                    <PrimaryButton v-if="can('approve_applications')"  @click="showApproveModal = true" class="w-full justify-center py-3 bg-green-600 hover:bg-green-700 shadow text-sm">
                        Approve Evaluation
                    </PrimaryButton>
                    <SecondaryButton v-if="can('reject_applications')" @click="showRejectModal = true" class="w-full justify-center py-3 !text-red-600 border-red-200 hover:bg-red-50 text-sm">
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
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to approve this New Franchise application?</p>
                <div class="flex justify-center gap-3">
                    <SecondaryButton @click="showApproveModal = false" class="w-1/2 justify-center" :disabled="approveProcessing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitApprove" class="w-1/2 justify-center bg-green-600 hover:bg-green-700" :disabled="approveProcessing">
                        {{ approveProcessing ? 'Approving...' : 'Yes, Approve' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRejectModal" @close="showRejectModal = false" maxWidth="sm">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 text-red-600">Reject Application</h3>
                <div class="mb-5">
                    <InputLabel value="Reason for Rejection" class="text-xs mb-1" />
                    <textarea v-model="rejectForm.remarks" class="w-full border-gray-300 focus:border-red-500 rounded-md shadow-sm text-sm" rows="3"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="showRejectModal = false" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitReject" class="bg-red-600 hover:bg-red-700" :disabled="rejectForm.processing || !rejectForm.remarks">
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