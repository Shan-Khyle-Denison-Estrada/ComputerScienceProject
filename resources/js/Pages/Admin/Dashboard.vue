<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: Object,
    chart: Object,
    recent_payments: Array,
    pending_applications: Array,
    available_fiscal_years: Array,
    filters: Object
});

// Reactive Filters
const selectedFiscalYear = ref(props.filters.fiscal_year);
const selectedPeriod = ref(props.filters.chart_period);
const customStartDate = ref(props.filters.start_date || '');
const customEndDate = ref(props.filters.end_date || '');

// Watch Filters and Fetch Data
watch([selectedFiscalYear, selectedPeriod, customStartDate, customEndDate], () => {
    // If custom is selected, don't query until both dates are populated
    if (selectedPeriod.value === 'custom' && (!customStartDate.value || !customEndDate.value)) {
        return;
    }

    router.get(window.location.pathname, {
        fiscal_year: selectedFiscalYear.value,
        chart_period: selectedPeriod.value,
        start_date: selectedPeriod.value === 'custom' ? customStartDate.value : null,
        end_date: selectedPeriod.value === 'custom' ? customEndDate.value : null,
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'chart', 'recent_payments', 'pending_applications', 'filters']
    });
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
        
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // Slightly more visible blue top
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
                    pointBorderWidth: 2,
                    // Hide default points if data is dense (e.g. daily view over a year) to prevent clutter
                    pointRadius: props.chart.data.length > 40 ? 0 : 4, 
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#3b82f6',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                    tension: 0.3, // Slightly less rubber-band effect to better represent true data angles
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false, // Huge UX win: triggers tooltips without needing to hover exactly over the tiny point
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)', // Slightly transparent slate-900
                        titleColor: '#94a3b8', // slate-400
                        titleFont: { size: 12, weight: 'normal' },
                        bodyFont: { size: 14, weight: 'bold' },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false, // Removes the redundant color box for single-dataset charts
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
                        border: { display: false }, // Removes the solid axis line for a cleaner look
                        grid: { borderDash: [4, 4], color: '#e2e8f0', drawTicks: false },
                        ticks: { 
                            font: { size: 11 },
                            color: '#64748b',
                            padding: 8,
                            callback: function(value) {
                                // Uses 10K, 1M formatting for axis to save space and reduce cognitive load
                                return new Intl.NumberFormat('en-PH', {
                                    style: 'currency',
                                    currency: 'PHP',
                                    notation: 'compact',
                                    compactDisplay: 'short'
                                }).format(value);
                            }
                        }
                    },
                    x: { 
                        grid: { display: false, drawTicks: false },
                        ticks: { 
                            font: { size: 12, weight: '500' },
                            color: '#64748b',
                            padding: 8,
                            maxRotation: 45, // Prevents long labels from wrapping awkwardly
                            minRotation: 0
                        }
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

// Matches the routing logic used in your Applications Index.vue
const getApplicationLink = (app) => {
    try {
    switch (app.type) {
        case 'Renewal': 
            return route('admin.applications.renewal.show', app.id);
        case 'Change of Owner': 
        case 'Change of Owner (Deceased)': // <-- Add this case
            return route('admin.applications.change-of-owner.show', app.id);
        case 'Change of Unit': 
            return route('admin.applications.change-of-unit.show', app.id);
        case 'New Franchise':
            return route('admin.applications.show-new-franchise', app.id);
        case 'Franchise Owner Account': 
        default: 
            return route('admin.applications.show', app.id);
    }
    } catch (error) {
        // Fallback static paths just in case Ziggy's `route()` helper isn't rendering cleanly
        switch (app.type) {
            case 'Renewal': 
                return `/admin/applications/renewal/${app.id}`;
            case 'Change of Owner': 
            case 'Change of Owner (Deceased)':
                return `/admin/applications/change-of-owner/${app.id}`;
            case 'Change of Unit': 
                return `/admin/applications/change-of-unit/${app.id}`;
            case 'New Franchise':
                return `/admin/applications/new-franchise/${app.id}`;
            default: 
                return `/admin/applications/${app.id}`;
        }
    }
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <div class="h-full flex flex-col gap-6 overflow-y-auto overflow-x-hidden pb-4 custom-scrollbar">
            
            <div class="flex-none flex flex-col gap-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                        <p class="text-gray-500 text-sm mt-1">Overview of Tricycle Franchise Management System</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                        <a :href="`/dashboard/report/download?fiscal_year=${selectedFiscalYear}`" 
                           class="w-full sm:w-auto flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-700 text-white px-4 py-2.5 rounded-xl shadow-sm transition-colors text-sm font-semibold">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download Report
                        </a>

                        <div class="flex items-center justify-between w-full sm:w-auto gap-2 bg-indigo-50 border border-indigo-100 px-4 py-2.5 rounded-xl shadow-sm">
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-xs font-semibold text-indigo-500 uppercase tracking-wider">Fiscal Year:</span>
                            </div>
                            <select v-model="selectedFiscalYear" class="w-full sm:w-auto text-right sm:text-left text-base font-black text-indigo-700 bg-transparent border-none focus:ring-0 p-0 pr-8 min-w-[110px] cursor-pointer">
                                <option v-for="year in props.available_fiscal_years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    <Link href="/admin/franchises" class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100 cursor-pointer hover:shadow-md hover:border-blue-200 transition-all block group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded" 
                                  :class="props.stats.franchise_growth >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'">
                                {{ props.stats.franchise_growth > 0 ? '+' : '' }}{{ props.stats.franchise_growth }}% vs Prev FY
                            </span>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.total_franchises }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Franchises Created</p>
                        </div>
                    </Link>

                    <Link href="/admin/franchise-owners" class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100 cursor-pointer hover:shadow-md hover:border-emerald-200 transition-all block group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.total_operators }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Total Operators Added</p>
                        </div>
                    </Link>

                    <Link href="/payments" class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100 cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all block group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <span class="text-xs font-bold px-2 py-1 rounded" 
                                  :class="props.stats.revenue_growth >= 0 ? 'text-green-600 bg-green-50' : 'text-red-600 bg-red-50'">
                                {{ props.stats.revenue_growth > 0 ? '+' : '' }}{{ props.stats.revenue_growth }}% vs Prev FY
                            </span>
                        </div>
                        <div>
                            <span class="text-xl sm:text-2xl font-bold text-slate-800">{{ formatCurrency(props.stats.total_revenue) }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Total Revenue</p>
                        </div>
                    </Link>

                    <Link href="/admin/complaints" class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100 cursor-pointer hover:shadow-md hover:border-orange-200 transition-all block group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center group-hover:bg-orange-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.pending_complaints }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Pending Complaints</p>
                        </div>
                    </Link>

                    <Link href="/admin/red-flags" class="bg-white rounded-2xl shadow-sm p-6 flex flex-col justify-between border border-gray-100 cursor-pointer hover:shadow-md hover:border-red-200 transition-all block group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-full bg-red-50 text-red-600 flex items-center justify-center group-hover:bg-red-100 transition-colors">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>
                            </div>
                        </div>
                        <div>
                            <span class="text-3xl font-black text-slate-800">{{ props.stats.pending_red_flags }}</span>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mt-1">Pending Red Flags</p>
                        </div>
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col flex-1 min-h-[400px]">
                            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-bold text-slate-800">Revenue Overview</h2>
                                    <p class="text-xs text-slate-500 mt-1">Income generated from franchise payments</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <select v-model="selectedPeriod" class="text-sm border-gray-200 rounded-lg text-slate-600 focus:ring-blue-500 focus:border-blue-500 py-1.5 pl-3 pr-8">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="annually">Annually</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div v-if="selectedPeriod === 'custom'" class="p-4 bg-slate-50 border-b border-gray-100 flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-semibold text-slate-600">From:</label>
                                    <input type="date" v-model="customStartDate" class="text-sm border-gray-200 rounded-lg py-1 px-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-semibold text-slate-600">To:</label>
                                    <input type="date" v-model="customEndDate" class="text-sm border-gray-200 rounded-lg py-1 px-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="p-6 flex-1 relative h-64 sm:h-auto">
                                <canvas ref="mainChartCanvas"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col">
                            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-bold text-slate-800">Pending Applications</h2>
                                <Link href="/applications" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View All</Link>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-gray-100">
                                            <th class="p-4 font-semibold">Reference</th>
                                            <th class="p-4 font-semibold">Applicant</th>
                                            <th class="p-4 font-semibold">Type</th>
                                            <th class="p-4 font-semibold">Date</th>
                                            <th class="p-4 font-semibold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-if="props.pending_applications.length === 0">
                                            <td colspan="5" class="p-4 text-center text-slate-400 italic text-sm">
                                                No pending applications found for this fiscal year.
                                            </td>
                                        </tr>
                                        <tr v-for="app in props.pending_applications" :key="app.id" class="hover:bg-slate-50 transition-colors group">
                                            <td class="p-4">
                                                <Link :href="getApplicationLink(app)" class="text-blue-600 font-semibold text-sm group-hover:underline">
                                                    {{ app.reference_number }}
                                                </Link>
                                            </td>
                                            <td class="p-4 text-sm font-medium text-slate-700">{{ app.applicant }}</td>
                                            <td class="p-4 text-sm text-slate-600">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-slate-100 text-slate-700">
                                                    {{ app.type }}
                                                </span>
                                            </td>
                                            <td class="p-4 text-sm text-slate-500">{{ app.date }}</td>
                                            <td class="p-4">
                                                <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full bg-amber-50 text-amber-600 border border-amber-200">
                                                    {{ app.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full">
                            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                                <h2 class="text-base font-bold text-slate-800">Recent Payments</h2>
                                <Link href="/payments" class="text-sm font-semibold text-blue-600 hover:text-blue-700">View All</Link>
                            </div>
                            
                            <div v-if="props.recent_payments.length === 0" class="h-full flex items-center justify-center text-slate-400 italic text-sm p-4">
                                No recent transactions for this fiscal year.
                            </div>
                            
                            <div v-else class="divide-y divide-gray-50">
                                <div v-for="payment in props.recent_payments" :key="payment.id" class="p-4 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <!-- <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">
                                            {{ payment.plate_number.slice(-3) }}
                                        </div> -->
                                        <div>
                                            <p class="text-sm font-bold text-slate-700">{{ payment.payee }}</p>
                                            <p class="text-xs text-slate-400">{{ payment.franchise_number }} • {{ payment.date }}</p>
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
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>