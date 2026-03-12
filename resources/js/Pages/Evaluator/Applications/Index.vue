<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; 
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    applications: Object, 
    filters: Object 
});

const search = ref(props.filters?.search || '');

const handleSearch = debounce(() => {
    router.get(route('evaluator.applications.index'), {
        search: search.value,
    }, { preserveState: true, replace: true });
}, 300);

watch(search, handleSearch);
</script>

<template>
    <Head title="Pending Renewals - Evaluator" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pending Renewals for Evaluation</h2>
        </template>
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md flex items-center shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ $page.props.flash.success }}
        </div>
        <div class="w-full">
            <div class="bg-white p-4 mb-4 flex items-center justify-between border-b border-gray-200 rounded-lg">
                <div class="w-1/3">
                    <TextInput v-model="search" type="text" class="w-full" placeholder="Search Reference No or Name..." />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="p-4 font-semibold text-sm text-gray-700">Ref #</th>
                                <th class="p-4 font-semibold text-sm text-gray-700">Applicant</th>
                                <th class="p-4 font-semibold text-sm text-gray-700">Type</th>
                                <th class="p-4 font-semibold text-sm text-gray-700">Status</th>
                                <th class="p-4 font-semibold text-sm text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="app in applications.data" :key="app.id" class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 text-sm text-gray-900 font-medium">{{ app.reference_number }}</td>
                                <td class="p-4 text-sm text-gray-600">{{ app.first_name }} {{ app.last_name }}</td>
                                <td class="p-4 text-sm text-gray-600">{{ app.application_type }}</td>
                                <td class="p-4 text-sm text-gray-600">
                                    <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm">
                                    <Link v-if="app.application_type === 'Change of Owner'" 
                                        :href="route('evaluator.applications.show-change-of-owner', app.id)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">Evaluate &rarr;</Link>
                                        
                                    <Link v-else-if="app.application_type === 'Change of Unit'" 
                                        :href="route('evaluator.applications.show-change-of-unit', app.id)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">Evaluate &rarr;</Link>
                                        
                                    <Link v-else-if="app.application_type === 'New Franchise'" 
                                        :href="route('evaluator.applications.show-new-franchise', app.id)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">Evaluate &rarr;</Link>

                                    <Link v-else-if="app.application_type === 'Franchise Owner Account'" 
                                        :href="route('evaluator.applications.show-franchise-owner-account', app.id)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">Evaluate &rarr;</Link>
                                        
                                    <Link v-else 
                                        :href="route('evaluator.applications.show', app.id)" 
                                        class="text-blue-600 hover:text-blue-800 font-medium text-sm">Evaluate &rarr;</Link>
                                </td>
                            </tr>
                            <tr v-if="applications.data.length === 0">
                                <td colspan="5" class="p-6 text-center text-gray-500">No pending renewals to evaluate.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    <Pagination :links="applications.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>