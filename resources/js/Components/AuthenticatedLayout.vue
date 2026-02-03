<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// --- LOGIC ---
const page = usePage();
const user = computed(() => page.props.auth.user);

// State for the sidebar
const isSidebarOpen = ref(true); 

// State for the User Dropdown
const isUserDropdownOpen = ref(false);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

// Define menu items based on role
const menuItems = computed(() => {
    if (user.value.role === 'admin') {
        return [
            { 
                name: 'Dashboard', 
                route: 'admin.dashboard', 
                icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
            },
            { 
                name: 'Manage Users', 
                route: 'admin.users.index', 
                icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' 
            },
            { 
                name: 'Drivers', 
                route: 'admin.drivers.index', 
                icon: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2' 
            },
            { 
                name: 'Zones', 
                route: 'admin.zones.index', 
                icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z' 
            },
            { 
                name: 'Payments', 
                route: 'admin.payments.index', 
                // Icon: Banknotes / Cash (Standard for PH payments)
                icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' 
            },
            { 
                name: 'Assessments', 
                route: 'admin.assessments.index', 
                // Icon: Clipboard Document List
                icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' 
            },
        ];
    }
    if (user.value.role === 'franchise_owner') {
        return [
            { 
                name: 'Overview', 
                route: 'franchise.dashboard', 
                icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
            },
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
                'transform lg:transform-none',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
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

                <div class="relative">
                    <button 
                        @click="isUserDropdownOpen = !isUserDropdownOpen"
                        class="flex items-center focus:outline-none p-1 rounded-lg hover:bg-gray-50 transition-colors"
                    >
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
                        
                        <svg class="ml-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div 
                        v-if="isUserDropdownOpen" 
                        @click="isUserDropdownOpen = false" 
                        class="fixed inset-0 z-10 cursor-default"
                    ></div>

                    <div 
                        v-show="isUserDropdownOpen"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-20 ring-1 ring-black ring-opacity-5"
                    >
                        <Link 
                            :href="route('profile.edit')" 
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            @click="isUserDropdownOpen = false"
                        >
                            Profile
                        </Link>
                        
                        <div class="border-t border-gray-100"></div>

                        <Link 
                            :href="route('logout')" 
                            method="post" 
                            as="button" 
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                        >
                            Sign Out
                        </Link>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 relative">
                <slot />
            </main>
        </div>
    </div>
</template>