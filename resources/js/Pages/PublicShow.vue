<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    franchise: Object,
    // [!code ++] Receive the dynamic list from controller
    natureOfComplaints: {
        type: Array,
        default: () => []
    }
});

// --- HELPER: Get Color ---
function getHexFromColorString(colorString) {
    if (!colorString) return '#3b82f6';
    if (colorString.startsWith('#')) return colorString;
    try {
        const ctx = document.createElement("canvas").getContext("2d");
        ctx.fillStyle = colorString;
        return ctx.fillStyle === '#000000' && colorString.toLowerCase() !== 'black' 
            ? '#3b82f6' 
            : ctx.fillStyle;
    } catch (e) {
        return '#3b82f6';
    }
}

const zoneColor = computed(() => getHexFromColorString(props.franchise.zone?.color));

// Helper for Driver Name
const getDriverName = (driver) => {
    if (driver.user) return `${driver.user.last_name}, ${driver.user.first_name}`;
    if (driver.first_name && driver.last_name) return `${driver.last_name}, ${driver.first_name}`;
    return 'Unknown Name';
};

const currentUnit = computed(() => props.franchise.current_active_unit?.new_unit);
const currentOwner = computed(() => props.franchise.current_ownership?.new_owner);

// --- COMPLAINT LOGIC ---
const showComplaintModal = ref(false);
const complaintSuccess = ref(false);

// [!code --] Removed hardcoded array
// const complaintOptions = [ ... ];

const form = useForm({
    franchise_id: props.franchise.id,
    nature_of_complaint: '',
    remarks: '',
    fare_collected: '',
    pick_up_point: '',
    drop_off_point: '',
    complainant_contact: '',
    incident_date: new Date().toISOString().split('T')[0],
    incident_time: new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }),
});

const submitComplaint = () => {
    form.post(route('complaints.store'), {
        preserveScroll: true,
        onSuccess: () => {
            complaintSuccess.value = true;
            form.reset();
            setTimeout(() => {
                showComplaintModal.value = false;
                complaintSuccess.value = false;
            }, 3000);
        }
    });
};
</script>

<template>
    <Head :title="`Franchise ${franchise.id} - Public Record`" />

    <div class="h-screen w-screen bg-gray-100 overflow-hidden relative font-sans text-gray-900 selection:bg-blue-100 selection:text-blue-900 flex flex-col items-center justify-center">
        
        <div class="absolute top-0 left-0 w-full h-[45%] overflow-hidden z-0">
            <div class="absolute inset-0 transition-colors duration-700 ease-in-out" 
                 :style="{ backgroundColor: zoneColor }">
            </div>
            <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/10 to-black/50"></div>
        </div>

        <div class="relative z-10 w-full max-w-md px-4 h-full max-h-screen flex flex-col justify-center">
            
            <div class="text-center text-white mb-4 shrink-0">
                <div class="uppercase tracking-widest text-[10px] font-bold opacity-80">Official Public Record</div>
                <h1 class="text-3xl font-black tracking-tight drop-shadow-md">
                    FRANCHISE #{{ franchise.id }}
                </h1>
                <div class="mt-1 inline-flex items-center gap-2 bg-white/20 backdrop-blur-md px-3 py-0.5 rounded-full border border-white/30 text-[10px] font-bold uppercase tracking-wide shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    {{ franchise.status }}
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col w-full max-h-[75vh]">
                
                <div class="h-1.5 w-full shrink-0" :style="{ backgroundColor: zoneColor }"></div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6 scrollbar-thin scrollbar-thumb-gray-200">
                    
                    <div class="text-center">
                        <div class="text-[10px] uppercase text-gray-400 font-bold tracking-widest mb-1">Zone Assignment</div>
                        <h2 class="text-xl font-black uppercase mb-1 leading-tight" :style="{ color: zoneColor }">
                            {{ franchise.zone?.description || 'No Zone Assigned' }}
                        </h2>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="text-[10px] uppercase text-gray-500 mb-0.5">Plate Number</div>
                            <div class="text-4xl font-mono font-bold text-gray-900 tracking-tighter">
                                {{ currentUnit ? currentUnit.plate_number : 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500 font-medium mt-1">
                                {{ currentUnit?.make?.name }} • {{ currentUnit?.model_year }}
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm relative overflow-hidden">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-[10px] uppercase text-gray-500 font-bold mb-1">Franchise Owner</div>
                                <div class="font-bold text-gray-900 text-base leading-tight">
                                    {{ currentOwner ? `${currentOwner.user.last_name}, ${currentOwner.user.first_name} ${currentOwner.user.middle_name || ''}` : 'Unknown Owner' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1 max-w-[200px] leading-snug">
                                    {{ currentOwner?.user.contact_number || 'No Contact Number Recorded' }}
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                <div class="text-[10px] font-mono text-gray-400 uppercase">TIN</div>
                                <div class="text-xs font-mono font-bold text-gray-600">
                                    {{ currentOwner ? currentOwner.tin_number : '---' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-3 sticky top-0 bg-white z-10 py-1">
                            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wide">Authorized Drivers</h3>
                        </div>

                        <div v-if="franchise.driver_assignments && franchise.driver_assignments.length > 0" class="space-y-2">
                            <div v-for="assignment in franchise.driver_assignments" :key="assignment.id" 
                                 class="flex items-center gap-3 bg-white border border-gray-100 rounded-lg p-2.5 shadow-sm">
                                <div class="h-9 w-9 rounded-full bg-gray-100 flex-shrink-0 flex items-center justify-center border border-gray-200 overflow-hidden text-gray-500 font-bold text-sm">
                                    <img v-if="assignment.driver.user_photo" :src="`/storage/${assignment.driver.user_photo}`" class="h-full w-full object-cover" />
                                    <span v-else>{{ assignment.driver.first_name.charAt(0) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-sm text-gray-900 truncate">
                                        {{ getDriverName(assignment.driver) }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        LIC: {{ assignment.driver.license_number }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="bg-gray-50 rounded-lg p-3 text-center border border-dashed border-gray-200">
                            <p class="text-xs text-gray-400 italic">No drivers assigned.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-3 border-t border-gray-100 flex items-center justify-between shrink-0">
                    <Link href="/verify" class="flex-1 text-center text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wider transition">
                        Scan Another
                    </Link>
                    <div class="w-px h-4 bg-gray-300 mx-2"></div>
                    <button @click="showComplaintModal = true" class="flex-1 text-center text-[10px] font-bold text-red-600 hover:text-red-800 uppercase tracking-wider transition">
                        Report Complaint
                    </button>
                </div>
            </div>
        </div>

        <div class="absolute bottom-2 w-full text-center z-0">
             <p class="text-[10px] text-gray-400/80">System-generated public record.</p>
        </div>

        <div v-if="showComplaintModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showComplaintModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    
                    <div v-if="complaintSuccess" class="p-6 bg-green-50 flex flex-col items-center justify-center text-center">
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mb-4">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-green-800">Report Submitted</h3>
                        <p class="text-sm text-green-600 mt-2">Thank you. Your report has been logged successfully.</p>
                        <button @click="showComplaintModal = false" class="mt-4 text-xs font-bold text-green-700 underline">Close</button>
                    </div>

                    <form v-else @submit.prevent="submitComplaint" class="p-6">
                        <div class="text-center mb-5">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">File a Complaint</h3>
                            <p class="text-xs text-gray-500">Franchise #{{ franchise.id }} • {{ currentUnit?.plate_number }}</p>
                        </div>

                        <div class="space-y-3 text-sm">
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Nature of Complaint *</label>
                                <select v-model="form.nature_of_complaint" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" required>
                                    <option value="" disabled>Select Issue...</option>
                                    <option v-for="nature in natureOfComplaints" :key="nature.id" :value="nature.name">
                                        {{ nature.name }}
                                    </option>
                                    <option>
                                        Other
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Details / Remarks</label>
                                <textarea v-model="form.remarks" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="Describe what happened..."></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Incident Date *</label>
                                    <input type="date" v-model="form.incident_date" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Time *</label>
                                    <input type="time" v-model="form.incident_time" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Pick Up Point</label>
                                    <input type="text" v-model="form.pick_up_point" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Drop Off Point</label>
                                    <input type="text" v-model="form.drop_off_point" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Fare Collected (₱)</label>
                                    <input type="number" step="0.01" v-model="form.fare_collected" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Your Contact # *</label>
                                    <input type="text" v-model="form.complainant_contact" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" placeholder="09xxxxxxxxx" required>
                                </div>
                            </div>

                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="showComplaintModal = false" class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                                Cancel
                            </button>
                            <button type="submit" :disabled="form.processing" class="px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Submit Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* Custom Scrollbar */
.scrollbar-thin::-webkit-scrollbar { width: 4px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
</style>