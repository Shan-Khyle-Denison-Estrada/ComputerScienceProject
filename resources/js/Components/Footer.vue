<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Globally fetch the settings from Inertia's shared props
const page = usePage();
const settings = computed(() => page.props.settings);
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
                        {{ settings?.about_us ? settings.about_us.substring(0, 120) + '...' : 'Streamlining the franchise management process. Efficient, Transparent, Digital.' }}
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">System</h3>
                    <ul class="space-y-3">
                        <li><Link href="/" class="hover:text-blue-400 transition-colors text-sm">Home</Link></li>
                        <li><Link href="/about" class="hover:text-blue-400 transition-colors text-sm">About Us</Link></li>
                        <li><Link href="/ordinances" class="hover:text-blue-400 transition-colors text-sm">City Ordinances</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Portals</h3>
                    <ul class="space-y-3">
                        <li><Link :href="route('login')" class="hover:text-blue-400 transition-colors text-sm">Franchise Owner Login</Link></li>
                        <li><Link :href="route('login')" class="hover:text-blue-400 transition-colors text-sm">Administrator Login</Link></li>
                        <li><Link href="/track" class="hover:text-blue-400 transition-colors text-sm">Track Application</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contact</h3>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ settings?.address || 'City Hall Main Bldg, Zamboanga City' }}</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.2 3 5z"/></svg>
                            <span>{{ settings?.contact_number || '(062) 123-4567' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-slate-500">
                    &copy; {{ new Date().getFullYear() }} {{ settings?.lgu_name || 'City Government' }}. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>