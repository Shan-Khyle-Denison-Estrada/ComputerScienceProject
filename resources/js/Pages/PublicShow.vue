<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    franchise: Object
});

// --- HELPER: Get Color ---
function getHexFromColorString(colorString) {
    if (!colorString) return '#3b82f6'; // Default Blue
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
                        <h2 class="text-xl font-black uppercase mb-4 leading-tight" :style="{ color: zoneColor }">
                            {{ franchise.zone?.description || 'No Zone Assigned' }}
                        </h2>

                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-1 opacity-5">
                                <!-- <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg> -->
                            </div>
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
                    </div>

                    <div>
                        <div class="flex items-center gap-2 mb-3 sticky top-0 bg-white z-10 py-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
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

                    <div class="pt-4 border-t border-gray-100">
                         <div class="flex justify-between items-start">
                            <div class="min-w-0 pr-2">
                                <div class="text-[10px] uppercase text-gray-400 font-bold tracking-widest mb-0.5">Franchise Owner</div>
                                <div class="text-sm font-bold text-gray-800 break-words">
                                    {{ currentOwner ? `${currentOwner.user.last_name}, ${currentOwner.user.first_name}` : 'No Active Owner' }}
                                </div>
                                <div v-if="currentOwner?.user?.contact_number" class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="truncate">{{ currentOwner.user.contact_number }}</span>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="text-[10px] font-mono text-gray-400">TIN: {{ currentOwner ? currentOwner.tin_number : '---' }}</div>
                            </div>
                         </div>
                    </div>

                </div>

                <div class="bg-gray-50 p-3 border-t border-gray-100 text-center shrink-0">
                    <Link href="/verify" class="inline-flex items-center justify-center gap-2 text-[10px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wider transition">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        Scan Another QR
                    </Link>
                </div>

            </div>
        </div>

        <div class="absolute bottom-2 w-full text-center z-0">
             <p class="text-[10px] text-gray-400/80">System-generated public record.</p>
        </div>

    </div>
</template>

<style scoped>
/* Custom Scrollbar for the internal container */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #e5e7eb;
    border-radius: 20px;
}
</style>