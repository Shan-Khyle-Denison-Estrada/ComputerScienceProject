<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Using your external modal
import CreateFranchiseAccountModal from '@/Components/Modals/CreateFranchiseAccountModal.vue';

const props = defineProps({
    application: Object,
    // barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
});

const activeTab = ref('applicant_details'); 
const showCreateAccountModal = ref(false);
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
    const proposedUnits = app.proposed_units || [];
    const unit = proposedUnits.length > 0 ? proposedUnits[0] : {};

    return {
        id: app.id,
        reference_no: app.reference_number || 'N/A',
        status: app.status || 'Pending', 
        applicant: {
            name: `${app.first_name || ''} ${app.last_name || ''}`,
            contact: app.contact_number || 'N/A',
            email: app.email || 'N/A',
            tin_number: app.tin_number || 'N/A',
            address: `${app.street_address || ''}, ${app.barangay || ''}, ${app.city || ''}, ${app.province || ''}`.replace(/^[,\s]+|[,\s]+$/g, '') || 'N/A',
            zone: app.zone?.description || 'N/A',
        },
        proposed_unit: {
            make: unit.make?.name || 'Not specified',
            year: unit.model_year || 'Not specified',
            motor_no: unit.motor_number || 'Not specified',
            chassis_no: unit.chassis_number || 'Not specified',
            plate_no: unit.plate_number || 'N/A',
            front_photo: unit.front_photo ? `/storage/${unit.front_photo}` : null,
            back_photo: unit.back_photo ? `/storage/${unit.back_photo}` : null,
            left_photo: unit.left_photo ? `/storage/${unit.left_photo}` : null,
            right_photo: unit.right_photo ? `/storage/${unit.right_photo}` : null
        },
        evaluations: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement ? evalDoc.requirement.name : 'Document',
            status: evalDoc.is_compliant === 1 ? 'Approved' : (evalDoc.is_compliant === 0 ? 'Rejected' : 'Pending'),
            file_url: evalDoc.file_path ? `/storage/${evalDoc.file_path}` : null,
        }))
    };
});

const openDocumentModal = (url) => { currentDocumentUrl.value = url; showDocumentModal.value = true; };
const closeDocumentModal = () => { showDocumentModal.value = false; currentDocumentUrl.value = null; };
const isImageUrl = (url) => { if (!url) return false; return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(url.split('?')[0]); };
</script>

<template>
    <Head title="Finalize Franchise Account" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Encoder Action: Finalize Franchise Account</h2>
                    <p class="text-sm text-gray-500 mt-1">Application Ref: {{ application.reference_no }}</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-semibold border bg-indigo-100 text-indigo-700 border-indigo-200">
                    {{ application.status }}
                </div>
            </div>
        </template>
        
        <div class="w-full flex flex-row gap-0 h-[calc(100vh-160px)] overflow-hidden relative">
            
            <div class="w-2/3 bg-white shadow-sm border-r border-gray-200 p-6 flex flex-col h-full flex-shrink-0">
                <div class="border-b border-gray-200 mb-6 flex gap-6 overflow-x-auto pb-1 flex-shrink-0">
                    <button @click="activeTab = 'applicant_details'" :class="activeTab === 'applicant_details' ? 'border-indigo-600 text-indigo-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Applicant Details</button>
                    <button @click="activeTab = 'proposed_unit'" :class="activeTab === 'proposed_unit' ? 'border-indigo-600 text-indigo-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Proposed Unit</button>
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-indigo-600 text-indigo-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Evaluations</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-indigo-600 text-indigo-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        
                        <div v-if="activeTab === 'applicant_details'" class="space-y-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-4">Applicant Information</h3>
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Name</p><p class="font-medium text-gray-900">{{ application.applicant.name }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">TIN Number</p><p class="font-medium text-gray-900">{{ application.applicant.tin_number }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Contact</p><p class="font-medium text-gray-900">{{ application.applicant.contact }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Zone Requested</p><p class="font-medium text-gray-900">{{ application.applicant.zone }}</p></div>
                                <div class="col-span-2"><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Address</p><p class="font-medium text-gray-900">{{ application.applicant.address }}</p></div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'proposed_unit'" class="space-y-6">
                            <div class="grid grid-cols-2 gap-y-4 gap-x-6 bg-gray-50 p-4 rounded-xl border border-gray-100 mb-6">
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Make / Model</p><p class="font-medium text-gray-900">{{ application.proposed_unit.make }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Year</p><p class="font-medium text-gray-900">{{ application.proposed_unit.year }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Motor No.</p><p class="font-medium text-gray-900">{{ application.proposed_unit.motor_no }}</p></div>
                                <div><p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Chassis No.</p><p class="font-medium text-gray-900">{{ application.proposed_unit.chassis_no }}</p></div>
                            </div>
                            <h4 class="font-bold text-gray-700 text-sm mb-3">Unit Photos</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-for="view in unitViews" :key="view.key" class="border rounded-lg p-2 bg-gray-50">
                                    <p class="text-xs font-semibold text-gray-600 mb-2 text-center">{{ view.label }}</p>
                                    <div class="aspect-video bg-gray-200 rounded flex items-center justify-center overflow-hidden">
                                        <img v-if="application.proposed_unit[`${view.key}_photo`]" :src="application.proposed_unit[`${view.key}_photo`]" class="object-cover w-full h-full" />
                                        <span v-else class="text-xs text-gray-400">No Image provided</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'evaluations'">
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="req in application.evaluations" :key="req.id" class="p-4 rounded-xl border-2 transition-all bg-white shadow-sm border-green-200">
                                     <div class="flex justify-between items-start mb-2">
                                         <p class="text-sm font-bold text-gray-800">{{ req.name }}</p>
                                         <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">Approved</span>
                                     </div>
                                     <button v-if="req.file_url" type="button" @click.stop="openDocumentModal(req.file_url)" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">View Attached Document</button>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'assessment'">
                            <div class="bg-white border rounded-xl shadow-sm overflow-hidden border-green-200 p-6">
                                <h3 class="font-bold text-gray-800 text-lg mb-2">Assessment Paid & Cleared</h3>
                                <p class="text-sm text-gray-600">This application has met all financial obligations and is fully verified.</p>
                            </div>
                        </div>

                    </Transition>
                </div>
            </div>

            <div class="w-1/3 bg-gray-50 p-8 flex flex-col h-full flex-shrink-0 border-l border-gray-200">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <h3 class="font-bold text-xl text-gray-800">Encode / Finalize</h3>
                    <p class="text-sm text-gray-500 mt-2">Create the official Franchise Owner Account, map the user, and mark the application as Completed.</p>
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-3 flex-shrink-0 mt-auto">
                    <PrimaryButton @click="showCreateAccountModal = true" class="w-full justify-center py-3 bg-indigo-600 hover:bg-indigo-700 shadow text-sm">
                        Finalize Account Generation
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="showDocumentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900 bg-opacity-75" @click="closeDocumentModal"></div>
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-5xl h-[90vh] flex flex-col">
                    <div class="px-6 py-4 border-b flex justify-between bg-gray-50"><h3 class="font-bold">Viewer</h3><button @click="closeDocumentModal">X</button></div>
                    <div class="flex-1 bg-gray-200 relative flex items-center justify-center overflow-auto p-4">
                        <img v-if="isImageUrl(currentDocumentUrl)" :src="currentDocumentUrl" class="max-w-full max-h-full object-contain" />
                        <iframe v-else :src="currentDocumentUrl" class="w-full h-full bg-white shadow-sm" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
        </Transition>

        <CreateFranchiseAccountModal 
            :show="showCreateAccountModal" 
            :application="props.application" 
            :barangays="barangays"
            :zones="zones"
            :unitMakes="unitMakes"
            @close="showCreateAccountModal = false" 
        />

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>