<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    franchises: Object,
    operators: Array,
    units: Array,
    drivers: Array,
    zones: Array,
    filters: Object
});

const showAddModal = ref(false);
const search = ref(props.filters.search || '');

const form = useForm({
    operator_id: '',
    unit_id: '',
    driver_id: '',
    zone_id: '',
    date_issued: new Date().toISOString().split('T')[0],
});

// Helper to format Driver Name robustly
const getDriverName = (driver) => {
    if (driver.user) {
        return `${driver.user.last_name}, ${driver.user.first_name}`;
    }
    if (driver.first_name && driver.last_name) {
        return `${driver.last_name}, ${driver.first_name}`;
    }
    return driver.name || 'Unknown Driver';
};

const submitForm = () => {
    form.post(route('admin.franchises.store'), {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        }
    });
};

const handleSearch = () => {
    router.get(route('admin.franchises.index'), { search: search.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Franchises" />

    <AuthenticatedLayout>
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Franchises</h1>
                <p class="text-gray-600 text-sm">Manage franchises, assignments, and history.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                     <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input v-model="search" @keyup.enter="handleSearch" type="text" class="pl-10 pr-4 py-2 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full sm:w-64 shadow-sm text-sm" placeholder="Search Franchise ID..." />
                </div>
                <PrimaryButton @click="showAddModal = true" class="flex items-center gap-2">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    New Franchise
                </PrimaryButton>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-medium border-b border-gray-200 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Franchise ID</th>
                            <th class="px-6 py-4">Current Owner</th>
                            <th class="px-6 py-4">Assigned Unit</th>
                            <th class="px-6 py-4">Details</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="franchise in franchises.data" :key="franchise.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-blue-600 text-base">#{{ franchise.id }}</div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold uppercase"
                                    :class="{
                                        'bg-green-100 text-green-800': franchise.status === 'renewed',
                                        'bg-orange-100 text-orange-800': franchise.status === 'pending renewal',
                                        'bg-red-100 text-red-800': franchise.status === 'terminated',
                                        'bg-gray-100 text-gray-800': !['renewed', 'pending renewal', 'terminated'].includes(franchise.status)
                                    }">
                                    {{ franchise.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="franchise.current_ownership?.new_owner">
                                    <div class="font-medium text-gray-900">
                                        {{ franchise.current_ownership.new_owner.user.last_name }}, {{ franchise.current_ownership.new_owner.user.first_name }}
                                    </div>
                                    <div class="text-xs text-gray-500">Since: {{ franchise.current_ownership.date_transferred }}</div>
                                </div>
                                <div v-else class="text-gray-400 italic">No Owner</div>
                            </td>
                            <td class="px-6 py-4">
                                <div v-if="franchise.current_active_unit?.new_unit">
                                    <span class="font-mono bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200 font-bold">
                                        {{ franchise.current_active_unit.new_unit.plate_number }}
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1">{{ franchise.current_active_unit.new_unit.make.name }}</div>
                                </div>
                                <div v-else class="text-gray-400 italic">No Unit</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div v-if="franchise.driver_assignments && franchise.driver_assignments.length > 0" class="flex flex-col gap-1">
                                    <span v-for="assignment in franchise.driver_assignments" :key="assignment.id" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ assignment.driver.user ? assignment.driver.user.last_name : assignment.driver.last_name }}
                                    </span>
                                </div>
                                <span v-else class="text-gray-400 italic text-xs">Unassigned</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('admin.franchises.show', franchise.id)" class="text-blue-600 hover:text-blue-800 font-medium text-sm transition-colors">
                                    View Details &rarr;
                                </Link>
                            </td>
                        </tr>
                         <tr v-if="franchises.data.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No franchises found. Create one to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
             <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50" v-if="franchises.links && franchises.meta">
                <div class="text-xs text-gray-500">
                    Showing {{ franchises.meta.from }} to {{ franchises.meta.to }} of {{ franchises.meta.total }} results
                </div>
                <div class="flex gap-2">
                    <Link 
                        v-for="(link, key) in franchises.meta.links" 
                        :key="key"
                        :href="link.url || '#'"
                        v-html="link.label"
                        class="px-3 py-1 border rounded-md text-xs transition-colors"
                        :class="[
                            link.active ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                    />
                </div>
            </div>
        </div>

        <Modal :show="showAddModal" @close="showAddModal = false">
            <div class="p-6">
                <div class="text-center mb-6 border-b pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Issue New Franchise</h2>
                    <p class="text-sm text-gray-500">Create a new record and assign initial ownership.</p>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel>Franchise Owner (Operator)</InputLabel>
                        <select v-model="form.operator_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Select Operator</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">
                                {{ op.user.last_name }}, {{ op.user.first_name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <InputLabel>Assign Unit</InputLabel>
                        <select v-model="form.unit_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="" disabled>Select Unit</option>
                            <option v-for="u in units" :key="u.id" :value="u.id">
                                {{ u.plate_number }} - {{ u.make.name }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel>Driver</InputLabel>
                            <select v-model="form.driver_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Select Driver</option>
                                <option v-for="d in drivers" :key="d.id" :value="d.id">
                                    {{ getDriverName(d) }}
                                </option>
                            </select>
                            <p v-if="drivers.length === 0" class="text-xs text-red-500 mt-1">No drivers available.</p>
                        </div>
                        <div>
                            <InputLabel>Zone</InputLabel>
                            <select v-model="form.zone_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="" disabled>Select Zone</option>
                                <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.description }}</option>
                            </select>
                             <p v-if="zones.length === 0" class="text-xs text-red-500 mt-1">No zones available.</p>
                        </div>
                    </div>

                    <div>
                        <InputLabel>Date Issued</InputLabel>
                        <TextInput type="date" v-model="form.date_issued" class="mt-1 block w-full" required />
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                        <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">Issue Franchise</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>