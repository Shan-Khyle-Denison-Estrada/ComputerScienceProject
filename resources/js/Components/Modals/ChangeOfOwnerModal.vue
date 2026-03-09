<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    show: Boolean,
    application: Object,
    operatorExists: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

// --- PSGC API States ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);

const selectedProvinceCode = ref('');
const selectedCityCode = ref('');

const form = useForm({
    franchise_id: '',
    first_name: '',
    middle_name: '',
    last_name: '',
    contact_number: '',
    email: '',
    tin_number: '',
    street_address: '',
    province: '', // Added province
    barangay: '',
    city: '',
    password: '',
    password_confirmation: '',
    change_date: '',
    remarks: 'Approved and Finalized Change of Owner.',
});

// --- API Fetching Logic ---
onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load provinces:", error);
    }
});

const fetchCities = async (provinceCode) => {
    if (!provinceCode) return;
    try {
        const isNCR = provinceCode === '130000000';
        const endpoint = isNCR 
            ? 'https://psgc.gitlab.io/api/regions/130000000/cities-municipalities'
            : `https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities`;
        const res = await fetch(endpoint);
        citiesList.value = (await res.json()).sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) { console.error(error); }
};

const fetchBarangays = async (cityCode) => {
    if (!cityCode) return;
    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays`);
        barangaysList.value = (await res.json()).sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) { console.error(error); }
};

// --- Dropdown Handlers ---
const handleProvinceChange = async (event) => {
    const code = event.target.value;
    selectedProvinceCode.value = code;
    const p = provincesList.value.find(x => x.code === code);
    form.province = p ? p.name : '';
    
    form.city = ''; form.barangay = '';
    selectedCityCode.value = '';
    citiesList.value = []; barangaysList.value = [];
    
    await fetchCities(code);
};

const handleCityChange = async (event) => {
    const code = event.target.value;
    selectedCityCode.value = code;
    const c = citiesList.value.find(x => x.code === code);
    form.city = c ? c.name : '';

    form.barangay = ''; barangaysList.value = [];
    
    await fetchBarangays(code);
};

// Watch for the modal opening to Auto-fill the inputs
watch(() => props.show, async (isOpen) => {
    if (isOpen && props.application?.raw_proposed_owner) {
        
        form.franchise_id = props.application.franchise_details?.id || '';
        
        const owner = props.application.raw_proposed_owner;
        form.first_name = owner.first_name;
        form.middle_name = owner.middle_name;
        form.last_name = owner.last_name;
        form.contact_number = owner.contact_number;
        form.email = owner.email;
        form.tin_number = owner.tin_number;
        
        form.street_address = owner.street_address || '';
        form.province = owner.province || '';
        form.city = owner.city || 'Zamboanga City';
        form.barangay = owner.barangay || '';

        // Reverse lookup to load the dropdown options correctly based on mapped names
        if (form.province && provincesList.value.length > 0) {
            const p = provincesList.value.find(x => x.name === form.province);
            if (p) {
                selectedProvinceCode.value = p.code;
                await fetchCities(p.code);
                
                if (form.city) {
                    const c = citiesList.value.find(x => x.name === form.city);
                    if (c) {
                        selectedCityCode.value = c.code;
                        await fetchBarangays(c.code);
                    }
                }
            }
        }

        // Reset fields
        form.password = '';
        form.password_confirmation = '';
        form.change_date = '';
        form.remarks = 'Approved and Finalized Change of Owner.';
    } else if (!isOpen) {
        selectedProvinceCode.value = '';
        selectedCityCode.value = '';
        citiesList.value = [];
        barangaysList.value = [];
    }
});

const submit = () => {
    // Ensure passwords are wiped if it's an existing operator to prevent validation issues
    if (props.operatorExists) {
        form.password = '';
        form.password_confirmation = '';
    }

    form.post(route('admin.applications.change-of-owner.finalize', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Finalize Change of Owner</h2>
            
            <form @submit.prevent="submit">
                
                <div class="mb-6 p-4 rounded-lg border" :class="operatorExists ? 'bg-green-50 border-green-200' : 'bg-blue-50 border-blue-200'">
                    <InputLabel value="System Detection" class="text-sm font-bold mb-1" :class="operatorExists ? 'text-green-900' : 'text-blue-900'" />
                    <div class="flex items-center gap-2">
                        <svg v-if="operatorExists" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <svg v-else class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-semibold" :class="operatorExists ? 'text-green-800' : 'text-blue-800'">
                            {{ operatorExists ? 'Existing Operator Found' : 'New Operator Setup' }}
                        </span>
                    </div>
                    <p class="text-xs mt-2" :class="operatorExists ? 'text-green-700' : 'text-blue-700'">
                        {{ operatorExists ? 'The TIN matches an existing operator. Passwords are hidden, and this franchise will automatically link to their existing account.' : 'No existing operator found for this TIN. A new account will be created. Please assign a temporary password below.' }}
                    </p>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Proposed Owner Details</h3>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel value="First Name" class="text-xs mb-0" />
                            <TextInput v-model="form.first_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Middle Name" class="text-xs mb-0" />
                            <TextInput v-model="form.middle_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Last Name" class="text-xs mb-0" />
                            <TextInput v-model="form.last_name" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <InputLabel value="Contact Number" class="text-xs mb-0" />
                            <TextInput v-model="form.contact_number" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="Email Address" class="text-xs mb-0" />
                            <TextInput v-model="form.email" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                        <div>
                            <InputLabel value="TIN Number" class="text-xs mb-0" />
                            <TextInput v-model="form.tin_number" class="block w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel value="Province" class="text-xs mb-0" />
                            <select v-model="selectedProvinceCode" @change="handleProvinceChange" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5" required>
                                <option value="" disabled>Select Province</option>
                                <option v-for="p in provincesList" :key="p.code" :value="p.code">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="City" class="text-xs mb-0" />
                            <select v-model="selectedCityCode" @change="handleCityChange" :disabled="!citiesList.length" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5 disabled:bg-gray-100" required>
                                <option value="" disabled>Select City</option>
                                <option v-for="c in citiesList" :key="c.code" :value="c.code">{{ c.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Barangay" class="text-xs mb-0" />
                            <select v-model="form.barangay" :disabled="!barangaysList.length" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1.5 disabled:bg-gray-100" required>
                                <option value="" disabled>Select Barangay</option>
                                <option v-for="b in barangaysList" :key="b.code" :value="b.name">{{ b.name }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Street Address" class="text-xs mb-0" />
                            <TextInput v-model="form.street_address" class="block w-full text-sm py-1.5" />
                        </div>
                    </div>

                </div>

                <div v-if="!operatorExists" class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Account Setup</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Temporary Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password" class="block w-full text-sm py-1.5" :required="!operatorExists" />
                            <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div>
                            <InputLabel value="Confirm Password" class="text-xs mb-0" />
                            <TextInput type="password" v-model="form.password_confirmation" class="block w-full text-sm py-1.5" :required="!operatorExists" />
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Change Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Effectivity Date" class="text-xs mb-0" />
                            <TextInput type="date" v-model="form.change_date" class="block w-full text-sm py-1.5" required />
                            <div v-if="form.errors.change_date" class="text-red-500 text-xs mt-1">{{ form.errors.change_date }}</div>
                        </div>
                        <div>
                             <InputLabel value="Remarks" class="text-xs mb-0" />
                             <TextInput v-model="form.remarks" class="block w-full text-sm py-1.5" placeholder="Reason for change..." />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="emit('close')" class="text-xs h-9" :disabled="form.processing">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="text-xs h-9 px-6">
                        {{ form.processing ? 'Finalizing...' : 'Save & Finalize' }}
                    </PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>