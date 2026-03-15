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
        month: 'short',
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
        month: 'short',
        day: 'numeric'
    });
});

const payeeName = computed(() => {
    const { payee_first_name, payee_middle_name, payee_last_name } = props.payment;
    const middle = payee_middle_name ? ` ${payee_middle_name} ` : ' ';
    return `${payee_first_name || ''}${middle}${payee_last_name || ''}`.trim();
});

const payeeAddress = computed(() => {
    // Added payee_province to the destructured object and the array
    const { payee_street_address, payee_barangay, payee_city, payee_province } = props.payment;
    return [payee_street_address, payee_barangay, payee_city, payee_province].filter(Boolean).join(', ');
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
    
    words += (pesos === 1) ? ' Peso' : ' Pesos';

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
    <div class="receipt-container">
        
        <div class="text-center mb-3">
            <h1 class="text-[14px] font-bold uppercase tracking-wide">Official Receipt</h1>
            <p class="text-[10px] text-gray-600 mt-1">Printed: {{ printDateTime }}</p>
        </div>

        <div class="border-y border-dashed border-gray-400 py-2 mb-3 text-[11px] space-y-1">
            <div class="flex justify-between items-start">
                <span class="font-bold mr-2">O.R. No:</span> 
                <span class="font-mono font-bold text-right">{{ payment.or_number }}</span>
            </div>
            <div class="flex justify-between items-start">
                <span class="font-bold mr-2">Date:</span> 
                <span class="text-right">{{ formattedDate }}</span>
            </div>
            <div class="flex justify-between items-start">
                <span class="font-bold mr-2">Ref:</span> 
                <span class="text-right">#{{ payment.assessment_id }}</span>
            </div>
            <div v-if="payment.franchise_number" class="flex justify-between items-start">
                <span class="font-bold mr-2">Fran. No:</span> 
                <span class="font-bold text-right">{{ payment.franchise_number }}</span>
            </div>
        </div>

        <div class="text-[11px] leading-tight space-y-3 mb-3">
            <div>
                <div class="font-bold">Received from:</div>
                <div class="uppercase pl-2">{{ payeeName }}</div>
            </div>
            <div v-if="payment.franchise_owner">
                <div class="font-bold">Franchise Owner:</div>
                <div class="uppercase pl-2">{{ payment.franchise_owner }}</div>
            </div>
            <div v-if="payment.payee_contact_number">
                <div class="font-bold">Contact No:</div>
                <div class="pl-2">{{ payment.payee_contact_number }}</div>
            </div>
            <div>
                <div class="font-bold">Address:</div>
                <div class="pl-2">{{ payeeAddress }}</div>
            </div>
            <div>
                <div class="font-bold">The sum of:</div>
                <div class="uppercase italic text-[10px] pl-2">{{ amountInWords }}</div>
            </div>
        </div>

        <div class="border-y border-dashed border-gray-400 py-3 text-center mb-4">
            <div class="font-bold text-[12px] uppercase tracking-wider mb-1">Amount Paid</div>
            <div class="font-mono font-bold text-[18px]">{{ formatCurrency(payment.amount_paid) }}</div>
        </div>

        <div class="text-center text-[10px] text-gray-600 pb-4">
            <p>Thank you for your payment!</p>
            <p class="mt-2">--- End of Receipt ---</p>
        </div>

    </div>
</template>

<style scoped>
/* Browser preview styling */
.receipt-container {
    width: 58mm;
    margin: 0 auto;
    background: white;
    color: black;
    font-family: 'Courier New', Courier, monospace; /* Thermal standard font */
    padding: 2mm;
}

/* Crucial Print Directives */
@media print {
    @page {
        margin: 0;
        /* Tells the browser this is a continuous 58mm roll, not an A4 page */
        size: 58mm auto; 
    }
    body {
        margin: 0;
        padding: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .receipt-container {
        width: 100%;
        padding: 2mm;
        margin: 0;
    }
}
</style>