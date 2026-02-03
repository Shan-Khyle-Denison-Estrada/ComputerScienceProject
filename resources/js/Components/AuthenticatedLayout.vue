<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// --- LOGIC ---
const page = usePage();
// Ensure we are accessing the user correctly
const user = computed(() => page.props.auth.user);

// State for the drawer
// Default to true (open) on desktop
const isSidebarOpen = ref(true); 

// Toggle function handles both Mobile (slide-over) and Desktop (collapse)
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

// Define menu items based on role
const menuItems = computed(() => {
    if (user.value.role === 'admin') {
        return [
            { name: 'Dashboard', route: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
            { name: 'Manage Users', route: 'admin.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
        ];
    }
    if (user.value.role === 'franchise_owner') {
        return [
            { name: 'Overview', route: 'franchise.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
        ];
    }
    return [];
});
</script>

<template>
    <div class="h-screen flex overflow-hidden bg-gray-100">

        <div 
            v-show="isSidebarOpen" 
            @click="isSidebarOpen = false"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 transition-opacity lg:hidden"
        ></div>

        <aside 
            class="fixed lg:relative z-50 inset-y-0 left-0 bg-gray-900 text-white transition-all duration-300 ease-in-out flex flex-col overflow-hidden"
            :class="[
                // Mobile behavior: Slide in/out
                'transform lg:transform-none',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                // Desktop behavior: Width toggle (Open = 64, Closed = 0)
                isSidebarOpen ? 'lg:w-64' : 'lg:w-0'
            ]"
        >
            <div class="w-64 flex flex-col h-full">
                
                <div class="flex items-center justify-center h-16 border-b border-gray-800 bg-gray-900 shadow-lg flex-shrink-0">
                    <span class="text-2xl font-bold tracking-wider whitespace-nowrap">
                        TRICY<span class="text-blue-500">SYS</span>
                    </span>
                </div>

                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                    <Link 
                        v-for="item in menuItems" 
                        :key="item.name"
                        :href="route(item.route)" 
                        class="group flex items-center px-2 py-3 text-base font-medium rounded-md transition-colors whitespace-nowrap"
                        :class="route().current(item.route) 
                            ? 'bg-gray-800 text-white border-l-4 border-blue-500' 
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                    >
                        <svg class="mr-4 h-6 w-6 text-gray-400 group-hover:text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        {{ item.name }}
                    </Link>
                </nav>

                <div class="p-4 border-t border-gray-800 bg-gray-900 flex-shrink-0">
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button" 
                        class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors whitespace-nowrap"
                    >
                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out
                    </Link>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden transition-all duration-300">
            
            <header class="bg-white shadow-sm flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8 relative z-10">
                
                <div class="flex items-center">
                    <button 
                        @click="toggleSidebar" 
                        class="text-gray-500 focus:outline-none hover:text-gray-700 focus:bg-gray-100 p-2 rounded-md"
                    >
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center">
                    <div class="flex flex-col items-end mr-3">
                        <span class="text-sm font-bold text-gray-900 leading-tight">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase tracking-wide mt-0.5">
                            {{ user.role.replace('_', ' ') }}
                        </span>
                    </div>
                    
                    <div class="h-10 w-10 rounded-full bg-gray-800 text-white flex items-center justify-center border-2 border-gray-100 shadow-sm overflow-hidden">
                        <img v-if="user.user_photo" :src="'/storage/' + user.user_photo" class="h-full w-full object-cover" />
                        <span v-else class="text-lg font-bold">
                             {{ user.first_name ? user.first_name.charAt(0) : 'U' }}
                        </span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 relative">
                <slot />
            </main>
        </div>
    </div>
</template>