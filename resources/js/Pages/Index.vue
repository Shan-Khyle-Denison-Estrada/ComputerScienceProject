<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Props from Laravel (Settings removed from here)
const props = defineProps({
    activeFranchisesCount: Number,
});

// Fetch globally shared settings
const page = usePage();
const settings = computed(() => page.props.settings);

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
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full pt-12 pb-32">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-8">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full theme-bg opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 theme-bg"></span>
                    </span>
                    <span class="text-xs font-bold text-white uppercase tracking-widest">
                        {{ settings?.office_name || 'Tricycle Franchising Board' }}
                    </span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-black text-white tracking-tight mb-6 leading-[1.1]">
                    Smart Franchising for <br/>
                    <span class="theme-text-gradient drop-shadow-sm">{{ settings?.lgu_name || 'The City' }}</span>
                </h1>
                
                <p class="text-lg md:text-xl text-slate-300 mb-10 leading-relaxed font-light max-w-2xl">
                    Apply, renew, and manage your tricycle franchise entirely online. We are eliminating long queues to bring you a seamless, transparent, and modern government experience.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 mb-6">
                    <Link :href="route('login')" class="theme-btn text-white px-8 py-4 rounded-xl font-bold text-lg text-center transition-all hover:-translate-y-1 shadow-lg flex items-center justify-center gap-2">
                        Access Portal
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>
                    <Link href="/verify" class="px-8 py-4 rounded-xl font-bold text-lg text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/10 text-center transition-all flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        Scan QR
                    </Link>
                </div>

                <div class="mt-6 flex items-center gap-2 text-sm text-slate-300 bg-slate-900/30 w-max px-4 py-2 rounded-lg backdrop-blur-sm border border-white/5">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Existing franchise owner without an account?</span>
                    <Link :href="route('apply')" class="font-bold text-white hover:underline transition-all theme-hover-text">
                        Register here.
                    </Link>
                </div>
            </div>
        </div>
    </div>

    <div class="relative z-20 -mt-16 mb-24 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-2xl shadow-slate-200/50 border border-slate-100 p-8 flex flex-col md:flex-row justify-between items-center gap-8 divide-y md:divide-y-0 md:divide-x divide-slate-100">
            <div class="text-center px-6 flex-1 w-full">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
                <div class="text-4xl font-black text-slate-800 mb-1">{{ activeFranchisesCount }}</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active Franchises</div>
            </div>
            <div class="text-center px-6 pt-6 md:pt-0 flex-1 w-full">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="text-4xl font-black theme-text mb-1">100%</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">Online Process</div>
            </div>
            <div class="text-center px-6 pt-6 md:pt-0 flex-1 w-full">
                <div class="flex justify-center mb-3">
                    <div class="p-3 rounded-full bg-slate-50 theme-text">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div class="text-4xl font-black text-slate-800 mb-1">24/7</div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest">System Availability</div>
            </div>
        </div>
    </div>

    <div class="bg-slate-50 py-24 mt-12 border-t border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="theme-text font-bold uppercase tracking-widest text-sm mb-2">Support</h2>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">Frequently Asked Questions</h3>
            </div>

            <div class="space-y-4">
                <div 
                    v-for="(faq, index) in faqs" 
                    :key="index"
                    class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300"
                    :class="{'theme-ring border-transparent': openFaq === index}"
                >
                    <button 
                        @click="toggleFaq(index)" 
                        class="w-full flex justify-between items-center p-6 bg-white hover:bg-slate-50 transition-colors focus:outline-none text-left"
                    >
                        <span class="font-bold text-slate-800 text-lg pr-4">{{ faq.question }}</span>
                        <div class="shrink-0 w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center transition-transform duration-300" :class="{'rotate-180 theme-bg text-white': openFaq === index, 'text-slate-500': openFaq !== index}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </button>
                    
                    <div 
                        v-show="openFaq === index" 
                        class="px-6 pb-6 bg-white text-slate-600 leading-relaxed border-t border-slate-50 pt-4"
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