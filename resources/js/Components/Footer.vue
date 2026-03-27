<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Globally fetch the settings and auth from Inertia's shared props
const page = usePage();
const settings = computed(() => page.props.settings);
const user = computed(() => page.props.auth?.user); // <-- Added Auth check here
</script>

<template>
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-3 mb-4">
                        <img 
                            :src="settings?.lgu_logo_path ? '/storage/' + settings.lgu_logo_path : '/zc_seal.png'" 
                            alt="LGU Logo" 
                            class="h-10 w-10 rounded-full object-cover opacity-90 shadow-sm bg-white/10" 
                            onerror="this.style.display='none'"
                        />
                        <span class="text-xl font-bold text-white tracking-tight">TRICY<span class="text-blue-500">SYS</span></span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-400">
                        {{ settings?.about_us ? settings.about_us.substring(0, 120) + '...' : 'Smart Franchising System for modernizing tricycle franchise management and ensuring seamless transactions.' }}
                    </p>
                </div>

                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-sm">Quick Links</h3>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><Link href="/" class="hover:text-blue-400 transition-colors">Home</Link></li>
                        <li><Link href="/about" class="hover:text-blue-400 transition-colors">About Us</Link></li>
                        <li><Link href="/ordinances" class="hover:text-blue-400 transition-colors">Ordinances</Link></li>
                        <!-- <li><Link href="/news" class="hover:text-blue-400 transition-colors">News & Updates</Link></li>
                        <li><Link href="/contact" class="hover:text-blue-400 transition-colors">Contact</Link></li> -->
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-sm">Account</h3>
                    
                    <div v-if="user" class="space-y-2">
                        <p class="text-sm text-slate-400 mb-2">You are currently signed in.</p>
                        <Link :href="route('dashboard')" class="inline-flex items-center gap-2 text-sm font-bold text-blue-500 hover:text-blue-400 transition-colors">
                            Go to My Portal
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </Link>
                    </div>

                    <div v-else class="space-y-3">
                        <div>
                            <Link :href="route('apply')" class="text-sm text-slate-400 hover:text-blue-400 transition-colors block">Register New Operator Account</Link>
                        </div>
                        <div class="pt-2 border-t border-slate-800">
                            <span class="text-sm text-slate-400 block mb-1">Already have an account?</span>
                            <Link :href="route('login')" class="text-sm font-bold text-blue-500 hover:text-blue-400 transition-colors">
                                Login here
                            </Link>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-white font-bold mb-4 uppercase tracking-wider text-sm">Contact Us</h3>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ settings?.address || 'City Hall Main Bldg, Zamboanga City' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>{{ settings?.contact_number || '+63 912 345 6789' }}</span>
                        </li>
                        <!-- <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>{{ settings?.email || 'support@tricysys.online' }}</span>
                        </li> -->
                    </ul>
                </div>

            </div>
            
            <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-slate-500 text-center md:text-left">
                    &copy; {{ new Date().getFullYear() }} {{ settings?.office_name || 'Tricycle Franchising Board' }}. All rights reserved.
                </p>
                <div class="flex gap-4">
                    <Link href="/privacy-policy" class="text-sm text-slate-500 hover:text-white transition-colors">Privacy Policy</Link>
                    <Link href="/terms-of-service" class="text-sm text-slate-500 hover:text-white transition-colors">Terms of Service</Link>
                </div>
            </div>
        </div>
    </footer>
</template>