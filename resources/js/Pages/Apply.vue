<script setup>
import NavBar from '@/Components/NavBar.vue';
import Footer from '@/Components/Footer.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios'; // <-- Import axios for background API calls

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

// --- Custom Search & Selection Logic ---
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
    
    // Reset dependents
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
    } catch (error) {
        console.error("Failed to load cities:", error);
    }
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
    } catch (error) {
        console.error("Failed to load barangays:", error);
    }
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
    { id: 2, title: 'Franchise Units', desc: 'Unit & Zone Details', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
    { id: 3, title: 'Evaluation Docs', desc: 'Upload Requirements', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }
];

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    email: '',
    contact_number: '',
    tin_number: '',
    street_address: '',
    province: '',
    city: '',
    barangay: '',

    units: [
        {
            make_name: '', zone_id: '', franchise_number: '', date_issued: '', model_year: '', plate_number: '', cr_number: '', motor_number: '', chassis_number: '',
            unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
            cr_photo: null, or_photo: null, franchise_certificate_photo: null
        }
    ],
    
    requirement_files: {}, 
    agreed_to_terms: false
});

// --- OTP & Email Verification State ---
const isEmailVerified = ref(false);
const showOtpModal = ref(false);
const otpCode = ref('');
const otpError = ref('');
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);

const handleEmailChange = (event) => {
    form.clearErrors('email');
    if (isEmailVerified.value) {
        isEmailVerified.value = false; // Reset verification if they change the email
    }
};

const sendOtp = async () => {
    isSendingOtp.value = true;
    otpError.value = '';
    try {
        await axios.post(route('application.send-otp'), { email: form.email });
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
        await axios.post(route('application.verify-otp'), { 
            email: form.email, 
            otp: otpCode.value 
        });
        
        // Success
        isEmailVerified.value = true;
        showOtpModal.value = false;
        otpCode.value = '';
        
        // Automatically proceed to next step
        currentStep.value++;
        window.scrollTo(0, 0);
    } catch (error) {
        otpError.value = error.response?.data?.message || 'Invalid verification code.';
    } finally {
        isVerifyingOtp.value = false;
    }
};


// --- Frontend Validation Logic ---
const validateStep1 = () => {
    form.clearErrors();
    let isValid = true;

    if (!form.first_name?.trim()) { form.setError('first_name', 'First name is required.'); isValid = false; }
    if (!form.last_name?.trim()) { form.setError('last_name', 'Last name is required.'); isValid = false; }
    
    if (!form.email?.trim()) { 
        form.setError('email', 'Email is required.'); 
        isValid = false; 
    } else if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(form.email)) { 
        form.setError('email', 'Please enter a valid email address.'); 
        isValid = false; 
    }
    
    if (!form.contact_number || form.contact_number.length < 13) { 
        form.setError('contact_number', 'Valid 11-digit contact number is required.'); 
        isValid = false; 
    }
    
    if (!form.tin_number || form.tin_number.length < 17) { 
        form.setError('tin_number', 'Valid TIN number is required.'); 
        isValid = false; 
    }

    if (!form.street_address?.trim()) { form.setError('street_address', 'Street address is required.'); isValid = false; }
    if (!form.province) { form.setError('province', 'Province is required.'); isValid = false; }
    if (!form.city) { form.setError('city', 'City is required.'); isValid = false; }
    if (!form.barangay) { form.setError('barangay', 'Barangay is required.'); isValid = false; }

    return isValid;
};

const validateStep2 = () => {
    form.clearErrors();
    let isValid = true;
    let firstErrorIndex = -1;

    form.units.forEach((unit, index) => {
        let unitHasError = false;

        if (!unit.zone_id) { form.setError(`units.${index}.zone_id`, 'Target Zone is required.'); isValid = false; unitHasError = true; }
        if (!unit.franchise_number?.toString().trim()) { form.setError(`units.${index}.franchise_number`, 'Franchise No. is required.'); isValid = false; unitHasError = true; }
        if (!unit.date_issued) { form.setError(`units.${index}.date_issued`, 'Date Issued is required.'); isValid = false; unitHasError = true; }
        if (!unit.make_name) { form.setError(`units.${index}.make_name`, 'Make is required.'); isValid = false; unitHasError = true; }
        if (!unit.model_year) { form.setError(`units.${index}.model_year`, 'Model Year is required.'); isValid = false; unitHasError = true; }
        if (!unit.plate_number?.toString().trim()) { form.setError(`units.${index}.plate_number`, 'Plate No. is required.'); isValid = false; unitHasError = true; }
        if (!unit.motor_number?.toString().trim()) { form.setError(`units.${index}.motor_number`, 'Motor No. is required.'); isValid = false; unitHasError = true; }
        // if (!unit.cr_number?.toString().trim()) { form.setError(`units.${index}.cr_number`, 'CR Number is required.'); isValid = false; unitHasError = true; }
        if (!unit.chassis_number?.toString().trim()) { form.setError(`units.${index}.chassis_number`, 'Chassis No. is required.'); isValid = false; unitHasError = true; }

        if (!unit.unit_front_photo) { form.setError(`units.${index}.unit_front_photo`, 'Front photo required.'); isValid = false; unitHasError = true; }
        if (!unit.unit_back_photo) { form.setError(`units.${index}.unit_back_photo`, 'Back photo required.'); isValid = false; unitHasError = true; }
        if (!unit.unit_left_photo) { form.setError(`units.${index}.unit_left_photo`, 'Left photo required.'); isValid = false; unitHasError = true; }
        if (!unit.unit_right_photo) { form.setError(`units.${index}.unit_right_photo`, 'Right photo required.'); isValid = false; unitHasError = true; }

        if (!unit.cr_photo) { form.setError(`units.${index}.cr_photo`, 'CR document required.'); isValid = false; unitHasError = true; }
        if (!unit.or_photo) { form.setError(`units.${index}.or_photo`, 'OR document required.'); isValid = false; unitHasError = true; }
        if (!unit.franchise_certificate_photo) { form.setError(`units.${index}.franchise_certificate_photo`, 'Franchise Certificate required.'); isValid = false; unitHasError = true; }

        if (unitHasError && firstErrorIndex === -1) {
            firstErrorIndex = index;
        }
    });

    if (firstErrorIndex !== -1) {
        expandedUnitIndex.value = firstErrorIndex;
    }

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

// --- Utilities & Handlers ---
const addUnit = () => {
    form.units.push({
        make_name: '', zone_id: '', franchise_number: '', model_year: '', plate_number: '', cr_number: '', motor_number: '', chassis_number: '',
        unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
        cr_photo: null, or_photo: null, franchise_certificate_photo: null
    });
    expandedUnitIndex.value = form.units.length - 1;
};

const removeUnit = (index) => {
    if (form.units.length > 1) form.units.splice(index, 1);
};

const toggleUnit = (index) => {
    expandedUnitIndex.value = expandedUnitIndex.value === index ? -1 : index;
};

const handleFileChange = (event, index, field) => {
    const file = event.target.files[0];
    if (file) {
        // Add franchise_certificate_photo to the documents list so it accepts PDF
        const isDocument = ['cr_photo', 'or_photo', 'franchise_certificate_photo'].includes(field);
        const validTypes = isDocument 
            ? ['image/jpeg', 'image/png', 'application/pdf'] 
            : ['image/jpeg', 'image/png'];
        
        if (!validTypes.includes(file.type)) {
            form.setError(`units.${index}.${field}`, `Invalid file type. Allowed: ${isDocument ? 'JPG, PNG, PDF' : 'JPG, PNG'}`);
            event.target.value = ''; // Reset the input
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            form.setError(`units.${index}.${field}`, 'File size must not exceed 5MB.');
            event.target.value = ''; // Reset the input
            return;
        }

        form.units[index][field] = file;
        form.clearErrors(`units.${index}.${field}`);
    }
};

const handleRequirementUpload = (event, reqId) => {
    const file = event.target.files[0];
    if (file) {
        const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        
        if (!validTypes.includes(file.type)) {
            form.setError(`requirement_files.${reqId}`, 'Invalid file type. Allowed: JPG, PNG, PDF');
            event.target.value = ''; // Reset the input
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            form.setError(`requirement_files.${reqId}`, 'File size must not exceed 5MB.');
            event.target.value = ''; // Reset the input
            return;
        }

        form.requirement_files[reqId] = file;
        form.clearErrors(`requirement_files.${reqId}`);
    }
};

const removeRequirementFile = (reqId) => {
    delete form.requirement_files[reqId];
};

const nextStep = async () => { 
    if (currentStep.value === 1) {
        if (!validateStep1()) return;
        
        // Intercept: Require Email Verification before moving to Step 2
        if (!isEmailVerified.value) {
            await sendOtp();
            return; 
        }
    } else if (currentStep.value === 2) {
        if (!validateStep2()) return;
    }
    
    if (currentStep.value < 3) { 
        currentStep.value++; 
        window.scrollTo(0, 0); 
    } 
};

const prevStep = () => { if (currentStep.value > 1) { currentStep.value--; window.scrollTo(0, 0); } };

// Modals State
const showPrivacyModal = ref(false);
const showErrorModal = ref(false);
const showSuccessModal = ref(false);

const openPrivacyModal = () => {
    if (!validateStep3()) {
        form.agreed_to_terms = false;
        return;
    }
    showPrivacyModal.value = true;
};

const confirmAndSubmit = () => {
    showPrivacyModal.value = false;
    
    form.post(route('application.store'), {
        preserveScroll: true,
        onError: () => { 
            showErrorModal.value = true; 
            form.agreed_to_terms = false;
        },
        onSuccess: () => {
            showErrorModal.value = false;
            showSuccessModal.value = true; 
            form.reset(); 
        }
    });
};

const goToHome = () => {
    showSuccessModal.value = false;
    router.visit('/'); 
};

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
    <Head title="Apply for Franchise" />
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
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon"></path>
                                </svg>
                            </div>
                            <p class="mt-2 text-xs font-bold uppercase tracking-wider hidden sm:block" :class="currentStep >= step.id ? 'text-blue-600' : 'text-gray-500'">{{ step.title }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-xl rounded-2xl border border-gray-100 relative">
                    <div class="p-8">
                        
                        <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Applicant Information</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="First Name" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <TextInput v-model="form.first_name" @input="form.clearErrors('first_name')" placeholder="e.g. Juan" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.first_name" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel value="Middle Name" />
                                    <TextInput v-model="form.middle_name" placeholder="e.g. Dela Cruz" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Last Name" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <TextInput v-model="form.last_name" @input="form.clearErrors('last_name')" placeholder="e.g. Santos" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.last_name" class="mt-2" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start gap-1">
                                            <InputLabel value="Email" />
                                            <span class="text-red-600 font-bold">*</span>
                                        </div>
                                        <span v-if="isEmailVerified" class="text-xs font-bold text-green-600 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Verified
                                        </span>
                                    </div>
                                    <TextInput type="email" v-model="form.email" @input="handleEmailChange" :class="{'border-green-500 bg-green-50': isEmailVerified}" placeholder="e.g. juan.santos@example.com" class="mt-1 block w-full transition-colors" />
                                    <InputError :message="form.errors.email" class="mt-2" />
                                </div>
                                <div>
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Contact No." />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <TextInput v-model="form.contact_number" @input="form.contact_number = formatContactNumber($event.target.value); form.clearErrors('contact_number')" placeholder="09XX-XXX-XXXX" maxlength="13" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.contact_number" class="mt-2" />
                                </div>
                            </div>
                            <div>
                                <div class="flex items-start gap-1">
                                    <InputLabel value="TIN Number" />
                                    <span class="text-red-600 font-bold">*</span>
                                </div>
                                <TextInput v-model="form.tin_number" @input="form.tin_number = formatTinNumber($event.target.value); form.clearErrors('tin_number')" placeholder="XXX-XXX-XXX-00000" maxlength="17" class="mt-1 block w-full" />
                                <InputError :message="form.errors.tin_number" class="mt-2" />
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div>
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Street / House No." />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <TextInput v-model="form.street_address" @input="form.clearErrors('street_address')" placeholder="e.g. 123 Main Street" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.street_address" class="mt-2" />
                                </div>
                                
                                <div class="relative">
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Province" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <div class="relative z-20">
                                        <TextInput 
                                            v-model="form.province" 
                                            @focus="activeDropdown = 'province'"
                                            @input="form.clearErrors('province')"
                                            class="mt-1 block w-full"
                                            placeholder="Search Province..."
                                            autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                    {{ prov.name }}
                                                </li>
                                                <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.province" class="mt-2" />
                                </div>

                                <div class="relative">
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="City/Municipality" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <div class="relative z-20">
                                        <TextInput 
                                            v-model="form.city" 
                                            @focus="activeDropdown = 'city'"
                                            @input="form.clearErrors('city')"
                                            :disabled="!citiesList.length"
                                            class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                            placeholder="Search City..."
                                            autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                    {{ city.name }}
                                                </li>
                                                <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.city" class="mt-2" />
                                </div>

                                <div class="relative">
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Barangay" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <div class="relative z-20">
                                        <TextInput 
                                            v-model="form.barangay" 
                                            @focus="activeDropdown = 'barangay'"
                                            @input="form.clearErrors('barangay')"
                                            :disabled="!barangaysList.length"
                                            class="mt-1 block w-full disabled:bg-gray-100 disabled:text-gray-500"
                                            placeholder="Search Barangay..."
                                            autocomplete="off"
                                        />
                                        <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-gray-200 rounded-md shadow-2xl max-h-60 overflow-y-auto z-50 ring-1 ring-black ring-opacity-5">
                                            <ul class="py-1">
                                                <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm text-gray-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                    {{ brgy.name }}
                                                </li>
                                                <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-gray-500 italic text-center">No results found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.barangay" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                            <div class="flex justify-between items-center border-b pb-4">
                                <h2 class="text-xl font-semibold text-gray-800">Franchise Units</h2>
                                <button type="button" @click="addUnit" class="text-sm bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100 transition font-medium">Add Unit</button>
                            </div>
                            
                            <div v-for="(unit, index) in form.units" :key="index" class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden transition-all duration-300 relative z-0">
                                <div @click="toggleUnit(index)" class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50" :class="{'bg-blue-50': expandedUnitIndex === index}">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm" :class="expandedUnitIndex === index ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'">{{ index + 1 }}</div>
                                    <div><h3 class="font-bold text-gray-700">{{ unit.make_name || 'New Unit' }}</h3><p class="text-xs text-gray-500">{{ unit.plate_number || 'No Plate' }}</p></div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button v-if="form.units.length > 1" type="button" @click.stop="removeUnit(index)" class="text-red-500 text-sm font-medium">Remove</button>
                                        <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="{'rotate-180': expandedUnitIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>

                                <div v-show="expandedUnitIndex === index" class="p-6 bg-gray-50 border-t border-gray-100">
                                    <div class="mb-6 bg-white p-4 rounded border border-blue-100">
                                        <div class="flex items-start gap-1">
                                            <InputLabel value="Target Zone" />
                                            <span class="text-red-600 font-bold">*</span>
                                        </div>
                                        <select v-model="unit.zone_id" @change="form.clearErrors(`units.${index}.zone_id`)" class="mt-1 block w-full border-blue-300 rounded-md focus:border-blue-500 focus:ring-blue-500">
                                            <option value="" disabled>Select Zone</option>
                                            <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.description }} ({{ zone.color }})</option>
                                        </select>
                                        <InputError :message="form.errors[`units.${index}.zone_id`]" class="mt-2" />
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Franchise No." />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput v-model="unit.franchise_number" @input="form.clearErrors(`units.${index}.franchise_number`)" placeholder="e.g. 12345" class="mt-1 block w-full"/>
                                            <InputError :message="form.errors[`units.${index}.franchise_number`]" class="mt-2" />
                                        </div>
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Date Issued" />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput type="date" v-model="unit.date_issued" @input="form.clearErrors(`units.${index}.date_issued`)" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.date_issued`]" class="mt-2" />
                                        </div>
                                        <div>
                                    <div class="flex items-start gap-1">
                                        <InputLabel value="Make" />
                                        <span class="text-red-600 font-bold">*</span>
                                    </div>
                                    <div class="relative mt-1">
                                        <TextInput 
                                            v-model="unit.make_name" 
                                            @input="form.clearErrors(`units.${index}.make_name`)" 
                                            :list="`make-options-${index}`"
                                            placeholder="Type or select a make/model" 
                                            class="block w-full pr-10 datalist-input" 
                                        />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-500">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>
                                    <datalist :id="`make-options-${index}`">
                                        <option v-for="make in unitMakes" :key="make.id" :value="make.name"></option>
                                    </datalist>
                                    <InputError :message="form.errors[`units.${index}.make_name`]" class="mt-2" />
                                </div>
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Model Year" />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput type="number" v-model="unit.model_year" @input="form.clearErrors(`units.${index}.model_year`)" placeholder="e.g. 2024" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.model_year`]" class="mt-2" />
                                        </div>
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Plate No." />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput v-model="unit.plate_number" @input="form.clearErrors(`units.${index}.plate_number`)" placeholder="e.g. ABC 1234" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.plate_number`]" class="mt-2" />
                                        </div>                                        
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Motor No." />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput v-model="unit.motor_number" @input="form.clearErrors(`units.${index}.motor_number`)" placeholder="e.g. M-123456" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.motor_number`]" class="mt-2" />
                                        </div>
                                        <!-- <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="CR Number" />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput v-model="unit.cr_number" @input="form.clearErrors(`units.${index}.cr_number`)" placeholder="e.g. CR-987654" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.cr_number`]" class="mt-2" />
                                        </div> -->
                                        <div>
                                            <div class="flex items-start gap-1">
                                                <InputLabel value="Chassis No." />
                                                <span class="text-red-600 font-bold">*</span>
                                            </div>
                                            <TextInput v-model="unit.chassis_number" @input="form.clearErrors(`units.${index}.chassis_number`)" placeholder="e.g. C-123456" class="mt-1 block w-full" />
                                            <InputError :message="form.errors[`units.${index}.chassis_number`]" class="mt-2" />
                                        </div>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4">
                                        <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase">Tricycle Unit Photos</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="Tricycle Photo (Front)" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.unit_front_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_front_photo.name }}</span>
                                                    <button type="button" @click="unit.unit_front_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png" @change="e => handleFileChange(e, index, 'unit_front_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.unit_front_photo`]" class="mt-2" />
                                            </div>

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="Tricycle Photo (Back)" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.unit_back_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_back_photo.name }}</span>
                                                    <button type="button" @click="unit.unit_back_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png" @change="e => handleFileChange(e, index, 'unit_back_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.unit_back_photo`]" class="mt-2" />
                                            </div> 

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="Tricycle Photo (Left)" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.unit_left_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_left_photo.name }}</span>
                                                    <button type="button" @click="unit.unit_left_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png" @change="e => handleFileChange(e, index, 'unit_left_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.unit_left_photo`]" class="mt-2" />
                                            </div> 

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="Tricycle Photo (Right)" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.unit_right_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.unit_right_photo.name }}</span>
                                                    <button type="button" @click="unit.unit_right_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png" @change="e => handleFileChange(e, index, 'unit_right_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.unit_right_photo`]" class="mt-2" />
                                            </div> 

                                        </div>
                                        <h4 class="text-xs font-bold text-gray-500 mb-3 uppercase">Documents</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="CR" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.cr_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.cr_photo.name }}</span>
                                                    <button type="button" @click="unit.cr_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png,.pdf" @change="e => handleFileChange(e, index, 'cr_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.cr_photo`]" class="mt-2" />
                                            </div> 

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="OR" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.or_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.or_photo.name }}</span>
                                                    <button type="button" @click="unit.or_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png,.pdf" @change="e => handleFileChange(e, index, 'or_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.or_photo`]" class="mt-2" />
                                            </div> 

                                            <div class="bg-white p-3 border rounded">
                                                <div class="flex items-start gap-1">
                                                    <InputLabel value="Franchise Certificate" />
                                                    <span class="text-red-600 font-bold">*</span>
                                                </div>
                                                <div v-if="unit.franchise_certificate_photo" class="mt-2 flex items-center justify-between bg-green-50 p-2 rounded border border-green-200">
                                                    <span class="text-xs text-green-700 font-medium truncate pr-2">{{ unit.franchise_certificate_photo.name }}</span>
                                                    <button type="button" @click="unit.franchise_certificate_photo = null" class="text-gray-400 hover:text-red-500 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                                                </div>
                                                <input v-else type="file" accept=".jpg,.jpeg,.png,.pdf" @change="e => handleFileChange(e, index, 'franchise_certificate_photo')" class="block w-full text-xs mt-1"/>
                                                <InputError :message="form.errors[`units.${index}.franchise_certificate_photo`]" class="mt-2" />
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <InputError :message="form.errors.units" class="mt-2" />
                        </div>

                        <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                            <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Evaluation Requirements</h2>
                            <p class="text-sm text-gray-600">Please upload the required documents for your application (PDF or Image, Max 5MB).</p>

                            <div v-if="Object.keys(requirements).length === 0" class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                No requirements configured. You may proceed.
                            </div>

                            <div v-else class="space-y-8 relative z-0">
                                <div v-for="(groupReqs, groupName) in requirements" :key="groupName" class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                    <h3 class="font-bold text-blue-800 uppercase text-sm mb-4 border-b border-blue-100 pb-2">
                                        {{ groupName || 'General Requirements' }}
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div v-for="req in groupReqs" :key="req.id" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                            
                                            <div class="md:col-span-1">
                                                <label class="block text-sm font-medium text-gray-700">
                                                    {{ req.name }} <span class="text-red-600 font-bold">*</span>
                                                </label>
                                                <p class="text-xs text-gray-400">Required Document</p>
                                            </div>

                                        <div class="md:col-span-2">
                                            
                                            <div v-if="form.requirement_files[req.id]" class="flex items-center justify-between bg-blue-50 p-3 rounded-lg border border-blue-200">
                                                <div class="flex items-center text-sm text-blue-700 font-medium truncate">
                                                    <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    <span class="truncate">{{ form.requirement_files[req.id].name }}</span>
                                                </div>
                                                <button type="button" @click="removeRequirementFile(req.id)" class="ml-4 text-sm text-red-500 hover:text-red-700 font-medium flex-shrink-0">
                                                    Remove
                                                </button>
                                            </div>

                                            <input v-else
                                                type="file" 
                                                @change="e => handleRequirementUpload(e, req.id)"
                                                accept=".jpg,.jpeg,.png,.pdf"
                                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer"
                                            />
                                            
                                            <InputError :message="form.errors[`requirement_files.${req.id}`]" class="mt-1" />
                                        </div>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="terms" type="checkbox" v-model="form.agreed_to_terms" class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="terms" class="font-medium text-blue-900">I certify that the information above is true and correct.</label>
                                        <p class="text-blue-700">I understand that any false information may result in the rejection of this application.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center relative z-0 rounded-b-2xl">
                        <SecondaryButton type="button" @click="prevStep" :disabled="currentStep === 1" class="!text-gray-500 !border-gray-300" :class="{'opacity-50 pointer-events-none': currentStep === 1}">
                            &larr; Back
                        </SecondaryButton>
                        <div class="flex gap-3">
                            <PrimaryButton type="button" v-if="currentStep < 3" @click="nextStep" :disabled="isSendingOtp" class="px-8">
                                <span v-if="isSendingOtp">Sending Verification...</span>
                                <span v-else>Next Step &rarr;</span>
                            </PrimaryButton>
                            <PrimaryButton type="button" v-else @click="openPrivacyModal" :disabled="form.processing || !form.agreed_to_terms" class="bg-green-600 hover:bg-green-700 ring-green-500 px-8 disabled:opacity-50">
                                Submit Application
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <Footer />
    </div>

    <div v-if="showOtpModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center transform transition-all">
            <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Verify Your Email</h3>
            <p class="text-gray-600 mb-6 text-sm">
                We've sent a 6-digit verification code to <strong>{{ form.email }}</strong>. Please enter it below to proceed.
            </p>

            <TextInput v-model="otpCode" placeholder="Enter 6-digit code" class="mb-4 block w-full text-center text-xl tracking-widest" maxlength="6" />
            <InputError :message="otpError" class="mb-4 text-center" />

            <div class="flex gap-3 w-full">
                <SecondaryButton @click="showOtpModal = false; isSendingOtp = false;" class="flex-1 justify-center">Cancel</SecondaryButton>
                <PrimaryButton @click="verifyOtp" :disabled="isVerifyingOtp || otpCode.length < 6" class="flex-1 justify-center bg-blue-600 hover:bg-blue-700">
                    <span v-if="isVerifyingOtp">Verifying...</span>
                    <span v-else>Verify Code</span>
                </PrimaryButton>
            </div>
        </div>
    </div>

    <div v-if="showPrivacyModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50" aria-labelledby="privacy-modal-title" role="dialog" aria-modal="true">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 m-4 relative animate-fade-in">
            <div class="flex items-center justify-between mb-5 border-b pb-4">
                <h3 class="text-xl font-bold text-gray-800 flex items-center" id="privacy-modal-title">
                    <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    Data Privacy Consent
                </h3>
                <button type="button" @click="showPrivacyModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="mb-6 max-h-96 overflow-y-auto pr-2 text-gray-600 space-y-4 text-sm leading-relaxed">
                
                <div 
                    v-if="settings?.privacy_policy" 
                    class="prose max-w-none text-sm text-gray-600 space-y-4"
                    v-html="settings.privacy_policy"
                ></div>

                <div v-else class="space-y-4">
                    <p>
                        In compliance with the <strong>Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, we require your explicit consent to collect, process, and store your personal information for the purpose of evaluating and managing your franchise application.
                    </p>
                    <p>
                        By proceeding, you acknowledge and agree to the following:
                    </p>
                    <ul class="list-disc list-inside space-y-2 ml-2">
                        <li>The information and documents provided will be used exclusively by the relevant authorities to process, assess, and verify your application.</li>
                        <li>Your data will be stored securely and retained only for as long as necessary to fulfill the purposes mentioned or as required by law.</li>
                        <li><strong>Public Transparency:</strong> Certain non-sensitive details regarding your franchise application, such as franchise status, plate numbers, and assigned zones, may be made available to the public for transparency, verification, and regulatory compliance.</li>
                    </ul>
                </div>

                <p class="pt-4 border-t border-gray-100 mt-4 font-medium text-gray-800">
                    By clicking "I Agree", you declare that the information provided is true and correct, and you authorize the system to process your data as outlined above.
                </p>
            </div>

            <div class="flex justify-end gap-3 border-t pt-5">
                <SecondaryButton type="button" @click="showPrivacyModal = false" class="!text-gray-600 !border-gray-300">
                    Cancel
                </SecondaryButton>
                <PrimaryButton type="button" @click="confirmAndSubmit" class="bg-blue-600 hover:bg-blue-700 px-6">
                    <span v-if="form.processing">Submitting...</span>
                    <span v-else>I Agree & Submit</span>
                </PrimaryButton>
            </div>
        </div>
    </div>

    <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black bg-opacity-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6 m-4 relative">
            
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-xl font-bold text-red-600 flex items-center" id="modal-title">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Submission Failed
                </h3>
                <button type="button" @click="showErrorModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="mb-6 max-h-64 overflow-y-auto pr-2">
                <p class="text-sm text-gray-700 mb-3">Please correct the following issues before continuing:</p>
                <ul class="list-disc list-inside space-y-2">
                    <li v-for="(error, field) in form.errors" :key="field" class="text-sm text-red-500">
                        {{ error }}
                    </li>
                </ul>
            </div>

            <div class="flex justify-end border-t pt-4">
                <button type="button" @click="showErrorModal = false" class="px-5 py-2 bg-gray-200 text-gray-800 font-medium rounded-md hover:bg-gray-300 transition-colors">
                    Got it, let me fix them
                </button>
            </div>
            
        </div>
    </div>

    <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center transform transition-all">
            
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-50 mb-6">
                <svg class="h-10 w-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Application Submitted!</h3>
            
            <p class="text-gray-600 mb-8 leading-relaxed">
                {{ $page.props.flash?.success || 'Your franchise application has been successfully submitted and is now pending evaluation.' }}
            </p>

            <button type="button" @click="goToHome" class="w-full inline-flex justify-center items-center rounded-xl bg-green-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200">
                Return to Homepage
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
            
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Custom Scrollbar for the Dropdowns and Modals */
ul::-webkit-scrollbar, div::-webkit-scrollbar {
    width: 6px;
}
ul::-webkit-scrollbar-track, div::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
ul::-webkit-scrollbar-thumb, div::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
ul::-webkit-scrollbar-thumb:hover, div::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
/* Hide default datalist arrow but keep it clickable beneath the custom SVG */
.datalist-input::-webkit-calendar-picker-indicator {
    opacity: 0;
    cursor: pointer;
    width: 20px; 
    height: 100%;
}
</style>