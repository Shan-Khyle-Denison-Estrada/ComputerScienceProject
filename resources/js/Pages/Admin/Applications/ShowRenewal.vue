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
});

// --- STATE ---
const activeTab = ref('evaluation');
const showInspectionModal = ref(false);
const showRequirementModal = ref(false);

const selectedItemIndex = ref(null);
const selectedRequirementIndex = ref(null);

const showReturnModal = ref(false);
const showRejectModal = ref(false);

const inspectionForm = reactive({ item: '', status: '', remarks: '', options: [] });
const returnForm = reactive({ remarks: '' });
const rejectForm = reactive({ remarks: '' });
const requirementForm = reactive({ remarks: '' });

// --- COMPUTED PROPERTIES ---
const application = computed(() => {
    const app = props.application || {};
    return {
        ...app,
        type: 'Renewal',
        status: app.status || 'Pending',
        reference_no: app.reference_no || 'REN-2026-089',
        applicant: app.applicant || {
            first_name: 'Maria', last_name: 'Santos', email: 'maria@example.com',
            contact: '09181234567', address: 'Mabini St., Molave', civil_status: 'Married', birthdate: '1985-05-15'
        },
        evaluation_requirements: app.evaluation_requirements || [
            { id: 1, name: 'Barangay Clearance', status: 'Pending', remarks: '' }
        ],
        // DUMMY DATA FOR RENEWAL
        inspection_requirements: app.inspection_requirements || [
            { id: 1, name: 'Signal Lights', status: 'Pending', remarks: '', options: 'Working, Defective' },
            { id: 2, name: 'Brakes', status: 'Good', remarks: 'Newly replaced pads', options: 'Good, Needs Repair' }
        ],
        complaints: app.complaints || [
            { id: 1, nature_of_complaint: 'Overcharging', status: 'Pending', incident_date: '2026-01-10', incident_time: '14:30', remarks: 'Driver asked for 50 pesos instead of regular fare.', complainant_contact: '09998887777', pick_up_point: 'Market', drop_off_point: 'Plaza' }
        ],
        receipt: app.receipt || {
            or_number: 'OR-992384', date: '2026-02-15', total_amount_due: 1250,
            particulars: [ { name: 'Franchise Renewal Fee', amount: 1000 }, { name: 'Inspection Fee', amount: 250 } ]
        }
    };
});

const selectedRequirement = computed(() => {
    if (selectedRequirementIndex.value === null) return null;
    return application.value.evaluation_requirements[selectedRequirementIndex.value];
});

// --- ACTIONS ---
const confirmRejectApplication = () => {
    if (!confirm("Reject this renewal application?")) return;
    router.post(route('admin.applications.reject', application.value.id), { remarks: rejectForm.remarks });
};

const confirmApproveApplication = () => {
    if (!confirm("Are you sure you want to APPROVE this renewal?")) return;
    router.post(route('admin.applications.approve', application.value.id));
};

const resolveComplaint = (index) => {
    if (confirm('Are you sure you want to mark this complaint as Resolved?')) {
        application.value.complaints[index].status = 'Resolved';
    }
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
        application.value.evaluation_requirements[selectedRequirementIndex.value].status = status;
        application.value.evaluation_requirements[selectedRequirementIndex.value].remarks = requirementForm.remarks;
        closeRequirementModal();
    }
};

const openInspectionModal = (index, itemData) => {
    selectedItemIndex.value = index;
    inspectionForm.item = itemData.name;
    inspectionForm.status = itemData.status === 'Pending' ? '' : itemData.status;
    inspectionForm.remarks = itemData.remarks;
    inspectionForm.options = itemData.options ? itemData.options.split(',').map(o => o.trim()) : ['Pass', 'Fail'];
    showInspectionModal.value = true;
};

const closeInspectionModal = () => {
    showInspectionModal.value = false;
    selectedItemIndex.value = null;
};

const saveInspection = () => {
    if (selectedItemIndex.value !== null) {
         application.value.inspection_requirements[selectedItemIndex.value].status = inspectionForm.status;
         application.value.inspection_requirements[selectedItemIndex.value].remarks = inspectionForm.remarks;
    }
    closeInspectionModal();
};

const formatCurrency = (value) => {
    if(!value) return '₱0.00';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
};
</script>

<template>
    <Head title="Application Details - Renewal" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col overflow-hidden" :key="application.id">
            
            <div class="flex-none mb-3 flex items-center justify-between">
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
                    <template v-if="application.status !== 'Approved' && application.status !== 'Rejected'">
                        <button @click="showReturnModal = true" class="px-4 py-2 bg-amber-500 text-white text-xs font-bold uppercase rounded-lg">Return</button>
                        <button @click="showRejectModal = true" class="px-4 py-2 bg-red-600 text-white text-xs font-bold uppercase rounded-lg">Reject</button>
                        <button @click="confirmApproveApplication" class="px-4 py-2 bg-green-600 text-white text-xs font-bold uppercase rounded-lg">Approve Renewal</button>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex gap-4 h-full min-h-0">
                <div class="w-80 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden shrink-0">
                    <div class="bg-gray-50 border-b border-gray-100 p-4">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wide bg-blue-100 text-blue-800">{{ application.status }}</span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-200 px-2 py-0.5 rounded">{{ application.type }}</span>
                        </div>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-4">
                         <div class="flex flex-col items-center text-center mb-6">
                            <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-2xl font-bold text-gray-400 mb-3 overflow-hidden">
                                <span>{{ application.applicant.first_name.charAt(0) }}</span>
                            </div>
                            <h2 class="text-lg font-bold">{{ application.applicant.first_name }} {{ application.applicant.last_name }}</h2>
                        </div>
                    </div>
                </div>

                <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-w-0">
                    
                    <div class="flex items-center gap-6 border-b border-gray-100 px-6">
                        <button @click="activeTab = 'evaluation'" :class="activeTab === 'evaluation' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Evaluation</button>
                        <button @click="activeTab = 'inspection'" :class="activeTab === 'inspection' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Inspection</button>
                        <button @click="activeTab = 'complaints'" :class="activeTab === 'complaints' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Complaints / History</button>
                        <button @click="activeTab = 'receipt'" :class="activeTab === 'receipt' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Receipt & Payment</button>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-gray-50/50 p-6">
                        
                        <div v-if="activeTab === 'evaluation'" class="space-y-6">
                            <div v-for="(req, index) in application.evaluation_requirements" :key="req.id" @click="openRequirementModal(index)" class="p-4 bg-white border border-gray-200 rounded flex justify-between cursor-pointer">
                                <p class="font-bold">{{ req.name }}</p>
                                <span class="text-sm font-bold">{{ req.status }}</span>
                            </div>
                        </div>

                        <div v-if="activeTab === 'inspection'" class="space-y-6">
                            <div v-for="(item, index) in application.inspection_requirements" :key="item.id" @click="openInspectionModal(index, item)" class="p-4 bg-white border border-gray-200 rounded flex justify-between cursor-pointer">
                                <div><p class="font-bold">{{ item.name }}</p><p class="text-xs text-gray-500">{{ item.remarks || 'No remarks' }}</p></div>
                                <span class="font-bold">{{ item.status }}</span>
                            </div>
                        </div>

                        <div v-if="activeTab === 'complaints'" class="space-y-4">
                            <div v-for="(complaint, index) in application.complaints" :key="complaint.id" class="bg-white p-5 border border-gray-200 rounded">
                                <div class="flex justify-between">
                                    <h3 class="font-bold text-gray-900">{{ complaint.nature_of_complaint }}</h3>
                                    <button v-if="complaint.status === 'Pending'" @click="resolveComplaint(index)" class="text-xs text-blue-600">Mark as Resolved</button>
                                </div>
                                <p class="text-sm text-gray-600 my-2">{{ complaint.remarks }}</p>
                            </div>
                        </div>

                         <div v-if="activeTab === 'receipt'" class="bg-white p-6 border border-gray-200 rounded">
                            <h3 class="font-bold">OR #{{ application.receipt.or_number }}</h3>
                            <div v-for="item in application.receipt.particulars" :key="item.name" class="flex justify-between mt-2">
                                <span>{{ item.name }}</span>
                                <span>{{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between font-bold">
                                <span>Total</span>
                                <span>{{ formatCurrency(application.receipt.total_amount_due) }}</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showRequirementModal" @close="closeRequirementModal">
            <div class="p-6">
                <h2 class="text-lg font-bold mb-4">Evaluate Document</h2>
                <textarea v-model="requirementForm.remarks" class="w-full border-gray-300 rounded"></textarea>
                <div class="flex justify-end gap-2 mt-4">
                    <PrimaryButton @click="saveRequirementStatus('Approved')">Approve</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="showInspectionModal" @close="closeInspectionModal">
            <div class="p-6">
                <h2 class="text-lg font-bold mb-4">Update Inspection: {{ inspectionForm.item }}</h2>
                <select v-model="inspectionForm.status" class="w-full border-gray-300 rounded mb-4">
                    <option v-for="opt in inspectionForm.options" :key="opt" :value="opt">{{ opt }}</option>
                </select>
                <TextInput v-model="inspectionForm.remarks" class="w-full" placeholder="Remarks..." />
                <div class="flex justify-end mt-4">
                    <PrimaryButton @click="saveInspection">Save</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>