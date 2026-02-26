<script setup>
import { computed } from 'vue';

const props = defineProps({
    franchise: Object,
    currentOwner: Object,
    currentUnit: Object,
    systemSetting: Object, // <-- Prop for LGU logo
});

const ownerName = computed(() => {
    if (!props.currentOwner || !props.currentOwner.user) return 'UNASSIGNED';
    return `${props.currentOwner.user.first_name} ${props.currentOwner.user.last_name}`;
});

// NEW: Computed property to correctly format the User's address
const ownerAddress = computed(() => {
    if (!props.currentOwner || !props.currentOwner.user) return 'N/A';
    
    const user = props.currentOwner.user;
    // Filter out null/empty values and join with a comma
    const addressParts = [user.street_address, user.barangay, user.city].filter(Boolean);
    
    return addressParts.length > 0 ? addressParts.join(', ') : 'N/A';
});
</script>

<template>
    <div class="w-full max-w-[800px] bg-white p-6 mx-auto font-serif text-center relative text-black">
        <div class="relative z-10">
            
            <div style="display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; align-items: flex-start !important; justify-content: space-between !important;" class="mb-5">
                
                <div style="width: 130px; height: 130px; flex-shrink: 0;" class="flex items-center justify-center">
                    <img v-if="systemSetting?.lgu_logo_path" :src="`/storage/${systemSetting.lgu_logo_path}`" alt="LGU Logo" class="w-full h-full object-contain" />
                    <div v-else class="w-full h-full border-2 border-dashed border-gray-400 flex items-center justify-center rounded-full text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">
                        Logo
                    </div>
                </div>

                <div style="flex-grow: 1;" class="px-4 flex flex-col items-center justify-center">
                    <div class="leading-tight mb-3">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Republic of the Philippines</p>
                        <h1 class="text-xl font-black uppercase tracking-widest text-gray-900 mt-0.5">Tricycle Adjudication Board</h1>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-0.5">Zamboanga City</p>
                    </div>

                    <div class="leading-tight mb-3">
                        <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">Certificate of Public Convenience<br/>to Operate Motorized Tricycle</h2>
                        <p class="text-[9px] text-gray-500 uppercase tracking-widest mt-0.5">[Good for one (1) year]</p>
                    </div>

                    <div class="leading-tight">
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-900 inline-block underline">This Serves as a Franchise</h3>
                        <p class="text-[9px] text-gray-500 uppercase tracking-widest mt-0.5 italic">(Renewal)</p>
                    </div>
                </div>

                <div style="width: 130px; height: 130px; flex-shrink: 0;" class="flex items-center justify-center">
                    <img v-if="franchise?.qr_code" :src="`/storage/qrcodes/${franchise.qr_code}`" alt="QR Code" class="w-full h-full object-contain" />
                    <div v-else class="w-full h-full border-2 border-dashed border-gray-400 flex items-center justify-center text-[8px] font-bold text-gray-400 uppercase tracking-widest text-center">
                        NO QR
                    </div>
                    <p>{{ franchise.id }}</p>
                    <p>{{ franchise.zone.color }}</p>
                </div>
            </div>
            
            <div class="mb-4 text-gray-900 leading-snug">
                
                <div class="flex flex-col gap-1 mb-4 text-sm">
                    <p class="text-left ml-6"><span class="font-bold">Name of Operator:</span> {{ ownerName }}</p>
                    <p class="text-left ml-6"><span class="font-bold">Address:</span> {{ ownerAddress }}</p>
                </div>

                <p class="text-xs text-left tracking-widest ml-6 mb-4 leading-relaxed">The undersigned operator of legal age and a Filipino citizen, is authorized to operate for hire one (1) unit motorized tricycle described hereunder:</p>

                <div class="my-4 mx-12 border border-gray-800">
                    <table style="display: table !important; width: 100% !important; border-collapse: collapse !important;" class="w-full text-xs text-center">
                        <thead style="display: table-header-group !important;">
                            <tr style="display: table-row !important;" class="bg-gray-50 border-b border-gray-800">
                                <th style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-bold uppercase text-[9px] tracking-widest">Make</th>
                                <th style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-bold uppercase text-[9px] tracking-widest">Year</th>
                                <th style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-bold uppercase text-[9px] tracking-widest">Motor No.</th>
                                <th style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-bold uppercase text-[9px] tracking-widest">Chassis No.</th>
                                <th style="display: table-cell !important; vertical-align: middle !important;" class="px-2 py-1.5 font-bold uppercase text-[9px] tracking-widest">Plate No.</th>
                            </tr>
                        </thead>
                        <tbody style="display: table-row-group !important;">
                            <tr style="display: table-row !important;">
                                <td style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-medium">{{ currentUnit?.make?.name || 'N/A' }}</td>
                                <td style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-medium">{{ currentUnit?.model_year || 'N/A' }}</td>
                                <td style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-medium">{{ currentUnit?.motor_number || 'N/A' }}</td>
                                <td style="display: table-cell !important; vertical-align: middle !important;" class="border-r border-gray-800 px-2 py-1.5 font-medium">{{ currentUnit?.chassis_number || 'N/A' }}</td>
                                <td style="display: table-cell !important; vertical-align: middle !important;" class="px-2 py-1.5 font-bold">{{ currentUnit?.plate_number || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-left tracking-widest ml-6 mt-4">Subject however, to the following terms and conditions, to wit:</p>
                
                <ol class="list-decimal text-left ml-12 mr-6 text-[11px] space-y-1 mb-6 mt-2 leading-tight">
                    <li>1. This Certificate of Convenience to operate Motorized Tricycle is subject to the legal provisions prescribed in the City Ordinance No. 185 and its amendments, pertinent issuances and such other rules and regulation as maybe promulgated hereafter.</li>
                    <li>2. The unit herein authorized shall be registered with the LAND TRANSPORTATION OFFICE, ZAMBOANGA CITY upon issuance of this franchise.</li>
                    <li>3. This Certificate of Public Convenience shall be valid only for a period of one (1) year from date issue.</li>
                    <li>4. The unit shall not operate during its rest day.</li>
                    <li>5. The Operator/Driver SHALL CHARGE ONLY THE AUTHORIZED FARE RATE as prescribed under Ordinance No. 185 and its amendments..</li>
                    <li>6. Operator and/or driver shall secure and accomplish all legal form requirements and submit the same to this office as supporting papers prior to the issuance of this certificate.</li>
                    <li>7. Copy of this certificate, together with the original or machine copies of the current registration papers of the motorized tricycle shall be kept with the unit(s) at all times while in operation.</li>
                    <li>8. Any alteration, addition, or deletion on this certificate shall invalidate this certificate.</li>
                    <li>9. Failure of the operator or the driver to comply with the Ordinance No. 185 and its amendments or any of the conditions herein will be sufficient cause for the suspension or revocation of this certificate by this office.</li>
                </ol>

                <div class="mt-4">
                    <p class="font-bold text-sm text-left ml-6 mb-8">CONFORME:</p>
                    
                    <div style="display: flex !important; justify-content: center !important; width: 100% !important;">
                        <div class="text-center flex flex-col items-center">
                            <div class="w-48 border-b border-gray-800 mb-1.5"></div>
                            <p class="text-[9px] uppercase font-bold text-gray-600 tracking-widest">Operator Signature</p>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <p class="text-xs font-bold tracking-widest mt-8 mb-16 uppercase">ISSUED IN ZAMBOANGA CITY, PHILIPPINES</p>

            <div style="display: flex !important; flex-direction: row !important; justify-content: space-around !important; align-items: flex-end !important;" class="px-4">
                <div class="text-center flex flex-col items-center">
                    <div class="w-48 border-b border-gray-800 mb-1.5"></div>
                    <p class="text-[9px] uppercase font-bold text-gray-600 tracking-widest">Recommending Approval</p>
                </div>

                <div class="text-center flex flex-col items-center">
                    <div class="w-48 border-b border-gray-800 mb-1.5"></div>
                    <p class="text-[9px] uppercase font-bold text-gray-600 tracking-widest">Authorizing Officer</p>
                </div>
            </div>
            
        </div>
    </div>
</template>

<style>
/* Default: Hide print area on screen */
#print-area {
    display: none;
}

/* Print Specific Styles */
@media print {
    @page {
        margin: 0;
        size: auto;
    }

    body > * {
        visibility: hidden;
    }

    #print-area, #print-area * {
        visibility: visible;
    }

    /* Print wrapper should strictly be block and absolute to allow inner flows to function normally */
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        background: white;
        z-index: 9999;
        display: block !important; 
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>