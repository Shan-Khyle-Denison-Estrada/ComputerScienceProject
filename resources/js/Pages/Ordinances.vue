<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Fetch globally shared settings
const page = usePage();
const settings = computed(() => page.props.settings);

// Calculate Dynamic Theme Color
const currentThemeColor = computed(() => settings.value?.theme_color || '#2563eb');

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
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-900 via-slate-900/60 to-slate-800"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10">
            <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl mb-6">
                City Ordinances & Rules
            </h1>
            <p class="mt-4 max-w-2xl text-lg text-slate-300 mx-auto font-light leading-relaxed whitespace-pre-line">
                {{ settings?.ordinances_text || 'General guidelines, laws, and regulations strictly implemented for the tricycle transport sector.' }}
            </p>
        </div>
    </div>

    <div class="bg-slate-50 py-16 sm:py-24 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div v-if="settings?.ordinances && settings.ordinances.length > 0" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div 
                    v-for="(ord, idx) in settings.ordinances" 
                    :key="idx"
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col h-full"
                >
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2.5 theme-bg-light theme-text rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900 leading-tight">{{ ord.title }}</h3>
                                <p v-if="ord.subtitle" class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1">{{ ord.subtitle }}</p>
                            </div>
                        </div>
                        
                        <p class="text-sm text-slate-600 mb-6 flex-1">
                            {{ ord.summary }}
                        </p>

                        <button 
                            @click="openPdfViewer('/storage/' + ord.file, ord.title)" 
                            class="theme-btn w-full mt-auto py-3 px-4 text-white font-semibold rounded-xl text-sm transition-colors flex justify-center items-center gap-2"
                        >
                            <span>Read Document</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div v-else class="text-center py-24 bg-white rounded-3xl border border-slate-200 border-dashed">
                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-4 text-sm font-semibold text-slate-900">No ordinances available</h3>
                <p class="mt-2 text-sm text-slate-500">The city government hasn't uploaded any documents yet.</p>
            </div>

        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-[100] flex flex-col bg-slate-900/95 backdrop-blur-sm">
        <div class="flex-1 flex flex-col max-w-6xl w-full mx-auto p-4 sm:p-6 lg:p-8 h-full">
            <div class="bg-white rounded-t-2xl px-6 py-4 flex justify-between items-center shadow-md z-10">
                <div class="flex items-center gap-3">
                    <div class="p-2 theme-bg-light theme-text rounded-lg hidden sm:block">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">{{ currentPdfTitle }}</h3>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Official Document</p>
                    </div>
                </div>
                <button 
                    @click="closeModal" 
                    class="p-2 rounded-full hover:bg-slate-100 text-slate-500 hover:text-slate-900 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 bg-slate-100 relative">
                <iframe 
                    :src="currentPdfUrl" 
                    class="w-full h-full"
                    frameborder="0"
                ></iframe>
            </div>

            <div class="bg-white px-6 py-4 rounded-b-2xl border-t border-slate-200 flex justify-between items-center shadow-md z-10">
                <span class="text-sm text-slate-500 hidden sm:block">Use the controls above to navigate the PDF.</span>
                <div class="flex gap-4 w-full sm:w-auto">
                    <a 
                        :href="currentPdfUrl" 
                        download
                        class="flex-1 sm:flex-none px-6 py-2.5 border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-sm transition-colors flex justify-center items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        Download
                    </a>
                    <button 
                        @click="closeModal" 
                        class="theme-btn flex-1 sm:flex-none px-6 py-2.5 text-white font-bold rounded-xl text-sm flex items-center justify-center"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.theme-text { color: v-bind(currentThemeColor); }
.theme-bg-light { background-color: v-bind('currentThemeColor + "1A"'); }
.theme-btn { background-color: v-bind(currentThemeColor); border: 1px solid v-bind(currentThemeColor); transition: filter 0.2s; }
.theme-btn:hover { filter: brightness(0.90); }
</style>