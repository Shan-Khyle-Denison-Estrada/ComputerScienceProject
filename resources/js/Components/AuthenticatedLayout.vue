<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// --- LOGIC ---
const page = usePage();
const user = computed(() => page.props.auth.user);

// State for the sidebar
const isSidebarOpen = ref(false); 

// State for the User Dropdown
const isUserDropdownOpen = ref(false);

// State for Notifications Dropdown
const isNotificationDropdownOpen = ref(false);
import { router } from '@inertiajs/vue3';

const markAsRead = (notification) => {
    router.post(route('notifications.read', notification.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            isNotificationDropdownOpen.value = false;
            // Optionally navigate to the URL tied to the notification
            if (notification.data.url) {
                router.get(notification.data.url);
            }
        },
    });
};

// State for Expanded Groups
const expandedGroups = ref({});

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const toggleGroup = (groupName) => {
    expandedGroups.value[groupName] = !expandedGroups.value[groupName];
};

// Helper to determine if a group is active (one of its children is the current route)
const isGroupActive = (children) => {
    return children.some(child => route().current(child.route));
};

// Helper to check if user has a specific role (base or temporary)
const hasRole = (roleName) => {
    if (!user.value) return false;
    if (user.value.active_roles && Array.isArray(user.value.active_roles)) {
        return user.value.active_roles.includes(roleName);
    }
    return user.value.role === roleName; 
};

// Extract only the temporary roles for UI display
const temporaryRoles = computed(() => {
    if (user.value?.active_roles && Array.isArray(user.value.active_roles)) {
        return user.value.active_roles.filter(r => r !== user.value.role);
    }
    return [];
});

// Define menu items divided by role headers
const menuItems = computed(() => {
    let items = [];

    if (hasRole('admin')) {
        items.push({ type: 'header', name: 'Admin' });
        items.push(
            // --- MAIN PAGES (Top Level) ---
            { name: 'Dashboard', route: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
            { name: 'Franchises', route: 'admin.franchises.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' },
            { name: 'Applications', route: 'admin.applications.index', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },

            // --- GROUPS ---
            {
                name: 'Master Data',
                icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                children: [
                    { name: 'Franchise Owners', route: 'admin.franchise-owners.index' },
                    { name: 'Tricycles', route: 'admin.units.index' },
                    { name: 'Drivers', route: 'admin.drivers.index' },
                ]
            },
            {
                name: 'Operations',
                icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                children: [
                    { name: 'Complaints', route: 'admin.complaints.index' },
                    { name: 'Red Flags', route: 'admin.red-flags.index' },
                ]
            },
            {
                name: 'Financials',
                icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                children: [
                    { name: 'Payments', route: 'admin.payments.index' },
                    { name: 'Assessments', route: 'admin.assessments.index' },
                ]
            },
            {
                name: 'System',
                icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                children: [
                    { name: 'Zones', route: 'admin.zones.index' },
                    { name: 'Manage Users', route: 'admin.users.index' },
                    { name: 'Application Logs', route: 'admin.application-logs.index' },
                    { name: 'Settings', route: 'admin.settings.index' },
                ]
            }
        );
    }
    
    if (hasRole('franchise_owner')) {
        items.push({ type: 'header', name: 'Franchise Owner' });
        items.push(
            { name: 'Overview', route: 'franchise.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
            { name: 'New Application', route: 'franchise.make-application', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' }
        );
    }
    
    if (hasRole('evaluator')) {
        items.push({ type: 'header', name: 'Evaluator' });
        items.push(
            { name: 'Applications', route: 'evaluator.applications.index', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
            { name: 'Assessments', route: 'admin.assessments.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }
        );
    }

    // Process-Specific Roles
    const applicationRolesMap = {
        'inspector': { route: 'inspector.applications.index', label: 'Inspector' },
        'city_anti_pollution_officer': { route: 'capo.applications.index', label: 'Anti Pollution Officer' },
        'reviewer': { route: 'reviewer.applications.index', label: 'Reviewer' },
        'sp_approver': { route: 'sp_approver.applications.index', label: 'SP Approver' },
        'tab_approver': { route: 'tab_approver.applications.index', label: 'TAB Approver' },
    };

    Object.keys(applicationRolesMap).forEach(role => {
        if (hasRole(role)) {
            items.push({ type: 'header', name: applicationRolesMap[role].label });
            items.push({ 
                name: `Applications`, 
                route: applicationRolesMap[role].route, 
                icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' 
            });
        }
    });

    if (hasRole('collector')) {
        items.push({ type: 'header', name: 'Collector' });
        items.push(
            { name: 'Payments', route: 'admin.payments.index', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }
        );
    }

    if (hasRole('encoder')) {
        items.push({ type: 'header', name: 'Encoder' });
        items.push(
            { name: 'Franchises', route: 'admin.franchises.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' },
            { name: 'Applications', route: 'admin.applications.index', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
            { name: 'Drivers', route: 'admin.drivers.index', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
            { name: 'Assessments', route: 'admin.assessments.index', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' }
        );
    }

    if (hasRole('releaser')) {
        items.push({ type: 'header', name: 'Releaser' });
        items.push(
            { name: 'Franchises', route: 'admin.franchises.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' }
        );
    }

    return items;
});

// Initialize Expanded State based on current route
onMounted(() => {
    menuItems.value.forEach(item => {
        if (item.children && isGroupActive(item.children)) {
            expandedGroups.value[item.name] = true;
        }
    });
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
                isSidebarOpen ? 'lg:w-72' : 'lg:w-0'
            ]"
        >
            <div class="w-72 flex flex-col h-full">
                
                <div class="flex items-center justify-center h-16 border-b border-gray-800 bg-gray-900 shadow-lg flex-shrink-0">
                    <span class="text-2xl font-bold tracking-wider whitespace-nowrap">
                        TRICY<span class="text-blue-500">SYS</span>
                    </span>
                </div>

                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                    
                    <template v-for="(item, index) in menuItems" :key="index">
                        
                        <div v-if="item.type === 'header'" class="px-3 pt-4 pb-1 mt-2 text-xs font-extrabold text-gray-500 uppercase tracking-widest border-b border-gray-800">
                            {{ item.name }}
                        </div>

                        <Link 
                            v-else-if="!item.children"
                            :href="route(item.route)" 
                            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap"
                            :class="route().current(item.route) 
                                ? 'bg-gray-800 text-white border-l-4 border-blue-500' 
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white'"
                        >
                            <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            <span class="capitalize">{{ item.name }}</span>
                        </Link>

                        <div v-else class="space-y-1">
                            <button 
                                @click="toggleGroup(item.name)"
                                class="w-full group flex items-center justify-between px-2 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap text-gray-300 hover:bg-gray-800 hover:text-white focus:outline-none"
                                :class="{ 'bg-gray-800 text-white': expandedGroups[item.name] || isGroupActive(item.children) }"
                            >
                                <div class="flex items-center">
                                    <svg class="mr-3 h-5 w-5 text-gray-400 group-hover:text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                    </svg>
                                    {{ item.name }}
                                </div>
                                <svg 
                                    class="ml-2 h-4 w-4 transform transition-transform duration-200"
                                    :class="expandedGroups[item.name] ? 'rotate-90 text-white' : 'text-gray-500'"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                >
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div v-show="expandedGroups[item.name]" class="space-y-1 pl-10">
                                <Link 
                                    v-for="child in item.children" 
                                    :key="child.name"
                                    :href="route(child.route)"
                                    class="group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors whitespace-nowrap"
                                    :class="route().current(child.route) 
                                        ? 'text-blue-400' 
                                        : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full mr-2" :class="route().current(child.route) ? 'bg-blue-400' : 'bg-gray-600 group-hover:bg-gray-400'"></span>
                                    {{ child.name }}
                                </Link>
                            </div>
                        </div>

                    </template>

                </nav>

                <div class="p-4 border-t border-gray-800 bg-gray-900 flex-shrink-0">
                    <Link 
                        :href="route('logout')" 
                        method="post" 
                        as="button"
                        replace
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

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden transition-all duration-300 p-0">
            
            <header class="bg-white shadow-sm flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8 relative z-50">
                
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

                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button 
                            @click="isNotificationDropdownOpen = !isNotificationDropdownOpen"
                            class="text-gray-400 hover:text-gray-600 p-2 rounded-md focus:outline-none relative transition-colors"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="$page.props.auth.notifications?.length" class="absolute top-1 right-1 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                            </span>
                        </button>

                        <div v-if="isNotificationDropdownOpen" @click="isNotificationDropdownOpen = false" class="fixed inset-0 z-40 cursor-default"></div>
                        
                        <div v-show="isNotificationDropdownOpen" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5">
                            <div class="px-4 py-2 border-b border-gray-100 font-bold text-gray-700 text-sm">Notifications</div>
                            
                            <div v-if="!$page.props.auth.notifications?.length" class="px-4 py-6 text-sm text-gray-500 text-center">
                                You have no new notifications.
                            </div>
                            
                            <div v-else class="max-h-64 overflow-y-auto">
                                <button 
                                    v-for="notification in $page.props.auth.notifications" 
                                    :key="notification.id"
                                    @click="markAsRead(notification)"
                                    class="w-full text-left px-4 py-3 hover:bg-gray-50 border-b border-gray-50 flex flex-col transition-colors"
                                >
                                    <span class="text-sm font-semibold text-gray-800">{{ notification.data.title }}</span>
                                    <span class="text-xs text-gray-600 mt-0.5">{{ notification.data.message }}</span>
                                    <span class="text-xs font-mono text-blue-500 mt-1">{{ notification.data.reference_number }}</span>
                                </button>
                            </div>
                        </div>
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
                            
                            <div class="flex gap-1 mt-0.5">
                                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full uppercase tracking-wide border border-blue-100">
                                    {{ user.role.replaceAll('_', ' ') }}
                                </span>
                                <span 
                                    v-for="tRole in temporaryRoles" 
                                    :key="tRole" 
                                    class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full uppercase tracking-wide border border-purple-200"
                                    title="Temporary Access Granted"
                                >
                                    + {{ tRole.replaceAll('_', ' ') }}
                                </span>
                            </div>
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
                        class="fixed inset-0 z-40 cursor-default"
                    ></div>

                    <div 
                        v-show="isUserDropdownOpen"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5"
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
                            replace
                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                        >
                            Sign Out
                        </Link>
                    </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto relative p-4">
                <slot />
            </main>
        </div>
    </div>
</template>