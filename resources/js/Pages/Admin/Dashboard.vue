<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    chart: Object, // Single chart prop
    recent_payments: Array
});

// Chart Reference
const mainChartCanvas = ref(null);
let chartInstance = null;

// Currency Formatter
const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 0
    }).format(value);
};

// Initialize Single Chart
const initChart = () => {
    if (mainChartCanvas.value) {
        if (chartInstance) chartInstance.destroy();
        
        const ctx = mainChartCanvas.value.getContext('2d');
        
        // Create a gradient for the line chart
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.2)'); // Blue
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        chartInstance = new Chart(mainChartCanvas.value, {
            type: 'line',
            data: {
                labels: props.chart.labels,
                datasets: [{
                    label: 'Revenue',
                    data: props.chart.data,
                    borderColor: '#3b82f6', // Tailwind Blue-500
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#3b82f6',
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    tension: 0.4, // Smooth curve
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                return formatCurrency(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [4, 4], color: '#e2e8f0' },
                        ticks: { font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 12, weight: 'bold' } }
                    }
                }
            }
        });
    }
};

onMounted(() => {
    initChart();
});

watch(() => props.chart, () => initChart(), { deep: true });
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col gap-6 overflow-hidden">
            
            <div class="flex-none flex flex-col gap-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-500 text-sm">Overview of Tricycle Franchise Management System</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded bg-slate-100 text-slate-600" 
                                  :class="props.stats.franchise_growth >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'">
                                {{ props.stats.franchise_growth }}%
                            </span>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.total_franchises }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Total Franchises</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded bg-emerald-50 text-emerald-600">Active</span>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.active_drivers }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Registered Drivers</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded" 
                                  :class="props.stats.revenue_growth >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'">
                                {{ props.stats.revenue_growth }}%
                            </span>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ formatCurrency(props.stats.total_revenue) }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Total Collections</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-3 gap-6 pb-2">
                
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-slate-700">Collection Trends</h3>
                        <div class="flex gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <span class="text-xs text-slate-500">Revenue</span>
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 relative w-full">
                        <canvas ref="mainChartCanvas"></canvas>
                    </div>
                </div>

                <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-0 flex flex-col overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700">Recent Transactions</h3>
                        <Link href="/admin/payments" class="text-xs font-bold text-indigo-500 hover:text-indigo-600">View All</Link>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto custom-scrollbar">
                        <div v-if="props.recent_payments.length === 0" class="h-full flex items-center justify-center text-slate-400 italic text-sm p-4">
                            No recent transactions.
                        </div>

                        <div v-else class="divide-y divide-gray-50">
                            <div v-for="payment in props.recent_payments" :key="payment.id" class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">
                                        {{ payment.plate_number.slice(-3) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">{{ payment.payee }}</p>
                                        <p class="text-xs text-slate-400">{{ payment.plate_number }} • {{ payment.date }}</p>
                                    </div>
                                </div>
                                <span class="font-mono font-bold text-sm text-emerald-600">
                                    +{{ formatCurrency(payment.amount).replace('PHP', '').trim() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>