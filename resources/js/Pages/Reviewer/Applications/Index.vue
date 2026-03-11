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
    router.get(route('reviewer.applications.index'), {
        search: search.value,
    }, { preserveState: true, replace: true });
}, 300);

watch(search, handleSearch);
</script>

<template>
    <Head title="Pending Reviews - Reviewer" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pending Renewals for Final Review</h2>
        </template>

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
                                    <Link v-if="app.application_type === 'Renewal'" :href="route('reviewer.applications.showRenewal', app.id)" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Review File &rarr;</Link>
                                    <Link v-else-if="app.application_type === 'Change of Unit'" :href="route('reviewer.applications.showChangeOfUnit', app.id)" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Review File &rarr;</Link>
                                    <Link v-else-if="app.application_type === 'Change of Owner'" :href="route('reviewer.applications.showChangeOfOwner', app.id)" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Review File &rarr;</Link>
                                    
                                    <Link v-else-if="app.application_type === 'New Franchise'" :href="`/reviewer/applications/new-franchise/${app.id}`" class="text-blue-600 hover:text-blue-800 font-medium text-sm">Review File &rarr;</Link>
                                </td>
                            </tr>
                            <tr v-if="applications.data.length === 0">
                                <td colspan="5" class="p-6 text-center text-gray-500">No fully paid, cleared applications requiring review.</td>
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