<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ChangeOfUnitModal from '@/Components/Modals/ChangeOfUnitModal.vue';

const props = defineProps({ 
    application: Object, 
    inspectionItems: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] } 
});

const activeTab = ref('unit_comparison'); 
const showChangeUnitModal = ref(false);
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
    const currentUnitData = franchise.current_active_unit?.new_unit || {};
    const proposedUnit = app.proposed_units?.[0] || {};

    return {
        id: app.id,
        reference_no: app.reference_number || 'N/A',
        status: app.status || 'Pending',
        current_unit: {
            make: currentUnitData.make?.name || 'N/A',
            motor_no: currentUnitData.motor_number || 'N/A',
            chassis_no: currentUnitData.chassis_number || 'N/A',
            plate_no: currentUnitData.plate_number || 'N/A',
            year: currentUnitData.model_year || 'N/A',
        },
        proposed_unit: {
            make: proposedUnit.make?.name || 'N/A',
            motor_no: proposedUnit.motor_number || 'N/A',
            chassis_no: proposedUnit.chassis_number || 'N/A',
            plate_no: proposedUnit.plate_number || 'N/A',
            year: proposedUnit.model_year || 'N/A',
            front_photo: proposedUnit.front_photo ? `/storage/${proposedUnit.front_photo}` : null,
            back_photo: proposedUnit.back_photo ? `/storage/${proposedUnit.back_photo}` : null,
            left_photo: proposedUnit.left_photo ? `/storage/${proposedUnit.left_photo}` : null,
            right_photo: proposedUnit.right_photo ? `/storage/${proposedUnit.right_photo}` : null
        },
        evaluations: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement?.name || 'Document',
            status: evalDoc.is_compliant === 1 ? 'Approved' : 'Pending',
            file_url: evalDoc.file_path ? `/storage/${evalDoc.file_path}` : null,
        }))
    };
});

const inspectionsList = computed(() => {
    const proposedUnit = props.application?.proposed_units?.[0] || {};
    const unitInspections = proposedUnit.unit_inspections || [];
    return props.inspectionItems.map(item => {
        const found = unitInspections.find(i => i.inspection_item_id === item.id);
        return { 
            name: item.name, 
            status: found ? found.rating : 'Pending', 
            remarks: found?.remarks || '' 
        };
    });
});

const openDocumentModal = (url) => { currentDocumentUrl.value = url; showDocumentModal.value = true; };
const closeDocumentModal = () => { showDocumentModal.value = false; currentDocumentUrl.value = null; };
const isImageUrl = (url) => { if (!url) return false; return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(url.split('?')[0]); };
</script>

<template>
    <Head title="Finalize Change of Unit" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Encoder Action: Finalize Change of Unit</h2>
                    <p class="text-sm text-gray-500 mt-1">Application Ref: {{ application.reference_no }}</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-semibold border bg-emerald-100 text-emerald-700 border-emerald-200">
                    {{ application.status }}
                </div>
            </div>
        </template>
        
        <div class="w-full flex flex-row gap-0 h-[calc(100vh-160px)] overflow-hidden relative">
            <div class="w-2/3 bg-white shadow-sm border-r border-gray-200 p-6 flex flex-col h-full flex-shrink-0">
                <div class="border-b border-gray-200 mb-6 flex gap-6 overflow-x-auto pb-1 flex-shrink-0">
                    <button @click="activeTab = 'unit_comparison'" :class="activeTab === 'unit_comparison' ? 'border-emerald-600 text-emerald-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Unit Comparison</button>
                    <button @click="activeTab = 'inspections'" :class="activeTab === 'inspections' ? 'border-emerald-600 text-emerald-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Inspections</button>
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-emerald-600 text-emerald-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Evaluations</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-emerald-600 text-emerald-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment (Paid)</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        <div v-if="activeTab === 'unit_comparison'" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-sm">
                                    <h4 class="font-bold text-gray-600 border-b pb-2 mb-4 uppercase tracking-wide text-xs">Current Unit</h4>
                                    <p class="text-xs text-gray-500 uppercase">Make / Year</p><p class="font-bold text-gray-900 mb-3">{{ application.current_unit.make }} - {{ application.current_unit.year }}</p>
                                    <p class="text-xs text-gray-500 uppercase">Motor No.</p><p class="font-bold text-gray-900 mb-3">{{ application.current_unit.motor_no }}</p>
                                    <p class="text-xs text-gray-500 uppercase">Chassis No.</p><p class="font-bold text-gray-900">{{ application.current_unit.chassis_no }}</p>
                                </div>
                                <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-200 shadow-sm relative overflow-hidden">
                                    <div class="absolute top-0 right-0 bg-emerald-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg">PROPOSED</div>
                                    <h4 class="font-bold text-emerald-700 border-b border-emerald-200 pb-2 mb-4 uppercase tracking-wide text-xs">New Unit Details</h4>
                                    <p class="text-xs text-emerald-600 uppercase">Make / Year</p><p class="font-bold text-emerald-900 mb-3">{{ application.proposed_unit.make }} - {{ application.proposed_unit.year }}</p>
                                    <p class="text-xs text-emerald-600 uppercase">Motor No.</p><p class="font-bold text-emerald-900 mb-3">{{ application.proposed_unit.motor_no }}</p>
                                    <p class="text-xs text-emerald-600 uppercase">Chassis No.</p><p class="font-bold text-emerald-900">{{ application.proposed_unit.chassis_no }}</p>
                                </div>
                            </div>
                            
                            <h4 class="font-bold text-gray-700 text-sm mb-3 mt-4 border-t pt-4">Proposed Unit Photos</h4>
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

                        <div v-else-if="activeTab === 'inspections'">
                            <div class="grid grid-cols-1 gap-4">
                                <div v-for="item in inspectionsList" :key="item.name" class="p-4 rounded-xl border border-gray-200 bg-white">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-sm font-bold text-gray-800">{{ item.name }}</p>
                                        <span class="px-2 py-0.5 rounded text-xs font-bold"
                                              :class="item.status === 'Pending' ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700'">
                                            {{ item.status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ item.remarks }}</p>
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
                                     <button v-if="req.file_url" @click.stop="openDocumentModal(req.file_url)" class="text-xs text-blue-600 hover:text-blue-800 hover:underline">View Attached Document</button>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="activeTab === 'assessment'" class="bg-white border rounded-xl shadow-sm border-green-200 p-6">
                            <h3 class="font-bold text-gray-800 text-lg mb-2">Assessment Paid & Cleared</h3>
                            <p class="text-sm text-gray-600">The transfer fees have been settled.</p>
                        </div>
                    </Transition>
                </div>
            </div>

            <div class="w-1/3 bg-gray-50 p-8 flex flex-col h-full flex-shrink-0 border-l border-gray-200">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </div>
                    <h3 class="font-bold text-xl text-gray-800">Encode / Finalize</h3>
                    <p class="text-sm text-gray-500 mt-2">Finalize the swapping of units securely, generate the history log, and set the application to Completed.</p>
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-3 flex-shrink-0 mt-auto">
                    <PrimaryButton @click="showChangeUnitModal = true" class="w-full justify-center py-3 bg-emerald-600 hover:bg-emerald-700 shadow text-sm">
                        Finalize Unit Swap
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

        <ChangeOfUnitModal 
            :show="showChangeUnitModal" 
            :application="props.application" 
            :unitMakes="unitMakes"
            @close="showChangeUnitModal = false" 
        />

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>