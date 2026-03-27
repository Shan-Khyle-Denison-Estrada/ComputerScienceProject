<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavBar from '@/Components/NavBar.vue';
import Footer from '@/Components/Footer.vue';

// Fetch globally shared settings
const page = usePage();
const settings = computed(() => page.props.settings);

// Calculate Dynamic Theme Color
const currentThemeColor = computed(() => settings.value?.theme_color || '#2563eb');
</script>

<template>
    <Head title="Terms of Service" />
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
                Terms of Service
            </h1>
            
            <div class="flex justify-center mb-8">
                <div class="h-1 w-24 theme-bg rounded-full"></div>
            </div>

            <p class="mx-auto max-w-2xl text-lg leading-8 text-slate-300 font-light">
                Please read these terms carefully before using our services.
            </p>
        </div>
    </div>

    <div class="py-16 bg-white sm:py-24">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            <div class="flex flex-col bg-slate-50 rounded-3xl p-8 sm:p-12 shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                <div 
                    class="prose max-w-none text-base leading-relaxed text-gray-600" 
                    v-html="settings?.terms_of_service || 'Our terms of service are currently being updated. Please check back later.'">
                </div>
            </div>
        </div>
    </div>

    <Footer />
</template>

<style scoped>
.theme-bg { background-color: v-bind(currentThemeColor); }
</style>