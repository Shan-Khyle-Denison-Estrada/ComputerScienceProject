<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    franchises: Array,
    requirements: Object // Grouped by type
});

const form = useForm({
    franchise_id: '',
    type: '',
    remarks: '',
    proposed_data: {} 
});

// Stepper visual
const steps = [
    { id: 1, name: 'Details', status: 'current' },
    { id: 2, name: 'Requirements', status: 'upcoming' },
    { id: 3, name: 'Review', status: 'upcoming' },
];

const typeOptions = [
    { value: 'renewal', label: 'Franchise Renewal' },
    { value: 'change_of_owner', label: 'Transfer Ownership' },
    { value: 'change_of_unit', label: 'Change Unit' },
];

// Computed requirements preview
const currentRequirements = computed(() => {
    return form.type ? props.requirements[form.type] || [] : [];
});

const submit = () => {
    form.post(route('franchise.applications.store'));
};
</script>

<template>
    <Head title="New Application" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Application</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <nav aria-label="Progress" class="mb-8">
                    <ol role="list" class="space-y-4 md:flex md:space-y-0 md:space-x-8">
                        <li v-for="step in steps" :key="step.name" class="md:flex-1">
                            <div class="group pl-4 py-2 flex flex-col border-l-4 md:pl-0 md:pt-4 md:pb-0 md:border-l-0 md:border-t-4"
                                :class="step.status === 'current' ? 'border-indigo-600' : 'border-gray-200'">
                                <span class="text-xs text-indigo-600 font-semibold tracking-wide uppercase" :class="step.status === 'current' ? 'text-indigo-600' : 'text-gray-500'">Step {{ step.id }}</span>
                                <span class="text-sm font-medium">{{ step.name }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Application Details</h3>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <div>
                            <InputLabel value="Select Franchise" />
                            <select v-model="form.franchise_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" disabled>Select a franchise...</option>
                                <option v-for="f in franchises" :key="f.id" :value="f.id">
                                    MTOP #{{ f.id }} - {{ f.plate }} ({{ f.zone }})
                                </option>
                            </select>
                            <InputError :message="form.errors.franchise_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Application Type" />
                            <select v-model="form.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" disabled>Select type...</option>
                                <option v-for="t in typeOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <div v-if="currentRequirements.length > 0" class="bg-blue-50 p-4 rounded-md border border-blue-100">
                            <p class="text-sm font-bold text-blue-800 mb-2">Required Documents:</p>
                            <ul class="list-disc list-inside text-sm text-blue-700">
                                <li v-for="req in currentRequirements" :key="req.id">{{ req.name }}</li>
                            </ul>
                        </div>

                        <div>
                            <InputLabel value="Remarks / Notes" />
                            <textarea v-model="form.remarks" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton :disabled="form.processing">Proceed to Requirements</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>