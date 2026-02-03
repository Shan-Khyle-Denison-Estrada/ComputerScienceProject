<script setup>
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue'; // Import ref for state management
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
    // Prevent background scrolling when modal is open
    document.body.style.overflow = 'hidden';
};

// Function to close the modal
const closeModal = () => {
    showModal.value = false;
    currentPdfUrl.value = '';
    // Re-enable scrolling
    document.body.style.overflow = 'auto';
};
</script>

<template>
    <Head title="City Ordinances" />
    <NavBar />

    <div class="relative bg-slate-900 py-24 sm:py-32 overflow-hidden">
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
                class="h-24 w-auto mx-auto mb-8 drop-shadow-2xl"
            />
            <h1 class="text-4xl font-black tracking-tight text-white sm:text-6xl mb-4">
                Legal Framework
            </h1>
            <p class="text-lg text-slate-300 max-w-2xl mx-auto">
                The key city ordinances governing the operation, franchising, and modernization of tricycle transport in Zamboanga City.
            </p>
        </div>
    </div>

    <div class="bg-slate-50 py-20 relative">
        <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-slate-200 hidden lg:block transform -translate-x-1/2"></div>

        <div class="mx-auto max-w-7xl px-6 lg:px-8 space-y-16">
            
            <div class="relative flex flex-col lg:flex-row items-center gap-12">
                <div class="absolute left-1/2 w-4 h-4 bg-blue-600 rounded-full border-4 border-white shadow-sm hidden lg:block transform -translate-x-1/2"></div>
                
                <div class="w-full lg:w-1/2 lg:pr-16 lg:text-right order-2 lg:order-1">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8 hover:shadow-xl transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-2 h-full bg-blue-600 lg:left-auto lg:right-0"></div>
                        
                        <span class="inline-block py-1 px-3 rounded-full bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-wide mb-4">
                            Foundational Law
                        </span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">City Ordinance No. 185</h2>
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">Series of 1996</h3>
                        
                        <p class="text-slate-600 leading-relaxed">
                            Entitled “An Ordinance Prescribing the Operation of Tricycles and Granting Franchise for the Operation Thereof Within the Territorial Jurisdiction of the City of Zamboanga”.
                        </p>
                        <p class="mt-4 text-slate-600 text-sm">
                            This ordinance established the Tricycle Adjudication Board (TAB) and defined the original rules for franchising and regulation.
                        </p>

                        <div class="mt-6 flex lg:justify-end">
                            <button 
                                @click="openPdfViewer('/pdfs/ordinance_185.pdf', 'City Ordinance No. 185')"
                                class="text-blue-600 font-semibold text-sm hover:underline flex items-center gap-2"
                            >
                                Read Full Text
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="w-full lg:w-1/2 order-1 lg:order-2"></div>
            </div>

            <div class="relative flex flex-col lg:flex-row items-center gap-12">
                <div class="absolute left-1/2 w-4 h-4 bg-green-500 rounded-full border-4 border-white shadow-sm hidden lg:block transform -translate-x-1/2"></div>

                <div class="w-full lg:w-1/2 order-1"></div>

                <div class="w-full lg:w-1/2 lg:pl-16 order-2">
                     <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8 hover:shadow-xl transition-shadow relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-green-500"></div>

                        <span class="inline-block py-1 px-3 rounded-full bg-green-50 text-green-700 text-xs font-bold uppercase tracking-wide mb-4">
                            Modernization Amendment
                        </span>
                        <h2 class="text-2xl font-bold text-slate-900 mb-4">City Ordinance No. 494</h2>
                        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">Major Amendment</h3>
                        
                        <div class="prose prose-sm prose-slate text-slate-600 leading-relaxed">
                            <p>
                                Amends Section 5, Article III of Ordinance No. 185. It mandates the modernization of the tricycle fleet to ensure environmental sustainability.
                            </p>
                            <ul class="list-disc pl-4 space-y-1 mt-2">
                                <li><strong>Mandatory Replacement:</strong> Old units must be replaced with Euro 4-compliant or electric tricycles within 5 years.</li>
                                <li><strong>Franchise Validity:</strong> Only franchises issued up to 2002 are renewable.</li>
                                <li><strong>Cancellation:</strong> Franchises inactive for 3 years are automatically cancelled.</li>
                                <li><strong>Moratorium:</strong> No new franchises will be granted in congested zones.</li>
                            </ul>
                        </div>

                        <div class="mt-6">
                            <button 
                                @click="openPdfViewer('/pdfs/ordinance_494.pdf', 'City Ordinance No. 494')"
                                class="text-green-600 font-semibold text-sm hover:underline flex items-center gap-2"
                            >
                                Read Full Text
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <Footer />

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

        <div class="relative w-full max-w-5xl h-[85vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-200">
            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                <h3 class="text-lg font-bold text-slate-900 truncate pr-4">
                    {{ currentPdfTitle }}
                </h3>
                <button 
                    @click="closeModal"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors"
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

            <div class="bg-white px-6 py-3 border-t border-slate-100 flex justify-end">
                <a 
                    :href="currentPdfUrl" 
                    download
                    class="text-sm font-semibold text-blue-600 hover:text-blue-800 mr-4"
                >
                    Download PDF
                </a>
                <button 
                    @click="closeModal" 
                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-sm transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

</template>