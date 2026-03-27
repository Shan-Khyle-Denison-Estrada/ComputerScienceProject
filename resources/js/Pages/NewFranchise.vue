<script setup>
import NavBar from '@/Components/NavBar.vue';
import Footer from '@/Components/Footer.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { useForm, Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    zones: { type: Array, default: () => [] },
    unitMakes: { type: Array, default: () => [] },
    requirements: { type: Object, default: () => ({}) },
    settings: { type: Object, default: () => ({}) }
});

// --- API State for Addresses ---
const provincesList = ref([]);
const citiesList = ref([]);
const barangaysList = ref([]);
const activeDropdown = ref(null);

onMounted(async () => {
    try {
        const res = await fetch('https://psgc.gitlab.io/api/provinces');
        let provinces = await res.json();
        
        provinces.push({ code: '130000000', name: 'Metro Manila', isNCR: true });
        provincesList.value = provinces.sort((a, b) => a.name.localeCompare(b.name));
    } catch (error) {
        console.error("Failed to load locations:", error);
    }
});

const filteredProvinces = computed(() => {
    if (!form.province) return provincesList.value;
    return provincesList.value.filter(p => p.name.toLowerCase().includes(form.province.toLowerCase()));
});

const filteredCities = computed(() => {
    if (!form.city) return citiesList.value;
    return citiesList.value.filter(c => c.name.toLowerCase().includes(form.city.toLowerCase()));
});

const filteredBarangays = computed(() => {
    if (!form.barangay) return barangaysList.value;
    return barangaysList.value.filter(b => b.name.toLowerCase().includes(form.barangay.toLowerCase()));
});

const selectProvince = async (prov) => {
    form.province = prov.name;
    form.clearErrors('province');
    activeDropdown.value = null;
    
    form.city = '';
    form.barangay = '';
    citiesList.value = [];
    barangaysList.value = [];

    try {
        if (prov.isNCR) {
            const res = await fetch(`https://psgc.gitlab.io/api/regions/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        } else {
            const res = await fetch(`https://psgc.gitlab.io/api/provinces/${prov.code}/cities-municipalities`);
            citiesList.value = await res.json();
        }
    } catch (error) { console.error("Failed to load cities:", error); }
};

const selectCity = async (city) => {
    form.city = city.name;
    form.clearErrors('city');
    activeDropdown.value = null;
    form.barangay = '';
    barangaysList.value = [];

    try {
        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${city.code}/barangays`);
        barangaysList.value = await res.json();
    } catch (error) { console.error("Failed to load barangays:", error); }
};

const selectBarangay = (brgy) => {
    form.barangay = brgy.name;
    form.clearErrors('barangay');
    activeDropdown.value = null;
};

// --- Form State ---
const currentStep = ref(1);
const expandedUnitIndex = ref(0);

const steps = [
    { id: 1, title: 'Applicant Profile', desc: 'Personal Info', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { id: 2, title: 'Franchise Unit', desc: 'Unit Details', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 3, title: 'Evaluation Docs', desc: 'Requirements', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }
];

const form = useForm({
    first_name: '', middle_name: '', last_name: '', email: '', contact_number: '', tin_number: '',
    street_address: '', province: '', city: '', barangay: '',
    units: [{
        make_id: '', zone_id: '', model_year: '', plate_number: '', cr_number: '', motor_number: '', chassis_number: '',
        unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
        cr_photo: null, or_photo: null
    }],
    requirement_files: {}, 
    information_is_true: false, 
    agreed_to_terms: false
});

// --- OTP Logic ---
const isEmailVerified = ref(false);
const showOtpModal = ref(false);
const otpCode = ref('');
const otpError = ref('');
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);

const handleEmailChange = () => {
    form.clearErrors('email');
    if (isEmailVerified.value) isEmailVerified.value = false; 
};

const sendOtp = async () => {
    isSendingOtp.value = true;
    otpError.value = '';
    try {
        await axios.post(route('new-franchise.send-otp'), { email: form.email });
        showOtpModal.value = true;
    } catch (error) {
        form.setError('email', 'Failed to send OTP. Please check your email format or try again later.');
    } finally {
        isSendingOtp.value = false;
    }
};

const verifyOtp = async () => {
    isVerifyingOtp.value = true;
    otpError.value = '';
    try {
        await axios.post(route('new-franchise.verify-otp'), { email: form.email, otp: otpCode.value });
        isEmailVerified.value = true;
        showOtpModal.value = false;
        otpCode.value = '';
        currentStep.value++;
        window.scrollTo(0, 0);
    } catch (error) {
        otpError.value = error.response?.data?.message || 'Invalid verification code.';
    } finally {
        isVerifyingOtp.value = false;
    }
};

// --- Validations ---
const validateStep1 = () => {
    form.clearErrors();
    let isValid = true;
    if (!form.first_name?.trim()) { form.setError('first_name', 'Required.'); isValid = false; }
    if (!form.last_name?.trim()) { form.setError('last_name', 'Required.'); isValid = false; }
    if (!form.email?.trim()) { form.setError('email', 'Required.'); isValid = false; } 
    else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(form.email)) { form.setError('email', 'Invalid email address.'); isValid = false; }
    if (!form.contact_number || form.contact_number.length < 13) { form.setError('contact_number', 'Valid 11-digit number is required.'); isValid = false; }
    if (!form.street_address?.trim()) { form.setError('street_address', 'Required.'); isValid = false; }
    if (!form.province) { form.setError('province', 'Required.'); isValid = false; }
    if (!form.city) { form.setError('city', 'Required.'); isValid = false; }
    if (!form.barangay) { form.setError('barangay', 'Required.'); isValid = false; }
    return isValid;
};

const validateStep2 = () => {
    form.clearErrors();
    let isValid = true;
    let unitHasError = false;
    let index = 0; // We only have one unit now

    let unit = form.units[index];
    if (!unit.zone_id) { form.setError(`units.${index}.zone_id`, 'Target Zone is required.'); isValid = false; unitHasError = true; }
    if (!unit.make_id) { form.setError(`units.${index}.make_id`, 'Make is required.'); isValid = false; unitHasError = true; }
    if (!unit.model_year) { form.setError(`units.${index}.model_year`, 'Model Year is required.'); isValid = false; unitHasError = true; }
    if (!unit.plate_number?.toString().trim()) { form.setError(`units.${index}.plate_number`, 'Plate No. is required.'); isValid = false; unitHasError = true; }
    if (!unit.motor_number?.toString().trim()) { form.setError(`units.${index}.motor_number`, 'Motor No. is required.'); isValid = false; unitHasError = true; }
    if (!unit.cr_number?.toString().trim()) { form.setError(`units.${index}.cr_number`, 'CR Number is required.'); isValid = false; unitHasError = true; }
    if (!unit.chassis_number?.toString().trim()) { form.setError(`units.${index}.chassis_number`, 'Chassis No. is required.'); isValid = false; unitHasError = true; }
    if (!unit.unit_front_photo) { form.setError(`units.${index}.unit_front_photo`, 'Front photo required.'); isValid = false; unitHasError = true; }
    if (!unit.unit_back_photo) { form.setError(`units.${index}.unit_back_photo`, 'Back photo required.'); isValid = false; unitHasError = true; }
    if (!unit.unit_left_photo) { form.setError(`units.${index}.unit_left_photo`, 'Left photo required.'); isValid = false; unitHasError = true; }
    if (!unit.unit_right_photo) { form.setError(`units.${index}.unit_right_photo`, 'Right photo required.'); isValid = false; unitHasError = true; }
    if (!unit.cr_photo) { form.setError(`units.${index}.cr_photo`, 'CR document required.'); isValid = false; unitHasError = true; }
    if (!unit.or_photo) { form.setError(`units.${index}.or_photo`, 'OR document required.'); isValid = false; unitHasError = true; }

    return isValid;
};

const validateStep3 = () => {
    form.clearErrors();
    let isValid = true;
    for (const groupName in props.requirements) {
        props.requirements[groupName].forEach(req => {
            if (!form.requirement_files[req.id]) {
                form.setError(`requirement_files.${req.id}`, 'This required document is missing.');
                isValid = false;
            }
        });
    }
    return isValid;
};

// --- Handlers ---
const handleFileChange = (event, index, field) => {
    const file = event.target.files[0];
    if (file) {
        form.units[index][field] = file;
        form.clearErrors(`units.${index}.${field}`);
    }
};

const handleRequirementUpload = (event, reqId) => {
    const file = event.target.files[0];
    if (file) {
        form.requirement_files[reqId] = file;
        form.clearErrors(`requirement_files.${reqId}`);
    }
};

const removeRequirementFile = (reqId) => { delete form.requirement_files[reqId]; };

// Navigation
const nextStep = async () => { 
    if (currentStep.value === 1) {
        if (!validateStep1()) return;
        if (!isEmailVerified.value) {
            await sendOtp();
            return;
        }
    } else if (currentStep.value === 2) {
        if (!validateStep2()) return;
    }
    if (currentStep.value < 3) { currentStep.value++; window.scrollTo(0, 0); }
};

const prevStep = () => { 
    if (currentStep.value > 1) { 
        currentStep.value--; 
        window.scrollTo(0, 0); 
    } 
};

// Submission Modals
const showPrivacyModal = ref(false);
const showSuccessModal = ref(false);
const page = usePage();

const openPrivacyModal = () => {
    if (!validateStep3()) return;
    showPrivacyModal.value = true;
};

const confirmAndSubmit = () => {
    form.post(route('new-franchise.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showPrivacyModal.value = false;
            showSuccessModal.value = true;
        }
    });
};

const goToHome = () => { router.visit('/'); };

const formatContactNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 11) parts = parts.substring(0, 11); 
    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 4);
    if (parts.length >= 5) formatted += '-' + parts.substring(4, 7);
    if (parts.length >= 8) formatted += '-' + parts.substring(7, 11);
    if (val.endsWith('-') && (parts.length === 4 || parts.length === 7)) formatted += '-';
    return formatted;
};

const formatTinNumber = (val) => {
    if (!val) return '';
    let parts = val.replace(/\D/g, ''); 
    if (parts.length > 14) parts = parts.substring(0, 14); 
    let formatted = '';
    if (parts.length > 0) formatted += parts.substring(0, 3);
    if (parts.length >= 4) formatted += '-' + parts.substring(3, 6);
    if (parts.length >= 7) formatted += '-' + parts.substring(6, 9);
    if (parts.length >= 10) formatted += '-' + parts.substring(9, 14);
    if (val.endsWith('-') && (parts.length === 3 || parts.length === 6 || parts.length === 9)) formatted += '-';
    return formatted;
};
</script>

<template>
    <Head title="New Franchise Application" />
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">
        <NavBar />
        
        <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-10"></div>

        <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                <div class="mb-8">
                    <div class="flex items-center justify-between relative max-w-3xl mx-auto">
                        <div class="absolute left-0 top-1/2 transform -translate-y-1/2 w-full h-1 bg-gray-200 -z-10"></div>
                        <div v-for="step in steps" :key="step.id" class="flex flex-col items-center bg-gray-50 px-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors duration-300"
                                :class="currentStep >= step.id ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon"></path></svg>
                            </div>
                            <p class="mt-2 text-xs font-bold uppercase tracking-wider hidden sm:block" :class="currentStep >= step.id ? 'text-blue-600' : 'text-gray-500'">{{ step.title }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="Object.keys(form.errors).length > 0 && currentStep === 3" class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg">
                    <h3 class="text-red-800 font-bold text-sm mb-2">Please correct the following errors:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>

                <div class="bg-white shadow-xl rounded-2xl border border-gray-100 relative">
                    <div class="p-8">
                        <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Applicant Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel>First Name <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.first_name" placeholder="e.g. Juan" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.first_name" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel>Middle Name</InputLabel>
                                    <TextInput v-model="form.middle_name" placeholder="e.g. Dela Cruz" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel>Last Name <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.last_name" placeholder="e.g. Santos" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.last_name" class="mt-1" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel>Email Address <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <div class="relative">
                                        <TextInput type="email" v-model="form.email" @input="handleEmailChange" placeholder="e.g. juan.santos@example.com" class="mt-1 block w-full pr-10" />
                                        <div v-if="isEmailVerified" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none mt-1">
                                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.email" class="mt-1" />
                                </div>
                                <div>
                                    <InputLabel>Contact No. <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.contact_number" @input="form.contact_number = formatContactNumber($event.target.value);" placeholder="09XX-XXX-XXXX" maxlength="13" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.contact_number" class="mt-1" />
                                </div>
                            </div>
                            <div>
                                <InputLabel>TIN Number</InputLabel>
                                <TextInput v-model="form.tin_number" @input="form.tin_number = formatTinNumber($event.target.value);" placeholder="XXX-XXX-XXX-00000" maxlength="17" class="mt-1 block w-full" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <InputLabel>Street Address <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.street_address" placeholder="e.g. 123 Main Street, Block 4" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.street_address" class="mt-1" />
                                </div>

                                <div class="relative z-30">
                                    <InputLabel>Province <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.province" @focus="activeDropdown = 'province'" placeholder="Search Province" class="mt-1 block w-full cursor-text" />
                                    <div v-if="activeDropdown === 'province'" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto">
                                        <ul class="py-1"><li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ prov.name }}</li></ul>
                                    </div>
                                    <InputError :message="form.errors.province" class="mt-1" />
                                </div>

                                <div class="relative z-20">
                                    <InputLabel>City <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.city" @focus="activeDropdown = 'city'" :disabled="!form.province" placeholder="Search City" class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-400" />
                                    <div v-if="activeDropdown === 'city' && form.province" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto">
                                        <ul class="py-1"><li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ city.name }}</li></ul>
                                    </div>
                                    <InputError :message="form.errors.city" class="mt-1" />
                                </div>

                                <div class="relative z-10">
                                    <InputLabel>Barangay <span class="text-red-600 font-bold">*</span></InputLabel>
                                    <TextInput v-model="form.barangay" @focus="activeDropdown = 'barangay'" :disabled="!form.city" placeholder="Search Barangay" class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-400" />
                                    <div v-if="activeDropdown === 'barangay' && form.city" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto">
                                        <ul class="py-1"><li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm cursor-pointer hover:bg-blue-50 hover:text-blue-700">{{ brgy.name }}</li></ul>
                                    </div>
                                    <InputError :message="form.errors.barangay" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                            <div class="flex justify-between items-center border-b pb-4">
                                <h2 class="text-xl font-semibold text-gray-800">Franchise Unit</h2>
                            </div>

                            <div v-for="(unit, index) in form.units" :key="index" class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden transition-all duration-300 relative z-0">
                                <div class="flex items-center justify-between p-4 cursor-pointer bg-blue-50 border-b border-gray-200">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm bg-blue-600 text-white">1</div>
                                        <div>
                                            <h3 class="font-bold text-gray-700">{{ unit.make_id ? unitMakes.find(m => m.id === unit.make_id)?.name : 'New Unit Details' }}</h3>
                                            <p class="text-xs text-gray-400">{{ unit.plate_number || 'Plate number pending' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-5 bg-gray-50/50">
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                                        <div>
                                            <InputLabel>Target Zone <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <select v-model="unit.zone_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                                <option value="" disabled>Select Zone</option>
                                                <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.description }}</option>
                                            </select>
                                            <InputError :message="form.errors[`units.${index}.zone_id`]" class="mt-1" />
                                        </div>
                                        
                                        <div>
                                            <InputLabel>Make <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <select v-model="unit.make_id" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                                <option value="" disabled>Select Make</option>
                                                <option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                                            </select>
                                            <InputError :message="form.errors[`units.${index}.make_id`]" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel>Model Year <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <TextInput type="number" v-model="unit.model_year" placeholder="e.g. 2024" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.model_year`]" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel>Plate No. <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <TextInput v-model="unit.plate_number" placeholder="e.g. ABC 1234" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.plate_number`]" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel>Motor No. <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <TextInput v-model="unit.motor_number" placeholder="e.g. M-123456" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.motor_number`]" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel>CR Number <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <TextInput v-model="unit.cr_number" placeholder="e.g. CR-987654" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.cr_number`]" class="mt-1" />
                                        </div>
                                        <div>
                                            <InputLabel>Chassis No. <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <TextInput v-model="unit.chassis_number" placeholder="e.g. C-123456" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.chassis_number`]" class="mt-1" />
                                        </div>
                                    </div>

                                    <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase">Unit Photos</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Photo (Front) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.unit_front_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_front_photo.name }}</span>
                                                <button type="button" @click="unit.unit_front_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'unit_front_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.unit_front_photo`]" class="mt-1" />
                                        </div>
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Photo (Back) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.unit_back_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_back_photo.name }}</span>
                                                <button type="button" @click="unit.unit_back_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'unit_back_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.unit_back_photo`]" class="mt-1" />
                                        </div>
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Photo (Left) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.unit_left_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_left_photo.name }}</span>
                                                <button type="button" @click="unit.unit_left_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'unit_left_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.unit_left_photo`]" class="mt-1" />
                                        </div>
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Photo (Right) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.unit_right_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_right_photo.name }}</span>
                                                <button type="button" @click="unit.unit_right_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'unit_right_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.unit_right_photo`]" class="mt-1" />
                                        </div>
                                    </div>

                                    <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase">Documents</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Certificate of Registration (CR) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.cr_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.cr_photo.name }}</span>
                                                <button type="button" @click="unit.cr_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'cr_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.cr_photo`]" class="mt-1" />
                                        </div>
                                        <div class="bg-white p-3 border rounded">
                                            <InputLabel>Official Receipt (OR) <span class="text-red-600 font-bold">*</span></InputLabel>
                                            <div v-if="unit.or_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.or_photo.name }}</span>
                                                <button type="button" @click="unit.or_photo = null" class="text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                            </div>
                                            <input v-else type="file" @change="e => handleFileChange(e, index, 'or_photo')" class="block w-full text-xs mt-1"/>
                                            <InputError :message="form.errors[`units.${index}.or_photo`]" class="mt-1" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Evaluation Requirements</h2>
                            <p class="text-sm text-gray-600">Please upload the required documents for your application (PDF or Image, Max 5MB).</p>
                            
                            <div v-if="Object.keys(requirements).length === 0" class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                No requirements configured. You may proceed.
                            </div>
                            
                            <div v-else class="space-y-8 relative z-0">
                                <div v-for="(groupReqs, groupName) in requirements" :key="groupName" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                    <h3 class="font-bold text-blue-800 uppercase text-sm mb-4 border-b border-blue-100 pb-2">{{ groupName || 'General Requirements' }}</h3>
                                    <div class="space-y-4">
                                        <div v-for="req in groupReqs" :key="req.id" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                            <div class="md:col-span-1">
                                                <label class="block text-sm font-medium text-gray-700">
                                                    {{ req.name }} <span class="text-red-600 font-bold">*</span>
                                                </label>
                                            </div>
                                            <div class="md:col-span-2">
                                                <div v-if="form.requirement_files[req.id]" class="flex items-center justify-between bg-blue-50 p-3 rounded-lg border border-blue-200">
                                                    <div class="flex items-center text-sm text-blue-700 font-medium truncate">
                                                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                        {{ form.requirement_files[req.id].name }}
                                                    </div>
                                                    <button type="button" @click="removeRequirementFile(req.id)" class="text-blue-400 hover:text-red-500 transition-colors shrink-0 ml-4">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                                <div v-else class="relative border-2 border-dashed border-gray-300 rounded-lg p-4 hover:bg-gray-50 hover:border-blue-400 transition-colors text-center cursor-pointer group">
                                                    <input type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="(e) => handleRequirementUpload(e, req.id)" accept=".pdf,image/*" />
                                                    <svg class="mx-auto h-8 w-8 text-gray-400 group-hover:text-blue-500 transition-colors mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                                    <p class="text-xs text-gray-500 group-hover:text-blue-600 font-medium">Click to upload or drag and drop</p>
                                                </div>
                                                <InputError :message="form.errors[`requirement_files.${req.id}`]" class="mt-1" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 bg-blue-50 p-5 rounded-lg border border-blue-100 flex items-start gap-3">
                                <input id="info_true" type="checkbox" v-model="form.information_is_true" class="mt-0.5 w-5 h-5 text-blue-600 bg-white border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                <label for="info_true" class="text-sm text-blue-900 font-medium cursor-pointer leading-relaxed">
                                    I hereby certify that all information provided in this application, including all attached documents, are true, correct, and complete to the best of my knowledge. I understand that any false statement or misrepresentation may lead to the rejection of my application.
                                </label>
                            </div>
                        </div>

                        <div class="mt-10 pt-6 border-t border-gray-100 flex items-center justify-between">
                            <SecondaryButton @click="prevStep" :class="{ 'invisible': currentStep === 1 }">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                                Back
                            </SecondaryButton>

                            <PrimaryButton v-if="currentStep < 3" @click="nextStep" :disabled="isSendingOtp" class="bg-blue-600 hover:bg-blue-700">
                                <template v-if="isSendingOtp">
                                    Sending Verification...
                                    <svg class="animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </template>
                                <template v-else>
                                    Next Step
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </template>
                            </PrimaryButton>

                            <PrimaryButton v-if="currentStep === 3" @click="openPrivacyModal" :disabled="!form.information_is_true" class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                Submit Application
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <Footer />
        
        <Modal :show="showOtpModal" @close="!isVerifyingOtp ? showOtpModal = false : null" max-width="md">
            <div class="p-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-center text-gray-900 mb-2">Verify Your Email</h3>
                <p class="text-sm text-center text-gray-500 mb-6">We've sent a 6-digit verification code to <span class="font-medium text-gray-900">{{ form.email }}</span>. Please enter it below to proceed.</p>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Verification Code" />
                        <TextInput type="text" v-model="otpCode" class="mt-1 block w-full text-center text-2xl tracking-[0.5em] font-mono" placeholder="------" maxlength="6" autofocus />
                        <InputError :message="otpError" class="mt-2 text-center" />
                    </div>
                    <div class="flex flex-col gap-3 mt-6">
                        <PrimaryButton @click="verifyOtp" :disabled="otpCode.length !== 6 || isVerifyingOtp" class="w-full justify-center py-3">
                            {{ isVerifyingOtp ? 'Verifying...' : 'Verify Code & Continue' }}
                        </PrimaryButton>
                        <button type="button" @click="sendOtp" :disabled="isSendingOtp" class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                            {{ isSendingOtp ? 'Sending...' : 'Didn\'t receive it? Resend Code' }}
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

        <div v-if="showPrivacyModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50" aria-labelledby="privacy-modal-title" role="dialog" aria-modal="true">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 m-4 relative animate-fade-in">
                <div class="flex items-center justify-between mb-5 border-b pb-4">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center" id="privacy-modal-title">
                        <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Privacy Policy & Terms
                    </h3>
                    <button @click="showPrivacyModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="mt-2 h-64 overflow-y-auto p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 leading-relaxed custom-scrollbar">
                    
                    <div v-if="settings?.privacy_policy || settings?.terms_of_service" class="prose max-w-none text-sm text-gray-600">
                        <div v-if="settings?.privacy_policy" v-html="settings.privacy_policy" class="mb-6"></div>
                        <hr v-if="settings?.privacy_policy && settings?.terms_of_service" class="my-6 border-gray-300">
                        <div v-if="settings?.terms_of_service" v-html="settings.terms_of_service"></div>
                    </div>

                    <div v-else>
                        <p class="mb-4">By checking the box below, you explicitly consent to the collection, processing, and storage of your personal data by the Tricycle Franchising Board.</p>
                        <p class="mb-4 font-bold">1. Data Collection</p>
                        <p class="mb-4">We collect personal information such as your name, address, contact details, and attached documents purely for the assessment and processing of your franchise application.</p>
                        <p class="mb-4 font-bold">2. Data Usage</p>
                        <p class="mb-4">Your data will be exclusively used to verify your identity, evaluate your eligibility, and communicate official updates regarding your application status.</p>
                        <p class="mb-4 font-bold">3. Data Protection</p>
                        <p>Your information is stored securely in accordance with the Data Privacy Act of 2012 (RA 10173). We will not share your data with third parties without your prior consent unless mandated by law.</p>
                    </div>
                </div>

                <div class="mt-6 flex items-start">
                    <div class="flex items-center h-5">
                        <input id="terms" type="checkbox" v-model="form.agreed_to_terms" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                    </div>
                    <label for="terms" class="ml-3 text-sm font-medium text-gray-900 cursor-pointer">
                        I have read and agree to the Privacy Policy and Terms of Service.
                    </label>
                </div>

                <div class="mt-8 flex justify-end gap-3 border-t pt-4">
                    <SecondaryButton @click="showPrivacyModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" @click="confirmAndSubmit" :disabled="!form.agreed_to_terms || form.processing" class="bg-blue-600 hover:bg-blue-700 px-6">
                        {{ form.processing ? 'Submitting...' : 'I Agree & Submit' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 m-4 relative text-center animate-fade-in">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-6">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Application Submitted!</h3>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    {{ page.props.flash?.success || 'Your new franchise application has been successfully submitted and is now pending evaluation.' }}
                </p>

                <button type="button" @click="goToHome" class="w-full inline-flex justify-center items-center rounded-xl bg-green-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-green-700 transition-all duration-200">
                    Return to Homepage
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

ul::-webkit-scrollbar, div::-webkit-scrollbar { width: 6px; }
ul::-webkit-scrollbar-track, div::-webkit-scrollbar-track { background: transparent; }
ul::-webkit-scrollbar-thumb, div::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
ul:hover::-webkit-scrollbar-thumb, div:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
</style>