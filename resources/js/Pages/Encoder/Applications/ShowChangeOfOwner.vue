<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import ChangeOfOwnerModal from '@/Components/Modals/ChangeOfOwnerModal.vue';

const props = defineProps({ application: Object });

const activeTab = ref('operator_comparison'); 
const showChangeOwnerModal = ref(false);
const showDocumentModal = ref(false);
const currentDocumentUrl = ref(null);

const application = computed(() => {
    const app = props.application || {};
    const franchise = app.franchise || {};
    const currentOwnership = franchise.current_ownership || {};
    const currentUser = currentOwnership.new_owner?.user || {};

    return {
        id: app.id,
        reference_no: app.reference_number || 'N/A',
        status: app.status || 'Pending',
        current_owner: {
            name: `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim() || 'N/A',
            tin_number: currentOwnership.new_owner?.tin_number || 'N/A',
            contact: currentUser.contact_number || 'N/A'
        },
        proposed_owner: {
            name: `${app.first_name || ''} ${app.last_name || ''}`.trim() || 'N/A',
            tin_number: app.tin_number || 'N/A',
            contact: app.contact_number || 'N/A'
        },
        evaluations: (app.evaluations || []).map(evalDoc => ({
            id: evalDoc.id,
            name: evalDoc.requirement?.name || 'Document',
            status: evalDoc.is_compliant === 1 ? 'Approved' : 'Pending',
            file_url: evalDoc.file_path ? `/storage/${evalDoc.file_path}` : null,
        }))
    };
});

const openDocumentModal = (url) => { currentDocumentUrl.value = url; showDocumentModal.value = true; };
const closeDocumentModal = () => { showDocumentModal.value = false; currentDocumentUrl.value = null; };
const isImageUrl = (url) => { if (!url) return false; return /\.(jpeg|jpg|gif|png|webp|svg|bmp)$/i.test(url.split('?')[0]); };
</script>

<template>
    <Head title="Finalize Change of Owner" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Encoder Action: Finalize Change of Owner</h2>
                    <p class="text-sm text-gray-500 mt-1">Application Ref: {{ application.reference_no }}</p>
                </div>
                <div class="px-3 py-1 rounded-full text-sm font-semibold border bg-purple-100 text-purple-700 border-purple-200">
                    {{ application.status }}
                </div>
            </div>
        </template>
        
        <div class="w-full flex flex-row gap-0 h-[calc(100vh-160px)] overflow-hidden relative">
            <div class="w-2/3 bg-white shadow-sm border-r border-gray-200 p-6 flex flex-col h-full flex-shrink-0">
                <div class="border-b border-gray-200 mb-6 flex gap-6 overflow-x-auto pb-1 flex-shrink-0">
                    <button @click="activeTab = 'operator_comparison'" :class="activeTab === 'operator_comparison' ? 'border-purple-600 text-purple-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Operator Comparison</button>
                    <button @click="activeTab = 'evaluations'" :class="activeTab === 'evaluations' ? 'border-purple-600 text-purple-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Evaluations</button>
                    <button @click="activeTab = 'assessment'" :class="activeTab === 'assessment' ? 'border-purple-600 text-purple-600 border-b-2 font-semibold' : 'text-gray-500 font-medium hover:text-gray-700'" class="pb-2 whitespace-nowrap transition-colors">Assessment (Paid)</button>
                </div>

                <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 pb-4">
                    <Transition name="fade" mode="out-in">
                        <div v-if="activeTab === 'operator_comparison'" class="grid grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-sm">
                                <h4 class="font-bold text-gray-600 border-b pb-2 mb-4 uppercase tracking-wide text-xs">Current Owner Details</h4>
                                <p class="text-xs text-gray-500 uppercase">Name</p><p class="font-bold text-gray-900 mb-3">{{ application.current_owner.name }}</p>
                                <p class="text-xs text-gray-500 uppercase">TIN Number</p><p class="font-bold text-gray-900 mb-3">{{ application.current_owner.tin_number }}</p>
                                <p class="text-xs text-gray-500 uppercase">Contact</p><p class="font-bold text-gray-900">{{ application.current_owner.contact }}</p>
                            </div>
                            <div class="bg-purple-50 p-6 rounded-xl border border-purple-200 shadow-sm relative overflow-hidden">
                                <div class="absolute top-0 right-0 bg-purple-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg">PROPOSED</div>
                                <h4 class="font-bold text-purple-700 border-b border-purple-200 pb-2 mb-4 uppercase tracking-wide text-xs">New Owner Details</h4>
                                <p class="text-xs text-purple-500 uppercase">Name</p><p class="font-bold text-purple-900 mb-3">{{ application.proposed_owner.name }}</p>
                                <p class="text-xs text-purple-500 uppercase">TIN Number</p><p class="font-bold text-purple-900 mb-3">{{ application.proposed_owner.tin_number }}</p>
                                <p class="text-xs text-purple-500 uppercase">Contact</p><p class="font-bold text-purple-900">{{ application.proposed_owner.contact }}</p>
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
                    <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mb-4">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    </div>
                    <h3 class="font-bold text-xl text-gray-800">Encode / Finalize</h3>
                    <p class="text-sm text-gray-500 mt-2">Finalize the transfer of ownership securely to officially map the franchise to the new user.</p>
                </div>

                <div class="pt-4 border-t border-gray-200 space-y-3 flex-shrink-0 mt-auto">
                    <PrimaryButton @click="showChangeOwnerModal = true" class="w-full justify-center py-3 bg-purple-600 hover:bg-purple-700 shadow text-sm">
                        Finalize Ownership Transfer
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

        <ChangeOfOwnerModal 
            :show="showChangeOwnerModal" 
            :application="props.application" 
            @close="showChangeOwnerModal = false" 
        />

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>