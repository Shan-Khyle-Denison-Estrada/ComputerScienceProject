<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

import ChangeOfOwnerModal from '@/Components/Modals/ChangeOfOwnerModal.vue';

const props = defineProps({
    application: Object,
});

// --- STATE ---
const activeTab = ref('operator_comparison'); 
const showChangeOwnerModal = ref(false); 

// Evaluation State
const showRequirementModal = ref(false);
const selectedRequirementIndex = ref(null);
const requirementForm = reactive({ remarks: '' });

// Application Rejection/Return/Approve State
const showRejectModal = ref(false);
const rejectForm = reactive({ remarks: '', processing: false });

const showReturnModal = ref(false);
const returnForm = reactive({ remarks: '', processing: false });

const showApproveModal = ref(false);
const approveProcessing = ref(false);

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    
    // Safely extract franchise and owner details
    const franchise = app.franchise || {};
    const zone = app.zone || {};
    
    const currentOwnership = franchise.current_ownership || {};
    const currentOperator = currentOwnership.new_owner || {};
    const currentUser = currentOperator.user || {};

    // Map Assessment and Payments
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
        type: app.application_type || 'Change of Owner',
        status: app.status || 'Pending', 
        reference_no: app.reference_number || 'N/A',
        remarks: app.remarks || null,
        
        franchise_details: {
            id: franchise.id,
            zone: franchise.zone?.description || app.zone?.description || 'N/A',
            date_issued: franchise.date_issued ? new Date(franchise.date_issued).toLocaleDateString() : 'N/A',
            status: franchise.status || 'N/A',
            mtfrb_case_no: franchise.mtfrb_case_no || 'N/A',
        },
        
        // Mapped Current Owner Details (The person letting go of the franchise)
        current_owner: {
            first_name: currentUser.first_name || 'Not specified',
            middle_name: currentUser.middle_name || '',
            last_name: currentUser.last_name || 'Not specified',
            contact: currentUser.contact_number || 'N/A',
            email: currentUser.email || 'N/A',
            tin_number: currentOperator.tin_number || 'N/A', 
            address: `${currentUser.street_address || ''}, ${currentUser.barangay || ''}, ${currentUser.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
        },
        
        // Mapped Proposed New Owner Details for the View Display
        proposed_owner: {
            first_name: app.first_name || 'Not specified',
            middle_name: app.middle_name || '',
            last_name: app.last_name || 'Not specified',
            contact: app.contact_number || 'N/A',
            email: app.email || 'N/A',
            tin_number: app.tin_number || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
        },

        // NEW: Raw values specifically for auto-filling the Finalization Modal form
        raw_proposed_owner: {
            first_name: app.first_name || '',
            middle_name: app.middle_name || '',
            last_name: app.last_name || '',
            contact_number: app.contact_number || '',
            email: app.email || '',
            tin_number: app.tin_number || '',
            street_address: app.street_address || '',
            barangay: app.barangay || '',
            city: app.city || 'Zamboanga City',
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

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});


// --- ACTIONS ---
const openApproveModal = () => {
    showApproveModal.value = true;
};

const closeApproveModal = () => {
    showApproveModal.value = false;
};

const submitApproval = () => {
    approveProcessing.value = true;
    router.post(route('admin.applications.change-of-owner.approve', application.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            closeApproveModal();
        },
        onFinish: () => {
            approveProcessing.value = false;
        }
    });
};

const openReturnModal = () => {
    returnForm.remarks = '';
    returnForm.processing = false;
    showReturnModal.value = true;
};

const closeReturnModal = () => {
    showReturnModal.value = false;
};

const submitReturn = () => {
    if (!returnForm.remarks.trim()) return; 
    
    returnForm.processing = true;
    router.post(route('admin.applications.change-of-owner.return', application.value.id), {
        remarks: returnForm.remarks
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeReturnModal();
        },
        onFinish: () => {
            returnForm.processing = false;
        }
    });
};

const openRejectModal = () => {
    rejectForm.remarks = '';
    rejectForm.processing = false;
    showRejectModal.value = true;
};

const closeRejectModal = () => {
    showRejectModal.value = false;
};

const submitRejection = () => {
    if (!rejectForm.remarks.trim()) return; 
    
    rejectForm.processing = true;
    router.post(route('admin.applications.change-of-owner.reject', application.value.id), {
        remarks: rejectForm.remarks
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
        },
        onFinish: () => {
            rejectForm.processing = false;
        }
    });
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
    setTimeout(() => {
        selectedRequirementIndex.value = null;
        requirementForm.remarks = '';
    }, 200);
};

const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value === null) return;
    
    const evaluation = application.value.evaluation_requirements[selectedRequirementIndex.value];
    
    router.post(route('admin.applications.change-of-owner.evaluate', application.value.id), {
        evaluation_id: evaluation.id,
        status: status,
        remarks: requirementForm.remarks || 'Pending Review'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeRequirementModal();
        }
    });
};

</script>

<template>
    <Head title="Application Details - Change of Owner" />

    <AuthenticatedLayout>
        <div class="h-[calc(100vh-100px)] flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Change of Owner Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Completed'">
                        <span class="px-4 py-2 bg-green-100 text-green-800 text-xs font-bold uppercase rounded-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Application Finalized
                        </span>
                    </template>
                    <template v-else-if="application.status === 'Approved'">
                        <PrimaryButton @click="showChangeOwnerModal = true" class="flex items-center gap-2">
                            Finalize Ownership Change
                        </PrimaryButton>
                    </template>
                    <template v-else-if="!['Rejected', 'Returned'].includes(application.status)">
                        <button @click="openReturnModal" class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold uppercase rounded-lg transition-colors">Return</button>
                        <button @click="openRejectModal" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase rounded-lg transition-colors">Reject</button>
                        <button @click="openApproveModal" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase rounded-lg transition-colors">Approve</button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-w-0">  
                
                <div class="flex items-center justify-between border-b border-gray-200 px-4 pt-2 bg-gray-50">
                    <div class="flex items-center overflow-x-auto custom-scrollbar">
                        <button @click="activeTab = 'operator_comparison'" :class="activeTab === 'operator_comparison' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Operator Comparison</button>
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-colors">Receipt & Payment</button>
                    </div>

                    <div class="hidden md:flex items-center gap-2 mb-1">
                        <span class="text-xs text-gray-500">Target Franchise:</span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded bg-gray-200 text-gray-700 font-mono tracking-wide">#{{ application.franchise_details.id }}</span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-blue-100 text-blue-800">{{ application.status }}</span>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6 custom-scrollbar">
                    
                    <div v-if="activeTab === 'operator_comparison'" class="space-y-6">
                        
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-6">
                            <h3 class="font-bold text-blue-900 mb-6 flex items-center gap-2 text-lg">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                Change of Owner Request Overview
                            </h3>
                            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 relative">
                                
                                <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
                                    <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-4 border-b pb-2 flex justify-between items-center">
                                        <span>Current Owner</span>
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

                                <div class="bg-white p-5 rounded-lg border-2 border-blue-300 shadow-sm relative overflow-hidden">
                                    <div class="absolute top-0 right-0 bg-blue-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg tracking-wider">PROPOSED</div>
                                    <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider mb-4 border-b border-blue-100 pb-2 flex justify-between items-center">
                                        <span>New Target Owner</span>
                                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Full Name</span><span class="font-bold text-gray-900">{{ application.proposed_owner.first_name }} {{ application.proposed_owner.middle_name }} {{ application.proposed_owner.last_name }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Contact Number</span><span class="font-bold text-gray-900">{{ application.proposed_owner.contact }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">Email Address</span><span class="font-bold text-gray-900">{{ application.proposed_owner.email }}</span></div>
                                        <div class="flex justify-between text-sm"><span class="text-gray-500">TIN Number</span><span class="font-bold text-gray-900">{{ application.proposed_owner.tin_number }}</span></div>
                                        <div class="flex flex-col text-sm pt-2 border-t border-gray-100">
                                            <span class="text-gray-500 mb-1">Registered Address</span>
                                            <span class="font-bold text-gray-900">{{ application.proposed_owner.address }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm mt-4 flex gap-4 items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-blue-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Franchise Status Context</h4>
                                <p class="text-sm text-gray-600 mt-1">This application targets Franchise <span class="font-bold text-gray-800">#{{ application.franchise_details.id }}</span> in <span class="font-bold text-gray-800">{{ application.franchise_details.zone }}</span>. It is currently <span class="font-bold uppercase text-[10px] bg-gray-100 px-2 py-0.5 rounded">{{ application.franchise_details.status }}</span>.</p>
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
                            <p class="text-sm text-gray-600">Are you sure you want to approve this application? Approving it confirms that all requirements and payments are compliant.</p>
                            <p class="text-sm text-gray-500 italic">Note: After approving, you will still need to click "Finalize Ownership Change" to officially update the franchise's database records.</p>
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

        <ChangeOfOwnerModal :show="showChangeOwnerModal" :application="application" @close="showChangeOwnerModal = false" />

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