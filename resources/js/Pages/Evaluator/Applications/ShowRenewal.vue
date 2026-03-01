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

const showReturnModal = ref(false);
const returnForm = reactive({ remarks: '', processing: false });

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
            or_number: pay.id, 
            amount_paid: pay.amount_paid,
            date: new Date(pay.created_at).toLocaleDateString(),
            payee: `${pay.payee_first_name} ${pay.payee_last_name}`.trim()
        }))
    } : null;

    const formatTime = (timeStr) => {
        if (!timeStr) return 'N/A';
        try {
            const [hours, minutes] = timeStr.split(':');
            const d = new Date();
            d.setHours(hours, minutes);
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch(e) { return timeStr; }
    };

    return {
        id: app.id,
        type: app.application_type || 'Renewal',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        franchise_details: {
            id: franchise.id,
            zone: franchise.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A',
            complaints: (franchise.complaints || []).map(c => ({
                id: c.id,
                nature: c.nature_of_complaint,
                date: c.incident_date ? new Date(c.incident_date).toLocaleDateString() : 'N/A',
                time: formatTime(c.incident_time),
                pick_up_point: c.pick_up_point || 'Not specified',
                drop_off_point: c.drop_off_point || 'Not specified',
                fare_collected: c.fare_collected,
                complainant_contact: c.complainant_contact || 'N/A',
                status: c.status || 'Pending',
                remarks: c.remarks || 'No narrative provided.'
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
            right_photo: currentUnitData.unit_right_photo ? `/storage/${currentUnitData.unit_right_photo}` : (currentUnitData.right_photo ? `/storage/${currentUnitData.right_photo}` : null)
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

const submitReturn = () => {
    if(!returnForm.remarks) return;
    returnForm.processing = true;
    router.post(route('evaluator.applications.return', application.value.id), { remarks: returnForm.remarks }, {
        onFinish: () => returnForm.processing = false
    });
};

const resolveComplaint = (complaintId) => {
    if (confirm('Are you sure you want to mark this complaint as resolved?')) {
        router.post(route('evaluator.applications.resolve-complaint', [application.value.id, complaintId]), {}, { preserveScroll: true });
    }
};

const resolveRedFlag = (flagId) => {
    if (confirm('Are you sure you want to mark this red flag as resolved?')) {
        router.post(route('evaluator.applications.resolve-red-flag', [application.value.id, flagId]), {}, { preserveScroll: true });
    }
};

const openDocumentModal = (url) => {
    currentDocumentUrl.value = url;
    showDocumentModal.value = true;
};

const closeDocumentModal = () => {
    showDocumentModal.value = false;
    currentDocumentUrl.value = null;
};

// HELPER: Check if URL is an image to correctly center it instead of loading an unstyled iframe
const isImageUrl = (url) => {
    if (!url) return false;
    const cleanUrl = url.split('?')[0]; 
    return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(cleanUrl);
};

</script>

<template>
    <Head title="Evaluate Renewal" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Evaluate Renewal</h2>
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
                    <button @click="activeTab = 'complaints'" :class="activeTab === 'complaints' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Complaints</button>
                    <button @click="activeTab = 'red_flags'" :class="activeTab === 'red_flags' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Red Flags</button>
                    <button @click="activeTab = 'inspections'" :class="activeTab === 'inspections' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Inspections (Read-Only)</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'franchise_overview'" class="space-y-8">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg> Franchise Owner</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Name</p><p class="font-medium text-gray-900">{{ application.current_owner.first_name }} {{ application.current_owner.last_name }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">TIN Number</p><p class="font-medium text-gray-900">{{ application.current_owner.tin_number }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Contact</p><p class="font-medium text-gray-900">{{ application.current_owner.contact }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Email</p><p class="font-medium text-gray-900">{{ application.current_owner.email }}</p></div>
                                    <div class="col-span-2"><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Address</p><p class="font-medium text-gray-900">{{ application.current_owner.address }}</p></div>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2"><svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg> Processing Details</h3>
                                <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Zone Assigned</p><p class="font-medium text-gray-900">{{ application.franchise_details.zone }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">MTFRB Case No.</p><p class="font-medium text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Previous Issue Date</p><p class="font-medium text-gray-900">{{ application.franchise_details.date_issued }}</p></div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'unit_details'" class="space-y-6">
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100 mb-6">
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Make / Model</p><p class="font-medium text-gray-900">{{ application.current_unit.make }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Year</p><p class="font-medium text-gray-900">{{ application.current_unit.year }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Motor No.</p><p class="font-medium text-gray-900">{{ application.current_unit.motor_no }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Chassis No.</p><p class="font-medium text-gray-900">{{ application.current_unit.chassis_no }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Plate Number</p><p class="font-medium text-gray-900">{{ application.current_unit.plate_no }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">CR Number</p><p class="font-medium text-gray-900">{{ application.current_unit.cr_no }}</p></div>
                            </div>
                            <h4 class="font-bold text-gray-700 text-sm mb-3">Unit Photos</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="view in unitViews" :key="view.key" class="border rounded-lg p-2 bg-gray-50">
                                    <p class="text-xs font-semibold text-gray-600 mb-2 text-center">{{ view.label }}</p>
                                    <div class="aspect-video bg-gray-200 rounded flex items-center justify-center overflow-hidden">
                                        <img v-if="application.current_unit[`${view.key}_photo`]" :src="application.current_unit[`${view.key}_photo`]" class="object-cover w-full h-full" />
                                        <span v-else class="text-xs text-gray-400">No Image provided</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'complaints'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-3">Active Complaints</h3>
                            <div v-if="application.franchise_details.complaints.length === 0" class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-lg text-center">No complaints recorded against this franchise.</div>
                            <div v-else class="space-y-3">
                                <div v-for="comp in application.franchise_details.complaints" :key="comp.id" class="p-4 border border-red-100 bg-red-50 rounded-lg relative">
                                    <div class="flex justify-between items-start mb-2">
                                        <h4 class="font-semibold text-red-800">{{ comp.nature }}</h4>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-red-200" :class="comp.status === 'resolved' ? 'text-green-600' : 'text-red-600'">{{ comp.status.toUpperCase() }}</span>
                                            <button v-if="comp.status !== 'resolved'" @click="resolveComplaint(comp.id)" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 font-bold transition">Resolve</button>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                                        <p><span class="font-semibold text-gray-600">Date:</span> {{ comp.date }}</p>
                                        <p><span class="font-semibold text-gray-600">Time:</span> {{ comp.time }}</p>
                                        <p><span class="font-semibold text-gray-600">Pick-up:</span> {{ comp.pick_up_point }}</p>
                                        <p><span class="font-semibold text-gray-600">Drop-off:</span> {{ comp.drop_off_point }}</p>
                                        <p><span class="font-semibold text-gray-600">Fare:</span> ₱{{ comp.fare_collected }}</p>
                                        <p><span class="font-semibold text-gray-600">Contact:</span> {{ comp.complainant_contact }}</p>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-2 bg-white p-2 rounded border border-red-100"><span class="font-semibold">Remarks:</span> {{ comp.remarks }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'red_flags'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-3">Red Flags</h3>
                            <div v-if="application.franchise_details.red_flags.length === 0" class="text-sm text-gray-500 italic p-4 bg-gray-50 rounded-lg text-center">No red flags recorded.</div>
                            <div v-else class="space-y-3">
                                <div v-for="flag in application.franchise_details.red_flags" :key="flag.id" class="p-4 border border-orange-100 bg-orange-50 rounded-lg flex justify-between items-center">
                                    <div>
                                        <p class="font-bold text-orange-800">{{ flag.nature }}</p>
                                        <p class="text-xs text-gray-600 mt-1">Reported: {{ flag.date }}</p>
                                        <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Note:</span> {{ flag.remarks }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-orange-200" :class="flag.status === 'resolved' ? 'text-green-600' : 'text-orange-600'">{{ flag.status.toUpperCase() }}</span>
                                        <button v-if="flag.status !== 'resolved'" @click="resolveRedFlag(flag.id)" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 font-bold transition">Resolve</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'inspections'">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800 text-lg">Unit Inspections</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="(item, index) in inspectionsList" :key="item.id" 
                                     class="p-4 rounded-xl border-2 transition-all bg-white shadow-sm"
                                     :class="item.status === 'Pass' ? 'border-green-200' : (item.status === 'Fail' ? 'border-red-200' : 'border-gray-200')">
                                     <div class="flex justify-between items-start mb-2">
                                         <p class="text-sm font-bold text-gray-800">{{ item.name }}</p>
                                         <span class="px-2 py-0.5 rounded text-xs font-bold"
                                               :class="item.status === 'Pass' ? 'bg-green-100 text-green-700' : (item.status === 'Fail' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500')">
                                             {{ item.status }}
                                         </span>
                                     </div>
                                     <p class="text-xs text-gray-500 mt-1 truncate">{{ item.remarks }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'assessment'" class="space-y-6">
                            <div v-if="!application.assessment" class="p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
                                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" /></svg>
                                <h3 class="text-lg font-medium text-gray-900">No Assessment Yet</h3>
                                <p class="text-sm text-gray-500 mt-1">An assessment will be created once all requirements are evaluated.</p>
                            </div>
                            <div v-else class="bg-white border rounded-xl shadow-sm overflow-hidden">
                                <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg">Assessment Summary</h3>
                                        <p class="text-xs text-gray-500 mt-1">Assessed on {{ application.assessment.assessment_date }} &bull; Due by {{ application.assessment.assessment_due }}</p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-sm font-bold"
                                          :class="application.assessment.status === 'Paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                        {{ application.assessment.status }}
                                    </span>
                                </div>
                                <div class="p-6">
                                    <h4 class="font-semibold text-sm text-gray-700 mb-3 border-b pb-2">Particulars Breakdown</h4>
                                    <div class="space-y-2 mb-6">
                                        <div v-for="(p, index) in application.assessment.particulars" :key="index" class="flex justify-between text-sm">
                                            <span class="text-gray-600">{{ p.name }}</span>
                                            <span class="font-medium text-gray-900">₱{{ parseFloat(p.amount).toFixed(2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-base font-bold pt-3 border-t">
                                            <span class="text-gray-800">Total Amount Due</span>
                                            <span class="text-blue-600">₱{{ parseFloat(application.assessment.total_due).toFixed(2) }}</span>
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
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
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
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to approve this renewal evaluation?</p>
                <div class="flex justify-center gap-3">
                    <SecondaryButton @click="showApproveModal = false" class="w-1/2 justify-center" :disabled="approveProcessing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitApproval" class="w-1/2 justify-center bg-green-600 hover:bg-green-700" :disabled="approveProcessing">
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

        <Modal :show="showReturnModal" @close="showReturnModal = false" maxWidth="sm">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 text-yellow-600">Return Application</h3>
                <p class="text-sm text-gray-500 mb-4">Send this application back to the encoder/owner for corrections.</p>
                <div class="mb-5">
                    <InputLabel value="Reason for Return / Required Corrections" class="text-xs mb-1" />
                    <textarea v-model="returnForm.remarks" class="w-full border-gray-300 focus:border-yellow-500 focus:ring-yellow-500 rounded-md shadow-sm text-sm" rows="3" placeholder="What needs to be fixed?"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="showReturnModal = false" :disabled="returnForm.processing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitReturn" class="bg-yellow-500 hover:bg-yellow-600 focus:ring-yellow-500 text-white" :disabled="returnForm.processing || !returnForm.remarks.trim()">
                        Confirm Return
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