<script setup>
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    fiscal_year: String,
    report_date: String,
    date_range: String,
    stats: Object,
    monthly_revenue: Array
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    }).format(value);
};

// Automatically open the print dialog when the page loads
onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head :title="`Annual Report - FY ${props.fiscal_year}`" />

    <div class="min-h-screen bg-gray-100 py-8 print:bg-white print:py-0 flex justify-center">
        <div class="bg-white w-full max-w-[210mm] min-h-[297mm] p-12 shadow-lg print:shadow-none print:p-0">
            
            <div class="border-b-2 border-slate-800 pb-6 mb-8 text-center">
                <h1 class="text-3xl font-black text-slate-800 uppercase tracking-widest">Annual Status Report</h1>
                <p class="text-slate-500 mt-2">Tricycle Franchise Management System</p>
                <div class="mt-4 flex justify-between text-sm font-bold text-slate-600">
                    <span>Fiscal Year: {{ props.fiscal_year }}</span>
                    <span>Date Generated: {{ props.report_date }}</span>
                </div>
            </div>

            <div class="mb-10">
                <h2 class="text-lg font-bold text-slate-800 mb-4 uppercase border-b border-gray-200 pb-2">Executive Summary ({{ props.date_range }})</h2>
                <div class="grid grid-cols-2 gap-6">
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg print:border-slate-300">
                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Total Collections</p>
                        <p class="text-2xl font-black text-slate-800 mt-1">{{ formatCurrency(props.stats.revenue) }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg print:border-slate-300">
                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">New Franchises Created</p>
                        <p class="text-2xl font-black text-slate-800 mt-1">{{ props.stats.franchises }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg print:border-slate-300">
                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">New Operators Added</p>
                        <p class="text-2xl font-black text-slate-800 mt-1">{{ props.stats.operators }}</p>
                    </div>
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg print:border-slate-300">
                        <p class="text-xs text-slate-500 uppercase font-bold tracking-wider">Complaints Registered</p>
                        <p class="text-2xl font-black text-slate-800 mt-1">{{ props.stats.complaints }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-bold text-slate-800 mb-4 uppercase border-b border-gray-200 pb-2">Monthly Collection Breakdown</h2>
                
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-100 print:bg-slate-200">
                            <th class="p-3 font-bold text-slate-700 uppercase tracking-wider border-b border-slate-300">Month / Year</th>
                            <th class="p-3 font-bold text-slate-700 uppercase tracking-wider border-b border-slate-300 text-right">Collection Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="props.monthly_revenue.length === 0">
                            <td colspan="2" class="p-4 text-center text-slate-500 italic">No revenue recorded for this fiscal year.</td>
                        </tr>
                        <tr v-for="(row, index) in props.monthly_revenue" :key="index" class="border-b border-slate-100 print:border-slate-200">
                            <td class="p-3 text-slate-800">{{ row.month }}</td>
                            <td class="p-3 text-slate-800 text-right font-mono">{{ formatCurrency(row.total) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="props.monthly_revenue.length > 0">
                        <tr>
                            <td class="p-3 font-black text-slate-800 text-right uppercase tracking-wider">Total</td>
                            <td class="p-3 font-black text-slate-800 text-right font-mono text-lg border-t-2 border-slate-800">
                                {{ formatCurrency(props.stats.revenue) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-16 text-center text-xs text-slate-400 print:fixed print:bottom-0 print:w-full print:pb-8">
                <p>This is a system-generated report. No signature is required.</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
        size: A4;
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>