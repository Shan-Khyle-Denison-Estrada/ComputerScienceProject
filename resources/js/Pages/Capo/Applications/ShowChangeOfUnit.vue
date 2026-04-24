<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

const props = defineProps({
    application: Object,
    inspectionItems: { type: Array, default: () => [] },
    unitInspections: { type: Array, default: () => [] }
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

    const proposedUnitData = (app.proposed_units && app.proposed_units.length > 0) ? app.proposed_units[0] : {};
    const proposedMake = proposedUnitData.make || {};

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
        type: app.application_type || 'Change of Unit',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        franchise_details: {
            id: franchise.id,
            franchise_number: franchise.franchise_number || 'N/A',
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
            address: `${currentUser.street_address || ''}, ${currentUser.barangay || ''}, ${currentUser.city || ''}, ${currentUser.province || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
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

        proposed_unit: {
            make: proposedMake.name || 'Not specified',
            motor_no: proposedUnitData.motor_number || 'Not specified',
            chassis_no: proposedUnitData.chassis_number || 'Not specified',
            plate_no: proposedUnitData.plate_number || 'N/A',
            cr_no: proposedUnitData.cr_number || 'Not specified',
            year: proposedUnitData.model_year || 'Not specified',
            front_photo: proposedUnitData.unit_front_photo ? `/storage/${proposedUnitData.unit_front_photo}` : (proposedUnitData.front_photo ? `/storage/${proposedUnitData.front_photo}` : null),
            back_photo: proposedUnitData.unit_back_photo ? `/storage/${proposedUnitData.unit_back_photo}` : (proposedUnitData.back_photo ? `/storage/${proposedUnitData.back_photo}` : null),
            left_photo: proposedUnitData.unit_left_photo ? `/storage/${proposedUnitData.unit_left_photo}` : (proposedUnitData.left_photo ? `/storage/${proposedUnitData.left_photo}` : null),
            right_photo: proposedUnitData.unit_right_photo ? `/storage/${proposedUnitData.unit_right_photo}` : (proposedUnitData.right_photo ? `/storage/${proposedUnitData.right_photo}` : null)
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
            remarks: found ? found.remarks : 'No record'
        };
    });
});

const isPositiveRating = (rating) => ['pass', 'good', 'acceptable', 'passed', 'yes', 'approved'].includes(rating.toLowerCase());
const isNegativeRating = (rating) => ['fail', 'poor', 'defective', 'failed', 'no', 'bad', 'rejected'].includes(rating.toLowerCase());

const getBorderClass = (rating) => {
    if (rating === 'Pending') return 'border-gray-200';
    if (isPositiveRating(rating)) return 'border-green-200 bg-green-50';
    if (isNegativeRating(rating)) return 'border-red-200 bg-red-50';
    return 'border-blue-200 bg-blue-50';
};

const submitApproval = () => {
    approveProcessing.value = true;
    router.post(route('capo.applications.approve', application.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => showApproveModal.value = false,
        onFinish: () => approveProcessing.value = false
    });
};

const submitReject = () => {
    if(!rejectForm.remarks) return;
    rejectForm.processing = true;
    router.post(route('capo.applications.reject', application.value.id), { remarks: rejectForm.remarks }, {
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
    <Head title="Review Change of Unit - CAPO" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Review Change of Unit Application</h2>
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
                    <button @click="activeTab = 'unit_comparison'" :class="activeTab === 'unit_comparison' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Unit Comparison</button>
                    <button @click="activeTab = 'complaints'" :class="activeTab === 'complaints' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Complaints</button>
                    <button @click="activeTab = 'red_flags'" :class="activeTab === 'red_flags' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Red Flags</button>
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Evaluations</button>
                    <button @click="activeTab = 'inspections'" :class="activeTab === 'inspections' ? 'border-blue-600 text-blue-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Inspections</button>
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
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Franchise Number</p><p class="font-medium text-gray-900">{{ application.franchise_details.franchise_number }}</p></div>
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Zone Assigned</p><p class="font-medium text-gray-900">{{ application.franchise_details.zone }}</p></div>
                                    <!-- <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">MTFRB Case No.</p><p class="font-medium text-gray-900">{{ application.franchise_details.mtfrb_case_no }}</p></div> -->
                                    <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Date Issued</p><p class="font-medium text-gray-900">{{ application.franchise_details.date_issued }}</p></div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'unit_comparison'" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                                    <h4 class="font-bold text-gray-700 text-sm mb-4 border-b border-gray-200 pb-2 flex justify-between items-center">
                                        <span>Current Unit</span>
                                        <span class="text-xs font-normal text-gray-500 bg-gray-200 px-2 py-0.5 rounded">To be replaced</span>
                                    </h4>
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-4 mb-6">
                                        <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Make / Model</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.make }}</p></div>
                                        <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Year</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.year }}</p></div>
                                        <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Motor No.</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.motor_no }}</p></div>
                                        <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Chassis No.</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.chassis_no }}</p></div>
                                        <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">Plate Number</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.plate_no }}</p></div>
                                        <!-- <div><p class="text-[10px] text-gray-500 uppercase tracking-wider mb-1">CR Number</p><p class="font-medium text-sm text-gray-900">{{ application.current_unit.cr_no }}</p></div> -->
                                    </div>
                                    <h5 class="text-xs font-bold text-gray-500 uppercase mb-3">Unit Photos</h5>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="view in unitViews" :key="'curr-'+view.key" class="border border-gray-200 rounded-lg p-1.5 bg-white">
                                            <p class="text-[10px] font-semibold text-gray-500 mb-1 text-center">{{ view.label }}</p>
                                            <div class="aspect-video bg-gray-100 rounded flex items-center justify-center overflow-hidden">
                                                <img v-if="application.current_unit[`${view.key}_photo`]" :src="application.current_unit[`${view.key}_photo`]" class="object-cover w-full h-full" />
                                                <span v-else class="text-[10px] text-gray-400">No Image</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 p-5 rounded-xl border border-blue-200 shadow-sm relative">
                                    <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center z-10 shadow-sm text-gray-400">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    </div>

                                    <h4 class="font-bold text-blue-800 text-sm mb-4 border-b border-blue-200 pb-2 flex justify-between items-center">
                                        <span>Proposed Unit</span>
                                        <span class="text-xs font-normal text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">New Inspection Required</span>
                                    </h4>
                                    <div class="grid grid-cols-2 gap-y-4 gap-x-4 mb-6">
                                        <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">Make / Model</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.make }}</p></div>
                                        <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">Year</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.year }}</p></div>
                                        <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">Motor No.</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.motor_no }}</p></div>
                                        <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">Chassis No.</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.chassis_no }}</p></div>
                                        <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">Plate Number</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.plate_no }}</p></div>
                                        <!-- <div><p class="text-[10px] text-blue-500 uppercase tracking-wider mb-1">CR Number</p><p class="font-medium text-sm text-blue-900">{{ application.proposed_unit.cr_no }}</p></div> -->
                                    </div>
                                    <h5 class="text-xs font-bold text-blue-500 uppercase mb-3">Unit Photos</h5>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="view in unitViews" :key="'prop-'+view.key" class="border border-blue-100 rounded-lg p-1.5 bg-white">
                                            <p class="text-[10px] font-semibold text-blue-600 mb-1 text-center">{{ view.label }}</p>
                                            <div class="aspect-video bg-blue-100/50 rounded flex items-center justify-center overflow-hidden">
                                                <img v-if="application.proposed_unit[`${view.key}_photo`]" :src="application.proposed_unit[`${view.key}_photo`]" class="object-cover w-full h-full" />
                                                <span v-else class="text-[10px] text-blue-400">No Image</span>
                                            </div>
                                        </div>
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
                                        <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-red-200" :class="comp.status === 'resolved' ? 'text-green-600' : 'text-red-600'">{{ comp.status.toUpperCase() }}</span>
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
                                    <span class="text-xs font-bold px-2 py-1 rounded bg-white border border-orange-200" :class="flag.status === 'resolved' ? 'text-green-600' : 'text-orange-600'">{{ flag.status.toUpperCase() }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'evaluations'">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800 text-lg">Document Evaluations</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="req in application.evaluation_requirements" :key="req.id" 
                                     class="p-4 rounded-xl border-2 transition-all bg-white shadow-sm"
                                     :class="req.status === 'Approved' ? 'border-green-200' : (req.status === 'Rejected' ? 'border-red-200' : 'border-gray-200')">
                                     <div class="flex justify-between items-start mb-2">
                                         <p class="text-sm font-bold text-gray-800">{{ req.name }}</p>
                                         <span class="px-2 py-0.5 rounded text-xs font-bold"
                                               :class="req.status === 'Approved' ? 'bg-green-100 text-green-700' : (req.status === 'Rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500')">
                                             {{ req.status }}
                                         </span>
                                     </div>
                                     <div v-if="req.file_url" class="mt-2 mb-2">
                                         <button type="button" @click.stop="openDocumentModal(req.file_url)" class="text-xs text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1">
                                             <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg> View Attached Document
                                         </button>
                                     </div>
                                     <p class="text-xs text-gray-500 mt-1 truncate">Evaluator Note: {{ req.remarks }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'inspections'">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-800 text-lg">Unit Inspections History</h3>
                            </div>
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="(item, index) in inspectionsList" :key="item.id" 
                                     class="p-4 rounded-xl border-2 transition-all bg-white shadow-sm"
                                     :class="getBorderClass(item.status)">
                                     <div class="flex justify-between items-start mb-2">
                                         <p class="text-sm font-bold text-gray-800 pr-2">{{ item.name }}</p>
                                         <svg v-if="isPositiveRating(item.status)" class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                         <svg v-else-if="isNegativeRating(item.status)" class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                         <span v-else-if="item.status !== 'Pending'" class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-700 flex-shrink-0 border border-blue-200">{{ item.status }}</span>
                                         <div v-else class="w-5 h-5 rounded-full border-2 border-gray-300 flex-shrink-0"></div>
                                     </div>
                                     <p class="text-xs text-gray-500 bg-gray-50 p-2 rounded truncate mt-1">Inspector Note: {{ item.remarks }}</p>
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

            <div class="w-1/3 bg-gray-50 p-8 flex flex-col h-full flex-shrink-0 border-l border-gray-200">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <h3 class="font-bold text-xl text-gray-800">CAPO Final Review</h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Please review the applicant's franchise records, document evaluations, and the physical unit inspections utilizing the tabs on the left.
                    </p>
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-3 flex-shrink-0 mt-auto">
                    <PrimaryButton v-if="can('capo_approve_applications')" @click="showApproveModal = true" class="w-full justify-center py-3 bg-green-600 hover:bg-green-700 shadow text-sm">
                        Approve Application
                    </PrimaryButton>
                    <SecondaryButton v-if="can('capo_reject_applications')" @click="showRejectModal = true" class="w-full justify-center py-3 !text-red-600 border-red-200 hover:bg-red-50 text-sm">
                        Return to Inspector
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

        <Modal :show="showApproveModal" @close="showApproveModal = false" maxWidth="sm">
            <div class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center text-green-500 mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Approve Application?</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to approve this application as the City Anti-Pollution Officer?</p>
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
                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2 text-red-600">Return Application to Inspector</h3>
                <div class="mb-5">
                    <InputLabel value="Reason for Returning" class="text-xs mb-1" />
                    <textarea v-model="rejectForm.remarks" class="w-full border-gray-300 focus:border-red-500 rounded-md shadow-sm text-sm" rows="3" placeholder="Provide the reason for returning the application to the inspector..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <SecondaryButton @click="showRejectModal = false" :disabled="rejectForm.processing">Cancel</SecondaryButton>
                    <PrimaryButton @click="submitReject" class="bg-red-600 hover:bg-red-700" :disabled="rejectForm.processing || !rejectForm.remarks">
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