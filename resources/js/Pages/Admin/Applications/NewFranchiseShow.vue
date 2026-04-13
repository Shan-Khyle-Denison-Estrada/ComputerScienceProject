<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import CreateNewFranchiseModal from '@/Components/Modals/CreateNewFranchiseModal.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object,
    inspectionItems: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    isEncoder: { type: Boolean, default: false },
});

// --- STATE ---
const activeTab = ref('application_overview');
const showMediaModal = ref(false); 
const showRejectModal = ref(false);
const showApproveModal = ref(false);
const showCreateFranchiseModal = ref(false);

const mediaUrl = ref('');
const mediaTitle = ref('');

const rejectForm = reactive({ remarks: '', processing: false });
const approveProcessing = ref(false);

// Evaluation Modal State
const showRequirementModal = ref(false);
const selectedRequirementIndex = ref(null);
const requirementForm = reactive({ remarks: '' });
const returnToRequirementOnMediaClose = ref(false); // Tracks if we need to reopen the evaluation modal

// Inspection Modal State
const showInspectionModal = ref(false);
const selectedInspectionUnitId = ref(null);
const selectedInspectionIndex = ref(null);
const currentInspectionOptions = ref([]); // Add this to hold dynamic options
const inspectionForm = reactive({ status: '', remarks: '' });

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    
    const mappedAssessment = app.assessment ? {
        id: app.assessment.id,
        status: app.assessment.assessment_status || 'Pending',
        total_due: app.assessment.total_amount_due || 0,
        assessment_date: app.assessment.assessment_date ? new Date(app.assessment.assessment_date).toLocaleDateString() : 'N/A',
        assessment_due: app.assessment.assessment_due ? new Date(app.assessment.assessment_due).toLocaleDateString() : 'N/A',
        remarks: app.assessment.remarks || 'No remarks provided.',
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
        type: app.application_type || app.type || 'New Franchise',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        applicant: {
            first_name: app.first_name || '',
            middle_name: app.middle_name || '',
            last_name: app.last_name || '',
            email: app.email || 'N/A',
            contact: app.contact_number || 'N/A',
            tin_number: app.tin_number || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
            province: app.province || ''
        },

        proposed_units: (app.proposed_units || []).map(unit => {
            const unitInspections = unit.inspections || unit.unit_inspections || [];
            return {
                id: unit.id,
                zone: unit.zone?.name || unit.zone?.description || 'N/A',
                make: unit.make?.name || 'N/A',
                year: unit.model_year || 'Not specified',
                plate_no: unit.plate_number || 'N/A',
                motor_no: unit.motor_number || 'Not specified',
                chassis_no: unit.chassis_number || 'Not specified',
                cr_no: unit.cr_number || 'Not specified',
                front_photo: unit.unit_front_photo || unit.front_photo,
                back_photo: unit.unit_back_photo || unit.back_photo,
                left_photo: unit.unit_left_photo || unit.left_photo,
                right_photo: unit.unit_right_photo || unit.right_photo,
                cr_photo: unit.cr_photo,
                or_photo: unit.or_photo,
                inspectionsList: props.inspectionItems.map(item => {
                    const found = unitInspections.find(i => i.inspection_item_id === item.id || i.requirement_id === item.id);
                    return {
                        id: item.id,
                        name: item.name,
                        rating_options: item.rating_options || ['Pass', 'Fail', 'Needs Attention'],
                        status: found ? (found.rating || found.status) : 'Pending',
                        remarks: found ? found.remarks : ''
                    };
                })
            };
        }),

        evaluation_requirements: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement ? evalDoc.requirement.name : 'Document', 
            status: evalDoc.status || (evalDoc.is_compliant === 1 ? 'Approved' : (evalDoc.is_compliant === 0 ? 'Rejected' : 'Pending')),
            remarks: evalDoc.remarks || 'Pending Review',
            file_url: evalDoc.file_path,
        })),

        assessment: mappedAssessment
    };
});

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

// --- HELPERS ---
const isPdf = (path) => path && path.toLowerCase().endsWith('.pdf');

const viewMedia = (path, title) => {
    if (!path) return;
    mediaUrl.value = (path.startsWith('http') || path.startsWith('/storage')) ? path : '/storage/' + path;
    mediaTitle.value = title;
    showMediaModal.value = true;
};

const viewMediaFromRequirement = (path, title) => {
    if (!path) return;
    showRequirementModal.value = false; // Hide the evaluation modal
    returnToRequirementOnMediaClose.value = true; // Set flag to reopen it later
    viewMedia(path, title); // Open media modal
};

const closeMediaModal = () => {
    showMediaModal.value = false;
    mediaUrl.value = '';
    mediaTitle.value = '';
    
    // If we came from the Requirement Modal, reopen it after a short delay
    if (returnToRequirementOnMediaClose.value) {
        setTimeout(() => {
            showRequirementModal.value = true;
        }, 300); // 300ms allows the fade transition to complete smoothly
        returnToRequirementOnMediaClose.value = false; // Reset the flag
    }
};

const formatCurrency = (value) => {
    if(value === null || value === undefined) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};

// --- ACTIONS ---

// Approvals & Rejections
const openApproveModal = () => showApproveModal.value = true;
const closeApproveModal = () => showApproveModal.value = false;
const submitApproval = () => {
    approveProcessing.value = true;
    router.post(route('admin.applications.approve', application.value.id), {}, { 
        preserveScroll: true,
        onSuccess: () => closeApproveModal(),
        onFinish: () => approveProcessing.value = false
    });
};

const openRejectModal = () => { rejectForm.remarks = ''; rejectForm.processing = false; showRejectModal.value = true; };
const closeRejectModal = () => showRejectModal.value = false;
const submitRejection = () => {
    if (!rejectForm.remarks.trim()) return;
    rejectForm.processing = true;
    router.post(route('admin.applications.reject', application.value.id), { remarks: rejectForm.remarks }, {
        preserveScroll: true,
        onSuccess: () => closeRejectModal(),
        onFinish: () => rejectForm.processing = false
    });
};

// Requirements Evaluation
const openRequirementModal = (index) => {
    selectedRequirementIndex.value = index;
    requirementForm.remarks = application.value.evaluation_requirements[index].remarks;
    if (requirementForm.remarks === 'Pending Review') requirementForm.remarks = ''; 
    showRequirementModal.value = true;
};

const closeRequirementModal = () => {
    showRequirementModal.value = false;
    setTimeout(() => { selectedRequirementIndex.value = null; requirementForm.remarks = ''; }, 200);
};

const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value === null) return;
    const evaluation = application.value.evaluation_requirements[selectedRequirementIndex.value];
    router.post(route('admin.applications.evaluate', application.value.id), {
        evaluation_id: evaluation.id,
        status: status,
        remarks: requirementForm.remarks || 'Pending Review'
    }, {
        preserveScroll: true,
        onSuccess: () => closeRequirementModal()
    });
};

// Unit Inspections
const openInspectionModal = (unitId, index) => {
    selectedInspectionUnitId.value = unitId;
    selectedInspectionIndex.value = index;
    const unit = application.value.proposed_units.find(u => u.id === unitId);
    const item = unit.inspectionsList[index];
    
    // Set dynamic options, fallback if empty
    currentInspectionOptions.value = item.rating_options && item.rating_options.length ? item.rating_options : ['Pass', 'Fail', 'Needs Attention'];
    
    inspectionForm.status = item.status === 'Pending' ? '' : item.status;
    inspectionForm.remarks = item.remarks;
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    setTimeout(() => { selectedInspectionUnitId.value = null; selectedInspectionIndex.value = null; inspectionForm.status = ''; inspectionForm.remarks = ''; }, 200);
};

const saveInspectionStatus = () => {
    if (selectedInspectionIndex.value === null || !selectedInspectionUnitId.value) return;
    const unit = application.value.proposed_units.find(u => u.id === selectedInspectionUnitId.value);
    const item = unit.inspectionsList[selectedInspectionIndex.value];
    
    // Sends directly to the endpoint. Ensure the route exists in your web.php
    router.post(`/applications/new-franchise/${application.value.id}/inspect`, {
        unit_id: selectedInspectionUnitId.value,
        inspection_item_id: item.id,
        status: inspectionForm.status,
        remarks: inspectionForm.remarks
    }, {
        preserveScroll: true,
        onSuccess: () => closeInspectionModal()
    });
};
</script>

<template>
    <Head :title="`Application Details - ${application.reference_no}`" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-100px)] flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- <Link :href="route('admin.applications.index')" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors hidden md:block">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </Link> -->
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">New Franchise Application</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Completed'">
                        <span class="px-4 py-2 bg-green-100 text-green-800 text-xs font-bold uppercase rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Application Completed
                        </span>
                    </template>
                    <template v-else-if="application.status === 'Approved'">
                        <PrimaryButton @click="showCreateFranchiseModal = true" class="bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 text-white text-xs font-bold uppercase rounded-lg transition-colors">
                            Create Franchise
                        </PrimaryButton>
                    </template>
                    <template v-else-if="!['Rejected', 'Returned'].includes(application.status)">
                        <button @click="openRejectModal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase rounded-lg transition-colors">Reject</button>
                        <button @click="openApproveModal" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase rounded-lg transition-colors">Approve</button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex flex-col md:flex-row gap-4 h-full min-h-0">
                
                <div class="w-full md:w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-200 p-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide"
                                    :class="[
                                        application.status === 'Approved' ? 'bg-green-100 text-green-800' :
                                        application.status === 'Rejected' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'
                                    ]">
                                    {{ application.status }}
                                </span>
                                <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.type }}</span>
                            </div>
                            <div v-if="['Rejected', 'Returned'].includes(application.status) && application.remarks" 
                                class="p-2 mt-2"
                                :class="application.status === 'Rejected' ? 'bg-red-50 border-l-4 border-red-500 text-red-800' : 'bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800'">
                                <p class="text-xs font-medium">Reason: {{ application.remarks }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-5 custom-scrollbar">
                        <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-600 mb-3 overflow-hidden">
                                <span>{{ application.applicant.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">{{ application.applicant.first_name }} {{ application.applicant.last_name }}</h2>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Applicant Profile</h4>
                                <div class="text-sm space-y-1.5">
                                    <p class="flex justify-between"><span class="text-gray-500">Contact:</span> <span class="font-medium text-gray-900">{{ application.applicant.contact }}</span></p>
                                    <p class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-medium text-gray-900 truncate max-w-[120px]" :title="application.applicant.email">{{ application.applicant.email }}</span></p>
                                    <p class="flex justify-between"><span class="text-gray-500">TIN Number:</span> <span class="font-medium text-gray-900">{{ application.applicant.tin_number }}</span></p>
                                    <p class="text-gray-500 mt-2">Address:</p>
                                    <p class="font-medium text-gray-900 leading-snug">{{ application.applicant.address }} <span v-if="application.applicant.province">, {{ application.applicant.province }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-w-0">  
                    
                    <div class="flex items-center border-b border-gray-200 px-2 pt-2 overflow-x-auto custom-scrollbar">
                        <button @click="activeTab = 'application_overview'" :class="activeTab === 'application_overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Application Overview</button>
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                        <button @click="activeTab = 'inspection'" :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Inspection</button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6 custom-scrollbar">
                        
                        <div v-if="activeTab === 'application_overview'" class="space-y-8">
                            <div v-if="application.proposed_units.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No proposed units found.
                            </div>

                            <div v-for="(unit, unitIdx) in application.proposed_units" :key="unit.id" class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-4">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    Proposed Unit <span v-if="application.proposed_units.length > 1" class="text-sm font-normal text-gray-500 ml-2">(Unit #{{ unitIdx + 1 }})</span>
                                </h3>

                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Make & Model Year</span><span class="font-bold text-gray-900 text-sm">{{ unit.make }} ({{ unit.year }})</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Plate Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ unit.plate_no }}</span></div>
                                        <!-- <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">CR Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ unit.cr_no }}</span></div> -->
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Motor Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ unit.motor_no }}</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Chassis Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ unit.chassis_no }}</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Target Zone</span><span class="font-bold text-blue-600 text-sm">{{ unit.zone }}</span></div>
                                    </div>

                                    <div class="mt-6 pt-5 border-t border-gray-100">
                                        <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-3">Unit Visuals & Documentation</h4>
                                        <div class="flex flex-wrap gap-3">
                                            <button v-if="unit.front_photo" @click="viewMedia(unit.front_photo, 'Unit Front Photo')" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">Front Photo</button>
                                            <button v-if="unit.back_photo" @click="viewMedia(unit.back_photo, 'Unit Back Photo')" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">Back Photo</button>
                                            <button v-if="unit.left_photo" @click="viewMedia(unit.left_photo, 'Unit Left Photo')" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">Left Photo</button>
                                            <button v-if="unit.right_photo" @click="viewMedia(unit.right_photo, 'Unit Right Photo')" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">Right Photo</button>
                                            <button v-if="unit.cr_photo" @click="viewMedia(unit.cr_photo, 'CR Copy')" class="px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-800 hover:bg-indigo-100 transition-colors">CR Copy</button>
                                            <button v-if="unit.or_photo" @click="viewMedia(unit.or_photo, 'OR Copy')" class="px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-800 hover:bg-indigo-100 transition-colors">OR Copy</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'evaluation'" class="space-y-4">
                            <div v-if="application.evaluation_requirements.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No evaluations required or found.
                            </div>
                            <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" 
                                class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm flex items-center justify-between group transition-colors hover:border-blue-300 cursor-pointer"
                                @click="openRequirementModal(index)">
                                
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="{
                                        'bg-green-100': req.status === 'Approved',
                                        'bg-red-100': req.status === 'Rejected',
                                        'bg-gray-100': req.status === 'Pending'
                                    }">
                                        <svg v-if="req.status === 'Approved'" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="req.status === 'Rejected'" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm group-hover:text-blue-600 transition-colors">{{ req.name }}</h4>
                                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide" 
                                                :class="{
                                                    'bg-green-100 text-green-800': req.status === 'Approved',
                                                    'bg-red-100 text-red-800': req.status === 'Rejected',
                                                    'bg-gray-100 text-gray-800': req.status === 'Pending'
                                                }">
                                                {{ req.status }}
                                            </span>
                                            <span v-if="req.remarks && req.remarks !== 'Pending Review'" class="text-[11px] text-gray-500 italic max-w-xs truncate">
                                                - {{ req.remarks }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity px-2 text-xs font-bold uppercase tracking-wide z-10" @click.stop="viewMedia(req.file_url, req.name)">
                                    View File →
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'inspection'" class="space-y-8">
                            <div v-if="application.proposed_units.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No proposed units found to inspect.
                            </div>

                            <div v-for="(unit, unitIdx) in application.proposed_units" :key="'insp-' + unit.id" class="mb-8">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-4">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                    Unit Inspection Checklist <span v-if="application.proposed_units.length > 1" class="text-sm font-normal text-gray-500 ml-2">(Unit #{{ unitIdx + 1 }})</span>
                                </h3>
                                
                                <div v-if="unit.inspectionsList.length === 0" class="text-center py-6 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                    No inspection checklist items configured.
                                </div>
                                <div v-else class="space-y-3">
                                    <div v-for="(item, index) in unit.inspectionsList" :key="item.id" 
                                        class="bg-white p-4 border border-gray-200 rounded-lg shadow-sm flex items-center justify-between cursor-pointer hover:border-blue-300 transition-colors"
                                        @click="openInspectionModal(unit.id, index)">
                                        
                                        <div class="flex items-center gap-4">
                                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="{
                                                'bg-green-100': item.status === 'Pass' || item.status === 'Approved',
                                                'bg-red-100': item.status === 'Fail' || item.status === 'Rejected',
                                                'bg-gray-100': item.status === 'Pending' || item.status === 'Needs Attention'
                                            }">
                                                <svg v-if="item.status === 'Pass' || item.status === 'Approved'" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                <svg v-else-if="item.status === 'Fail' || item.status === 'Rejected'" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                <svg v-else class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-800 text-sm">{{ item.name }}</h4>
                                                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                                    <span class="px-2.5 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide" 
                                                        :class="{
                                                            'bg-green-100 text-green-800': item.status === 'Pass' || item.status === 'Approved',
                                                            'bg-red-100 text-red-800': item.status === 'Fail' || item.status === 'Rejected',
                                                            'bg-gray-100 text-gray-800': item.status === 'Pending' || item.status === 'Needs Attention'
                                                        }">
                                                        {{ item.status }}
                                                    </span>
                                                    <span v-if="item.remarks" class="text-[11px] text-gray-500 italic">
                                                        - {{ item.remarks }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'receipt'">
                            <div v-if="!application.assessment" class="bg-white p-10 border border-gray-200 rounded-lg shadow-sm text-center text-gray-500 italic">
                                No assessment generated for this application yet.
                            </div>
                            <div v-else class="space-y-4">
                                <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                                    <div class="flex justify-between items-center mb-4 border-b pb-4">
                                        <h3 class="font-bold text-gray-800 text-lg">Assessment Summary</h3>
                                        <span class="px-3 py-1 text-[11px] font-bold rounded uppercase tracking-wide" :class="application.assessment.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">
                                            {{ application.assessment.status }}
                                        </span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                                        <div>
                                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Date Issued</p>
                                            <p class="text-sm font-medium text-gray-900">{{ application.assessment.assessment_date }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Due Date</p>
                                            <p class="text-sm font-medium text-gray-900">{{ application.assessment.assessment_due }}</p>
                                        </div>
                                    </div>

                                    <table class="min-w-full divide-y divide-gray-200 mb-6">
                                        <thead>
                                            <tr>
                                                <th class="py-2 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Particulars</th>
                                                <th class="py-2 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="(item, idx) in application.assessment.particulars" :key="idx">
                                                <td class="py-3 text-sm text-gray-800">{{ item.name }}</td>
                                                <td class="py-3 text-sm font-medium text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th class="py-4 text-left text-sm font-bold text-gray-900">Total Amount Due</th>
                                                <th class="py-4 text-right text-lg font-bold text-blue-600">{{ formatCurrency(application.assessment.total_due) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                                    <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-4">Payment History</h3>
                                    <div v-if="application.assessment.payments.length === 0" class="text-center py-6 text-gray-500 text-sm italic">
                                        No payments have been recorded yet.
                                    </div>
                                    <div v-else class="space-y-3">
                                        <div v-for="payment in application.assessment.payments" :key="payment.or_number" class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-100">
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">OR #{{ payment.or_number }}</p>
                                                <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-0.5">Paid by {{ payment.payee }} on {{ payment.date }}</p>
                                            </div>
                                            <span class="text-base font-bold text-green-600">{{ formatCurrency(payment.amount_paid) }}</span>
                                        </div>
                                    </div>

                                    <div v-if="application.assessment.status === 'Pending'" class="mt-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded shadow-sm">
                                        <div class="flex items-start">
                                            <svg class="h-5 w-5 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            <div>
                                                <h4 class="text-sm font-bold text-yellow-800">Pending Payment</h4>
                                                <p class="text-xs text-yellow-700 mt-1">This application's assessment has not yet been paid. The applicant must settle this balance to proceed.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <Modal :show="showRequirementModal" @close="closeRequirementModal" maxWidth="md">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Evaluate Document</h3>
                    <div v-if="selectedRequirement" class="mb-5 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm font-semibold text-gray-800">{{ selectedRequirement.name }}</p>
                        <p class="text-xs text-gray-500 mt-1">Current Status: <span class="font-bold uppercase">{{ selectedRequirement.status }}</span></p>
                        <button @click="viewMediaFromRequirement(selectedRequirement.file_url, selectedRequirement.name)" class="mt-3 text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            View Document File
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Remarks / Notes (Optional for Approval)" class="text-xs mb-0" />
                            <TextInput v-model="requirementForm.remarks" class="block w-full text-sm py-1.5 mt-1" placeholder="Add remarks..." />
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <SecondaryButton @click="closeRequirementModal" class="flex-1 justify-center">Cancel</SecondaryButton>
                            <PrimaryButton @click="saveRequirementStatus('Rejected')" class="flex-1 justify-center bg-red-600 hover:bg-red-700 focus:ring-red-500">Reject</PrimaryButton>
                            <PrimaryButton @click="saveRequirementStatus('Approved')" class="flex-1 justify-center bg-green-600 hover:bg-green-700 focus:ring-green-500">Approve</PrimaryButton>
                        </div>
                    </div>
                </div>
            </Modal>

            <Modal :show="showInspectionModal" @close="closeInspectionModal" maxWidth="md">
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Unit Inspection Item</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Status" class="text-xs mb-2" />
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="option in currentInspectionOptions" 
                                    :key="option"
                                    type="button"
                                    @click="inspectionForm.status = option"
                                    :class="[
                                        'px-4 py-2 text-sm font-medium rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500',
                                        inspectionForm.status === option 
                                            ? 'bg-blue-600 text-white border-blue-600 shadow-sm' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                >
                                    {{ option }}
                                </button>
                            </div>
                        </div>
                        
                        <div>
                            <InputLabel value="Inspector Remarks (Optional)" class="text-xs mb-0" />
                            <TextInput v-model="inspectionForm.remarks" class="block w-full text-sm py-1.5 mt-1" placeholder="Details about this inspection point..." />
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-6">
                            <SecondaryButton @click="closeInspectionModal">Cancel</SecondaryButton>
                            <PrimaryButton @click="saveInspectionStatus" class="bg-blue-600 hover:bg-blue-700 focus:ring-blue-500" :disabled="!inspectionForm.status">
                                Save Inspection Result
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </Modal>

            <Transition name="fade">
                <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeApproveModal">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-5">
                                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Approve Application
                                </h2>
                                <button @click="closeApproveModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <p class="text-sm text-gray-600 mb-6">Are you sure you want to approve this new franchise application? This action will advance the application workflow.</p>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <SecondaryButton @click="closeApproveModal" :disabled="approveProcessing">Cancel</SecondaryButton>
                                <PrimaryButton @click="submitApproval" class="bg-green-600 hover:bg-green-700 focus:ring-green-500 text-white" :disabled="approveProcessing">
                                    {{ approveProcessing ? 'Approving...' : 'Confirm Approval' }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeRejectModal">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col" @click.stop>
                        <div class="p-6 flex flex-col">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    Reject Application
                                </h2>
                                <button @click="closeRejectModal" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>

                            <div class="space-y-5">
                                <p class="text-sm text-gray-600">This action will permanently deny the applicant's request for a new franchise.</p>
                                
                                <div>
                                    <InputLabel for="app_reject_remarks" value="Reason for Rejection" />
                                    <textarea id="app_reject_remarks" v-model="rejectForm.remarks" rows="4" required class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" placeholder="E.g. Applicant does not meet residency requirements..."></textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                                <SecondaryButton @click="closeRejectModal" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                                <PrimaryButton @click="submitRejection" class="bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white" :disabled="!rejectForm.remarks.trim() || rejectForm.processing">
                                    Confirm Rejection
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>

            <Transition name="fade">
                <div v-if="showMediaModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[90vh] flex flex-col overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50">
                            <h3 class="font-bold text-gray-800 truncate pr-4">{{ mediaTitle }}</h3>
                            <button @click="closeMediaModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="flex-1 bg-gray-100 p-4 overflow-hidden relative">
                            <iframe v-if="isPdf(mediaUrl)" :src="mediaUrl" class="w-full h-full border-0 rounded bg-white shadow-inner"></iframe>
                            <img v-else :src="mediaUrl" class="w-full h-full object-contain rounded shadow-inner" alt="Document Preview" />
                        </div>
                    </div>
                </div>
            </Transition>

        </div>
        <CreateNewFranchiseModal 
                :show="showCreateFranchiseModal" 
                :application="props.application" 
                :zones="zones"
                :unitMakes="unitMakes"
                @close="showCreateFranchiseModal = false" 
            />
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