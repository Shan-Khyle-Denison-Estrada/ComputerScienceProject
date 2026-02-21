<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object,
    inspectionItems: {
        type: Array,
        default: () => []
    },
    unitInspections: {
        type: Array,
        default: () => []
    },
    currentUnitId: {
        type: [Number, String],
        default: null
    }
});

// --- STATE ---
const activeTab = ref('franchise_overview'); 

// Modals State
const showRequirementModal = ref(false);
const selectedRequirementIndex = ref(null);
const requirementForm = reactive({ remarks: '' });

const showInspectionModal = ref(false);
const selectedInspectionIndex = ref(null);
const inspectionForm = reactive({ status: '', remarks: '' });

const showRejectModal = ref(false);
const rejectForm = reactive({ remarks: '', processing: false });

const showReturnModal = ref(false);
const returnForm = reactive({ remarks: '', processing: false });

const showApproveModal = ref(false);
const approveProcessing = ref(false);

const showFinalizeModal = ref(false);
const finalizeForm = useForm({
    new_date_issued: new Date().toISOString().split('T')[0],
    remarks: 'Approved and Finalized Renewal.'
});

const unitViews = [
    { key: 'front', label: 'Front View' },
    { key: 'back', label: 'Back View' },
    { key: 'left', label: 'Left Side View' },
    { key: 'right', label: 'Right Side View' }
];

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    
    const franchise = app.franchise || {};
    const currentOwnership = franchise.current_ownership || {};
    const currentOperator = currentOwnership.new_owner || {};
    const currentUser = currentOperator.user || {};
    
    const currentActiveUnit = franchise.current_active_unit || {};
    const currentUnitData = currentActiveUnit.new_unit || {};
    const currentMake = currentUnitData.make || {};

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
        type: app.application_type || 'Renewal',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        applicant: {
            first_name: app.first_name || '',
            last_name: app.last_name || '',
            email: app.email || 'N/A',
            contact: app.contact_number || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
        },
        
        franchise_details: {
            id: franchise.id,
            zone: franchise.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            status: franchise.status || 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A',
            
            // Map the newly loaded relationships
            complaints: (franchise.complaints || []).map(c => ({
                id: c.id,
                nature: c.nature_of_complaint,
                date: new Date(c.incident_date).toLocaleDateString(),
                status: c.status || 'Pending',
                remarks: c.remarks
            })),
            red_flags: (franchise.red_flags || []).map(r => ({
                id: r.id,
                nature: r.nature?.name || 'Unknown',
                date: new Date(r.created_at).toLocaleDateString(),
                status: r.status || 'Pending',
                remarks: r.remarks
            }))
        },
        
        current_owner: {
            first_name: currentUser.first_name || 'Not specified',
            middle_name: currentUser.middle_name || '',
            last_name: currentUser.last_name || 'Not specified',
            contact: currentUser.contact_number || 'N/A',
            email: currentUser.email || 'N/A',
            tin_number: currentOperator.tin_number || 'N/A',
            address: `${currentUser.street_address || ''}, ${currentUser.barangay || ''}, ${currentUser.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
        },

        current_unit: {
            make: currentMake.name || 'Not specified',
            motor_no: currentUnitData.motor_number || 'Not specified',
            chassis_no: currentUnitData.chassis_number || 'Not specified',
            plate_no: currentUnitData.plate_number || franchise.plate_number || 'N/A',
            cr_no: currentUnitData.cr_number || 'Not specified',
            year: currentUnitData.model_year || 'Not specified',
            front_photo: currentUnitData.unit_front_photo ? `/storage/${currentUnitData.unit_front_photo}` : (currentUnitData.front_photo ? `/storage/${currentUnitData.front_photo}` : null),
            back_photo: currentUnitData.unit_back_photo ? `/storage/${currentUnitData.unit_back_photo}` : (currentUnitData.back_photo ? `/storage/${currentUnitData.back_photo}` : null),
            left_photo: currentUnitData.unit_left_photo ? `/storage/${currentUnitData.unit_left_photo}` : (currentUnitData.left_photo ? `/storage/${currentUnitData.left_photo}` : null),
            right_photo: currentUnitData.unit_right_photo ? `/storage/${currentUnitData.unit_right_photo}` : (currentUnitData.right_photo ? `/storage/${currentUnitData.right_photo}` : null),
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

// NEW: Business Rule computation
const hasTooManyComplaints = computed(() => {
    return application.value.franchise_details.complaints.length > 3;
});

const inspectionsList = computed(() => {
    return props.inspectionItems.map(item => {
        const found = props.unitInspections.find(i => i.inspection_item_id === item.id);
        return {
            id: item.id,
            name: item.name,
            options: item.rating_options || ['Pass', 'Fail'],
            status: found ? found.rating : 'Pending',
            remarks: found ? found.remarks : ''
        };
    });
});

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

const selectedInspection = computed(() => {
    if (selectedInspectionIndex.value === null) return null;
    return inspectionsList.value[selectedInspectionIndex.value];
});

// --- ACTIONS ---
const openApproveModal = () => showApproveModal.value = true;
const closeApproveModal = () => showApproveModal.value = false;
const submitApproval = () => {
    if (hasTooManyComplaints.value) return; // double protection
    approveProcessing.value = true;
    router.post(route('admin.applications.renewal.approve', application.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => closeApproveModal(),
        onFinish: () => approveProcessing.value = false
    });
};

const openReturnModal = () => { returnForm.remarks = ''; returnForm.processing = false; showReturnModal.value = true; };
const closeReturnModal = () => showReturnModal.value = false;
const submitReturn = () => {
    if (!returnForm.remarks.trim()) return; 
    returnForm.processing = true;
    router.post(route('admin.applications.renewal.return', application.value.id), { remarks: returnForm.remarks }, {
        preserveScroll: true,
        onSuccess: () => closeReturnModal(),
        onFinish: () => returnForm.processing = false
    });
};

const openRejectModal = () => { rejectForm.remarks = ''; rejectForm.processing = false; showRejectModal.value = true; };
const closeRejectModal = () => showRejectModal.value = false;
const submitRejection = () => {
    if (!rejectForm.remarks.trim()) return; 
    rejectForm.processing = true;
    router.post(route('admin.applications.renewal.reject', application.value.id), { remarks: rejectForm.remarks }, {
        preserveScroll: true,
        onSuccess: () => closeRejectModal(),
        onFinish: () => rejectForm.processing = false
    });
};

const submitFinalize = () => {
    if (hasTooManyComplaints.value) return; // double protection
    finalizeForm.post(route('admin.applications.renewal.finalize', application.value.id), {
        onSuccess: () => showFinalizeModal.value = false
    });
};

// Complaint and Red Flag Actions
const resolveComplaint = (complaintId) => {
    if(confirm('Are you sure you want to mark this complaint as resolved?')) {
        router.patch(route('admin.applications.renewal.resolve-complaint', { application: application.value.id, complaint: complaintId }), {}, { preserveScroll: true });
    }
};

const resolveRedFlag = (redFlagId) => {
    if(confirm('Are you sure you want to mark this red flag as resolved?')) {
        router.patch(route('admin.applications.renewal.resolve-red-flag', { application: application.value.id, redFlag: redFlagId }), {}, { preserveScroll: true });
    }
};

const formatCurrency = (value) => {
    if(!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};

const isPdf = (url) => {
    if (!url) return false;
    return url.toLowerCase().endsWith('.pdf');
};

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
    router.post(route('admin.applications.renewal.evaluate', application.value.id), {
        evaluation_id: evaluation.id,
        status: status,
        remarks: requirementForm.remarks || 'Pending Review'
    }, {
        preserveScroll: true,
        onSuccess: () => closeRequirementModal()
    });
};

const openInspectionModal = (index) => {
    if (!props.currentUnitId) return alert('No active unit associated with this franchise to inspect.');
    selectedInspectionIndex.value = index;
    inspectionForm.status = inspectionsList.value[index].status === 'Pending' ? '' : inspectionsList.value[index].status;
    inspectionForm.remarks = inspectionsList.value[index].remarks;
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    setTimeout(() => { selectedInspectionIndex.value = null; inspectionForm.status = ''; inspectionForm.remarks = ''; }, 200);
};

const saveInspectionStatus = () => {
    if (selectedInspectionIndex.value === null || !props.currentUnitId) return;
    const inspection = inspectionsList.value[selectedInspectionIndex.value];
    router.post(route('admin.applications.renewal.inspect', application.value.id), {
        unit_id: props.currentUnitId,
        inspection_item_id: inspection.id,
        status: inspectionForm.status,
        remarks: inspectionForm.remarks
    }, {
        preserveScroll: true,
        onSuccess: () => closeInspectionModal()
    });
};
</script>

<template>
    <Head title="Application Details - Renewal" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-100px)] flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Renewal Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Completed'">
                        <span class="px-4 py-2 bg-green-100 text-green-800 text-xs font-bold uppercase rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Renewal Finalized
                        </span>
                    </template>
                    <template v-else-if="application.status === 'Approved'">
                        <PrimaryButton 
                            v-if="!hasTooManyComplaints" 
                            @click="showFinalizeModal = true" 
                            class="flex items-center gap-2">
                            Finalize Renewal
                        </PrimaryButton>
                    </template>
                    <template v-else-if="!['Rejected', 'Returned'].includes(application.status)">
                        <button @click="openReturnModal" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold uppercase rounded-lg transition-colors">Return</button>
                        <button @click="openRejectModal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase rounded-lg transition-colors">Reject</button>
                        
                        <button 
                            @click="hasTooManyComplaints ? null : openApproveModal()" 
                            :disabled="hasTooManyComplaints"
                            :class="[
                                'px-4 py-2 text-white text-xs font-bold uppercase rounded-lg transition-colors',
                                hasTooManyComplaints ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'
                            ]">
                            Approve
                        </button>
                    </template>
                </div>
            </div>

            <div v-if="hasTooManyComplaints && !['Rejected', 'Completed'].includes(application.status)" class="flex-none bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-red-800">Renewal Blocked: Excessive Complaints</h3>
                        <p class="text-xs text-red-700 mt-1">This franchise has <strong>{{ application.franchise_details.complaints.length }}</strong> recorded complaints. Per ordinance regulations, a franchise cannot be renewed if it exceeds 3 complaints.</p>
                    </div>
                </div>
            </div>

            <div class="flex-1 flex flex-col md:flex-row gap-4 h-full min-h-0">
                
                <div class="w-full md:w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-200 p-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-blue-100 text-blue-800">{{ application.status }}</span>
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
                                <h4 class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Applicant Submitter</h4>
                                <div class="text-sm space-y-1.5">
                                    <p class="flex justify-between"><span class="text-gray-500">Contact:</span> <span class="font-medium text-gray-900">{{ application.applicant.contact }}</span></p>
                                    <p class="flex justify-between"><span class="text-gray-500">Email:</span> <span class="font-medium text-gray-900 truncate max-w-[120px]" :title="application.applicant.email">{{ application.applicant.email }}</span></p>
                                    <p class="text-gray-500">Address:</p>
                                    <p class="font-medium text-gray-900 leading-snug">{{ application.applicant.address }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-w-0">  
                    
                    <div class="flex items-center border-b border-gray-200 px-2 pt-2 overflow-x-auto custom-scrollbar">
                        <button @click="activeTab = 'franchise_overview'" :class="activeTab === 'franchise_overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Franchise Overview</button>
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                        <button @click="activeTab = 'inspection'" :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Inspection</button>
                        <button @click="activeTab = 'complaints'" :class="activeTab === 'complaints' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">
                            Complaints <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ application.franchise_details.complaints.length }}</span>
                        </button>
                        <button @click="activeTab = 'red_flags'" :class="activeTab === 'red_flags' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">
                            Red Flags <span class="ml-1 bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ application.franchise_details.red_flags.length }}</span>
                        </button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6 custom-scrollbar">
                        
                        <div v-if="activeTab === 'franchise_overview'" class="space-y-6">
                            
                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                                
                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2 flex items-center justify-between">
                                        <span>Target Franchise Profile</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Franchise ID</span><span class="font-bold text-gray-900 font-mono">#{{ application.franchise_details.id }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">MTFRB Case No.</span><span class="font-bold text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Zone</span><span class="font-bold text-gray-900">{{ application.franchise_details.zone }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Current Expiration / Issue</span><span class="font-bold text-gray-900">{{ application.franchise_details.date_issued }}</span></div>
                                        <div class="flex justify-between items-center text-sm pt-2 border-t border-gray-100">
                                            <span class="text-gray-500">Current Status</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-gray-100 text-gray-800">{{ application.franchise_details.status }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2 flex items-center justify-between">
                                        <span>Registered Operator</span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Full Name</span><span class="font-bold text-gray-900">{{ application.current_owner.first_name }} {{ application.current_owner.middle_name }} {{ application.current_owner.last_name }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Contact Number</span><span class="font-bold text-gray-900">{{ application.current_owner.contact }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Email Address</span><span class="font-bold text-gray-900">{{ application.current_owner.email }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">TIN Number</span><span class="font-bold text-gray-900">{{ application.current_owner.tin_number }}</span></div>
                                        <div class="flex flex-col text-sm pt-2 border-t border-gray-100">
                                            <span class="text-gray-500 mb-1">Registered Address</span>
                                            <span class="font-bold text-gray-900">{{ application.current_owner.address }}</span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-6 mt-6">
                                <h3 class="font-bold text-blue-900 mb-4 flex items-center gap-2 text-lg">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    Active Unit Subject to Inspection
                                </h3>
                                
                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm mb-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Make & Model Year</span><span class="font-bold text-gray-900 text-sm">{{ application.current_unit.make }} ({{ application.current_unit.year }})</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Plate Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ application.current_unit.plate_no }}</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">CR Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ application.current_unit.cr_no }}</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Motor Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ application.current_unit.motor_no }}</span></div>
                                        <div><span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1">Chassis Number</span><span class="font-bold text-gray-900 text-sm uppercase">{{ application.current_unit.chassis_no }}</span></div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-[11px] font-bold text-blue-600 uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-blue-100 pb-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        Unit Visual Reference
                                    </h4>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div v-for="(view, idx) in unitViews" :key="idx" class="bg-white p-2 border border-blue-200 rounded-lg shadow-sm">
                                            <div class="text-[10px] text-gray-400 uppercase tracking-wider mb-2 font-bold text-center">
                                                <span>{{ view.label }}</span>
                                            </div>
                                            <div class="bg-gray-50 rounded border border-gray-100 h-32 flex items-center justify-center overflow-hidden">
                                                <img v-if="application.current_unit[`${view.key}_photo`]" 
                                                     :src="application.current_unit[`${view.key}_photo`]" 
                                                     class="w-full h-full object-cover hover:object-contain transition-all" />
                                                <div v-else class="text-center text-gray-400">
                                                    <svg class="w-6 h-6 mx-auto mb-1 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    <span class="text-[10px] italic">No image available</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'evaluation'" class="space-y-4">
                            <div v-if="application.evaluation_requirements.length === 0" class="text-center py-10 text-gray-500 italic bg-white border border-gray-200 rounded-lg">
                                No documents uploaded for evaluation.
                            </div>

                            <div v-for="(req, index) in application.evaluation_requirements" :key="index" 
                                @click="openRequirementModal(index)"
                                class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center cursor-pointer hover:border-blue-400 hover:shadow-md transition-all group">
                                
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5">
                                        <svg v-if="req.status === 'Approved'" class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else-if="req.status === 'Rejected'" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <svg v-else class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
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
                                <div class="text-gray-300 group-hover:text-blue-500 transition-colors px-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'inspection'" class="space-y-4">
                            <div v-if="!currentUnitId" class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z" />
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-yellow-800">No Active Unit Found</h4>
                                    <p class="text-xs text-yellow-700 mt-1">This franchise does not seem to have a registered active unit to inspect.</p>
                                </div>
                            </div>

                            <div v-else-if="inspectionsList.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No inspection items mapped to this database.
                            </div>
                            
                            <div v-for="(item, index) in inspectionsList" :key="index" 
                                @click="openInspectionModal(index)"
                                class="bg-white border border-gray-200 rounded-lg p-4 flex justify-between items-center cursor-pointer hover:border-blue-400 hover:shadow-md transition-all group">
                                
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 text-gray-400">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800 text-sm group-hover:text-blue-600 transition-colors">{{ item.name }}</h4>
                                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-gray-100 text-gray-800">
                                                {{ item.status }}
                                            </span>
                                            <span v-if="item.remarks" class="text-[11px] text-gray-500 italic max-w-xs truncate">
                                                - {{ item.remarks }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-gray-300 group-hover:text-blue-500 transition-colors px-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'complaints'" class="space-y-4">
                            <div v-if="application.franchise_details.complaints.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No complaints found for this franchise.
                            </div>
                            
                            <div v-for="complaint in application.franchise_details.complaints" :key="complaint.id" 
                                class="bg-white p-5 border border-gray-200 rounded-lg shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-bold text-gray-900 text-base">{{ complaint.nature }}</h4>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide"
                                                :class="complaint.status.toLowerCase() === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                                {{ complaint.status }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 font-mono">Incident Date: {{ complaint.date }}</p>
                                        <div class="mt-3 text-sm text-gray-700 bg-gray-50 p-3 rounded border border-gray-100">
                                            "{{ complaint.remarks }}"
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0" v-if="complaint.status.toLowerCase() !== 'resolved'">
                                        <button @click="resolveComplaint(complaint.id)" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded text-xs font-bold transition-colors">
                                            Mark as Resolved
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="activeTab === 'red_flags'" class="space-y-4">
                            <div v-if="application.franchise_details.red_flags.length === 0" class="text-center py-10 bg-white border border-gray-200 rounded-lg text-gray-500 italic">
                                No red flags found for this franchise.
                            </div>
                            
                            <div v-for="flag in application.franchise_details.red_flags" :key="flag.id" 
                                class="bg-white p-5 border border-red-100 rounded-lg shadow-sm">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                                            <h4 class="font-bold text-gray-900 text-base">{{ flag.nature }}</h4>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide"
                                                :class="flag.status.toLowerCase() === 'resolved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                                {{ flag.status }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 font-mono">Flagged Date: {{ flag.date }}</p>
                                        <div class="mt-3 text-sm text-gray-700 bg-red-50 p-3 rounded border border-red-100">
                                            "{{ flag.remarks }}"
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0" v-if="flag.status.toLowerCase() !== 'resolved'">
                                        <button @click="resolveRedFlag(flag.id)" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-blue-600 rounded text-xs font-bold transition-colors">
                                            Clear Flag
                                        </button>
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
                                        <span class="px-3 py-1 text-[11px] font-bold rounded uppercase tracking-wide"
                                            :class="application.assessment.status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">
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
                                        <div class="col-span-2">
                                            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Remarks</p>
                                            <p class="text-sm font-medium text-gray-900">{{ application.assessment.remarks }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3 mb-6">
                                        <div v-for="(item, index) in application.assessment.particulars" :key="index" class="flex justify-between text-sm">
                                            <span class="text-gray-600">{{ item.name }}</span>
                                            <span class="font-medium text-gray-900">{{ formatCurrency(item.amount) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="pt-4 border-t border-gray-200 flex justify-between items-center font-bold text-lg text-gray-900">
                                        <span>Total Amount Due</span>
                                        <span>{{ formatCurrency(application.assessment.total_due) }}</span>
                                    </div>
                                </div>

                                <div v-if="application.assessment.payments.length > 0" class="bg-white p-6 border border-gray-200 rounded-lg shadow-sm">
                                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-4">Payment Records</h3>
                                    <div v-for="payment in application.assessment.payments" :key="payment.or_number" class="mb-4 last:mb-0 pb-4 last:pb-0 border-b last:border-0 border-gray-100">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Ref ID</p>
                                                <p class="text-sm font-medium text-gray-900">#{{ payment.or_number }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Date Paid</p>
                                                <p class="text-sm font-medium text-gray-900">{{ payment.date }}</p>
                                            </div>
                                            <div class="col-span-2">
                                                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Payee Name</p>
                                                <p class="text-sm font-medium text-gray-900">{{ payment.payee }}</p>
                                            </div>
                                            <div class="col-span-2 pt-2">
                                                <div class="flex justify-between items-center bg-green-50 p-3 rounded-lg border border-green-100">
                                                    <span class="text-sm text-green-800 font-bold">Amount Paid</span>
                                                    <span class="text-base font-bold text-green-700">{{ formatCurrency(payment.amount_paid) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg flex items-start gap-3">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z" />
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

        <Transition name="fade">
            <div v-if="showRequirementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeRequirementModal">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col" @click.stop>
                    <div class="p-6 h-[85vh] flex flex-col" v-if="selectedRequirement">
                        <div class="flex-none flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ selectedRequirement.name }}</h2>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold uppercase" 
                                        :class="{
                                            'bg-green-100 text-green-700': selectedRequirement.status === 'Approved',
                                            'bg-red-100 text-red-700': selectedRequirement.status === 'Rejected',
                                            'bg-gray-100 text-gray-600': selectedRequirement.status === 'Pending'
                                        }">
                                        {{ selectedRequirement.status }}
                                    </span>
                                </div>
                            </div>
                            <button @click="closeRequirementModal" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <div class="flex-1 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center mb-4 relative overflow-hidden">
                            <iframe v-if="isPdf(selectedRequirement.file_url)" :src="selectedRequirement.file_url" class="w-full h-full border-0"></iframe>
                            <img v-else-if="selectedRequirement.file_url && selectedRequirement.file_url !== '#'" :src="selectedRequirement.file_url" class="max-w-full max-h-full object-contain" />
                            <div v-else class="text-center text-gray-400">
                                <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                <p class="text-sm font-medium">No Document Available</p>
                            </div>
                        </div>

                        <div class="flex-none pt-4 border-t border-gray-100">
                            <div class="mb-4">
                                <InputLabel for="req_remarks" value="Remarks (Required for Rejection)" />
                                <textarea id="req_remarks" v-model="requirementForm.remarks" rows="2" class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" placeholder="Provide reason if rejecting..."></textarea>
                            </div>
                            <div class="flex justify-between items-center">
                                <SecondaryButton @click="closeRequirementModal">Close</SecondaryButton>
                                <div class="flex gap-2">
                                    <PrimaryButton @click="saveRequirementStatus('Rejected')" class="bg-red-600 hover:bg-red-700 focus:ring-red-500">
                                        Reject
                                    </PrimaryButton>
                                    <PrimaryButton @click="saveRequirementStatus('Approved')" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
                                        Approve
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="showInspectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeInspectionModal">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col" @click.stop>
                    <div class="p-6 flex flex-col" v-if="selectedInspection">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ selectedInspection.name }}</h2>
                                <span class="px-2 py-0.5 mt-1 inline-block rounded text-xs font-bold uppercase tracking-wide bg-gray-100 text-gray-800">
                                    {{ selectedInspection.status }}
                                </span>
                            </div>
                            <button @click="closeInspectionModal" class="text-gray-400 hover:text-gray-600 mb-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <InputLabel value="Select Rating" />
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <button v-for="option in selectedInspection.options" :key="option"
                                        @click="inspectionForm.status = option"
                                        :class="['px-4 py-2 border rounded-lg text-sm font-medium transition-colors', inspectionForm.status === option ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50']">
                                        {{ option }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <InputLabel for="insp_remarks" value="Inspector Remarks / Notes" />
                                <textarea id="insp_remarks" v-model="inspectionForm.remarks" rows="3" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" placeholder="Add specific observation details here..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-between items-center pt-4 border-t border-gray-100">
                            <SecondaryButton @click="closeInspectionModal">Cancel</SecondaryButton>
                            <PrimaryButton @click="saveInspectionStatus" class="bg-blue-600 hover:bg-blue-700" :disabled="!inspectionForm.status">
                                Save Rating
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
                                <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77-1.333.192 3 1.732 3z" />
                                </svg>
                                Reject Application
                            </h2>
                            <button @click="closeRejectModal" class="text-gray-400 hover:text-gray-600 mb-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <p class="text-sm text-gray-600">Please provide a reason for rejecting this application permanently.</p>
                            <div>
                                <InputLabel for="app_reject_remarks" value="Rejection Reason" />
                                <textarea id="app_reject_remarks" v-model="rejectForm.remarks" rows="4" required class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" placeholder="State exactly what is missing or incorrect..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <SecondaryButton @click="closeRejectModal" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                            <PrimaryButton @click="submitRejection" class="bg-red-600 hover:bg-red-700 focus:ring-red-500" :disabled="!rejectForm.remarks.trim() || rejectForm.processing">
                                Confirm Rejection
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeReturnModal">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col" @click.stop>
                    <div class="p-6 flex flex-col">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                Return Application
                            </h2>
                            <button @click="closeReturnModal" class="text-gray-400 hover:text-gray-600 mb-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <p class="text-sm text-gray-600">Return this application to the Franchise Owner so they can comply with missing or incorrect requirements.</p>
                            <div>
                                <InputLabel for="app_return_remarks" value="Return Notes / Instructions" />
                                <textarea id="app_return_remarks" v-model="returnForm.remarks" rows="4" required class="mt-1 block w-full border-gray-300 focus:border-yellow-500 focus:ring-yellow-500 rounded-md shadow-sm text-sm" placeholder="E.g. Please re-upload a clearer picture of the OR/CR..."></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <SecondaryButton @click="closeReturnModal" :disabled="returnForm.processing">Cancel</SecondaryButton>
                            <PrimaryButton @click="submitReturn" class="bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500 text-white" :disabled="!returnForm.remarks.trim() || returnForm.processing">
                                Confirm Return
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="closeApproveModal">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col" @click.stop>
                    <div class="p-6 flex flex-col">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Confirm Approval
                            </h2>
                            <button @click="closeApproveModal" class="text-gray-400 hover:text-gray-600 mb-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <p class="text-sm text-gray-600">Are you sure you want to approve this application? Approving it confirms that all requirements, inspections, and payments are compliant.</p>
                            <p class="text-sm text-gray-500 italic">Note: After approving, you will still need to click "Finalize Renewal" to officially update the franchise's date of issuance.</p>
                        </div>

                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                            <SecondaryButton @click="closeApproveModal" :disabled="approveProcessing">Cancel</SecondaryButton>
                            <PrimaryButton @click="submitApproval" class="bg-green-600 hover:bg-green-700 focus:ring-green-500" :disabled="approveProcessing">
                                {{ approveProcessing ? 'Approving...' : 'Yes, Approve Application' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="showFinalizeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm" @click="showFinalizeModal = false">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col" @click.stop>
                    <div class="p-6 flex flex-col">
                        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Finalize Franchise Renewal
                            </h2>
                            <button @click="showFinalizeModal = false" class="text-gray-400 hover:text-gray-600 mb-auto">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <div class="space-y-5">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                                <p class="text-sm font-medium text-blue-800 mb-2">Update Validity</p>
                                <p class="text-xs text-blue-600">Finalizing this application will update the franchise record's issuance date, officially renewing it in the master list.</p>
                            </div>

                            <form @submit.prevent="submitFinalize">
                                <div>
                                    <InputLabel value="New Date of Issuance" class="text-xs mb-0" />
                                    <TextInput type="date" v-model="finalizeForm.new_date_issued" class="block w-full text-sm py-1.5 mt-1" required />
                                </div>
                                <div class="mt-4">
                                    <InputLabel value="Remarks / Notes" class="text-xs mb-0" />
                                    <TextInput v-model="finalizeForm.remarks" class="block w-full text-sm py-1.5 mt-1" placeholder="Add any final notes..." />
                                </div>

                                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                                    <SecondaryButton @click="showFinalizeModal = false" :disabled="finalizeForm.processing">Cancel</SecondaryButton>
                                    <PrimaryButton type="submit" class="bg-blue-600 hover:bg-blue-700 focus:ring-blue-500" :disabled="finalizeForm.processing">
                                        {{ finalizeForm.processing ? 'Finalizing...' : 'Save & Renew Franchise' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

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