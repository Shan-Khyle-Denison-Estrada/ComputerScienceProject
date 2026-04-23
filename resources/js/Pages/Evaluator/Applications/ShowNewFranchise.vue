<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object,
    inspectionItems: { type: Array, default: () => [] },
    unitInspections: { type: Array, default: () => [] },
    currentUnitId: { type: [Number, String], default: null }
});

const activeTab = ref('franchise_overview'); 

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
    const user = app.user || {};
    const franchise = app.franchise || {};
    
    // Extract the latest proposed unit
    const proposedUnits = app.proposed_units || [];
    const proposedUnit = proposedUnits.length > 0 ? proposedUnits[proposedUnits.length - 1] : {};

    const mappedAssessment = app.assessment ? {
        id: app.assessment.id,
        status: app.assessment.assessment_status || 'Pending',
        total_due: app.assessment.total_amount_due || 0,
        assessment_date: app.assessment.assessment_date ? new Date(app.assessment.assessment_date).toLocaleDateString() : 'N/A',
        assessment_due: app.assessment.assessment_due ? new Date(app.assessment.assessment_due).toLocaleDateString() : 'N/A',
        particulars: (app.assessment.particulars || []).map(p => ({
            name: p.name,
            amount: p.pivot ? p.pivot.subtotal : p.amount
        })),
        payments: (app.assessment.payments || []).map(pay => ({
            or_number: pay.id, 
            amount_paid: pay.amount_paid,
            date: new Date(pay.created_at).toLocaleDateString(),
            payee: `${pay.payee_first_name} ${pay.payee_last_name}`.trim()
        }))
    } : null;

    return {
        id: app.id,
        type: app.application_type || 'New Franchise',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        franchise_details: {
            id: franchise.id || 'N/A',
            // Retrieve zone directly from proposedUnit relation, fallback to app.zone
            zone: proposedUnit.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A',
        },
        
        current_owner: {
            first_name: app.first_name || user.first_name || 'Not specified',
            last_name: app.last_name || user.last_name || 'Not specified',
            contact: app.contact_number || user.contact_number || 'N/A',
            email: app.email || user.email || 'N/A',
            tin_number: app.tin_number || 'N/A',
            address: [app.street_address, app.barangay, app.city, app.province].filter(Boolean).join(', ') || 'N/A',
        },
        
        current_unit: {
            // Retrieve make directly from proposedUnit relation
            make: proposedUnit.make?.name || 'Not specified',
            motor_no: proposedUnit.motor_number || 'Not specified',
            chassis_no: proposedUnit.chassis_number || 'Not specified',
            plate_no: proposedUnit.plate_number || 'N/A',
            cr_no: proposedUnit.cr_number || 'Not specified',
            year: proposedUnit.model_year || 'Not specified',
            front_photo: proposedUnit.unit_front_photo ? `/storage/${proposedUnit.unit_front_photo}` : null,
            back_photo: proposedUnit.unit_back_photo ? `/storage/${proposedUnit.unit_back_photo}` : null,
            left_photo: proposedUnit.unit_left_photo ? `/storage/${proposedUnit.unit_left_photo}` : null,
            right_photo: proposedUnit.unit_right_photo ? `/storage/${proposedUnit.unit_right_photo}` : null
        },

        evaluation_requirements: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement ? evalDoc.requirement.name : 'Document',
            status: evalDoc.is_compliant === 1 ? 'Approved' : (evalDoc.is_compliant === 0 ? 'Rejected' : 'Pending'),
            remarks: evalDoc.remarks || 'Pending Review',
            file_url: evalDoc.file_path ? `/storage/${evalDoc.file_path}` : null,
        })),

        assessment: mappedAssessment
    };
});

const inspectionsList = computed(() => {
    return props.inspectionItems.map(item => {
        const found = props.unitInspections.find(i => i.inspection_item_id === item.id);
        return {
            id: item.id,
            name: item.name,
            status: found ? found.rating : 'Pending',
            remarks: found ? found.remarks : 'Awaiting Inspection'
        };
    });
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
        preserveScroll: true,
        onSuccess: () => closeRequirementModal()
    });
};

const submitApproval = () => {
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
                    <button @click="activeTab = 'franchise_overview'" :class="activeTab === 'franchise_overview' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Franchise Overview</button>
                    <button @click="activeTab = 'unit_details'" :class="activeTab === 'unit_details' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Unit Details</button>
                    <button @click="activeTab = 'inspections'" :class="activeTab === 'inspections' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Inspections (Read-Only)</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'franchise_overview'" class="space-y-8">
                            <section>
                                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Applicant Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                                    <div><p class="text-sm text-gray-500">Applicant Name</p><p class="font-medium text-gray-900">{{ application.current_owner.first_name }} {{ application.current_owner.last_name }}</p></div>
                                    <div><p class="text-sm text-gray-500">Contact Number</p><p class="font-medium text-gray-900">{{ application.current_owner.contact }}</p></div>
                                    <div><p class="text-sm text-gray-500">Email Address</p><p class="font-medium text-gray-900">{{ application.current_owner.email }}</p></div>
                                    <div><p class="text-sm text-gray-500">TIN</p><p class="font-medium text-gray-900">{{ application.current_owner.tin_number }}</p></div>
                                    <div class="col-span-2"><p class="text-sm text-gray-500">Address</p><p class="font-medium text-gray-900">{{ application.current_owner.address }}</p></div>
                                </div>
                            </section>

                            <section>
                                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Franchise Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                                    <div><p class="text-sm text-gray-500">Proposed Zone</p><p class="font-medium text-gray-900">{{ application.franchise_details.zone }}</p></div>
                                    <!-- <div><p class="text-sm text-gray-500">Date Issued</p><p class="font-medium text-gray-900">{{ application.franchise_details.date_issued }}</p></div> -->
                                    <!-- <div><p class="text-sm text-gray-500">MTFRB Case No.</p><p class="font-medium text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</p></div> -->
                                </div>
                            </section>
                        </div>

                        <div v-else-if="activeTab === 'unit_details'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2 border-b pb-2">Tricycle Information</h3>
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6 mb-6">
                                <div><p class="text-sm text-gray-500">Make/Model</p><p class="font-medium text-gray-900">{{ application.current_unit.make }} ({{ application.current_unit.year }})</p></div>
                                <div><p class="text-sm text-gray-500">Plate Number</p><p class="font-medium text-gray-900">{{ application.current_unit.plate_no }}</p></div>
                                <div><p class="text-sm text-gray-500">Motor Number</p><p class="font-medium text-gray-900">{{ application.current_unit.motor_no }}</p></div>
                                <div><p class="text-sm text-gray-500">Chassis Number</p><p class="font-medium text-gray-900">{{ application.current_unit.chassis_no }}</p></div>
                                <!-- <div><p class="text-sm text-gray-500">CR Number</p><p class="font-medium text-gray-900">{{ application.current_unit.cr_no }}</p></div> -->
                            </div>

                            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Unit Photos</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="view in unitViews" :key="view.key" class="border rounded-lg p-2 flex flex-col items-center">
                                    <p class="text-xs font-semibold text-gray-500 mb-2 uppercase">{{ view.label }}</p>
                                    <div class="w-full h-40 bg-gray-100 rounded flex items-center justify-center overflow-hidden cursor-pointer hover:opacity-90 transition"
                                         @click="application.current_unit[`${view.key}_photo`] ? openDocumentModal(application.current_unit[`${view.key}_photo`]) : null">
                                        <img v-if="application.current_unit[`${view.key}_photo`]" :src="application.current_unit[`${view.key}_photo`]" :alt="view.label" class="object-cover w-full h-full" />
                                        <span v-else class="text-gray-400 text-sm">No photo</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'inspections'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Inspection Results</h3>
                            <div class="overflow-hidden border border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in inspectionsList" :key="item.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span :class="{
                                                    'text-green-600 bg-green-100 px-2 py-1 rounded': item.status === 'Pass',
                                                    'text-red-600 bg-red-100 px-2 py-1 rounded': item.status === 'Fail',
                                                    'text-gray-500': item.status === 'Pending'
                                                }">{{ item.status }}</span>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ item.remarks || '-' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'assessment'" class="space-y-6">
                            <div v-if="application.assessment">
                                <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Assessment Details</h3>
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div><p class="text-sm text-gray-500">Status</p><p class="font-medium text-gray-900">{{ application.assessment.status }}</p></div>
                                    <div><p class="text-sm text-gray-500">Total Due</p><p class="font-medium text-gray-900 font-mono">₱{{ parseFloat(application.assessment.total_due).toFixed(2) }}</p></div>
                                </div>
                                <h4 class="font-medium text-sm text-gray-700 mb-2">Particulars</h4>
                                <ul class="mb-6 border rounded-lg divide-y">
                                    <li v-for="part in application.assessment.particulars" :key="part.name" class="p-3 flex justify-between text-sm">
                                        <span class="text-gray-600">{{ part.name }}</span>
                                        <span class="font-medium text-gray-900 font-mono">₱{{ parseFloat(part.amount).toFixed(2) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="text-center py-10 bg-gray-50 rounded-lg border border-dashed">
                                <p class="text-gray-500">No assessment generated yet.</p>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="w-1/3 bg-gray-50 border-r border-gray-200 flex flex-col h-full shadow-[inset_1px_0_5px_rgba(0,0,0,0.05)] flex-shrink-0">
                <div class="p-4 border-b border-gray-200 bg-white">
                    <h3 class="text-lg font-bold text-gray-800">Evaluation</h3>
                    <p class="text-xs text-gray-500">Review submitted documents below.</p>
                </div>
                
                <div v-if="can('evaluate_requirements')" class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                    <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" 
                         class="bg-white border rounded-lg p-3 shadow-sm hover:border-blue-300 transition-colors cursor-pointer relative"
                         @click="openRequirementModal(index)">
                         
                        <div class="flex justify-between items-start mb-1">
                            <h4 class="font-semibold text-sm text-gray-800 pr-4">{{ req.name }}</h4>
                            <span v-if="req.status === 'Approved'" class="text-green-500 flex-shrink-0" title="Approved">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            </span>
                            <span v-else-if="req.status === 'Rejected'" class="text-red-500 flex-shrink-0" title="Rejected">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                            </span>
                            <span v-else class="text-yellow-500 flex-shrink-0" title="Pending">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                            </span>
                        </div>
                        
                        <p class="text-xs text-gray-500 truncate mb-2">Remarks: {{ req.remarks }}</p>
                        
                        <div class="flex items-center text-xs text-blue-600 font-medium mt-2 pt-2 border-t border-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Review Document
                        </div>
                    </div>
                    
                    <div v-if="application.evaluation_requirements.length === 0" class="text-center py-8 text-gray-500 text-sm">
                        No requirements found for this application.
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                    <div v-if="application.status === 'Pending'" class="space-y-2">
                        <PrimaryButton v-if="can('approve_applications')"  @click="showApproveModal = true" class="w-full justify-center bg-green-600 hover:bg-green-700">Approve Application</PrimaryButton>
                        <SecondaryButton v-if="can('reject_applications')" @click="showRejectModal = true" class="w-full justify-center border-red-500 text-red-700 hover:bg-red-50">Reject Application</SecondaryButton>
                    </div>
                    <div v-else class="text-center p-3 bg-gray-100 rounded-lg text-sm text-gray-600 font-medium">
                        Application is already {{ application.status }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showRequirementModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="closeRequirementModal"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl w-full h-[90vh] flex flex-col overflow-hidden z-10">
                <div class="p-6 pb-4 flex justify-between items-center border-b border-gray-200 flex-shrink-0" v-if="selectedRequirementIndex !== null">
                    <h3 class="text-xl font-bold text-gray-900">{{ application.evaluation_requirements[selectedRequirementIndex].name }}</h3>
                    <button @click="closeRequirementModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="flex flex-1 overflow-hidden" v-if="selectedRequirementIndex !== null">
                    
                    <div class="flex-1 bg-gray-100 flex items-center justify-center relative p-6 overflow-hidden">
                        <div v-if="!application.evaluation_requirements[selectedRequirementIndex].file_url" class="text-gray-500 flex flex-col items-center">
                            <svg class="h-12 w-12 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            No file uploaded
                        </div>
                        <template v-else>
                            <img v-if="isImageUrl(application.evaluation_requirements[selectedRequirementIndex].file_url)" 
                                 :src="application.evaluation_requirements[selectedRequirementIndex].file_url" 
                                 class="max-w-full max-h-full object-contain shadow-sm border border-gray-200 bg-white" />
                            <iframe v-else 
                                    :src="application.evaluation_requirements[selectedRequirementIndex].file_url" 
                                    class="w-full h-full border border-gray-300 rounded bg-white shadow-sm"></iframe>
                        </template>
                    </div>

                    <div class="w-96 flex-shrink-0 bg-white border-l border-gray-200 flex flex-col p-6 overflow-y-auto">
                        <div class="mb-6 flex-1 flex flex-col">
                            <InputLabel value="Evaluator Remarks" class="text-sm font-semibold mb-1 text-gray-800" />
                            <p class="text-xs text-gray-500 mb-3">Optional for Approval, Required for Rejection</p>
                            <textarea v-model="requirementForm.remarks" 
                                      class="w-full flex-1 min-h-[150px] border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm resize-none p-3" 
                                      placeholder="Add your notes or findings about this document here..."></textarea>
                        </div>
                        
                        <div class="flex flex-col gap-3 mt-auto border-t border-gray-100 pt-6">
                            <PrimaryButton @click="saveRequirementStatus('Approved')" class="w-full justify-center bg-green-600 hover:bg-green-700 focus:ring-green-500 py-3 text-base">
                                Approve Document
                            </PrimaryButton>
                            <SecondaryButton @click="saveRequirementStatus('Rejected')" class="w-full justify-center border-red-500 text-red-600 hover:bg-red-50 py-3 text-base" :disabled="!requirementForm.remarks.trim()">
                                Reject Document
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDocumentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" @click="closeDocumentModal"></div>
            
            <div class="relative w-full max-w-3xl flex justify-center items-center z-10">
                <button @click="closeDocumentModal" class="absolute -top-12 right-0 text-white bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                <img v-if="currentDocumentUrl" :src="currentDocumentUrl" class="w-full h-auto max-h-[85vh] object-contain rounded" />
            </div>
        </div>

        <div v-if="showApproveModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showApproveModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm overflow-hidden z-10">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Approve Application?</h2>
                    <p class="text-sm text-gray-500 mb-6">Are you sure you want to approve this new franchise application? Ensure all requirements and assessments are valid.</p>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showApproveModal = false" :disabled="approveProcessing">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitApproval" class="bg-green-600 hover:bg-green-700" :disabled="approveProcessing">Confirm Approval</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showRejectModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showRejectModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden z-10">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-red-600 mb-2 flex items-center">Reject Application</h2>
                    <p class="text-sm text-gray-500 mb-4">This will permanently mark the application as rejected.</p>
                    <div class="mb-5">
                        <InputLabel value="Reason for Rejection" class="text-xs mb-1" />
                        <textarea v-model="rejectForm.remarks" class="w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" rows="3" placeholder="Why is this application being rejected?"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <SecondaryButton @click="showRejectModal = false" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitReject" class="bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="rejectForm.processing || !rejectForm.remarks.trim()">
                            Confirm Rejection
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

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