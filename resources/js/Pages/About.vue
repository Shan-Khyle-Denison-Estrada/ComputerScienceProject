<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";

// Fetch globally shared settings
const page = usePage();
const settings = computed(() => page.props.settings);

// Calculate Dynamic Theme Color
const currentThemeColor = computed(() => settings.value?.theme_color || '#2563eb');
</script>

<template>
    <Head title="About Us" />
    <NavBar />

    <div class="relative bg-slate-900 isolate overflow-hidden pt-24 pb-20 sm:pt-32 sm:pb-24">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>

        <div class="mx-auto max-w-7xl px-6 lg:px-8 text-center relative z-10">
            <div class="flex justify-center mb-8">
                <img 
                    v-if="settings?.office_logo_path"
                    :src="'/storage/' + settings.office_logo_path" 
                    alt="Office Seal" 
                    class="h-32 w-auto drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                />
                <img 
                    v-else
                    src="/tab_seal.png" 
                    alt="TAB Seal" 
                    class="h-32 w-auto drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                />
            </div>
            
            <h1 class="text-4xl font-black tracking-tight text-white sm:text-6xl mb-6">
                {{ settings?.office_name || 'Tricycle Adjudication Board' }}
            </h1>
            
            <div class="flex justify-center mb-8">
                <div class="h-1 w-24 theme-bg rounded-full"></div>
            </div>

            <p class="mx-auto max-w-2xl text-lg leading-8 text-slate-300 font-light">
                {{ settings?.about_us || 'Empowering the community through transparent, digital, and efficient tricycle franchise management.' }}
            </p>
        </div>
    </div>

    <div class="py-24 bg-white sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="mx-auto max-w-2xl lg:max-w-none">
                <div class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-2">
                    
                    <div class="flex flex-col bg-slate-50 rounded-3xl p-8 sm:p-10 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-x-4 border-b border-gray-200 pb-6 mb-6">
                            <div class="flex h-12 w-12 flex-none items-center justify-center rounded-lg theme-bg">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Our Mission</h2>
                        </div>
                        <p class="flex-auto text-base leading-relaxed text-gray-600 whitespace-pre-line">
                            {{ settings?.mission || 'To provide a fast, reliable, and graft-free franchising system for our local operators.' }}
                        </p>
                    </div>

                    <div class="flex flex-col bg-slate-50 rounded-3xl p-8 sm:p-10 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-x-4 border-b border-gray-200 pb-6 mb-6">
                            <div class="flex h-12 w-12 flex-none items-center justify-center rounded-lg theme-bg">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Our Vision</h2>
                        </div>
                        <p class="flex-auto text-base leading-relaxed text-gray-600 whitespace-pre-line">
                            {{ settings?.vision || 'A highly modernized and disciplined public transport sector benefiting all citizens.' }}
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.theme-bg { background-color: v-bind(currentThemeColor); }
</style>