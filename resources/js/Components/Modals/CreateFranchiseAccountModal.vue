<script setup>
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] }
});

const emit = defineEmits(['close']);

// --- STEPPER STATE ---
const currentStep = ref(1);
const steps = [
    { id: 1, title: 'Owner Profile', desc: 'Personal Information' },
    { id: 2, title: 'Franchise & Unit', desc: 'Zone & Tricycle Details' },
    { id: 3, title: 'Security', desc: 'Account Credentials' }
];

// --- STATE ---
const ownerPhotoPreview = ref(null);
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);

const franchiseUIStates = ref([
    { isExpanded: true, previews: { front: null, back: null, left: null, right: null } }
]);

// --- FORM ---
const form = useForm({
    first_name: '', middle_name: '', last_name: '',
    email: '', contact_number: '', tin_number: '',
    street_address: '', barangay: '', city: 'Zamboanga City',
    owner_photo: null, 

    franchises: [
        {
            zone_id: '', date_issued: new Date().toISOString().split('T')[0],
            make_id: '', model_year: '', plate_number: '', cr_number: '',
            motor_number: '', chassis_number: '',
            unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
        }
    ],

    password: '', password_confirmation: '',
});

// --- HELPERS ---
const filteredBarangays = computed(() => {
    if (!barangayQuery.value) return props.barangays;
    return props.barangays.filter(b => b.name.toLowerCase().includes(barangayQuery.value.toLowerCase()));
});

const getZoneName = (id) => {
    const zone = (props.zones.length ? props.zones : dummyZones).find(z => z.id === id);
    return zone ? (zone.name || zone.description) : 'No Zone';
};

// --- METHODS ---
const nextStep = () => { if (currentStep.value < 3) currentStep.value++; };
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

const selectBarangay = (name) => {
    form.barangay = name;
    barangayQuery.value = name;
    showBarangayDropdown.value = false;
};

const handleOwnerPhoto = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.owner_photo = file;
        ownerPhotoPreview.value = URL.createObjectURL(file);
    }
};

const toggleFranchise = (index) => {
    franchiseUIStates.value[index].isExpanded = !franchiseUIStates.value[index].isExpanded;
};

const addFranchise = () => {
    franchiseUIStates.value.forEach(state => state.isExpanded = false);
    form.franchises.push({
        zone_id: '', date_issued: new Date().toISOString().split('T')[0],
        make_id: '', model_year: '', plate_number: '', cr_number: '',
        motor_number: '', chassis_number: '',
        unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    });
    franchiseUIStates.value.push({
        isExpanded: true,
        previews: { front: null, back: null, left: null, right: null }
    });
};

const removeFranchise = (index) => {
    if (form.franchises.length > 1) {
        form.franchises.splice(index, 1);
        franchiseUIStates.value.splice(index, 1);
        if (franchiseUIStates.value.length > 0) {
            franchiseUIStates.value[franchiseUIStates.value.length - 1].isExpanded = true;
        }
    }
};

const handleUnitPhoto = (e, index, side) => {
    const file = e.target.files[0];
    if (file) {
        form.franchises[index][`unit_${side}_photo`] = file;
        franchiseUIStates.value[index].previews[side] = URL.createObjectURL(file);
    }
};

const submit = () => {
    if (!form.barangay && barangayQuery.value) form.barangay = barangayQuery.value;
    form.post(route('admin.accounts.store'), {
        onSuccess: () => { 
            emit('close'); form.reset(); currentStep.value = 1; ownerPhotoPreview.value = null;
            franchiseUIStates.value = [{ isExpanded: true, previews: { front: null, back: null, left: null, right: null } }];
        },
        forceFormData: true
    });
};

const dummyZones = [{id:1, name:'Zone 1'}, {id:2, name:'Zone 2'}];
const dummyMakes = [{id:1, name:'Honda'}, {id:2, name:'Kawasaki'}, {id:3, name:'Suzuki'}];
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="2xl">
        <div class="flex flex-col h-[600px] bg-white rounded-lg overflow-hidden">
            
            <div class="p-4 border-b border-gray-100 flex-none bg-gray-50/50">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-sm font-bold text-gray-800 uppercase tracking-tight">Create Account</h2>
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Step {{ currentStep }} of 3</span>
                </div>
                <div class="flex gap-2 mb-2">
                    <div v-for="s in steps" :key="s.id" :class="[currentStep >= s.id ? 'bg-blue-500' : 'bg-gray-200']" class="h-1 flex-1 rounded-full transition-all duration-300"></div>
                </div>
                <div class="text-center">
                    <span class="text-xs font-semibold text-gray-700">{{ steps[currentStep-1].title }}</span>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-5 custom-scrollbar">
                
                <div v-if="currentStep === 1" class="flex gap-6 animate-fade-in">
                    
                    <div class="w-32 flex-none flex flex-col items-center pt-2">
                        <div class="w-28 h-28 rounded-full border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden relative group bg-gray-50 mb-3 hover:border-blue-400 transition-colors">
                            <img v-if="ownerPhotoPreview" :src="ownerPhotoPreview" class="w-full h-full object-cover" />
                            <div v-else class="text-center p-2">
                                <svg class="w-8 h-8 mx-auto text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span class="text-[9px] text-gray-400 block mt-1 font-bold">UPLOAD PHOTO</span>
                            </div>
                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleOwnerPhoto" />
                        </div>
                        <p class="text-[10px] text-gray-400 text-center leading-tight">Click to upload owner's 1x1 or profile picture.</p>
                    </div>

                    <div class="flex-1 space-y-4">
                        <div>
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Personal Details</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div><InputLabel value="First Name" class="text-[11px]" /><TextInput v-model="form.first_name" class="w-full text-xs py-1.5" /></div>
                                <div><InputLabel value="Middle Name" class="text-[11px]" /><TextInput v-model="form.middle_name" class="w-full text-xs py-1.5" /></div>
                                <div><InputLabel value="Last Name" class="text-[11px]" /><TextInput v-model="form.last_name" class="w-full text-xs py-1.5" /></div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Contact & Info</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="col-span-1"><InputLabel value="Email Address" class="text-[11px]" /><TextInput type="email" v-model="form.email" class="w-full text-xs py-1.5" /></div>
                                <div><InputLabel value="Contact No." class="text-[11px]" /><TextInput v-model="form.contact_number" class="w-full text-xs py-1.5" /></div>
                                <div><InputLabel value="TIN No." class="text-[11px]" /><TextInput v-model="form.tin_number" class="w-full text-xs py-1.5 font-mono" placeholder="000-000-000" /></div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Address</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div><InputLabel value="Street Address" class="text-[11px]" /><TextInput v-model="form.street_address" class="w-full text-xs py-1.5" /></div>
                                <div class="relative">
                                    <InputLabel value="Barangay" class="text-[11px]" />
                                    <TextInput v-model="barangayQuery" @focus="showBarangayDropdown = true" @input="showBarangayDropdown = true" class="w-full text-xs py-1.5" placeholder="Search..." autocomplete="off" />
                                    <div v-if="showBarangayDropdown && filteredBarangays.length > 0" class="absolute z-10 w-full bg-white border border-gray-200 mt-1 rounded shadow-lg max-h-32 overflow-y-auto custom-scrollbar">
                                        <div v-for="b in filteredBarangays" :key="b.id" @click="selectBarangay(b.name)" class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-xs text-gray-700">{{ b.name }}</div>
                                    </div>
                                    <div v-if="showBarangayDropdown" @click="showBarangayDropdown = false" class="fixed inset-0 z-0 bg-transparent cursor-default"></div>
                                </div>
                                <div><InputLabel value="City" class="text-[11px]" /><TextInput v-model="form.city" class="w-full text-xs py-1.5 bg-gray-50" readonly /></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="currentStep === 2" class="space-y-4 animate-fade-in">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">Assigned Franchises</span>
                        <button @click="addFranchise" type="button" class="text-[10px] text-blue-600 font-bold hover:underline bg-blue-50 px-3 py-1 rounded transition-colors hover:bg-blue-100">+ ADD NEW FRANCHISE</button>
                    </div>

                    <div v-for="(franchise, index) in form.franchises" :key="index" class="border border-gray-200 rounded-lg overflow-hidden transition-all duration-300" :class="franchiseUIStates[index].isExpanded ? 'bg-white shadow-sm ring-1 ring-blue-100' : 'bg-gray-50/50'">
                        
                        <div @click="toggleFranchise(index)" class="flex justify-between items-center p-3 cursor-pointer hover:bg-gray-50 select-none bg-gray-50/30">
                            <div class="flex items-center gap-3">
                                <span class="text-[11px] font-bold text-gray-700">Franchise #{{ index + 1 }}</span>
                                <span v-if="!franchiseUIStates[index].isExpanded" class="text-[10px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                    {{ franchise.zone_id ? getZoneName(franchise.zone_id) : 'No Zone' }} • {{ franchise.plate_number || 'No Plate' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <button v-if="form.franchises.length > 1" @click.stop="removeFranchise(index)" class="text-gray-300 hover:text-red-500 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': franchiseUIStates[index].isExpanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        
                        <div v-show="franchiseUIStates[index].isExpanded" class="p-4 border-t border-gray-100 bg-white animate-fade-in">
                            <div class="grid grid-cols-4 gap-4">
                                <div>
                                    <InputLabel value="Zone" class="text-[10px]" />
                                    <select v-model="franchise.zone_id" class="w-full text-xs py-1.5 border-gray-300 rounded shadow-sm focus:border-blue-500">
                                        <option value="" disabled>Select</option>
                                        <option v-for="z in (props.zones.length ? props.zones : dummyZones)" :key="z.id" :value="z.id">{{ z.name || z.description }}</option>
                                    </select>
                                </div>
                                <div><InputLabel value="Date Issued" class="text-[10px]" /><TextInput type="date" v-model="franchise.date_issued" class="w-full text-xs py-1.5" /></div>
                                <div>
                                    <InputLabel value="Make / Brand" class="text-[10px]" />
                                    <select v-model="franchise.make_id" class="w-full text-xs py-1.5 border-gray-300 rounded shadow-sm">
                                        <option value="" disabled>Select</option>
                                        <option v-for="m in (props.unitMakes.length ? props.unitMakes : dummyMakes)" :key="m.id" :value="m.id">{{ m.name }}</option>
                                    </select>
                                </div>
                                <div><InputLabel value="Model Year" class="text-[10px]" /><TextInput type="number" v-model="franchise.model_year" placeholder="YYYY" class="w-full text-xs py-1.5" /></div>

                                <div><InputLabel value="Plate Number" class="text-[10px]" /><TextInput v-model="franchise.plate_number" placeholder="ABC 123" class="w-full text-xs py-1.5 uppercase font-mono" /></div>
                                <div><InputLabel value="CR Number" class="text-[10px]" /><TextInput v-model="franchise.cr_number" placeholder="Cert. Reg" class="w-full text-xs py-1.5 uppercase" /></div>
                                <div><InputLabel value="Motor Number" class="text-[10px]" /><TextInput v-model="franchise.motor_number" class="w-full text-xs py-1.5 uppercase" /></div>
                                <div><InputLabel value="Chassis Number" class="text-[10px]" /><TextInput v-model="franchise.chassis_number" class="w-full text-xs py-1.5 uppercase" /></div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100">
                                <span class="text-[9px] font-bold text-gray-400 uppercase block mb-2">Unit Photos</span>
                                <div class="grid grid-cols-4 gap-4">
                                    <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="aspect-video border rounded border-gray-200 overflow-hidden relative bg-gray-50 hover:border-blue-400 transition-colors group/photo">
                                        <img v-if="franchiseUIStates[index].previews[side]" :src="franchiseUIStates[index].previews[side]" class="w-full h-full object-cover" />
                                        <div v-else class="flex flex-col items-center justify-center h-full text-gray-300">
                                            <svg class="w-5 h-5 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                            <span class="text-[8px] uppercase font-bold">{{ side }}</span>
                                        </div>
                                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" @change="(e) => handleUnitPhoto(e, index, side)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="currentStep === 3" class="animate-fade-in flex gap-6">
                    <div class="w-1/3 bg-blue-50 p-5 rounded-lg border border-blue-100 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mb-3 shadow-sm text-blue-500">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <h4 class="text-sm font-bold text-blue-900 mb-1">Secure Account</h4>
                        <p class="text-[11px] text-blue-700 leading-relaxed">
                            These credentials will be used by the franchise owner to log in to their mobile application portal.
                        </p>
                    </div>

                    <div class="w-2/3 space-y-4 pt-2">
                        <div>
                            <InputLabel value="Portal ID / Email" class="text-[11px]" />
                            <TextInput v-model="form.email" class="w-full text-sm py-1.5 bg-gray-50 text-gray-500" readonly />
                            <p class="text-[10px] text-gray-400 mt-1">Username is auto-filled from email.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><InputLabel value="Password" class="text-[11px]" /><TextInput type="password" v-model="form.password" class="w-full text-sm py-1.5" placeholder="••••••••" /></div>
                            <div><InputLabel value="Confirm Password" class="text-[11px]" /><TextInput type="password" v-model="form.password_confirmation" class="w-full text-sm py-1.5" placeholder="••••••••" /></div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-between">
                <SecondaryButton @click="prevStep" v-if="currentStep > 1" class="text-xs">Back</SecondaryButton>
                <div v-else></div>
                <div class="flex gap-2">
                    <SecondaryButton @click="emit('close')" class="text-xs">Cancel</SecondaryButton>
                    <PrimaryButton v-if="currentStep < 3" @click="nextStep" class="text-xs">Next &rarr;</PrimaryButton>
                    <PrimaryButton v-else @click="submit" :disabled="form.processing" class="text-xs bg-green-600 hover:bg-green-700 ring-green-500">Create Account</PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>