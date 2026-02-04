<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    franchise: Object
});

// --- 1. AUTOMATIC COLOR CONVERSION ---

function getHexFromColorString(colorString) {
    if (!colorString) return '#475569'; 
    if (colorString.startsWith('#')) return colorString;

    try {
        const ctx = document.createElement("canvas").getContext("2d");
        ctx.fillStyle = colorString;
        const computed = ctx.fillStyle; 
        if (computed === '#000000' && colorString.toLowerCase() !== 'black') {
            return '#475569'; 
        }
        return computed;
    } catch (e) {
        return '#475569';
    }
}

const zoneHex = computed(() => {
    return getHexFromColorString(props.franchise.zone?.color);
});


// --- 2. GRADIENT MATH ---

const lightenDarkenColor = (col, amt) => {
    let usePound = false;
    if (col[0] === "#") {
        col = col.slice(1);
        usePound = true;
    }
    
    if (col.length !== 6) return col; 

    let num = parseInt(col, 16);
    let r = (num >> 16) + amt;
    let g = ((num >> 8) & 0x00FF) + amt;
    let b = (num & 0x0000FF) + amt;

    r = Math.max(0, Math.min(255, r));
    g = Math.max(0, Math.min(255, g));
    b = Math.max(0, Math.min(255, b));

    return (usePound ? "#" : "") + (g | (b << 8) | (r << 16)).toString(16).padStart(6, '0');
};

// --- 3. DYNAMIC STYLES ---

const backgroundStyle = computed(() => {
    const primary = zoneHex.value;
    const lighter = lightenDarkenColor(primary, 40);
    const darker = lightenDarkenColor(primary, -60);

    return {
        background: `linear-gradient(135deg, ${lighter} 0%, ${primary} 40%, ${darker} 100%)`
    };
});

const zoneTextStyle = computed(() => ({
    color: zoneHex.value
}));

// --- 4. UTILITIES ---

const toSentenceCase = (str) => {
    if (!str) return '';
    return str.toLowerCase().replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
};

const status = computed(() => {
    const s = (props.franchise.status || '').toLowerCase();
    switch (s) {
        case 'renewed': 
            return { bg: 'bg-emerald-100', text: 'text-emerald-800', border: 'border-emerald-200', dot: 'bg-emerald-500', label: 'Renewed' };
        case 'pending renewal': 
            return { bg: 'bg-amber-100', text: 'text-amber-800', border: 'border-amber-200', dot: 'bg-amber-500', label: 'Pending Renewal' };
        case 'terminated': 
            return { bg: 'bg-rose-100', text: 'text-rose-800', border: 'border-rose-200', dot: 'bg-rose-600', label: 'Terminated' };
        default: 
            return { bg: 'bg-slate-100', text: 'text-slate-800', border: 'border-slate-200', dot: 'bg-slate-500', label: s || 'Unknown' };
    }
});

const currentTimestamp = new Date().toLocaleString('en-US', {
    month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'
});
</script>

<template>
    <Head :title="`Franchise ${franchise.id}`" />

    <div class="h-screen w-full bg-slate-900 font-sans text-slate-600 flex flex-col relative overflow-hidden transition-colors duration-700 ease-in-out" 
         :style="backgroundStyle">
        
        <div class="absolute inset-0 opacity-20 pointer-events-none mix-blend-overlay"
             style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 200 200%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.65%22 numOctaves=%223%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22 opacity=%221%22/%3E%3C/svg%3E');">
        </div>
        <div class="absolute top-[-20%] left-[-10%] w-[50vh] h-[50vh] bg-white opacity-10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60vh] h-[60vh] bg-black opacity-20 rounded-full blur-[100px] pointer-events-none"></div>

        <nav class="relative z-20 w-full px-6 py-6 flex justify-between items-center text-white flex-shrink-0">
            <Link href="/" class="flex items-center gap-2 group">
                <div class="bg-white/10 group-hover:bg-white/20 transition-colors p-2 rounded-full backdrop-blur-md border border-white/10">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </div>
                <span class="text-sm font-bold tracking-wide shadow-sm">Home</span>
            </Link>
            <div class="flex items-center gap-2 opacity-90">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse shadow-[0_0_10px_rgba(74,222,128,0.8)]"></div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-white/90">Live Check</span>
            </div>
        </nav>

        <main class="relative z-10 flex-grow overflow-y-auto px-4 w-full">
            
            <div class="min-h-full flex flex-col items-center justify-center py-8 pb-20">
                
                <div class="text-center mb-6 md:mb-8 mt-2">
                    <p class="text-xs font-bold uppercase tracking-[0.3em] text-white mb-3 shadow-sm drop-shadow-md">Official Franchise ID</p>
                    
                    <div class="inline-block px-6 py-2 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 shadow-2xl">
                        <h1 class="text-5xl md:text-6xl font-black font-mono tracking-tighter text-white drop-shadow-lg">
                            FR-{{ franchise.id.toString().padStart(4, '0') }}
                        </h1>
                    </div>
                </div>

                <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up transform transition-all">
                    
                    <div class="px-8 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Current Status</span>
                        
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border shadow-sm" :class="[status.bg, status.border]">
                            <span class="w-2 h-2 rounded-full" :class="status.dot"></span>
                            <span class="text-xs font-extrabold uppercase tracking-wide" :class="status.text">
                                {{ status.label }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-slate-100">
                        
                        <div class="flex-1 p-8 bg-white">
                            
                            <div class="mb-8">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Plate Number</p>
                                <h2 class="text-5xl font-bold text-slate-900 font-mono tracking-tighter leading-none">
                                    {{ franchise.current_active_unit?.new_unit?.plate_number || 'NO PLATE' }}
                                </h2>
                                <div class="mt-2 inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 uppercase border border-slate-200">
                                    {{ franchise.current_active_unit?.new_unit?.make?.name || 'Unknown Unit' }}
                                </div>
                            </div>

                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Zone Assignment</p>
                                <h3 class="text-3xl font-black tracking-tight" :style="zoneTextStyle">
                                    {{ toSentenceCase(franchise.zone?.description) || 'Unassigned' }}
                                </h3>
                                
                                <div v-if="franchise.zone?.coverage?.length" class="mt-4">
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-2">Authorized Coverage</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <span v-for="(brgy, i) in (franchise.zone?.coverage || [])" :key="i"
                                              class="text-[10px] font-semibold px-2 py-1 bg-slate-50 text-slate-600 rounded border border-slate-100 text-center truncate shadow-sm">
                                            {{ brgy }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="w-full md:w-80 bg-slate-50/50 flex flex-col">
                            
                            <div class="p-6 flex-1 hover:bg-white transition-colors duration-200 border-b md:border-b-0 border-slate-100 group">
                                <div class="flex items-center gap-4 h-full">
                                    <div class="w-12 h-12 rounded-full bg-white border border-slate-200 group-hover:border-slate-300 flex items-center justify-center text-sm font-bold text-slate-400 shadow-sm transition-colors flex-shrink-0">
                                        {{ franchise.driver?.user?.first_name?.[0] || 'D' }}
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 group-hover:text-slate-500 mb-0.5">Driver</p>
                                        <p class="text-base font-bold text-slate-900 leading-tight">
                                            {{ franchise.driver?.user?.last_name }}, {{ franchise.driver?.user?.first_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden md:block w-full h-px bg-slate-100"></div>

                            <div class="p-6 flex-1 hover:bg-white transition-colors duration-200 group">
                                <div class="flex items-center gap-4 h-full">
                                    <div class="w-12 h-12 rounded-full bg-white border border-slate-200 group-hover:border-slate-300 flex items-center justify-center text-sm font-bold text-slate-400 shadow-sm transition-colors flex-shrink-0">
                                        {{ franchise.current_ownership?.new_owner?.user?.first_name?.[0] || 'O' }}
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 group-hover:text-slate-500 mb-0.5">Owner</p>
                                        <p class="text-base font-bold text-slate-900 leading-tight">
                                            {{ franchise.current_ownership?.new_owner?.user?.last_name }}, {{ franchise.current_ownership?.new_owner?.user?.first_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="px-8 py-3 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                        <p class="text-[10px] text-slate-400 font-medium font-mono">
                            {{ currentTimestamp }}
                        </p>
                        <div class="flex items-center gap-1.5 opacity-75">
                            <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            <span class="text-[10px] font-bold uppercase text-slate-400">Secure</span>
                        </div>
                    </div>

                </div>

                <div class="mt-8">
                    <Link href="/verify" class="group flex items-center gap-3 px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition-all shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 text-white/80 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h-4v-4H8m1-4h4m-4 4h4m6-4h2v2h-2v-2z" class="hidden" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h6v6H3V3zm12 0h6v6h-6V3zm0 12h6v6h-6v-6zM3 15h6v6H3v-6z" />
                        </svg>
                        <span class="text-sm font-bold text-white group-hover:text-white tracking-wide">Scan Another ID</span>
                    </Link>
                </div>

            </div>
        </main>
    </div>
</template>

<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>