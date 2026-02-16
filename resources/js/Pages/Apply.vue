<script setup>
import NavBar from '@/Components/NavBar.vue';
import Footer from '@/Components/Footer.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    barangays: { type: Array, default: () => [] },
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    requirements: { type: Object, default: () => ({}) }
});

const currentStep = ref(1);
const steps = [
    { id: 1, title: 'Applicant Profile', desc: 'Personal Info', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 2, title: 'Franchise Units', desc: 'Unit & Zone Details', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 3, title: 'Review & Submit', desc: 'Checklist & Finalize', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }
];

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    tin_number: '',
    street_address: '',
    barangay: '',
    city: 'Zamboanga City', // Default

    units: [
        {
            make_id: '',
            zone_id: '', // Moved here
            model_year: '',
            plate_number: '',
            motor_number: '',
            chassis_number: '',
            unit_front_photo: null,
            unit_back_photo: null,
            unit_left_photo: null,
            unit_right_photo: null,
            cr_photo: null,
            or_photo: null,
            franchise_certificate_photo: null, // Added
        }
    ],
    agreed_to_terms: false
});

// [!code ++] Track which unit card is currently open (default to 0)
const expandedUnitIndex = ref(0);

const addUnit = () => {
    form.units.push({
        make_id: '', zone_id: '', model_year: '', plate_number: '', motor_number: '', chassis_number: '',
        unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
        cr_photo: null, or_photo: null, franchise_certificate_photo: null
    });
    
    // [!code ++] Automatically expand the newly created unit
    expandedUnitIndex.value = form.units.length - 1;
};

// [!code ++] Toggle function
const toggleUnit = (index) => {
    expandedUnitIndex.value = expandedUnitIndex.value === index ? -1 : index;
};

const removeUnit = (index) => {
    if (form.units.length > 1) form.units.splice(index, 1);
};

const handleFileChange = (event, index, field) => {
    const file = event.target.files[0];
    if (file) form.units[index][field] = file;
};

const nextStep = () => { if (currentStep.value < 3) { currentStep.value++; window.scrollTo(0, 0); } };
const prevStep = () => { if (currentStep.value > 1) { currentStep.value--; window.scrollTo(0, 0); } };
const submit = () => { form.post(route('application.store'), { preserveScroll: true, onSuccess: () => form.reset() }); };
</script>

<template>
    <Head title="Apply for Franchise" />
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <NavBar />
        
        <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                
                <div class="text-center mb-10">
                    <h1 class="text-3xl font-bold text-gray-900">Franchise Application</h1>
                    <p class="mt-2 text-gray-600">Register your units efficiently.</p>
                </div>

                <div class="mb-8">
                    <div class="flex items-center justify-between relative max-w-3xl mx-auto">
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                        <div v-for="step in steps" :key="step.id" class="flex flex-col items-center bg-gray-50 px-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors duration-300"
                                :class="currentStep >= step.id ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon"></path>
                                </svg>
                            </div>
                            <p class="mt-2 text-xs font-bold uppercase tracking-wider hidden sm:block" :class="currentStep >= step.id ? 'text-blue-600' : 'text-gray-500'">{{ step.title }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                    <div class="p-8">
                        
                        <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Applicant Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel value="First Name" />
                                    <TextInput v-model="form.first_name" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.first_name" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Middle Name" />
                                    <TextInput v-model="form.middle_name" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel value="Last Name" />
                                    <TextInput v-model="form.last_name" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.last_name" class="mt-2" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel value="Email Address" />
                                    <TextInput type="email" v-model="form.email" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Contact Number" />
                                    <TextInput v-model="form.contact_number" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.contact_number" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="TIN Number" />
                                <TextInput v-model="form.tin_number" class="mt-1 block w-full" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-1">
                                    <InputLabel value="Street Address" />
                                    <TextInput v-model="form.street_address" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel value="Barangay" />
                                    <select v-model="form.barangay" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                        <option value="" disabled>Select Barangay</option>
                                        <option v-for="brgy in barangays" :key="brgy.id" :value="brgy.name">{{ brgy.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.barangay" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="City" />
                                    <TextInput v-model="form.city" class="mt-1 block w-full bg-gray-50" />
                                    <InputError :message="form.errors.city" class="mt-2" />
                                </div>
                            </div>
                        </div>

<div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
    <div class="flex justify-between items-center border-b pb-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Franchise Units</h2>
            <p class="text-sm text-gray-500">Click on a unit header to expand/collapse details.</p>
        </div>
        <button type="button" @click="addUnit" class="flex items-center gap-2 text-sm bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Unit
        </button>
    </div>
    
    <div v-for="(unit, index) in form.units" :key="index" class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden transition-all duration-300">
        
        <div 
            @click="toggleUnit(index)"
            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 transition select-none"
            :class="{'bg-blue-50 border-b border-blue-100': expandedUnitIndex === index}"
        >
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm"
                    :class="expandedUnitIndex === index ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'">
                    {{ index + 1 }}
                </div>
                <div>
                    <h3 class="font-bold text-gray-700">
                        {{ unit.make_id ? unitMakes.find(m => m.id === unit.make_id)?.name : 'New Unit' }}
                    </h3>
                    <p class="text-xs text-gray-500">
                        {{ unit.plate_number || 'No Plate' }} • {{ unit.zone_id ? zones.find(z => z.id === unit.zone_id)?.description : 'No Zone Selected' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button 
                    v-if="form.units.length > 1" 
                    @click.stop="removeUnit(index)" 
                    class="text-red-500 hover:text-red-700 text-sm font-medium px-2 py-1 rounded hover:bg-red-50 transition"
                >
                    Remove
                </button>
                
                <svg class="w-5 h-5 text-gray-400 transform transition-transform duration-200"
                    :class="{'rotate-180 text-blue-600': expandedUnitIndex === index}" 
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <div v-show="expandedUnitIndex === index" class="p-6 bg-gray-50 border-t border-gray-100">
            
            <div class="mb-6 bg-white p-4 rounded border border-blue-100 shadow-sm">
                <InputLabel value="Target Zone (TODA)" class="text-blue-900" />
                <select v-model="unit.zone_id" class="mt-1 block w-full border-blue-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                    <option value="" disabled>Select Zone</option>
                    <option v-for="zone in zones" :key="zone.id" :value="zone.id">
                        {{ zone.description }} ({{ zone.color }})
                    </option>
                </select>
                <InputError :message="form.errors[`units.${index}.zone_id`]" class="mt-2" />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <InputLabel value="Make/Brand" />
                    <select v-model="unit.make_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="" disabled>Select Make</option>
                        <option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                    </select>
                    <InputError :message="form.errors[`units.${index}.make_id`]" class="mt-2" />
                </div>
                <div>
                    <InputLabel value="Model Year" />
                    <TextInput type="number" v-model="unit.model_year" class="mt-1 block w-full" />
                    <InputError :message="form.errors[`units.${index}.model_year`]" class="mt-2" />
                </div>
                <div>
                    <InputLabel value="Plate Number (Optional)" />
                    <TextInput v-model="unit.plate_number" class="mt-1 block w-full" placeholder="For Registration" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <InputLabel value="Motor Number" />
                    <TextInput v-model="unit.motor_number" class="mt-1 block w-full" />
                    <InputError :message="form.errors[`units.${index}.motor_number`]" class="mt-2" />
                </div>
                <div>
                    <InputLabel value="Chassis Number" />
                    <TextInput v-model="unit.chassis_number" class="mt-1 block w-full" />
                    <InputError :message="form.errors[`units.${index}.chassis_number`]" class="mt-2" />
                </div>
            </div>

            <div class="border-t border-gray-200 pt-4">
                <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase tracking-wide">Unit Photos</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white p-3 border rounded shadow-sm">
                        <InputLabel value="Front View" class="mb-1 text-xs uppercase text-gray-500" />
                        <input type="file" @change="e => handleFileChange(e, index, 'unit_front_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 hover:file:bg-gray-200"/>
                        <InputError :message="form.errors[`units.${index}.unit_front_photo`]" class="mt-1" />
                    </div>
                    <div class="bg-white p-3 border rounded shadow-sm">
                        <InputLabel value="Back View" class="mb-1 text-xs uppercase text-gray-500" />
                        <input type="file" @change="e => handleFileChange(e, index, 'unit_back_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 hover:file:bg-gray-200"/>
                        <InputError :message="form.errors[`units.${index}.unit_back_photo`]" class="mt-1" />
                    </div>
                    <div class="bg-white p-3 border rounded shadow-sm">
                        <InputLabel value="Left Side View" class="mb-1 text-xs uppercase text-gray-500" />
                        <input type="file" @change="e => handleFileChange(e, index, 'unit_left_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 hover:file:bg-gray-200"/>
                        <InputError :message="form.errors[`units.${index}.unit_left_photo`]" class="mt-1" />
                    </div>
                    <div class="bg-white p-3 border rounded shadow-sm">
                        <InputLabel value="Right Side View" class="mb-1 text-xs uppercase text-gray-500" />
                        <input type="file" @change="e => handleFileChange(e, index, 'unit_right_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-100 hover:file:bg-gray-200"/>
                        <InputError :message="form.errors[`units.${index}.unit_right_photo`]" class="mt-1" />
                    </div>
                </div>

                <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase tracking-wide">Documents</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-yellow-50 p-3 border border-yellow-200 rounded">
                        <InputLabel value="Certificate of Registration (CR)" class="mb-1 text-xs uppercase text-yellow-800" />
                        <input type="file" @change="e => handleFileChange(e, index, 'cr_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-white hover:file:bg-gray-100"/>
                        <InputError :message="form.errors[`units.${index}.cr_photo`]" class="mt-1" />
                    </div>
                    <div class="bg-yellow-50 p-3 border border-yellow-200 rounded">
                        <InputLabel value="Official Receipt (OR)" class="mb-1 text-xs uppercase text-yellow-800" />
                        <input type="file" @change="e => handleFileChange(e, index, 'or_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-white hover:file:bg-gray-100"/>
                        <InputError :message="form.errors[`units.${index}.or_photo`]" class="mt-1" />
                    </div>
                    <div class="bg-green-50 p-3 border border-green-200 rounded">
                        <InputLabel value="Franchise Certificate" class="mb-1 text-xs uppercase text-green-800" />
                        <input type="file" @change="e => handleFileChange(e, index, 'franchise_certificate_photo')" class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-white hover:file:bg-gray-100"/>
                        <p class="text-[10px] text-green-600 mt-1">Optional for new applicants</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <InputError :message="form.errors.units" class="mt-2" />
</div>

                        <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Requirements Checklist</h2>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5">
                                <div v-for="(groupReqs, groupName) in requirements" :key="groupName" class="mb-4 last:mb-0">
                                    <h3 class="font-bold text-yellow-800 uppercase text-xs mb-2">{{ groupName || 'General' }}</h3>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li v-for="req in groupReqs" :key="req.id" class="text-sm text-gray-700">{{ req.name }}</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="flex items-start mt-6">
                                <div class="flex items-center h-5">
                                    <input id="terms" type="checkbox" v-model="form.agreed_to_terms" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="terms" class="font-medium text-gray-700">I certify that the information above is true and correct.</label>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <SecondaryButton @click="prevStep" :disabled="currentStep === 1" class="!text-gray-500 !border-gray-300" :class="{'opacity-50 pointer-events-none': currentStep === 1}">
                            &larr; Back
                        </SecondaryButton>
                        <div class="flex gap-3">
                            <PrimaryButton v-if="currentStep < 3" @click="nextStep" class="px-8">Next Step &rarr;</PrimaryButton>
                            <PrimaryButton v-else @click="submit" :disabled="form.processing || !form.agreed_to_terms" class="bg-green-600 hover:bg-green-700 ring-green-500 px-8 disabled:opacity-50">
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
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>