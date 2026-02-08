<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 font-sans">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="/logo.png" alt="Logo" class="h-8 w-auto grayscale opacity-80" onerror="this.style.display='none'"/>
                        <span class="text-xl font-bold text-white tracking-tight">TRICY<span class="text-blue-500">SYS</span></span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-400">
                        Streamlining the franchise management process for the City of Zamboanga. Efficient, Transparent, Digital.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">System</h3>
                    <ul class="space-y-3">
                        <li><Link href="/" class="hover:text-blue-400 transition-colors text-sm">Home</Link></li>
                        <li><Link href="/login" class="hover:text-blue-400 transition-colors text-sm">Login Portal</Link></li>
                        <li><Link href="/about" class="hover:text-blue-400 transition-colors text-sm">About Us</Link></li>
                        <li><Link href="/ordinances" class="hover:text-blue-400 transition-colors text-sm">Ordinances</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Resources</h3>
                    <ul class="space-y-3">
                        <li><a href="#" class="hover:text-blue-400 transition-colors text-sm">City Ordinances</a></li>
                        <!-- <li><a href="#" class="hover:text-blue-400 transition-colors text-sm">Penalty Matrix</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors text-sm">User Guide</a></li>
                        <li><a href="#" class="hover:text-blue-400 transition-colors text-sm">Download Forms</a></li> -->
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Contact Us</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>City Hall Main Bldg,<br>Zamboanga City</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>(062) 991-2345</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-slate-800 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-slate-500">&copy; 2026 City Government of Zamboanga. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-slate-500 hover:text-white transition-colors text-sm">Privacy</a>
                    <a href="#" class="text-slate-500 hover:text-white transition-colors text-sm">Terms</a>
                </div>
            </div>
        </div>
    </footer>
</template>