<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        
        <div class="h-full flex flex-col overflow-hidden">
            
            <div class="flex-none mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Dashboard Overview</h1>
                <p class="text-gray-600">Real-time monitoring of city transport data.</p>
            </div>

            <div class="flex-none grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500 relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Total Franchises</div>
                        <div class="text-3xl font-extrabold text-gray-800">1,250</div>
                        <div class="text-green-500 text-sm font-medium mt-2">↑ 12% from last month</div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-blue-50 to-transparent opacity-50"></div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500 relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Active Units</div>
                        <div class="text-3xl font-extrabold text-gray-800">980</div>
                        <div class="text-green-500 text-sm font-medium mt-2">● 85% Operational</div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-green-50 to-transparent opacity-50"></div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500 relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Daily Violations</div>
                        <div class="text-3xl font-extrabold text-gray-800">14</div>
                        <div class="text-red-500 text-sm font-medium mt-2">↓ 2 less than yesterday</div>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-red-50 to-transparent opacity-50"></div>
                </div>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="bg-white p-6 rounded-xl shadow-sm lg:col-span-2 flex flex-col h-full">
                    <div class="flex-none flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-gray-800">Revenue & Renewals</h3>
                        <select class="text-sm border-gray-300 rounded-md text-gray-600 focus:ring-blue-500 focus:border-blue-500">
                            <option>This Year</option>
                            <option>Last Year</option>
                        </select>
                    </div>
                    
                    <div class="flex-1 w-full relative border-b border-l border-gray-200 min-h-0">
                        <div class="absolute inset-0 flex flex-col justify-between text-xs text-gray-400 py-2">
                            <div class="border-b border-gray-100 w-full h-0"></div>
                            <div class="border-b border-gray-100 w-full h-0"></div>
                            <div class="border-b border-gray-100 w-full h-0"></div>
                            <div class="border-b border-gray-100 w-full h-0"></div>
                            <div class="border-b border-gray-100 w-full h-0"></div>
                        </div>

                        <svg viewBox="0 0 500 150" class="w-full h-full absolute inset-0 z-10 pb-6" preserveAspectRatio="none">
                            <defs>
                                <linearGradient id="gradient" x1="0" x2="0" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#3B82F6" stop-opacity="0.2"/>
                                    <stop offset="100%" stop-color="#3B82F6" stop-opacity="0"/>
                                </linearGradient>
                            </defs>
                            <path 
                                fill="url(#gradient)" 
                                stroke="#3B82F6" 
                                stroke-width="3" 
                                d="M0,120 C50,100 100,130 150,80 C200,40 250,90 300,60 C350,30 400,50 500,20 V150 H0 Z"
                            />
                        </svg>

                        <div class="absolute bottom-0 w-full flex justify-between text-xs text-gray-500 px-1">
                            <span>Jan</span>
                            <span>Mar</span>
                            <span>May</span>
                            <span>Jul</span>
                            <span>Sep</span>
                            <span>Nov</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-0 rounded-xl shadow-sm lg:col-span-1 flex flex-col h-full overflow-hidden">
                    <div class="flex-none p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800">Recent Violations</h3>
                        <button class="text-xs text-blue-600 font-bold hover:underline">View All</button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-left text-sm relative">
                            <thead class="bg-gray-50 text-gray-500 font-medium sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-3">Unit ID</th>
                                    <th class="px-6 py-3">Violation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="i in 10" :key="i">
                                    <td class="px-6 py-4 font-bold text-gray-700">TM-{{ 1000 + i }}</td>
                                    <td class="px-6 py-4" :class="i % 2 === 0 ? 'text-red-500' : 'text-orange-500'">
                                        {{ i % 2 === 0 ? 'Expired Permit' : 'Illegal Parking' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </AuthenticatedLayout>
</template>