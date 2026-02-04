<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    franchise: Object
});

// --- Logic ---

// Helper: Darken color for gradients/text
function adjustColor(hex, percent) {
    let num = parseInt(hex.replace("#",""), 16),
    amt = Math.round(2.55 * percent),
    R = (num >> 16) + amt,
    G = (num >> 8 & 0x00FF) + amt,
    B = (num & 0x0000FF) + amt;
    return "#" + (0x1000000 + (R<255?R<1?0:R:255)*0x10000 + (G<255?G<1?0:G:255)*0x100 + (B<255?B<1?0:B:255)).toString(16).slice(1);
}

const zoneColor = props.franchise.zone?.color || '#334155'; 

// Header Gradient
const headerStyle = computed(() => ({
    background: `linear-gradient(to bottom right, ${zoneColor}, ${adjustColor(zoneColor, -40)})`
}));

// Text Color for Zone Labels
const zoneTextStyle = computed(() => ({
    color: adjustColor(zoneColor, -20)
}));

// Status Configuration
const status = computed(() => {
    switch (props.franchise.status) {
        case 'renewed': 
            return { 
                color: 'text-emerald-700', 
                bg: 'bg-emerald-50', 
                border: 'border-emerald-100',
                label: 'Active Franchise' 
            };
        case 'pending renewal': 
            return { 
                color: 'text-amber-700', 
                bg: 'bg-amber-50', 
                border: 'border-amber-100',
                label: 'Renewal Pending' 
            };
        case 'terminated': 
            return { 
                color: 'text-rose-700', 
                bg: 'bg-rose-50', 
                border: 'border-rose-100',
                label: 'Terminated' 
            };
        default: 
            return { 
                color: 'text-slate-700', 
                bg: 'bg-slate-50', 
                border: 'border-slate-100',
                label: 'Unknown Status' 
            };
    }
});

const currentTimestamp = new Date().toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
});
</script>

<template>
    <Head :title="`Franchise ${franchise.id}`" />

    <div class="h-screen w-full bg-slate-100 font-sans text-slate-600 flex flex-col overflow-hidden relative">
        
        <div class="absolute top-0 left-0 w-full h-[35%] z-0" :style="headerStyle"></div>

        <div class="relative z-20 w-full px-6 py-6 flex justify-between items-center text-white/90">
            <Link href="/" class="flex items-center gap-2 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span class="text-sm font-medium tracking-wide">Return Home</span>
            </Link>
            <p class="text-xs font-bold uppercase tracking-widest opacity-70">Official Record</p>
        </div>

        <div class="relative z-10 flex-grow flex flex-col items-center justify-center px-4 sm:px-6 pb-6">
            
            <div class="text-center mb-6">
                <h1 class="text-4xl md:text-5xl font-mono font-bold text-white tracking-tight drop-shadow-md">
                    FR-{{ franchise.id.toString().padStart(4, '0') }}
                </h1>
            </div>

            <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col">
                
                <div class="px-8 py-4 border-b border-slate-100 flex items-center justify-between" :class="status.bg">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Verification Status</span>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full" :class="status.color.replace('text', 'bg')"></span>
                        <span class="text-sm font-bold uppercase" :class="status.color">{{ status.label }}</span>
                    </div>
                </div>

                <div class="p-8 border-b border-slate-100">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Plate Number</p>
                            <h2 class="text-5xl font-bold text-slate-800 font-mono tracking-tighter">
                                {{ franchise.current_active_unit?.new_unit?.plate_number || '---' }}
                            </h2>
                            <p class="mt-1 text-sm font-medium text-slate-500">
                                {{ franchise.current_active_unit?.new_unit?.make?.name || 'Unknown Unit' }}
                            </p>
                        </div>

                        <div class="md:text-right">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Designated Zone</p>
                            <h3 class="text-2xl font-bold" :style="zoneTextStyle">
                                {{ franchise.zone?.description || 'Unassigned' }}
                            </h3>
                            <div class="mt-2 flex flex-wrap justify-end gap-1">
                                <span v-for="(brgy, i) in (franchise.zone?.coverage || []).slice(0, 3)" :key="i"
                                    class="text-[10px] font-semibold px-2 py-0.5 bg-slate-100 text-slate-500 rounded">
                                    {{ brgy }}
                                </span>
                                <span v-if="(franchise.zone?.coverage || []).length > 3" class="text-[10px] text-slate-400 py-0.5">
                                    +{{ franchise.zone.coverage.length - 3 }} more
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2">
                    
                    <div class="p-8 border-b md:border-b-0 md:border-r border-slate-100 hover:bg-slate-50 transition-colors">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Authorized Driver</p>
                        <div class="flex items-center gap-4">
                             <div class="w-10 h-10 rounded bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-sm">
                                {{ franchise.driver?.user?.first_name?.[0] || '?' }}{{ franchise.driver?.user?.last_name?.[0] || '?' }}
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900 leading-tight">
                                    {{ franchise.driver?.user?.last_name }}, {{ franchise.driver?.user?.first_name }}
                                </p>
                                <p class="text-xs text-emerald-600 font-medium mt-0.5">License Verified</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 hover:bg-slate-50 transition-colors">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-3">Franchise Operator</p>
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-sm">
                                {{ franchise.current_ownership?.new_owner?.user?.first_name?.[0] || '?' }}{{ franchise.current_ownership?.new_owner?.user?.last_name?.[0] || '?' }}
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900 leading-tight">
                                    {{ franchise.current_ownership?.new_owner?.user?.last_name }}, {{ franchise.current_ownership?.new_owner?.user?.first_name }}
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">Owner since {{ new Date(franchise.current_ownership?.date_transferred).getFullYear() }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-between items-center">
                    <p class="text-[10px] text-slate-400 font-medium">
                        System Reference: <span class="font-mono text-slate-500">{{ currentTimestamp }}</span>
                    </p>
                    <div class="flex items-center gap-1.5">
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-[10px] font-bold uppercase text-slate-400">Live Data</span>
                    </div>
                </div>

            </div>

            <div class="mt-8">
                <Link href="/verify" class="text-sm font-semibold text-slate-400 hover:text-slate-600 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                    Scan Another ID
                </Link>
            </div>

        </div>
    </div>
</template>