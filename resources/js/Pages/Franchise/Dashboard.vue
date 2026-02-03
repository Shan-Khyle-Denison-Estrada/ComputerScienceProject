<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    // Props passed from controller
});
</script>

<template>
    <Head title="Franchise Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Franchise Portal
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="bg-blue-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                    <h3 class="text-2xl font-bold">Welcome, Partner!</h3>
                    <p class="mt-2 opacity-90">Your franchise license is active until December 31, 2026.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-gray-800 mb-2">My Tricycle Units</h4>
                            <p class="text-gray-600 mb-4">Manage your registered units and drivers.</p>
                            <div class="border-t pt-4">
                                <ul class="space-y-2">
                                    <li class="flex justify-between items-center text-sm">
                                        <span>Body #0142 (Plate: ABC-1234)</span>
                                        <span class="text-green-600 font-semibold bg-green-100 px-2 py-0.5 rounded-full text-xs">Active</span>
                                    </li>
                                    <li class="flex justify-between items-center text-sm">
                                        <span>Body #0143 (Plate: XYZ-9876)</span>
                                        <span class="text-yellow-600 font-semibold bg-yellow-100 px-2 py-0.5 rounded-full text-xs">Maintenance</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h4 class="text-lg font-bold text-gray-800 mb-2">Recent Notifications</h4>
                            <p class="text-gray-600 text-sm mb-4">Stay updated with city ordinances and violations.</p>
                            
                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                                <span class="block sm:inline">No active violations found. Good job!</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>