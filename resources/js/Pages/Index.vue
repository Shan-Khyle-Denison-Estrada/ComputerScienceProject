<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Props from Laravel
defineProps({
    activeFranchisesCount: Number,
});

// FAQ Logic
const openFaq = ref(null);
const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};

// Updated FAQs based on specific business rules
const faqs = [
    {
        question: "Can I apply for a new tricycle franchise?",
        answer: "No. The City does not issue new franchises 'out of thin air' to maintain traffic balance. You can only acquire a franchise by purchasing an existing one from a current owner."
    },
    {
        question: "How do I transfer ownership?",
        answer: "Since the system does not digitalize the entire business process, ownership transfers must be processed physically at the City Hall. Once transferred, the new owner can register online for renewals."
    },
    {
        question: "How do I renew my franchise?",
        answer: "Renewal is fully digital. Log in to your account, select your unit, and proceed with the renewal payment. An updated QR code will be generated upon approval."
    },
    {
        question: "I am a passenger. How do I check if a tricycle is legal?",
        answer: "Use the 'Scan QR' feature on this website to scan the code displayed on the tricycle. It will instantly tell you if the unit is registered and if its franchise is currently valid."
    }
];
</script>

<template>
    <Head title="Welcome" />
    <NavBar />

    <section class="relative bg-white overflow-hidden pt-16 pb-12 lg:pt-24 lg:pb-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-xs font-bold uppercase tracking-wide mb-6">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        Official Franchise Management System
                    </div>

                    <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl md:text-6xl mb-6">
                        Tricycle Franchise <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-blue-500">
                            Management System
                        </span>
                    </h1>

                    <p class="text-lg text-slate-600 mb-8 leading-relaxed">
                        The official platform for maintaining franchise validity. Existing owners can monitor their own franchise, while the public can scan QR codes to ensure passenger safety.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link href="/login" class="inline-flex justify-center items-center px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 hover:-translate-y-0.5">
                            Portal Login
                        </Link>
                        
                        <Link href="/verify" class="inline-flex justify-center items-center px-6 py-3.5 text-base font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                            <svg class="w-5 h-5 mr-2 text-slate-500 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                            Scan QR
                        </Link>
                    </div>

                    <div class="mt-5 flex justify-start">
                        <Link href="/apply" class="text-sm text-slate-500 hover:text-blue-600 transition-colors flex items-center gap-1 font-medium group">
                            Existing Operator without an account? 
                            <span class="underline underline-offset-4 decoration-slate-300 group-hover:decoration-blue-600 transition-colors">Apply here</span>
                            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </Link>
                    </div>

                </div>

                <div class="relative lg:ml-auto">
                    <div class="absolute -top-10 -right-10 w-72 h-72 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
                    <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-green-100 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
                    
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-100 rotate-1 hover:rotate-0 transition-transform duration-500 ease-out">
                         <img 
                            class="w-full h-auto object-cover max-h-[500px]" 
                            src="/hero_page_bg.png" 
                            alt="Zamboanga Tricycle"
                            onerror="this.src='https://images.unsplash.com/photo-1595154381734-7097e88426eb?q=80&w=2070&auto=format&fit=crop'" 
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    <div class="bg-blue-900 py-10 border-t border-blue-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-blue-800 rounded-lg">
                        <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Active Permits</h2>
                        <p class="text-blue-200 text-sm">Valid franchises currently in circulation.</p>
                    </div>
                </div>
                
                <div class="flex items-baseline gap-3">
                    <span class="text-5xl font-black text-white tracking-tighter">
                        {{ activeFranchisesCount || '1,250' }}
                    </span>
                    <span class="text-blue-200 font-bold uppercase text-sm">Active Franchises</span>
                </div>
            </div>
        </div>
    </div>

    <div class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-900">System Features</h2>
                <p class="mt-4 text-slate-500 max-w-2xl mx-auto">Focused on maintaining compliance and public safety through digital verification.</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-6 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Online Monitoring</h3>
                    <p class="text-slate-500 leading-relaxed">Existing franchise owners can monitor their franchises online. No need to queue for basic checks.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mb-6 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Public QR Scan</h3>
                    <p class="text-slate-500 leading-relaxed">Guests and passengers can scan the QR code on any tricycle to instantly verify if the unit's franchise is active and legal.</p>
                </div>
                
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center mb-6 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-3">Secured Acquisition</h3>
                    <p class="text-slate-500 leading-relaxed">New franchises are not issued. Ownership is exclusively obtained by procuring a franchise from an existing holder.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-24 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-center text-slate-900 mb-12">Frequently Asked Questions</h2>
            
            <div class="space-y-4">
                <div v-for="(faq, index) in faqs" :key="index" class="border border-slate-200 rounded-xl overflow-hidden transition-all duration-200" :class="{'ring-2 ring-blue-100 border-blue-200': openFaq === index}">
                    <button 
                        @click="toggleFaq(index)" 
                        class="w-full flex justify-between items-center p-6 bg-white hover:bg-slate-50 transition-colors focus:outline-none text-left"
                    >
                        <span class="font-bold text-slate-800">{{ faq.question }}</span>
                        <svg 
                            class="w-5 h-5 text-slate-400 transform transition-transform duration-200"
                            :class="{'rotate-180 text-blue-600': openFaq === index}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <div 
                        v-show="openFaq === index" 
                        class="px-6 pb-6 bg-white text-slate-600 leading-relaxed"
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
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
</style>