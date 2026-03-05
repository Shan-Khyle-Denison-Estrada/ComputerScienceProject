<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    payment: {
        type: Object,
        required: true
    }
});

const printDateTime = ref('');

// Capture exact date and time when printing
watch(() => props.payment, () => {
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
    if (!props.payment.created_at) return 'N/A';
    return new Date(props.payment.created_at).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const payeeName = computed(() => {
    const { payee_first_name, payee_middle_name, payee_last_name } = props.payment;
    const middle = payee_middle_name ? ` ${payee_middle_name} ` : ' ';
    return `${payee_first_name || ''}${middle}${payee_last_name || ''}`.trim();
});

const payeeAddress = computed(() => {
    const { payee_street_address, payee_barangay, payee_city } = props.payment;
    return [payee_street_address, payee_barangay, payee_city].filter(Boolean).join(', ');
});

// Helper function to convert numbers to words
const amountInWords = computed(() => {
    if (!props.payment || !props.payment.amount_paid) return '';
    
    const amount = parseFloat(props.payment.amount_paid);
    if (isNaN(amount)) return '';

    const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
    const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    const convertInteger = (num) => {
        if ((num = num.toString()).length > 9) return 'Overflow';
        const n = ('000000000' + num).substr(-9).match(/^(\d{3})(\d{3})(\d{3})$/);
        if (!n) return '';
        
        let str = '';
        const processGroup = (group) => {
            let res = '';
            if (group[0] !== '0') res += a[Number(group[0])] + 'Hundred ';
            const tensUnits = Number(group.substr(1));
            if (tensUnits !== 0) {
                if (tensUnits < 20) res += a[tensUnits];
                else res += b[group[1]] + ' ' + a[group[2]];
            }
            return res;
        };

        str += (n[1] != 0) ? processGroup(n[1]) + 'Million ' : '';
        str += (n[2] != 0) ? processGroup(n[2]) + 'Thousand ' : '';
        str += (n[3] != 0) ? processGroup(n[3]) : '';
        return str.trim();
    };

    const parts = amount.toFixed(2).split('.');
    const pesos = parseInt(parts[0], 10);
    const centavos = parseInt(parts[1], 10);

    let words = pesos === 0 ? 'Zero' : convertInteger(pesos);
    
    // Append currency
    words += (pesos === 1) ? ' Peso' : ' Pesos';

    // Handle cents
    if (centavos > 0) {
        const centavoWords = convertInteger(centavos);
        words += ` and ${centavoWords} Centavo${centavos > 1 ? 's' : ''} Only`;
    } else {
        words += ' Only';
    }

    return words;
});
</script>

<template>
    <div class="w-full max-w-4xl mx-auto bg-white text-black print:p-8 print:m-0 print:min-h-screen print:flex print:flex-col print:justify-center relative">
        
        <div class="w-full border-2 border-black p-10 relative">
            <div class="text-center mb-10 border-b-2 border-black pb-6">
                <h1 class="text-3xl font-bold uppercase tracking-widest mb-2">Official Receipt</h1>
                <!-- <p class="text-sm font-medium">Local Government Unit / MTFRB</p> -->
                <p class="text-xs text-gray-500 mt-2">Printed on: {{ printDateTime }}</p>
            </div>

            <div class="mb-8 space-y-2">
                <p class="text-lg">
                    <span class="font-bold w-40 inline-block">O.R. No:</span> 
                    <span class="text-red-600 font-bold font-mono tracking-wider">{{ payment.or_number }}</span>
                </p>
                <p class="text-base">
                    <span class="font-bold w-40 inline-block">Date:</span> 
                    {{ formattedDate }}
                </p>
                <p class="text-base">
                    <span class="font-bold w-40 inline-block">Assessment Ref:</span> 
                    #{{ payment.application_reference_id || payment.assessment_id }}
                </p>
            </div>

            <div class="space-y-6 text-base leading-loose mb-4">
                <div class="flex">
                    <span class="font-bold w-40 shrink-0">Received from:</span>
                    <span class="border-b border-black flex-1 uppercase px-4 font-semibold">{{ payeeName }}</span>
                </div>
                <div class="flex">
                    <span class="font-bold w-40 shrink-0">Address:</span>
                    <span class="border-b border-black flex-1 px-4">{{ payeeAddress }}</span>
                </div>
                <div class="flex">
                    <span class="font-bold w-40 shrink-0">The sum of:</span>
                    <span class="border-b border-black flex-1 px-4 uppercase font-bold italic text-sm">{{ amountInWords }}</span>
                </div>
                <div class="flex">
                    <span class="font-bold w-40 shrink-0">Amount:</span>
                    <span class="border-b border-black flex-1 px-4 font-mono font-bold text-lg">{{ formatCurrency(payment.amount_paid) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Removes browser headers, footers, and margins */
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