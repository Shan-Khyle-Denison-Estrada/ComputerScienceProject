<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    logs: Object,
});

const search = ref('');

// Client-side search for the current paginated data
const visibleLogs = computed(() => {
    if (!search.value) return props.logs.data; 
    const q = search.value.toLowerCase();
    return props.logs.data.filter(log => 
        (log.log_no && log.log_no.toLowerCase().includes(q)) ||
        log.reference_number.toLowerCase().includes(q) || 
        (log.application_type && log.application_type.toLowerCase().includes(q)) ||
        log.action.toLowerCase().includes(q) ||
        log.user_name.toLowerCase().includes(q) ||
        (log.user_role && log.user_role.toLowerCase().includes(q))
    );
});
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
                    <input v-model="search" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm transition" placeholder="Search current page..." />
                </div>
            </div>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Audit ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference No.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="log in visibleLogs" :key="log.id" class="hover:bg-gray-50 transition-colors">
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
                        <tr v-if="visibleLogs.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
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
            <div v-for="log in visibleLogs" :key="log.id" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
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
            <div v-if="visibleLogs.length === 0" class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">
                <p>No logs found</p>
            </div>
        </div>

        <div class="mt-6 flex justify-between items-center" v-if="props.logs.data.length > 0">
            <div class="text-sm text-gray-500">
                Showing {{ props.logs.from }} to {{ props.logs.to }} of {{ props.logs.total }} entries
            </div>
            <div v-if="props.logs.links && props.logs.links.length > 3">
                <Pagination :links="props.logs.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>