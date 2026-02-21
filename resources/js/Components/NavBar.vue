<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Globally fetch the settings from Inertia's shared props
const page = usePage();
const settings = computed(() => page.props.settings);

const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value;
};
</script>

<template>
    <nav class="bg-white/90 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                
                <div class="flex items-center">
                    <Link href="/" class="flex-shrink-0 flex items-center gap-3">
                        <img 
                            class="h-10 w-10 rounded-full object-cover border border-gray-200 shadow-sm" 
                            :src="settings?.lgu_logo_path ? '/storage/' + settings.lgu_logo_path : '/zc_seal.png'" 
                            alt="LGU Logo" 
                            onerror="this.style.display='none'"
                        />
                        <div class="flex flex-col justify-center">
                            <span class="font-black text-xl tracking-tighter text-gray-900 leading-none">
                                TRICY<span class="text-blue-600">SYS</span>
                            </span>
                            <span class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                                {{ settings?.lgu_name || 'Zamboanga City' }}
                            </span>
                        </div>
                    </Link>
                </div>

                <div class="hidden md:flex md:items-center md:space-x-8">
                    <Link href="/" class="text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">Home</Link>
                    <Link href="/about" class="text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">About</Link>
                    <Link href="/ordinances" class="text-sm font-semibold text-gray-600 hover:text-blue-600 transition-colors">Ordinances</Link>
                    
                    <div class="h-6 w-px bg-gray-200"></div>

                    <Link :href="route('login')" class="text-sm font-bold text-gray-900 hover:text-blue-600 transition-colors">Sign In</Link>
                </div>

                <div class="flex items-center md:hidden">
                    <button @click="toggleMobileMenu" class="text-gray-500 hover:text-gray-900 focus:outline-none p-2">
                        <svg v-if="!isMobileMenuOpen" class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div v-show="isMobileMenuOpen" class="md:hidden bg-white border-t border-gray-100">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 shadow-lg">
                <Link href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Home</Link>
                <Link href="/about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">About</Link>
                <Link href="/ordinances" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Ordinances</Link>
                <div class="border-t border-gray-100 my-2"></div>
                <Link :href="route('login')" class="block px-3 py-2 rounded-md text-base font-bold text-gray-900 hover:text-blue-600 hover:bg-gray-50">Sign In</Link>
            </div>
        </div>
    </nav>
</template>