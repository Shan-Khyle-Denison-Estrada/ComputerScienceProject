<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object,
    filters: Object,
});

// --- SEARCH, SORT STATE ---
const search = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sortField || '');
const sortDirection = ref(props.filters?.sortDirection || '');

// --- ACTIONS ---
const fetchResults = debounce(() => {
    router.get(window.location.pathname, {
        search: search.value,
        sortField: sortField.value,
        sortDirection: sortDirection.value
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

// Automatically trigger on typing
watch(search, () => {
    fetchResults();
});

const clearSearch = () => {
    search.value = '';
};

const sortBy = (field) => {
    if (sortField.value === field) {
        if (sortDirection.value === 'asc') {
            sortDirection.value = 'desc';
        } else {
            sortField.value = '';
            sortDirection.value = '';
        }
    } else {
        sortField.value = field;
        sortDirection.value = 'asc';
    }
    fetchResults();
};
</script>

<template>
    <Head title="Application Logs" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Application Logs</h1>
                <p class="text-gray-600 text-sm">Audit trail mapping out all tracking and actions made in applications.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input 
                        v-model="search" 
                        type="text" 
                        class="pl-10 pr-10 py-2 border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 block w-full sm:w-64 shadow-sm text-sm transition" 
                        placeholder="Search logs, references, users..." 
                    />
                    <button v-if="search" @click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('log_no')">
                                <div class="flex items-center gap-1">
                                    Audit ID
                                    <svg v-if="sortField === 'log_no' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'log_no' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('created_at')">
                                <div class="flex items-center gap-1">
                                    Date & Time
                                    <svg v-if="sortField === 'created_at' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'created_at' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('reference_number')">
                                <div class="flex items-center gap-1">
                                    Reference No.
                                    <svg v-if="sortField === 'reference_number' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'reference_number' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('application_type')">
                                <div class="flex items-center gap-1">
                                    Type
                                    <svg v-if="sortField === 'application_type' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'application_type' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('user_name')">
                                <div class="flex items-center gap-1">
                                    User
                                    <svg v-if="sortField === 'user_name' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'user_name' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('action')">
                                <div class="flex items-center gap-1">
                                    Action
                                    <svg v-if="sortField === 'action' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else-if="sortField === 'action' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                    <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-xs font-mono font-bold text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ log.log_no }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ log.created_at }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ log.reference_number }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ log.application_type }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 font-medium">{{ log.user_name }}</div>
                                <div class="text-xs text-gray-500 capitalize" v-if="log.user_role">{{ log.user_role }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ log.action }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500">{{ log.details }}</div>
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-base font-medium">No logs found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="md:hidden space-y-4">
            <div v-for="log in logs.data" :key="log.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-mono font-bold text-gray-600 w-max">{{ log.log_no }}</span>
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 w-max">
                            {{ log.reference_number }}
                        </span>
                        <span class="text-xs font-medium text-gray-500 uppercase">{{ log.application_type }}</span>
                    </div>
                    <span class="text-xs text-gray-500 text-right">{{ log.created_at }}</span>
                </div>
                <div class="mb-1">
                    <span class="text-sm font-bold text-gray-800">{{ log.action }}</span>
                    <span class="text-sm text-gray-600 ml-2">
                        by {{ log.user_name }} 
                        <span v-if="log.user_role" class="text-gray-400 capitalize font-normal">({{ log.user_role }})</span>
                    </span>
                </div>
                <div class="text-sm text-gray-500">
                    {{ log.details }}
                </div>
            </div>
            <div v-if="logs.data.length === 0" class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">
                <p>No logs found</p>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center" v-if="logs.data.length > 0">
            <div class="text-sm text-gray-500">
                Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} entries
            </div>
            <div v-if="logs.links && logs.links.length > 3">
                <Pagination :links="logs.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>