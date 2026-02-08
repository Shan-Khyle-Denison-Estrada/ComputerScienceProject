<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Modal State
const showModal = ref(false);
const currentPdfUrl = ref('');
const currentPdfTitle = ref('');

// Function to open the modal
const openPdfViewer = (url, title) => {
    currentPdfUrl.value = url;
    currentPdfTitle.value = title;
    showModal.value = true;
    document.body.style.overflow = 'hidden';
};

// Function to close the modal
const closeModal = () => {
    showModal.value = false;
    currentPdfUrl.value = '';
    document.body.style.overflow = 'auto';
};
</script>

<template>
    <Head title="City Ordinances" />
    <NavBar />

    <div class="relative bg-slate-900 py-20 sm:py-28 overflow-hidden">
        <img 
            src="/non_index_bg.png" 
            alt="Background" 
            class="absolute inset-0 -z-10 h-full w-full object-cover opacity-20"
            onerror="this.style.display='none'" 
        />
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-900 via-slate-900/80 to-transparent"></div>

        <div class="mx-auto max-w-7xl px-6 lg:px-8 text-center relative z-10">
            <img 
                src="/tab_seal.png" 
                alt="TAB Seal" 
                class="h-20 w-auto mx-auto mb-6 drop-shadow-2xl"
            />
            <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl mb-4">
                Legal Framework
            </h1>
            <p class="text-lg text-slate-300 max-w-2xl mx-auto">
                Governing laws, franchises, and modernization mandates for Zamboanga City transport.
            </p>
        </div>
    </div>

    <div class="bg-slate-50 py-16">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">

                <div class="flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
                    <div class="h-2 w-full bg-blue-600"></div>
                    <div class="p-8 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-6">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-wide">
                                Foundational Law
                            </span>
                            <span class="text-slate-400 font-serif italic">1996</span>
                        </div>
                        
                        <h2 class="text-2xl font-bold text-slate-900 group-hover:text-blue-700 transition-colors">
                            City Ordinance No. 185
                        </h2>
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 mt-1">
                            The Tricycle Franchise Code
                        </h3>
                        
                        <p class="text-slate-600 leading-relaxed mb-6 flex-grow">
                            This ordinance formally prescribed the operation of tricycles within the territorial jurisdiction of the City of Zamboanga. It established the <strong>Tricycle Adjudication Board (TAB)</strong> and defined the original regulatory framework for franchising.
                        </p>

                        <div class="pt-6 border-t border-slate-100 mt-auto">
                            <button 
                                @click="openPdfViewer('/pdfs/ordinance_185.pdf', 'City Ordinance No. 185')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-50 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-semibold rounded-xl transition-colors text-sm"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                View Full Document
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden group">
                    <div class="h-2 w-full bg-emerald-500"></div>
                    <div class="p-8 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-6">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wide">
                                Modernization Amendment
                            </span>
                            <span class="text-slate-400 font-serif italic">Current</span>
                        </div>

                        <h2 class="text-2xl font-bold text-slate-900 group-hover:text-emerald-700 transition-colors">
                            City Ordinance No. 494
                        </h2>
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4 mt-1">
                            Mandatory Fleet Modernization
                        </h3>
                        
                        <div class="text-slate-600 leading-relaxed mb-6 prose prose-sm prose-slate flex-grow">
                            <p>
                                A major amendment to Ordinance 185 focusing on environmental sustainability and fleet modernization.
                            </p>
                            <ul class="list-disc pl-4 space-y-2 mt-2">
                                <li><strong>Mandatory Replacement:</strong> Transition to Euro-4 compliant or E-Trikes.</li>
                                <li><strong>Franchise Cap:</strong> Strict moratorium on new franchises in congested zones.</li>
                                <li><strong>Validity:</strong> "Use it or lose it" policy for inactive franchises (3 years).</li>
                            </ul>
                        </div>

                        <div class="pt-6 border-t border-slate-100 mt-auto">
                            <button 
                                @click="openPdfViewer('/pdfs/ordinance_494.pdf', 'City Ordinance No. 494')"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-50 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-semibold rounded-xl transition-colors text-sm"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                View Full Document
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <Footer />

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <div class="relative w-full max-w-6xl h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200 ring-1 ring-white/10">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-slate-100 rounded-lg">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 truncate">
                        {{ currentPdfTitle }}
                    </h3>
                </div>
                <button 
                    @click="closeModal"
                    class="rounded-lg p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 bg-slate-100 relative">
                <iframe 
                    :src="currentPdfUrl" 
                    class="w-full h-full"
                    frameborder="0"
                ></iframe>
            </div>

            <div class="bg-white px-6 py-4 border-t border-slate-200 flex justify-between items-center">
                <span class="text-sm text-slate-500 hidden sm:block">Use the controls above to navigate the PDF.</span>
                <div class="flex gap-4">
                    <a 
                        :href="currentPdfUrl" 
                        download
                        class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold rounded-lg text-sm transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download
                    </a>
                    <button 
                        @click="closeModal" 
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg text-sm transition-colors"
                    >
                        Close Viewer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>