<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

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
                    <Link href="/" class="flex-shrink-0 flex items-center gap-2">
                        <img class="h-10 w-auto" src="/zc_seal.png" alt="Logo" onerror="this.style.display='none'"/>
                        <div class="flex flex-col">
                            <span class="font-black text-xl tracking-tighter text-gray-900 leading-none">
                                TRICY<span class="text-blue-600">SYS</span>
                            </span>
                            <span class="text-[0.65rem] font-bold text-gray-500 uppercase tracking-widest">
                                Zamboanga City
                            </span>
                        </div>
                    </Link>
                </div>

                <div class="hidden md:flex md:items-center md:space-x-8">
                    <Link href="/" class="text-gray-500 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Home</Link>
                    <Link href="/about" class="text-gray-500 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">About</Link>
                    <Link href="/ordinances" class="text-gray-500 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Ordinances</Link>
                    <Link href="/contact" class="text-gray-500 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition-colors">Contact</Link>
                    
                    <Link href="/login" class="bg-blue-600 text-white px-5 py-2.5 rounded-full text-sm font-bold shadow-lg shadow-blue-600/30 hover:bg-blue-700 hover:shadow-blue-600/50 transition-all transform hover:-translate-y-0.5">
                        Login Portal
                    </Link>
                </div>

                <div class="flex items-center md:hidden">
                    <button @click="toggleMobileMenu" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <span class="sr-only">Open main menu</span>
                        <svg v-if="!isMobileMenuOpen" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                <Link href="/contact" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Contact Us</Link>
                <div class="pt-4 pb-2">
                    <Link href="/login" class="block w-full text-center px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700">
                        Access Portal
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>