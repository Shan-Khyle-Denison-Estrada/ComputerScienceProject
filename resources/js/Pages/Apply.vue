<script setup>
import NavBar from '@/Components/NavBar.vue';
import Footer from '@/Components/Footer.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] }
});

// --- STEPPER STATE ---
const currentStep = ref(1);
const steps = [
    { 
        id: 1, 
        title: 'Operator Profile', 
        desc: 'Personal Info',
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' // User Icon
    },
    { 
        id: 2, 
        title: 'Franchise Details', 
        desc: 'Unit & Photos',
        icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' // Transport Icon
    },
    { 
        id: 3, 
        title: 'Requirements', 
        desc: 'Legal Documents',
        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' // Document Icon
    }
];

// --- STATE ---
const ownerPhotoPreview = ref(null);
const barangayQuery = ref('');
const showBarangayDropdown = ref(false);

const franchiseUIStates = ref([
    { isExpanded: true, previews: { front: null, back: null, left: null, right: null } }
]);

const requirementPreviews = ref({
    barangay_clearance: null,
    police_clearance: null,
    valid_id: null,
    or_cr: null
});

// --- FORM ---
const form = useForm({
    // Step 1: Profile
    first_name: '', middle_name: '', last_name: '',
    email: '', contact_number: '', tin_number: '',
    street_address: '', barangay: '', city: 'Zamboanga City',
    owner_photo: null, 

    // Step 2: Franchises
    franchises: [
        {
            zone_id: '', date_issued: new Date().toISOString().split('T')[0],
            make_id: '', model_year: '', plate_number: '', cr_number: '',
            motor_number: '', chassis_number: '',
            unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
        }
    ],

    // Step 3: Requirements
    barangay_clearance: null,
    police_clearance: null,
    valid_id: null,
    or_cr: null,
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
const nextStep = () => { 
    if (currentStep.value < 3) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        currentStep.value++; 
    }
};

const prevStep = () => { 
    if (currentStep.value > 1) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        currentStep.value--; 
    }
};

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

const handleRequirementUpload = (e, fieldName) => {
    const file = e.target.files[0];
    if (file) {
        form[fieldName] = file;
        if (file.type.startsWith('image/')) {
            requirementPreviews.value[fieldName] = URL.createObjectURL(file);
        } else {
            requirementPreviews.value[fieldName] = 'file'; 
        }
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
    
    form.post(route('applications.store'), {
        onSuccess: () => { 
            // Handle success
        },
        forceFormData: true
    });
};

// Mock data
const dummyZones = [{id:1, name:'Zone 1 (Poblacion)'}, {id:2, name:'Zone 2 (East Coast)'}, {id:3, name:'Zone 3 (West Coast)'}];
const dummyMakes = [{id:1, name:'Honda'}, {id:2, name:'Kawasaki'}, {id:3, name:'Suzuki'}, {id:4, name:'Yamaha'}];
</script>

<template>
    <Head title="Apply for Franchise" />
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <NavBar />

        <main class="flex-grow">
            <div class="bg-slate-900 text-white py-16 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
                <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
                <div class="max-w-4xl mx-auto relative z-10 text-center">
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight mb-3">
                        Franchise Application
                    </h1>
                    <p class="text-slate-400 text-sm sm:text-base max-w-2xl mx-auto">
                        Apply for a new tricycle franchise or register your existing unit. 
                        Complete the 3-step process below.
                    </p>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 relative z-20 pb-24">
                
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 mb-8">
                    <div class="relative">
                        <div class="absolute top-1/2 left-0 w-full h-1.5 bg-gray-100 -translate-y-1/2 rounded-full z-0"></div>
                        
                        <div class="absolute top-1/2 left-0 h-1.5 bg-gradient-to-r from-blue-600 to-blue-400 -translate-y-1/2 rounded-full z-0 transition-all duration-500 ease-out" 
                             :style="{ width: `${((currentStep - 1) / (steps.length - 1)) * 100}%` }"></div>

                        <div class="relative z-10 flex justify-between w-full">
                            <div v-for="step in steps" :key="step.id" class="flex flex-col items-center group cursor-default w-32">
                                
                                <div class="w-14 h-14 rounded-full flex items-center justify-center border-[3px] transition-all duration-300 shadow-sm relative"
                                     :class="[
                                        currentStep > step.id ? 'border-green-500 bg-green-500 text-white scale-105' : 
                                        currentStep === step.id ? 'border-blue-600 bg-white text-blue-600 ring-4 ring-blue-50 scale-110 shadow-blue-100' : 
                                        'border-gray-200 bg-white text-gray-300'
                                     ]">
                                    
                                    <svg v-if="currentStep > step.id" class="w-7 h-7 animate-fade-in" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                    
                                    <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon" />
                                    </svg>
                                </div>

                                <div class="mt-4 text-center transition-all duration-300">
                                    <span class="block text-xs font-bold uppercase tracking-wider mb-0.5" 
                                          :class="currentStep >= step.id ? 'text-gray-900' : 'text-gray-400'">
                                        {{ step.title }}
                                    </span>
                                    <span class="block text-[10px] font-medium" :class="currentStep >= step.id ? 'text-blue-600' : 'text-gray-300'">
                                        {{ step.desc }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden min-h-[500px] flex flex-col">
                    
                    <div class="flex-1 p-6 sm:p-10">
                        <div v-if="currentStep === 1" class="animate-fade-in space-y-8">
                            <h2 class="text-xl font-bold text-gray-800 border-b border-gray-100 pb-4 mb-6">Operator Profile</h2>

                            <div class="flex flex-col sm:flex-row gap-8 items-start">
                                <div class="w-full sm:w-auto flex flex-col items-center">
                                    <div class="w-32 h-32 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group bg-gray-50 hover:border-blue-500 transition-colors">
                                        <img v-if="ownerPhotoPreview" :src="ownerPhotoPreview" class="w-full h-full object-cover" />
                                        <div v-else class="text-center p-4">
                                            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <span class="text-xs text-gray-500 font-semibold">Upload Photo</span>
                                        </div>
                                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleOwnerPhoto" accept="image/*" />
                                    </div>
                                    <p class="text-xs text-gray-400 mt-3 text-center max-w-[150px]">
                                        Submit a clear 1x1 or 2x2 photo with white background.
                                    </p>
                                </div>

                                <div class="flex-1 w-full space-y-6">
                                    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-100">
                                        <h3 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4">Identity</h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div><InputLabel value="First Name" /><TextInput v-model="form.first_name" class="w-full mt-1" placeholder="Juan" /></div>
                                            <div><InputLabel value="Middle Name" /><TextInput v-model="form.middle_name" class="w-full mt-1" placeholder="Dela" /></div>
                                            <div><InputLabel value="Last Name" /><TextInput v-model="form.last_name" class="w-full mt-1" placeholder="Cruz" /></div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-100">
                                        <h3 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4">Contact Information</h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="col-span-1 sm:col-span-1"><InputLabel value="Email Address" /><TextInput type="email" v-model="form.email" class="w-full mt-1" placeholder="juan@example.com" /></div>
                                            <div><InputLabel value="Mobile Number" /><TextInput v-model="form.contact_number" class="w-full mt-1" placeholder="0912 345 6789" /></div>
                                            <div><InputLabel value="TIN No." /><TextInput v-model="form.tin_number" class="w-full mt-1 font-mono" placeholder="000-000-000" /></div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50/50 p-5 rounded-lg border border-gray-100">
                                        <h3 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4">Current Address</h3>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="sm:col-span-3"><InputLabel value="Street / House No." /><TextInput v-model="form.street_address" class="w-full mt-1" placeholder="123 Street Name" /></div>
                                            <div class="relative">
                                                <InputLabel value="Barangay" />
                                                <TextInput v-model="barangayQuery" @focus="showBarangayDropdown = true" @input="showBarangayDropdown = true" class="w-full mt-1" placeholder="Type to search..." autocomplete="off" />
                                                <div v-if="showBarangayDropdown && filteredBarangays.length > 0" class="absolute z-50 w-full bg-white border border-gray-200 mt-1 rounded shadow-xl max-h-48 overflow-y-auto custom-scrollbar">
                                                    <div v-for="b in filteredBarangays" :key="b.id" @click="selectBarangay(b.name)" class="px-3 py-2 hover:bg-blue-50 cursor-pointer text-sm text-gray-700">{{ b.name }}</div>
                                                </div>
                                                <div v-if="showBarangayDropdown" @click="showBarangayDropdown = false" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>
                                            </div>
                                            <div class="sm:col-span-2"><InputLabel value="City" /><TextInput v-model="form.city" class="w-full mt-1 bg-gray-100 text-gray-500" readonly /></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStep === 2" class="animate-fade-in space-y-6">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">Unit Information</h3>
                                    <p class="text-sm text-gray-500">Add details for the tricycle unit(s) you wish to register.</p>
                                </div>
                                <button @click="addFranchise" type="button" class="text-xs bg-blue-50 text-blue-600 font-bold px-4 py-2 rounded-lg border border-blue-100 hover:bg-blue-100 hover:border-blue-200 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    ADD ANOTHER UNIT
                                </button>
                            </div>

                            <div v-for="(franchise, index) in form.franchises" :key="index" class="border border-gray-200 rounded-xl overflow-hidden transition-all duration-300 bg-white shadow-sm">
                                <div @click="toggleFranchise(index)" class="flex justify-between items-center p-4 cursor-pointer bg-gray-50/50 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">{{ index + 1 }}</div>
                                        <div>
                                            <span class="text-sm font-bold text-gray-800 block">Franchise Application #{{ index + 1 }}</span>
                                            <span v-if="!franchiseUIStates[index].isExpanded" class="text-xs text-gray-500">
                                                {{ franchise.zone_id ? getZoneName(franchise.zone_id) : 'Zone not selected' }} • {{ franchise.plate_number || 'No Plate' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button v-if="form.franchises.length > 1" @click.stop="removeFranchise(index)" class="p-2 text-gray-400 hover:text-red-500 transition-colors" title="Remove">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': franchiseUIStates[index].isExpanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                </div>
                                
                                <div v-show="franchiseUIStates[index].isExpanded" class="p-6 bg-white animate-fade-in">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                                        <div class="sm:col-span-2">
                                            <InputLabel value="Preferred Zone / Route" />
                                            <select v-model="franchise.zone_id" class="w-full mt-1 border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                <option value="" disabled>Select Zone</option>
                                                <option v-for="z in (props.zones.length ? props.zones : dummyZones)" :key="z.id" :value="z.id">{{ z.name || z.description }}</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2"><InputLabel value="Acquisition Date" /><TextInput type="date" v-model="franchise.date_issued" class="w-full mt-1" /></div>
                                        
                                        <div>
                                            <InputLabel value="Make / Brand" />
                                            <select v-model="franchise.make_id" class="w-full mt-1 border-gray-300 rounded shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                                                <option value="" disabled>Select Make</option>
                                                <option v-for="m in (props.unitMakes.length ? props.unitMakes : dummyMakes)" :key="m.id" :value="m.id">{{ m.name }}</option>
                                            </select>
                                        </div>
                                        <div><InputLabel value="Model Year" /><TextInput type="number" v-model="franchise.model_year" placeholder="YYYY" class="w-full mt-1" /></div>
                                        <div class="sm:col-span-2"><InputLabel value="Plate Number" /><TextInput v-model="franchise.plate_number" placeholder="ABC 123" class="w-full mt-1 uppercase font-mono" /></div>
                                        
                                        <div class="sm:col-span-2"><InputLabel value="Certificate of Reg. (CR)" /><TextInput v-model="franchise.cr_number" class="w-full mt-1 uppercase" /></div>
                                        <div><InputLabel value="Motor Number" /><TextInput v-model="franchise.motor_number" class="w-full mt-1 uppercase" /></div>
                                        <div><InputLabel value="Chassis Number" /><TextInput v-model="franchise.chassis_number" class="w-full mt-1 uppercase" /></div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t border-gray-100">
                                        <div class="flex items-center gap-2 mb-4">
                                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /></svg>
                                            <span class="text-sm font-bold text-gray-700 uppercase tracking-wider">Unit Photos</span>
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                            <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="aspect-[4/3] border-2 border-dashed border-gray-200 rounded-lg overflow-hidden relative bg-gray-50 hover:border-blue-400 transition-colors group/photo">
                                                <img v-if="franchiseUIStates[index].previews[side]" :src="franchiseUIStates[index].previews[side]" class="w-full h-full object-cover" />
                                                <div v-else class="flex flex-col items-center justify-center h-full text-gray-400 p-2">
                                                    <span class="text-xs uppercase font-bold mb-1">{{ side }} VIEW</span>
                                                    <span class="text-[10px] text-gray-400 text-center">Click to upload</span>
                                                </div>
                                                <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*" @change="(e) => handleUnitPhoto(e, index, side)" />
                                                <div v-if="franchiseUIStates[index].previews[side]" class="absolute inset-0 bg-black/30 opacity-0 group-hover/photo:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold pointer-events-none">Change Photo</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStep === 3" class="animate-fade-in">
                            <div class="text-center mb-8">
                                <h3 class="text-xl font-bold text-gray-900">Evaluation Requirements</h3>
                                <p class="text-sm text-gray-500 max-w-lg mx-auto mt-2">
                                    Please upload clear scanned copies or photos of the following documents. These are required for the City Legal Office evaluation.
                                </p>
                            </div>

                            <div class="flex flex-col gap-4">
                                <div v-for="(label, key) in {
                                    barangay_clearance: 'Barangay Clearance',
                                    police_clearance: 'Police Clearance',
                                    valid_id: 'Valid Government ID',
                                    or_cr: 'LTO OR/CR'
                                }" :key="key" class="bg-white border border-gray-200 rounded-lg p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-blue-300 transition-colors shadow-sm">
                                    
                                    <div class="flex-1 flex items-center gap-4 w-full sm:w-auto">
                                        <div class="flex-shrink-0 w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l5.414 5.414a1 1 0 01.586 1.414V19a2 2 0 01-2 2z" /></svg>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800 text-sm">{{ label }}</h4>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span v-if="form[key]" class="text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full border border-green-100 flex items-center gap-1 w-fit">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                    UPLOADED
                                                </span>
                                                <span v-else class="text-[10px] font-bold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100 w-fit">REQUIRED</span>
                                                <span v-if="form[key]" class="text-[10px] text-gray-400 hidden sm:inline-block truncate max-w-[150px]">{{ form[key].name }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="w-full sm:w-64 h-14 relative group cursor-pointer">
                                        <div class="absolute inset-0 bg-gray-50 border border-dashed border-gray-300 rounded-lg flex items-center justify-center group-hover:bg-blue-50 group-hover:border-blue-400 transition-all">
                                             <div v-if="requirementPreviews[key]" class="flex items-center gap-2 text-blue-600">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                                <span class="text-xs font-bold">Change File</span>
                                             </div>
                                             <div v-else class="flex items-center gap-2 text-gray-500">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                                <span class="text-xs font-semibold">Click to Upload</span>
                                             </div>
                                        </div>
                                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="(e) => handleRequirementUpload(e, key)" />
                                    </div>

                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mt-8 flex gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-blue-800">Review Declaration</h4>
                                    <p class="text-xs text-blue-700 mt-1">
                                        By clicking "Submit Application", I hereby attest that all information provided is true and correct. I understand that any falsification of documents may result in the rejection of this application and possible legal action.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-6 border-t border-gray-100 flex justify-between items-center">
                        <SecondaryButton @click="prevStep" :disabled="currentStep === 1" class="!text-gray-500 !border-gray-300" :class="{'opacity-0 pointer-events-none': currentStep === 1}">
                            &larr; Back
                        </SecondaryButton>
                        
                        <div class="flex gap-3">
                            <PrimaryButton v-if="currentStep < 3" @click="nextStep" class="px-8">
                                Next Step &rarr;
                            </PrimaryButton>
                            <PrimaryButton v-else @click="submit" :disabled="form.processing" class="bg-green-600 hover:bg-green-700 ring-green-500 px-8">
                                <span v-if="form.processing">Submitting...</span>
                                <span v-else>Submit Application</span>
                            </PrimaryButton>
                        </div>
                    </div>

                </div>
            </div>
        </main>
        
        <Footer />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>