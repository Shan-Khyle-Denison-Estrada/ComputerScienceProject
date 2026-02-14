<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    application: Object
});

const emit = defineEmits(['close']);

// --- STATE ---
const mode = ref('existing'); // 'existing' or 'new'
const ownerQuery = ref('');
const showOwnerDropdown = ref(false);
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);
const photoPreview = ref(null);

// --- DUMMY DATA ---
const dummyOwners = [
    { id: 1, name: 'Juan Dela Cruz', email: 'juan@example.com' },
    { id: 2, name: 'Maria Santos', email: 'maria@example.com' },
    { id: 3, name: 'Pedro Penduko', email: 'pedro@example.com' },
    { id: 4, name: 'Jose Rizal', email: 'jose@example.com' },
];

const dummyBarangays = [
    { name: 'San Jose' }, { name: 'Tetuan' }, { name: 'Putik' }, { name: 'Pasonanca' }, 
    { name: 'Sta. Maria' }, { name: 'Tumaga' }
];

// --- FORM ---
const form = useForm({
    // Transaction Details
    transfer_date: new Date().toISOString().split('T')[0],
    transfer_fee: 500.00,
    remarks: '',

    // Mode: Existing
    existing_owner_id: '',
    
    // Mode: New
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    tin_number: '',
    street_address: '',
    barangay: '',
    city: 'Zamboanga City',
    password: '',
    password_confirmation: '',
    user_photo: null
});

// --- COMPUTED ---
const filteredOwners = computed(() => {
    if (!ownerQuery.value) return dummyOwners;
    return dummyOwners.filter(o => o.name.toLowerCase().includes(ownerQuery.value.toLowerCase()));
});

const filteredBarangays = computed(() => {
    if (!barangayQuery.value) return dummyBarangays;
    return dummyBarangays.filter(b => b.name.toLowerCase().includes(barangayQuery.value.toLowerCase()));
});

// --- ACTIONS ---
const selectOwner = (owner) => {
    form.existing_owner_id = owner.id;
    ownerQuery.value = owner.name;
    showOwnerDropdown.value = false;
};

const selectBarangay = (name) => {
    form.barangay = name;
    barangayQuery.value = name;
    showBarangayDropdown.value = false;
};

const handlePhotoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.user_photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    console.log(`Finalizing Transfer [Mode: ${mode.value}]`, form.data());
    emit('close');
    // Reset basic fields after close if needed
    setTimeout(() => form.reset('password', 'password_confirmation'), 500);
};

// Reset specific fields when mode changes
watch(mode, () => {
    ownerQuery.value = '';
    form.existing_owner_id = '';
});
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-5 border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Finalize Ownership Transfer</h2>
                    <p class="text-xs text-gray-500">Transfer franchise rights to a new or existing operator.</p>
                </div>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                
                <div class="flex p-1 bg-gray-100 rounded-lg mb-6">
                    <button 
                        type="button"
                        @click="mode = 'existing'"
                        class="flex-1 py-1.5 text-sm font-medium rounded-md transition-all duration-200"
                        :class="mode === 'existing' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Select Existing Owner
                    </button>
                    <button 
                        type="button"
                        @click="mode = 'new'"
                        class="flex-1 py-1.5 text-sm font-medium rounded-md transition-all duration-200"
                        :class="mode === 'new' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Register New Owner
                    </button>
                </div>

                <div v-if="mode === 'existing'" class="space-y-4 mb-6 min-h-[200px]">
                    <div class="relative">
                        <InputLabel value="Search Owner" class="mb-1" />
                        <div class="relative">
                            <TextInput 
                                v-model="ownerQuery" 
                                @focus="showOwnerDropdown = true"
                                class="w-full pl-9" 
                                placeholder="Type name to search..." 
                            />
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        
                        <div v-if="showOwnerDropdown" class="absolute z-10 w-full bg-white border border-gray-200 mt-1 rounded-md shadow-lg max-h-40 overflow-y-auto">
                            <div 
                                v-for="owner in filteredOwners" 
                                :key="owner.id" 
                                @click="selectOwner(owner)"
                                class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm border-b border-gray-50 last:border-0"
                            >
                                <div class="font-medium text-gray-800">{{ owner.name }}</div>
                                <div class="text-xs text-gray-500">{{ owner.email }}</div>
                            </div>
                            <div v-if="filteredOwners.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">No owners found.</div>
                        </div>
                        <div v-if="showOwnerDropdown" @click="showOwnerDropdown = false" class="fixed inset-0 z-0"></div>
                    </div>
                    
                    <div v-if="form.existing_owner_id" class="p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold">
                            {{ ownerQuery.charAt(0) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-blue-900">Selected: {{ ownerQuery }}</div>
                            <div class="text-xs text-blue-600">ID: {{ form.existing_owner_id }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="mode === 'new'" class="space-y-5 mb-6">
                    <div class="flex gap-4">
                        <div class="flex-none pt-1">
                            <div class="relative group h-16 w-16 mx-auto">
                                <div class="h-16 w-16 rounded-full bg-gray-50 border border-gray-200 flex items-center justify-center overflow-hidden">
                                    <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
                                    <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <label class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-10 cursor-pointer rounded-full transition-all">
                                    <input type="file" class="hidden" @change="handlePhotoChange" accept="image/*" />
                                </label>
                            </div>
                        </div>
                        <div class="flex-1 grid grid-cols-3 gap-2">
                            <div><InputLabel value="First Name" class="text-xs mb-0" /><TextInput v-model="form.first_name" class="block w-full text-sm py-1" /></div>
                            <div><InputLabel value="Middle Name" class="text-xs mb-0" /><TextInput v-model="form.middle_name" class="block w-full text-sm py-1" /></div>
                            <div><InputLabel value="Last Name" class="text-xs mb-0" /><TextInput v-model="form.last_name" class="block w-full text-sm py-1" /></div>
                            <div class="col-span-3"><InputLabel value="Email" class="text-xs mb-0" /><TextInput type="email" v-model="form.email" class="block w-full text-sm py-1" /></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 bg-gray-50 p-3 rounded border border-gray-100">
                        <div><InputLabel value="Mobile No." class="text-xs mb-0" /><TextInput v-model="form.contact_number" class="block w-full text-sm py-1" /></div>
                        <div><InputLabel value="TIN" class="text-xs mb-0" /><TextInput v-model="form.tin_number" class="block w-full text-sm py-1 font-mono" /></div>
                        <div class="col-span-2"><InputLabel value="Street Address" class="text-xs mb-0" /><TextInput v-model="form.street_address" class="block w-full text-sm py-1" /></div>
                        <div class="relative">
                            <InputLabel value="Barangay" class="text-xs mb-0" />
                            <TextInput v-model="barangayQuery" @focus="showBarangayDropdown=true" class="block w-full text-sm py-1" placeholder="Search..." />
                            <div v-if="showBarangayDropdown" class="absolute z-10 w-full bg-white border shadow-lg mt-1 max-h-24 overflow-auto rounded">
                                <div v-for="b in filteredBarangays" :key="b.name" @click="selectBarangay(b.name)" class="px-3 py-1 text-xs hover:bg-gray-100 cursor-pointer">{{ b.name }}</div>
                            </div>
                            <div v-if="showBarangayDropdown" @click="showBarangayDropdown = false" class="fixed inset-0 z-0"></div>
                        </div>
                        <div><InputLabel value="City" class="text-xs mb-0" /><TextInput v-model="form.city" class="block w-full text-sm py-1 bg-gray-100" readonly /></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div><InputLabel value="Password" class="text-xs mb-0" /><TextInput type="password" v-model="form.password" class="block w-full text-sm py-1" /></div>
                        <div><InputLabel value="Confirm" class="text-xs mb-0" /><TextInput type="password" v-model="form.password_confirmation" class="block w-full text-sm py-1" /></div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4 mb-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                             <InputLabel value="Remarks" class="text-xs mb-0" />
                             <TextInput v-model="form.remarks" class="block w-full text-sm py-1" placeholder="Optional notes..." />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="emit('close')" class="text-xs h-9">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" class="text-xs h-9 px-6">Confirm Transfer</PrimaryButton>
                </div>

            </form>
        </div>
    </Modal>
</template>