<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    assessment: {
        type: Object,
        required: true
    },
    operatorName: {
        type: String,
        default: 'N/A'
    }
});

const printDateTime = ref('');

// Capture the exact date and time whenever a new assessment is loaded for printing
watch(() => props.assessment, () => {
    printDateTime.value = new Date().toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}, { immediate: true, deep: true });

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(value || 0);
};

const formattedDate = computed(() => {
    if (!props.assessment.assessment_date) return 'N/A';
    return new Date(props.assessment.assessment_date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const franchiseNumber = computed(() => props.assessment.franchise?.franchise_number || 'N/A');
</script>

<template>
    <div class="w-full max-w-4xl mx-auto bg-white text-black print:p-8 print:m-0 print:min-h-screen print:flex print:flex-col print:justify-center relative">
        
        <div class="w-full">
            <div class="text-center mb-8 border-b-2 border-black pb-4 mt-8 print:mt-0">
                <h1 class="text-3xl font-bold uppercase tracking-widest mb-1">Payment Order</h1>
                <!-- <p class="text-sm font-medium">Local Government Unit / MTFRB</p>
                <p class="text-sm text-gray-600 mt-2">Assessment Reference: <span class="font-bold text-black">#{{ assessment.id }}</span></p> -->
                <p class="text-xs text-gray-500 mt-1">Printed on: {{ printDateTime }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-8 text-sm">
                <div>
                    <p class="mb-1"><span class="font-bold inline-block w-28">Operator:</span> <span class="uppercase">{{ operatorName }}</span></p>
                    <p class="mb-1"><span class="font-bold inline-block w-28">Franchise No:</span> <span class="uppercase font-bold">{{ franchiseNumber }}</span></p>
                    <p class="mb-1" v-if="assessment.application"><span class="font-bold inline-block w-28">Application Ref:</span> {{ assessment.application.reference_number }}</p>
                    <p class="mb-1"><span class="font-bold inline-block w-28">Remarks:</span> {{ assessment.remarks || 'None' }}</p>
                </div>
                <div class="text-right">
                    <p class="mb-1"><span class="font-bold">Assessment Date:</span> {{ formattedDate }}</p>
                    <p class="mb-1"><span class="font-bold">Due Date:</span> <span :class="{'text-red-600 font-bold': assessment.assessment_status === 'overdue'}">{{ assessment.assessment_due || 'N/A' }}</span></p>
                    <p class="mb-1"><span class="font-bold">Status:</span> <span class="uppercase font-semibold">{{ assessment.assessment_status }}</span></p>
                </div>
            </div>

            <div class="mb-12">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b-2 border-black text-left">
                            <th class="py-3 px-2 font-bold w-1/2">Particular / Fee Description</th>
                            <th class="py-3 px-2 text-center font-bold">Quantity</th>
                            <th class="py-3 px-2 text-right font-bold">Amount</th>
                            <th class="py-3 px-2 text-right font-bold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in assessment.particulars" :key="item.id" class="border-b border-gray-300">
                            <td class="py-3 px-2">{{ item.name }}</td>
                            <td class="py-3 px-2 text-center">{{ item.pivot.quantity }}</td>
                            <td class="py-3 px-2 text-right">{{ formatCurrency(item.pivot.subtotal / item.pivot.quantity) }}</td>
                            <td class="py-3 px-2 text-right font-mono">{{ formatCurrency(item.pivot.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-black">
                            <td colspan="3" class="py-4 px-2 text-right font-bold uppercase tracking-wider text-sm">Total Assessed Amount:</td>
                            <td class="py-4 px-2 text-right font-bold font-mono text-lg">{{ formatCurrency(assessment.total_amount_due) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>

<style>
/* This specific block removes the browser's default header (URL, Date) and footer (Page Numbers) */
@media print {
    @page {
        margin: 0; 
    }
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>