<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, reactive } from 'vue';

// Import Components
import CreateFranchiseAccountModal from '@/Components/Modals/CreateFranchiseAccountModal.vue';

const props = defineProps({
    application: Object,
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
});

// --- STATE ---
const activeTab = ref('evaluation');
const showFranchiseModal = ref(false); 
const showRequirementModal = ref(false);

const selectedFranchise = ref(null);
const selectedRequirementIndex = ref(null);

// Modal States
const showCreateAccountModal = ref(false);
const showReturnModal = ref(false);
const showRejectModal = ref(false);

const returnForm = reactive({ remarks: '' });
const rejectForm = reactive({ remarks: '' });
const requirementForm = reactive({ remarks: '' });

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    return {
        ...app,
        evaluation_requirements: app.evaluation_requirements || [],
        franchises: app.franchises || [],
        applicant: app.applicant || {
            first_name: 'Juan', last_name: 'Dela Cruz', email: 'juan@example.com',
            contact: '09171234567', tin_number: '123-456-789-000', address: 'Poblacion, Molave',
            civil_status: 'Single', birthdate: '1990-01-01'
        },
        status: app.status || 'Pending',
        type: 'Franchise Owner Account',
        reference_no: app.reference_no || 'FOA-2026-001'
    };
});

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

// --- ACTIONS ---
const confirmRejectApplication = () => {
    if (!confirm("Are you sure you want to completely REJECT this application? This action cannot be undone.")) return;
    router.post(route('admin.applications.reject', application.value.id), {
        remarks: rejectForm.remarks
    }, {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.remarks = '';
        }
    });
};

const confirmApproveApplication = () => {
    if (!confirm("Are you sure you want to APPROVE this application?")) return;
    router.post(route('admin.applications.approve', props.application.id));
};

const openRequirementModal = (index) => {
    selectedRequirementIndex.value = index;
    requirementForm.remarks = application.value.evaluation_requirements[index].remarks || '';
    showRequirementModal.value = true;
};

const closeRequirementModal = () => {
    showRequirementModal.value = false;
    selectedRequirementIndex.value = null;
    requirementForm.remarks = '';
};

const saveRequirementStatus = (status) => {
    if (selectedRequirementIndex.value !== null) {
        const evaluation = application.value.evaluation_requirements[selectedRequirementIndex.value];
        router.post(route('admin.applications.evaluate', application.value.id), {
            evaluation_id: evaluation.id, status: status, remarks: requirementForm.remarks
        }, {
            preserveScroll: true, onSuccess: () => closeRequirementModal()
        });
    }
};

const confirmReturnApplication = () => {
    router.post(route('admin.applications.return', application.value.id), {
        remarks: returnForm.remarks
    }, {
        onSuccess: () => {
            showReturnModal.value = false;
            returnForm.remarks = '';
        }
    });
};

const openFranchiseModal = (franchise) => {
    selectedFranchise.value = franchise;
    showFranchiseModal.value = true;
};

const closeFranchiseModal = () => {
    showFranchiseModal.value = false;
    selectedFranchise.value = null;
};
</script>

<template>
    <Head title="Application Details - Franchise Owner" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('admin.applications.index')" class="p-1.5 rounded-full hover:bg-gray-200 transition-colors">
                        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800 leading-tight">Application Details</h1>
                        <p class="text-xs text-gray-500">{{ application.reference_no }}</p>
                    </div>
                </div>

                <div class="flex gap-2">
                    <template v-if="application.status === 'Approved'">
                        <PrimaryButton @click="showCreateAccountModal = true" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            Create Franchise Owner Account
                        </PrimaryButton>
                    </template>
                    <template v-else-if="application.status !== 'Rejected'">
                        <button @click="showReturnModal = true" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">Return</button>
                        <button @click="showRejectModal = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Reject</button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">Approve</button>
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
                                    'bg-red-100 text-red-800': application.status === 'Rejected',
                                    'bg-amber-100 text-amber-800': application.status === 'Returned',
                                }">
                                {{ application.status }}
                            </span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.type }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar p-4">
                         <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-gray-200 border-2 border-white shadow-md flex items-center justify-center text-2xl font-bold text-gray-400 mb-3 overflow-hidden">
                                <img v-if="application.applicant.photo" :src="application.applicant.photo" class="w-full h-full object-cover" />
                                <span v-else>{{ application.applicant.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 leading-tight">
                                {{ application.applicant.first_name }} 
                                <span v-if="application.applicant.middle_name">{{ application.applicant.middle_name }}</span>
                                {{ application.applicant.last_name }}
                            </h2>
                            <p class="text-sm text-gray-500">{{ application.applicant.email }}</p>
                        </div>

                        <div class="space-y-4">
                            <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Contact No.</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.contact }}</p></div>
                             <div>
                                 <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">TIN No.</p>
                                 <p class="text-sm font-medium text-gray-800 font-mono">{{ application.applicant.tin_number }}</p>
                             </div>
                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Address</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.address }}</p></div>
                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Civil Status</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.civil_status }}</p></div>
                             <div><p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Date of Birth</p><p class="text-sm font-medium text-gray-800">{{ application.applicant.birthdate }}</p></div>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">
                    
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6">
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Evaluation</button>
                        <button @click="activeTab = 'franchises'" :class="activeTab === 'franchises' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">Franchises</button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar bg-gray-50/50 p-6">
                        
                        <div v-if="activeTab === 'evaluation'" class="space-y-6">
                            <div v-if="application.evaluation_requirements.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                    <h3 class="font-bold text-gray-800">Submitted Requirements</h3>
                                    <span class="text-xs text-gray-500">Click row to review</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" 
                                         class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors cursor-pointer group"
                                         @click="openRequirementModal(index)">
                                        <div class="flex items-center gap-4">
                                            <div class="p-2 bg-blue-50 rounded-lg text-blue-600 group-hover:bg-blue-100 transition-colors">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">{{ req.name }}</p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                     <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                        :class="{
                                                            'bg-green-100 text-green-700': req.status === 'Approved',
                                                            'bg-red-100 text-red-700': req.status === 'Rejected',
                                                            'bg-gray-100 text-gray-600': req.status === 'Pending'
                                                        }">
                                                        {{ req.status }}
                                                    </span>
                                                    <span v-if="req.remarks" class="text-xs text-red-500 italic">{{ req.remarks }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No evaluation requirements found.</p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'franchises'" class="space-y-6">
                            <div v-if="application.franchises.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                                    <h3 class="font-bold text-gray-800">Franchise Units</h3>
                                    <span class="text-xs text-gray-500">{{ application.franchises.length }} units attached</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    <div v-for="(franchise, index) in application.franchises" :key="index" 
                                         class="p-4 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition-colors group"
                                         @click="openFranchiseModal(franchise)">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center">
                                                 <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path d="M5 10l7-7m0 0l7 7m-7-7v18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                 </svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-bold text-gray-900">{{ franchise.id }}</p>
                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold uppercase rounded font-mono">{{ franchise.plate_number || 'NO PLATE' }}</span>
                                                </div>
                                                <p class="text-xs text-gray-500">{{ franchise.zone_name }} • {{ franchise.make_name }} {{ franchise.model_year }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                                <p class="text-gray-500 text-sm">No franchise units listed for this application.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showFranchiseModal" @close="closeFranchiseModal">
            <div class="p-6" v-if="selectedFranchise">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Franchise Details</h2>
                        <p class="text-sm text-gray-500">{{ selectedFranchise.zone_name }}</p>
                    </div>
                    <div class="text-right">
                         <span class="px-3 py-1 bg-blue-100 text-blue-800 font-mono font-bold rounded-lg text-sm">{{ selectedFranchise.plate_number || 'NO PLATE' }}</span>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeFranchiseModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRequirementModal" @close="closeRequirementModal">
            <div class="p-6 h-[80vh] flex flex-col" v-if="selectedRequirement">
                <div class="flex justify-between items-center mb-4 flex-none">
                    <h2 class="text-lg font-bold text-gray-900">{{ selectedRequirement.name }}</h2>
                    <button @click="closeRequirementModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-none pt-4 border-t border-gray-100">
                     <div class="mb-4">
                        <InputLabel for="req_remarks" value="Remarks (Required for Rejection)" />
                        <textarea v-model="requirementForm.remarks" rows="2" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" placeholder="Enter notes..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                         <button @click="saveRequirementStatus('Rejected')" :disabled="!requirementForm.remarks" class="px-4 py-2 bg-white border border-red-300 text-red-700 hover:bg-red-50 rounded-md text-sm font-bold shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors">Reject Document</button>
                        <PrimaryButton @click="saveRequirementStatus('Approved')" class="bg-green-600 hover:bg-green-700">Approve Document</PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <Modal :show="showReturnModal" @close="showReturnModal = false">
             <div class="p-6">
                <h2 class="text-lg font-bold text-amber-600 mb-2">Return Application</h2>
                <textarea v-model="returnForm.remarks" rows="4" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md text-sm" placeholder="Corrections needed..."></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <SecondaryButton @click="showReturnModal = false">Cancel</SecondaryButton>
                    <PrimaryButton class="bg-amber-600 hover:bg-amber-700" @click="confirmReturnApplication" :disabled="!returnForm.remarks">Confirm Return</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showRejectModal" @close="showRejectModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-red-900 mb-4">Reject Application</h2>
                <textarea v-model="rejectForm.remarks" rows="4" class="mt-1 block w-full border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md text-sm" placeholder="Reason..."></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <SecondaryButton @click="showRejectModal = false">Cancel</SecondaryButton>
                    <PrimaryButton class="bg-red-600 hover:bg-red-700" @click="confirmRejectApplication" :disabled="!rejectForm.remarks">Confirm Rejection</PrimaryButton>
                </div>
            </div>
        </Modal>

        <CreateFranchiseAccountModal 
            :show="showCreateAccountModal" 
            :application="application" 
            :barangays="barangays"
            :zones="zones"
            :unitMakes="unitMakes"
            @close="showCreateAccountModal = false" 
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #94a3b8; }
</style>