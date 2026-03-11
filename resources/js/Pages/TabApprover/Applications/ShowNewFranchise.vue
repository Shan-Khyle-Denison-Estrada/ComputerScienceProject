<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
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
        particulars: (app.assessment.particulars || []).map(p => ({
            name: p.name,
            amount: p.pivot ? p.pivot.subtotal : p.amount
        })),
        payments: (app.assessment.payments || []).map(pay => ({
            or_number: pay.or_number || pay.id, 
            amount_paid: pay.amount_paid,
            date: new Date(pay.created_at).toLocaleDateString(),
            payee: `${pay.payee_first_name || ''} ${pay.payee_last_name || ''}`.trim() || 'Applicant'
        }))
    } : null;

    return {
        id: app.id,
        type: app.application_type || 'New Franchise',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        // Checklist requirements
        evaluator_status: app.evaluator_status || 'Pending',
        inspector_status: app.inspector_status || 'Pending',
        capo_status: app.capo_status || 'Pending',
        reviewer_status: app.reviewer_status || 'Pending',
        sp_status: app.sp_status || 'Pending',
        
        franchise_details: {
            id: franchise.id,
            zone: franchise.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A',
        },
        
        current_owner: {
            first_name: currentUser.first_name || 'Not specified',
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
            front_photo: currentUnitData.unit_front_photo ? `/storage/${currentUnitData.unit_front_photo}` : null,
            back_photo: currentUnitData.unit_back_photo ? `/storage/${currentUnitData.unit_back_photo}` : null,
            left_photo: currentUnitData.unit_left_photo ? `/storage/${currentUnitData.unit_left_photo}` : null,
            right_photo: currentUnitData.unit_right_photo ? `/storage/${currentUnitData.unit_right_photo}` : null
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

const submitApproval = () => {
    approveProcessing.value = true;
    router.post(route('tab_approver.applications.approve', application.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => showApproveModal.value = false,
        onFinish: () => approveProcessing.value = false
    });
};

const submitReject = () => {
    if(!rejectForm.remarks) return;
    rejectForm.processing = true;
    router.post(route('tab_approver.applications.reject', application.value.id), { remarks: rejectForm.remarks }, {
        preserveScroll: true,
        onSuccess: () => showRejectModal.value = false,
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
    <Head title="Tabulation Finalize New Franchise" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tabulation: New Franchise</h2>
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
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Evaluations</button>
                    <button @click="activeTab = 'inspections'" :class="activeTab === 'inspections' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Inspections</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment & Payment</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'franchise_overview'" class="space-y-8">
                            <section>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Franchise Owner</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                                    <div><p class="text-sm text-gray-500">Full Name</p><p class="font-medium text-gray-900">{{ application.current_owner.first_name }} {{ application.current_owner.last_name }}</p></div>
                                    <div><p class="text-sm text-gray-500">Contact Number</p><p class="font-medium text-gray-900">{{ application.current_owner.contact }}</p></div>
                                    <div><p class="text-sm text-gray-500">Email Address</p><p class="font-medium text-gray-900">{{ application.current_owner.email }}</p></div>
                                    <div><p class="text-sm text-gray-500">TIN</p><p class="font-medium text-gray-900">{{ application.current_owner.tin_number }}</p></div>
                                    <div class="col-span-2"><p class="text-sm text-gray-500">Address</p><p class="font-medium text-gray-900">{{ application.current_owner.address }}</p></div>
                                </div>
                            </section>

                            <section>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Franchise Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                                    <div><p class="text-sm text-gray-500">Zone</p><p class="font-medium text-gray-900">{{ application.franchise_details.zone }}</p></div>
                                    <div><p class="text-sm text-gray-500">Date Issued</p><p class="font-medium text-gray-900">{{ application.franchise_details.date_issued }}</p></div>
                                    <!-- <div><p class="text-sm text-gray-500">MTFRB Case No.</p><p class="font-medium text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</p></div> -->
                                </div>
                            </section>
                        </div>

                        <div v-else-if="activeTab === 'unit_details'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Tricycle Information</h3>
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6 mb-6">
                                <div><p class="text-sm text-gray-500">Make/Model</p><p class="font-medium text-gray-900">{{ application.current_unit.make }} ({{ application.current_unit.year }})</p></div>
                                <div><p class="text-sm text-gray-500">Plate Number</p><p class="font-medium text-gray-900">{{ application.current_unit.plate_no }}</p></div>
                                <div><p class="text-sm text-gray-500">Motor Number</p><p class="font-medium text-gray-900">{{ application.current_unit.motor_no }}</p></div>
                                <div><p class="text-sm text-gray-500">Chassis Number</p><p class="font-medium text-gray-900">{{ application.current_unit.chassis_no }}</p></div>
                                <div><p class="text-sm text-gray-500">CR Number</p><p class="font-medium text-gray-900">{{ application.current_unit.cr_no }}</p></div>
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

                        <div v-else-if="activeTab === 'evaluations'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Document Evaluation Results</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="req in application.evaluation_requirements" :key="req.id" class="border rounded-lg p-4 bg-gray-50">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-medium text-gray-900">{{ req.name }}</h4>
                                        <span class="text-xs px-2 py-1 rounded-full font-semibold" :class="{
                                            'bg-green-100 text-green-700': req.status === 'Approved',
                                            'bg-red-100 text-red-700': req.status === 'Rejected',
                                            'bg-yellow-100 text-yellow-700': req.status === 'Pending'
                                        }">{{ req.status }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mb-2">Remarks: {{ req.remarks }}</p>
                                    <button v-if="req.file_url" @click="openDocumentModal(req.file_url)" class="text-sm font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1 mt-2">
                                        View Document &rarr;
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'inspections'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Unit Inspection Results</h3>
                            <div class="overflow-hidden border border-gray-200 sm:rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in inspectionsList" :key="item.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span :class="{
                                                    'text-green-600 bg-green-100 px-2 py-1 rounded font-semibold': item.status.toLowerCase() === 'pass' || item.status.toLowerCase() === 'good' || item.status.toLowerCase() === 'passed',
                                                    'text-red-600 bg-red-100 px-2 py-1 rounded font-semibold': item.status.toLowerCase() === 'fail' || item.status.toLowerCase() === 'failed' || item.status.toLowerCase() === 'poor',
                                                    'text-gray-500 font-semibold': item.status === 'Pending'
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
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">Financial Obligations</h3>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm text-gray-500">Total Assessed Amount</p>
                                        <p class="text-2xl font-bold text-gray-900 font-mono">₱{{ parseFloat(application.assessment.total_due).toFixed(2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500 mb-1">Payment Status</p>
                                        <span class="px-3 py-1 rounded-full text-sm font-bold uppercase tracking-wider" :class="{
                                            'bg-green-200 text-green-800': application.assessment.status === 'Paid',
                                            'bg-yellow-200 text-yellow-800': application.assessment.status === 'Pending',
                                            'bg-red-200 text-red-800': application.assessment.status === 'Unpaid' || application.assessment.status === 'Overdue'
                                        }">{{ application.assessment.status }}</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-8">
                                    <div>
                                        <h4 class="font-medium text-sm text-gray-700 mb-3 border-b pb-2">Fee Breakdown</h4>
                                        <ul class="border border-gray-200 rounded-lg divide-y bg-white">
                                            <li v-for="part in application.assessment.particulars" :key="part.name" class="p-3 flex justify-between text-sm">
                                                <span class="text-gray-600">{{ part.name }}</span>
                                                <span class="font-medium text-gray-900 font-mono">₱{{ parseFloat(part.amount).toFixed(2) }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <div>
                                        <h4 class="font-medium text-sm text-gray-700 mb-3 border-b pb-2">Payment Records</h4>
                                        <div v-if="application.assessment.payments.length > 0" class="space-y-3">
                                            <div v-for="(payment, idx) in application.assessment.payments" :key="idx" class="bg-gray-50 border rounded-lg p-3">
                                                <div class="flex justify-between items-start mb-1">
                                                    <span class="text-xs font-semibold text-gray-500 uppercase">OR / Ref: {{ payment.or_number }}</span>
                                                    <span class="font-bold text-green-600 font-mono">₱{{ parseFloat(payment.amount_paid).toFixed(2) }}</span>
                                                </div>
                                                <div class="flex justify-between items-center text-xs text-gray-500">
                                                    <span>Payee: {{ payment.payee }}</span>
                                                    <span>{{ payment.date }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="text-center py-6 bg-gray-50 rounded-lg border border-dashed text-sm text-gray-500">
                                            No payments have been recorded yet.
                                        </div>
                                    </div>
                                </div>
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
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        Final Tabulation
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Final validation of all requisite approvals before the system formally issues the franchise.</p>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                    
                    <div class="bg-white p-4 border rounded-lg shadow-sm space-y-4">
                        <h4 class="font-semibold text-gray-800 border-b pb-2 text-sm uppercase tracking-wider">Department Clearances</h4>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Evaluator Review</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.evaluator_status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                <svg v-if="application.evaluator_status === 'Approved'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.evaluator_status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Unit Inspection</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.inspector_status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                <svg v-if="application.inspector_status === 'Approved'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.inspector_status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">CAPO Clearance</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.capo_status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                <svg v-if="application.capo_status === 'Approved'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.capo_status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Final Reviewer</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.reviewer_status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                <svg v-if="application.reviewer_status === 'Approved'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.reviewer_status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">SP Authorization</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.sp_status === 'Approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                <svg v-if="application.sp_status === 'Approved'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.sp_status }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-white p-4 border rounded-lg shadow-sm space-y-4">
                        <h4 class="font-semibold text-gray-800 border-b pb-2 text-sm uppercase tracking-wider">Financial Clearance</h4>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Fees Status</span>
                            <span class="px-2 py-1 rounded text-xs font-bold flex items-center gap-1" :class="application.assessment?.status === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                <svg v-if="application.assessment?.status === 'Paid'" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                {{ application.assessment?.status || 'No Assessment' }}
                            </span>
                        </div>
                    </div>

                    <div class="text-sm text-gray-500 bg-indigo-50 border border-indigo-100 p-4 rounded-lg">
                        <strong>Notice:</strong> As the Tabulation Approver, you perform the final system check. Verifying that all preceding steps are complete and approving this application will officially finalize the new franchise and encode it into the active roster.
                    </div>
                </div>

                <div class="p-4 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                    <div v-if="application.status === 'Pending'" class="space-y-2">
                        <PrimaryButton @click="showApproveModal = true" class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 py-3 text-base">Finalize Application</PrimaryButton>
                        <SecondaryButton @click="showRejectModal = true" class="w-full justify-center border-red-500 text-red-700 hover:bg-red-50 py-3 text-base">Reject / Halt Issuance</SecondaryButton>
                    </div>
                    <div v-else class="text-center p-3 bg-gray-100 rounded-lg text-sm text-gray-600 font-medium">
                        Application is already {{ application.status }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDocumentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" @click="closeDocumentModal"></div>
            
            <div class="relative w-full max-w-5xl h-[90vh] flex justify-center items-center z-10 bg-white rounded-lg overflow-hidden">
                <div class="absolute top-0 right-0 p-4 z-20">
                     <button @click="closeDocumentModal" class="text-gray-500 hover:text-gray-800 bg-white rounded-full p-2 shadow-md">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div v-if="currentDocumentUrl" class="w-full h-full p-2 bg-gray-100 flex items-center justify-center">
                    <img v-if="isImageUrl(currentDocumentUrl)" 
                         :src="currentDocumentUrl" 
                         class="max-w-full max-h-full object-contain shadow-sm bg-white" />
                         
                    <iframe v-else 
                            :src="currentDocumentUrl" 
                            class="w-full h-full border border-gray-300 bg-white shadow-sm"></iframe>
                </div>
            </div>
        </div>

        <div v-if="showApproveModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showApproveModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm overflow-hidden z-10">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-indigo-600 mb-4">Finalize Franchise?</h2>
                    <p class="text-sm text-gray-500 mb-6">Approving this will permanently mark the application as approved and finalize the system records for this new franchise.</p>
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showApproveModal = false" :disabled="approveProcessing">Cancel</SecondaryButton>
                        <PrimaryButton @click="submitApproval" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500" :disabled="approveProcessing">Confirm Finalization</PrimaryButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showRejectModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" @click="showRejectModal = false"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden z-10">
                <div class="p-6">
                    <h2 class="text-lg font-bold text-red-600 mb-2">Halt Issuance</h2>
                    <p class="text-sm text-gray-500 mb-4">Provide the reason for halting the final issuance of this franchise.</p>
                    <div class="mb-5">
                        <InputLabel value="Reason for Rejection" class="text-xs mb-1" />
                        <textarea v-model="rejectForm.remarks" class="w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm text-sm" rows="3" placeholder="Explain the terminal issue found..."></textarea>
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