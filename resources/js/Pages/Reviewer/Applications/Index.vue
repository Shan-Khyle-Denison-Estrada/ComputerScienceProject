<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue'; 
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    applications: Object, 
    filters: Object 
});

// --- SEARCH, FILTER & SORT STATE ---
const search = ref(props.filters?.search || '');
const filterType = ref(props.filters?.type || '');
const sortField = ref(props.filters?.sortField || '');
const sortDirection = ref(props.filters?.sortDirection || '');
const showFilterModal = ref(false);

// --- ACTIONS ---
const fetchResults = debounce(() => {
    router.get(route('reviewer.applications.index'), {
        search: search.value,
        type: filterType.value,
        sortField: sortField.value,
        sortDirection: sortDirection.value
    }, { preserveState: true, replace: true, preserveScroll: true });
}, 300);

watch([search, filterType], () => {
    fetchResults();
});

const clearSearch = () => { search.value = ''; };

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

const openFilterModal = () => {
    showFilterModal.value = true;
    document.body.style.overflow = 'hidden';
};

const closeFilterModal = () => {
    showFilterModal.value = false;
    document.body.style.overflow = '';
};

const applyFilters = () => {
    closeFilterModal();
    fetchResults();
};

const resetFilters = () => {
    filterType.value = '';
    closeFilterModal();
    fetchResults();
};
</script>

<template>
    <Head title="Pending Reviews - Reviewer" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pending Renewals for Final Review</h2>
        </template>
        
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md flex items-center shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ $page.props.flash.success }}
        </div>

        <div class="w-full">
            <div class="bg-white p-4 mb-4 flex items-center justify-between border-b border-gray-200 rounded-lg shadow-sm">
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-80">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                        <input 
                            v-model="search" type="text" 
                            class="pl-10 pr-10 py-2 border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 block w-full shadow-sm text-sm transition" 
                            placeholder="Search Reference No or Name..." 
                        />
                        <button v-if="search" @click="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    
                    <button 
                        @click="openFilterModal" 
                        class="p-2 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition relative shrink-0"
                        :class="{'ring-2 ring-red-500 bg-red-50': filterType}"
                        title="Filter Applications"
                    >
                        <svg class="h-5 w-5" :class="filterType ? 'text-red-600' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                        <span v-if="filterType" class="absolute top-0 right-0 -mt-1 -mr-1 h-3 w-3 bg-red-500 border-2 border-white rounded-full"></span>
                    </button>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-full">
                        <thead class="select-none">
                            <tr class="bg-gray-50 border-b">
                                <th class="p-4 font-semibold text-sm text-gray-700 cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('reference_number')">
                                    <div class="flex items-center gap-1">
                                        Ref #
                                        <svg v-if="sortField === 'reference_number' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else-if="sortField === 'reference_number' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                    </div>
                                </th>
                                <th class="p-4 font-semibold text-sm text-gray-700 cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('applicant_name')">
                                    <div class="flex items-center gap-1">
                                        Applicant
                                        <svg v-if="sortField === 'applicant_name' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else-if="sortField === 'applicant_name' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                    </div>
                                </th>
                                <th class="p-4 font-semibold text-sm text-gray-700 cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('application_type')">
                                    <div class="flex items-center gap-1">
                                        Type
                                        <svg v-if="sortField === 'application_type' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else-if="sortField === 'application_type' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                    </div>
                                </th>
                                <th class="p-4 font-semibold text-sm text-gray-700 cursor-pointer hover:bg-gray-100 transition-colors" @click="sortBy('status')">
                                    <div class="flex items-center gap-1">
                                        Status
                                        <svg v-if="sortField === 'status' && sortDirection === 'asc'" class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else-if="sortField === 'status' && sortDirection === 'desc'" class="w-4 h-4 text-red-600 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        <svg v-else class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                    </div>
                                </th>
                                <th class="p-4 font-semibold text-sm text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="app in applications.data" :key="app.id" class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 text-sm text-gray-900 font-medium">{{ app.reference_number }}</td>
                                <td class="p-4 text-sm text-gray-600">{{ app.first_name }} {{ app.last_name }}</td>
                                <td class="p-4 text-sm text-gray-600">{{ app.application_type }}</td>
                                <td class="p-4 text-sm text-gray-600">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        {{ app.status }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm">
                                    <Link v-if="app.application_type === 'Renewal'" :href="route('reviewer.applications.showRenewal', app.id)" class="text-red-600 hover:text-red-800 font-medium text-sm">Review File &rarr;</Link>
                                    <Link v-else-if="app.application_type === 'Change of Unit'" :href="route('reviewer.applications.showChangeOfUnit', app.id)" class="text-red-600 hover:text-red-800 font-medium text-sm">Review File &rarr;</Link>
                                    <Link v-else-if="app.application_type === 'Change of Owner'" :href="route('reviewer.applications.showChangeOfOwner', app.id)" class="text-red-600 hover:text-red-800 font-medium text-sm">Review File &rarr;</Link>
                                    <Link v-else-if="app.application_type === 'New Franchise'" :href="`/reviewer/applications/new-franchise/${app.id}`" class="text-red-600 hover:text-red-800 font-medium text-sm">Review File &rarr;</Link>
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

        <transition name="fade">
            <div v-if="showFilterModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeFilterModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm flex flex-col overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white">
                        <h2 class="text-lg font-bold text-gray-900">Filter Applications</h2>
                        <button @click="closeFilterModal" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">✕</button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <InputLabel>Application Type</InputLabel>
                            <select v-model="filterType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm py-2.5">
                                <option value="">All Types</option>
                                <option value="Renewal">Renewal</option>
                                <option value="Change of Unit">Change of Unit</option>
                                <option value="Change of Owner">Change of Owner</option>
                                <option value="Change of Owner (Deceased)">Change of Owner (Deceased)</option>
                                <option value="New Franchise">New Franchise</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                        <SecondaryButton @click="resetFilters">Clear All</SecondaryButton>
                        <PrimaryButton @click="applyFilters">Done</PrimaryButton>
                    </div>
                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>