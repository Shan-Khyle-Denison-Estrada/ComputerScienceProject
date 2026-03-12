<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Props from Laravel
const props = defineProps({
    renewedFranchisesSum: Number,
});

// Fetch globally shared settings and auth
const page = usePage();
const settings = computed(() => page.props.settings);
const user = computed(() => page.props.auth?.user); // <-- Added Auth check

// FAQ Logic
const openFaq = ref(null);
const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};

// Use dynamic FAQs if available, otherwise fallback
const faqs = computed(() => {
    if (settings.value?.faqs && settings.value.faqs.length > 0) {
        return settings.value.faqs;
    }
    return [
        {
            question: "How do I apply or renew my franchise?",
            answer: "The process is fully digital. Log in to your account, fill out the required forms, upload your documents, and proceed with the payment."
        }
    ];
});

// Dynamic Hero Background
const heroStyle = computed(() => {
    if (settings.value?.hero_image_path) {
        return {
            backgroundImage: `url('/storage/${settings.value.hero_image_path}')`,
        };
    }
    return {
        backgroundImage: `url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80')`,
    };
});

// Safe theme color fallback
const currentThemeColor = computed(() => settings.value?.theme_color || '#2563eb');
</script>

<template>
    <Head title="Home" />
    <NavBar />

    <div class="relative bg-slate-900 min-h-[85vh] flex items-center font-sans overflow-hidden">
        <div class="absolute inset-0 z-0">
            <div 
                class="absolute inset-0 bg-cover bg-center bg-no-repeat transition-transform duration-1000 scale-105"
                :style="heroStyle"
            ></div>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-900/40"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full pt-10 pb-24 sm:pt-12 sm:pb-32 mt-12 sm:mt-0">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-6 sm:mb-8">
                    <span class="relative flex h-2 sm:h-2.5 w-2 sm:w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full theme-bg opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 sm:h-2.5 w-2 sm:w-2.5 theme-bg"></span>
                    </span>
                    <span class="text-[10px] sm:text-xs font-bold text-white uppercase tracking-widest text-center">
                        {{ settings?.office_name || 'Tricycle Franchising Board' }}
                    </span>
                </div>
                
                <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-black text-white tracking-tight mb-4 sm:mb-6 leading-[1.15] sm:leading-[1.1]">
                    Smart Franchising for <br class="hidden sm:block"/>
                    <span class="theme-text-gradient drop-shadow-sm">{{ settings?.lgu_name || 'The City' }}</span>
                </h1>
                
                <p class="text-base sm:text-lg md:text-xl text-slate-300 mb-8 sm:mb-10 leading-relaxed font-light max-w-2xl">
                    Apply, renew, and manage your tricycle franchise entirely online. We are eliminating long queues to bring you a seamless, transparent, and modern government experience.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 mb-6">
                    
                    <Link v-if="user" :href="route('dashboard')" class="theme-btn w-full sm:w-auto text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-bold text-base sm:text-lg text-center transition-all hover:-translate-y-1 shadow-lg flex items-center justify-center gap-2">
                        My Portal
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>
                    <Link v-else :href="route('login')" class="theme-btn w-full sm:w-auto text-white px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-bold text-base sm:text-lg text-center transition-all hover:-translate-y-1 shadow-lg flex items-center justify-center gap-2">
                        Access Portal
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>

                    <Link href="/verify" class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-bold text-base sm:text-lg text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/10 text-center transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        Scan QR
                    </Link>

                    <Link v-if="settings?.allow_new_applications" :href="route('new-franchise.create')" class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-4 rounded-xl font-bold text-base sm:text-lg text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/10 text-center transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        New Application
                    </Link>

                </div>

                <div v-if="!user" class="mt-6 flex flex-col sm:flex-row items-center justify-center sm:justify-start gap-2 sm:gap-3 bg-slate-900/40 sm:bg-slate-900/30 w-full max-w-full sm:w-fit p-4 sm:py-2 sm:px-4 rounded-lg backdrop-blur-sm border border-white/5 overflow-hidden box-border">
                    <div class="flex items-center justify-center gap-2 text-xs sm:text-sm text-slate-300 text-center sm:text-left">
                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="whitespace-normal break-words leading-snug">
                            Existing franchise owner without an account?
                        </span>
                    </div>
                    <div class="mt-1 sm:mt-0">
                        <Link :href="route('apply')" class="inline-block text-xs sm:text-sm font-bold text-white hover:underline transition-all theme-hover-text">
                            Register here.
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="relative z-20 -mt-12 sm:-mt-16 mb-16 sm:mb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-slate-100 p-6 sm:p-8 flex flex-col md:flex-row justify-between items-center gap-6 sm:gap-8 divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <div class="text-center px-4 sm:px-6 flex-1 w-full pt-4 md:pt-0 first:pt-0">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-5 sm:w-6 h-5 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
                <div class="text-3xl sm:text-4xl font-black text-slate-800 mb-1">{{ renewedFranchisesSum }}</div>
                <div class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest">Renewed Franchises</div>
            </div>
            <div class="text-center px-4 sm:px-6 pt-6 md:pt-0 flex-1 w-full">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-5 sm:w-6 h-5 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="text-3xl sm:text-4xl font-black theme-text mb-1">100%</div>
                <div class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest">Online Process</div>
            </div>
            <div class="text-center px-4 sm:px-6 pt-6 md:pt-0 flex-1 w-full">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-5 sm:w-6 h-5 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="text-3xl sm:text-4xl font-black text-slate-800 mb-1">24/7</div>
                <div class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest">System Availability</div>
            </div>
        </div>
    </div>

    <div class="bg-slate-50 py-16 sm:py-24 mt-8 sm:mt-12 border-t border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="theme-text font-bold uppercase tracking-widest text-xs sm:text-sm mb-2">Support</h2>
                <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Frequently Asked Questions</h3>
            </div>

            <div class="space-y-3 sm:space-y-4">
                <div 
                    v-for="(faq, index) in faqs" 
                    :key="index"
                    class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300"
                    :class="{'theme-ring border-transparent': openFaq === index}"
                >
                    <button 
                        @click="toggleFaq(index)" 
                        class="w-full flex justify-between items-center p-5 sm:p-6 bg-white hover:bg-slate-50 transition-colors focus:outline-none text-left"
                    >
                        <span class="font-bold text-slate-800 text-base sm:text-lg pr-4">{{ faq.question }}</span>
                        <div class="shrink-0 w-7 sm:w-8 h-7 sm:h-8 rounded-full bg-slate-100 flex items-center justify-center transition-transform duration-300" :class="{'rotate-180 theme-bg text-white': openFaq === index, 'text-slate-500': openFaq !== index}">
                            <svg class="w-4 sm:w-5 h-4 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>
                    
                    <div 
                        v-show="openFaq === index" 
                        class="px-5 sm:px-6 pb-5 sm:pb-6 bg-white text-sm sm:text-base text-slate-600 leading-relaxed border-t border-slate-50 pt-3 sm:pt-4"
                    >
                        {{ faq.answer }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.theme-bg { background-color: v-bind(currentThemeColor); }
.theme-text { color: v-bind(currentThemeColor); }
.theme-ring { --tw-ring-color: v-bind(currentThemeColor); box-shadow: 0 0 0 2px var(--tw-ring-color); }
.theme-btn { background-color: v-bind(currentThemeColor); border: 1px solid v-bind(currentThemeColor); }
.theme-btn:hover { filter: brightness(0.90); box-shadow: 0 10px 15px -3px v-bind('currentThemeColor + "40"'); }
.theme-text-gradient { background: linear-gradient(to right, #ffffff, v-bind(currentThemeColor)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.theme-hover-text:hover { color: v-bind(currentThemeColor); text-decoration-color: v-bind(currentThemeColor); }
</style>