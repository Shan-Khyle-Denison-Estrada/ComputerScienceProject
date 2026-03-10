<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import NavBar from "@/Components/NavBar.vue";
import Footer from "@/Components/Footer.vue";
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const props = defineProps({
    zones: Array,
    unitMakes: Array,
    requirements: Object,
});

// --- STATE MANAGEMENT ---
const currentStep = ref(1);
const totalSteps = 3;

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
    
    units: [{
        make_id: '',
        zone_id: '',
        motor_number: '',
        chassis_number: '',
        model_year: new Date().getFullYear(),
        unit_front_photo: null,
        unit_back_photo: null,
        unit_left_photo: null,
        unit_right_photo: null,
        cr_photo: null,
        or_photo: null,
    }],
    
    requirement_files: {},
});

// --- API STATE FOR ADDRESSES ---
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

// --- FORMATTERS ---
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

// --- OTP LOGIC ---
const otpStep = ref('idle'); 
const otpCode = ref('');
const otpError = ref('');

const sendOtp = async () => {
    if (!form.email) {
        otpError.value = "Please enter an email address first.";
        return;
    }
    otpStep.value = 'sending';
    otpError.value = '';
    form.clearErrors('email');
    
    try {
        await axios.post(route('application.send-otp'), { email: form.email });
        otpStep.value = 'sent';
    } catch (err) {
        otpError.value = err.response?.data?.message || "Failed to send OTP.";
        otpStep.value = 'idle';
    }
};

const verifyOtp = async () => {
    otpError.value = '';
    try {
        await axios.post(route('application.verify-otp'), { email: form.email, otp: otpCode.value });
        otpStep.value = 'verified';
    } catch (err) {
        otpError.value = err.response?.data?.message || "Invalid OTP code.";
    }
};

const handleEmailChange = () => {
    if (otpStep.value === 'verified') {
        otpStep.value = 'idle';
    }
    form.clearErrors('email');
};

// --- FILE UPLOAD HELPERS ---
const handleRequirementFile = (e, reqId) => {
    form.requirement_files[reqId] = e.target.files[0];
};

const handleUnitPhoto = (e, field) => {
    form.units[0][field] = e.target.files[0];
};

const getFileName = (file) => file ? file.name : null;

// --- STEP NAVIGATION & VALIDATION ---
const stepError = ref('');

const scrollToError = () => {
    setTimeout(() => {
        const errorBanner = document.getElementById('error-banner');
        if (errorBanner) {
            errorBanner.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }, 100);
};

const nextStep = () => {
    stepError.value = '';

    // Basic Validation per step
    if (currentStep.value === 1) {
        // <-- Added form.tin_number to the check here
        if (!form.first_name || !form.last_name || !form.contact_number || !form.tin_number || !form.street_address || !form.barangay || !form.city || !form.province) {
            stepError.value = "Please fill in all required applicant fields.";
            scrollToError();
            return;
        }
        if (form.contact_number.length < 13) {
            stepError.value = "Please provide a valid 11-digit contact number.";
            scrollToError();
            return;
        }
        if (form.tin_number.length < 15) { // Needs to be well formatted
            stepError.value = "Please provide a complete TIN Number.";
            scrollToError();
            return;
        }
        if (otpStep.value !== 'verified') {
            stepError.value = "Please verify your email address before proceeding.";
            scrollToError();
            return;
        }
    } else if (currentStep.value === 2) {
        const u = form.units[0];
        if (!u.zone_id || !u.make_id || !u.motor_number || !u.chassis_number || !u.model_year) {
            stepError.value = "Please fill in all required unit details.";
            scrollToError();
            return;
        }
        if (!u.or_photo || !u.cr_photo || !u.unit_front_photo || !u.unit_back_photo || !u.unit_left_photo || !u.unit_right_photo) {
            stepError.value = "Please upload all required unit photos and documents.";
            scrollToError();
            return;
        }
    }

    if (currentStep.value < totalSteps) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        stepError.value = '';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const submitApplication = () => {
    form.post(route('apply.new_franchise.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            currentStep.value = 1;
            otpStep.value = 'idle';
            otpCode.value = '';
            form.reset();
        },
        onError: () => {
            scrollToError();
        }
    });
};
</script>

<template>
    <Head title="Apply for New Franchise" />
    <NavBar />

    <div class="min-h-screen bg-slate-50 py-12 px-4 sm:px-6 lg:px-8 mt-12 font-sans text-slate-800">
        
        <div v-if="activeDropdown" @click="activeDropdown = null" class="fixed inset-0 z-10"></div>

        <div class="max-w-4xl mx-auto relative z-20">
            
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center p-3 bg-blue-100 rounded-full mb-4 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight">New Franchise Application</h1>
                <p class="mt-3 text-slate-500 max-w-2xl mx-auto">Follow the steps below to submit your details, upload your requirements, and register your proposed tricycle for a new franchise.</p>
            </div>

            <Transition name="fade">
                <div v-if="$page.props.flash?.success" class="mb-8 p-5 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-4 shadow-sm">
                    <div class="p-2 bg-green-500 rounded-full text-white shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold">Application Submitted Successfully!</h3>
                        <p class="text-sm mt-1">{{ $page.props.flash.success }}</p>
                    </div>
                </div>
            </Transition>

            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative z-20">
                
                <div class="bg-slate-50/50 border-b border-slate-100 px-6 py-6 sm:px-10">
                    <div class="flex items-center justify-between relative">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-slate-200 rounded-full z-0 hidden sm:block"></div>
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-blue-500 rounded-full z-0 transition-all duration-500 hidden sm:block" :style="`width: ${((currentStep - 1) / (totalSteps - 1)) * 100}%`"></div>

                        <div class="relative z-10 flex flex-col items-center gap-2" v-for="step in totalSteps" :key="step">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-colors duration-300 border-2"
                                :class="[
                                    currentStep === step ? 'bg-blue-600 border-blue-600 text-white shadow-md shadow-blue-200' : 
                                    currentStep > step ? 'bg-blue-500 border-blue-500 text-white' : 'bg-white border-slate-300 text-slate-400'
                                ]"
                            >
                                <svg v-if="currentStep > step" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span v-else>{{ step }}</span>
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider hidden sm:block" :class="currentStep >= step ? 'text-blue-600' : 'text-slate-400'">
                                {{ step === 1 ? 'Applicant' : step === 2 ? 'Tricycle' : 'Documents' }}
                            </span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submitApplication" class="p-6 sm:p-10 relative">
                    
                    <div id="error-banner" v-if="stepError || Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 flex items-start gap-3">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-sm font-medium">
                            <p v-if="stepError">{{ stepError }}</p>
                            <ul v-if="Object.keys(form.errors).length > 0" class="list-disc ml-4 mt-2">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </div>

                    <Transition name="slide-fade" mode="out-in">
                        <div v-show="currentStep === 1" key="step1" class="space-y-6">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Personal Information</h2>
                                <p class="text-sm text-slate-500 mb-6">Enter the primary operator's details.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div>
                                    <InputLabel>First Name <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.first_name" class="mt-1 w-full bg-slate-50" placeholder="Juan" />
                                </div>
                                <div>
                                    <InputLabel>Middle Name</InputLabel>
                                    <TextInput v-model="form.middle_name" class="mt-1 w-full bg-slate-50" placeholder="Dela" />
                                </div>
                                <div>
                                    <InputLabel>Last Name <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.last_name" class="mt-1 w-full bg-slate-50" placeholder="Cruz" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <InputLabel>Contact Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput 
                                        v-model="form.contact_number" 
                                        @input="form.contact_number = formatContactNumber($event.target.value)" 
                                        class="mt-1 w-full bg-slate-50" 
                                        placeholder="09XX-XXX-XXXX" 
                                        maxlength="13" 
                                    />
                                </div>
                                <div>
                                    <InputLabel>TIN Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput 
                                        v-model="form.tin_number" 
                                        @input="form.tin_number = formatTinNumber($event.target.value)" 
                                        class="mt-1 w-full bg-slate-50" 
                                        placeholder="XXX-XXX-XXX-00000" 
                                        maxlength="17" 
                                    />
                                </div>
                            </div>

                            <div class="bg-blue-50/50 border border-blue-100 p-5 rounded-xl mt-6 relative overflow-hidden">
                                <div v-if="otpStep === 'verified'" class="absolute inset-0 bg-green-50/90 z-10 flex flex-col items-center justify-center transition-all">
                                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white mb-2 shadow-lg shadow-green-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <span class="font-bold text-green-800">Email Verified Successfully</span>
                                    <span class="text-xs text-green-600 mt-1">{{ form.email }}</span>
                                </div>

                                <InputLabel>Email Address <span class="text-red-500">*</span></InputLabel>
                                <p class="text-xs text-slate-500 mb-3">We will send a One-Time Password to verify your email.</p>
                                
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <TextInput 
                                        v-model="form.email" 
                                        @input="handleEmailChange" 
                                        type="email" 
                                        class="w-full flex-1" 
                                        :class="{'border-red-500 focus:ring-red-500': form.errors.email}"
                                        placeholder="juandelacruz@email.com" 
                                        :disabled="otpStep === 'verified'" 
                                    />
                                    <PrimaryButton v-if="otpStep === 'idle' || otpStep === 'sending'" type="button" @click="sendOtp" :disabled="otpStep === 'sending'" class="shrink-0 justify-center">
                                        <svg v-if="otpStep === 'sending'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        {{ otpStep === 'sending' ? 'Sending...' : 'Send OTP' }}
                                    </PrimaryButton>
                                </div>
                                
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-2 font-medium">{{ form.errors.email }}</p>
                                <p v-if="otpError" class="text-red-500 text-xs mt-2 font-medium">{{ otpError }}</p>

                                <Transition name="fade">
                                    <div v-if="otpStep === 'sent'" class="mt-4 pt-4 border-t border-blue-100">
                                        <InputLabel>Enter the 6-digit code</InputLabel>
                                        <div class="flex flex-col sm:flex-row gap-3 mt-1">
                                            <TextInput v-model="otpCode" class="w-full sm:w-48 tracking-[0.5em] text-center font-bold text-lg bg-white" placeholder="••••••" maxlength="6" />
                                            <SecondaryButton type="button" @click="verifyOtp" class="justify-center border-blue-300 text-blue-700 hover:bg-blue-50">Verify Code</SecondaryButton>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <div class="pt-6 border-t border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800 mb-4">Complete Address</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="md:col-span-2">
                                        <InputLabel>House No. / Street <span class="text-red-500">*</span></InputLabel>
                                        <TextInput v-model="form.street_address" class="mt-1 w-full bg-slate-50" placeholder="123 Main Street" />
                                    </div>

                                    <div class="relative" :class="{ 'z-50': activeDropdown === 'province' }">
                                        <InputLabel>Province <span class="text-red-500">*</span></InputLabel>
                                        <div class="relative">
                                            <TextInput 
                                                v-model="form.province" 
                                                @focus="activeDropdown = 'province'"
                                                @input="form.clearErrors('province')"
                                                class="mt-1 block w-full bg-slate-50"
                                                placeholder="Search Province..."
                                                autocomplete="off"
                                            />
                                            <div v-if="activeDropdown === 'province'" class="absolute w-full mt-1 bg-white border border-slate-200 rounded-md shadow-2xl max-h-60 overflow-y-auto ring-1 ring-black ring-opacity-5 custom-scrollbar">
                                                <ul class="py-1">
                                                    <li v-for="prov in filteredProvinces" :key="prov.code" @click="selectProvince(prov)" class="px-4 py-2 text-sm text-slate-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                        {{ prov.name }}
                                                    </li>
                                                    <li v-if="!filteredProvinces.length" class="px-4 py-3 text-sm text-slate-500 italic text-center">No results found</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative" :class="{ 'z-50': activeDropdown === 'city' }">
                                        <InputLabel>City/Municipality <span class="text-red-500">*</span></InputLabel>
                                        <div class="relative">
                                            <TextInput 
                                                v-model="form.city" 
                                                @focus="activeDropdown = 'city'"
                                                @input="form.clearErrors('city')"
                                                :disabled="!citiesList.length"
                                                class="mt-1 block w-full bg-slate-50 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                placeholder="Search City..."
                                                autocomplete="off"
                                            />
                                            <div v-if="activeDropdown === 'city' && citiesList.length" class="absolute w-full mt-1 bg-white border border-slate-200 rounded-md shadow-2xl max-h-60 overflow-y-auto ring-1 ring-black ring-opacity-5 custom-scrollbar">
                                                <ul class="py-1">
                                                    <li v-for="city in filteredCities" :key="city.code" @click="selectCity(city)" class="px-4 py-2 text-sm text-slate-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                        {{ city.name }}
                                                    </li>
                                                    <li v-if="!filteredCities.length" class="px-4 py-3 text-sm text-slate-500 italic text-center">No results found</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative md:col-span-2" :class="{ 'z-50': activeDropdown === 'barangay' }">
                                        <InputLabel>Barangay <span class="text-red-500">*</span></InputLabel>
                                        <div class="relative">
                                            <TextInput 
                                                v-model="form.barangay" 
                                                @focus="activeDropdown = 'barangay'"
                                                @input="form.clearErrors('barangay')"
                                                :disabled="!barangaysList.length"
                                                class="mt-1 block w-full bg-slate-50 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                                                placeholder="Search Barangay..."
                                                autocomplete="off"
                                            />
                                            <div v-if="activeDropdown === 'barangay' && barangaysList.length" class="absolute w-full mt-1 bg-white border border-slate-200 rounded-md shadow-2xl max-h-60 overflow-y-auto ring-1 ring-black ring-opacity-5 custom-scrollbar">
                                                <ul class="py-1">
                                                    <li v-for="brgy in filteredBarangays" :key="brgy.code" @click="selectBarangay(brgy)" class="px-4 py-2 text-sm text-slate-700 cursor-pointer hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                                        {{ brgy.name }}
                                                    </li>
                                                    <li v-if="!filteredBarangays.length" class="px-4 py-3 text-sm text-slate-500 italic text-center">No results found</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </Transition>

                    <Transition name="slide-fade" mode="out-in">
                        <div v-show="currentStep === 2" key="step2" class="space-y-6">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Proposed Tricycle Details</h2>
                                <p class="text-sm text-slate-500 mb-6">Register the physical unit you intend to franchise.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-slate-50/50 p-5 rounded-xl border border-slate-100">
                                <div class="md:col-span-2">
                                    <InputLabel>Preferred Zone / Route <span class="text-red-500">*</span></InputLabel>
                                    <select v-model="form.units[0].zone_id" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white text-slate-900">
                                        <option disabled value="">-- Select Zone --</option>
                                        <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel>Unit Brand / Make <span class="text-red-500">*</span></InputLabel>
                                    <select v-model="form.units[0].make_id" class="mt-1 block w-full border-slate-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white text-slate-900">
                                        <option disabled value="">-- Select Make --</option>
                                        <option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel>Model Year <span class="text-red-500">*</span></InputLabel>
                                    <TextInput type="number" v-model="form.units[0].model_year" class="mt-1 w-full bg-white text-slate-900" />
                                </div>
                                <div>
                                    <InputLabel>Motor / Engine Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.units[0].motor_number" class="mt-1 w-full bg-white text-slate-900" />
                                </div>
                                <div>
                                    <InputLabel>Chassis Number <span class="text-red-500">*</span></InputLabel>
                                    <TextInput v-model="form.units[0].chassis_number" class="mt-1 w-full bg-white text-slate-900" />
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800 mb-4">Unit Documentation</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-xl p-4 hover:bg-blue-50 hover:border-blue-400 transition-colors cursor-pointer group flex flex-col items-center justify-center text-center min-h-[120px]">
                                        <input type="file" @change="e => handleUnitPhoto(e, 'or_photo')" accept="image/*,application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                        <svg class="w-8 h-8 text-slate-400 group-hover:text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                        <span class="font-bold text-slate-700 text-sm">Official Receipt (OR) <span class="text-red-500">*</span></span>
                                        <span v-if="form.units[0].or_photo" class="text-xs text-green-600 font-bold mt-1 truncate max-w-full px-2">✓ {{ getFileName(form.units[0].or_photo) }}</span>
                                        <span v-else class="text-xs text-slate-400 mt-1">Tap or drag file here</span>
                                    </div>
                                    <div class="relative border-2 border-dashed border-slate-300 rounded-xl p-4 hover:bg-blue-50 hover:border-blue-400 transition-colors cursor-pointer group flex flex-col items-center justify-center text-center min-h-[120px]">
                                        <input type="file" @change="e => handleUnitPhoto(e, 'cr_photo')" accept="image/*,application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                        <svg class="w-8 h-8 text-slate-400 group-hover:text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                        <span class="font-bold text-slate-700 text-sm">Cert. of Registration (CR) <span class="text-red-500">*</span></span>
                                        <span v-if="form.units[0].cr_photo" class="text-xs text-green-600 font-bold mt-1 truncate max-w-full px-2">✓ {{ getFileName(form.units[0].cr_photo) }}</span>
                                        <span v-else class="text-xs text-slate-400 mt-1">Tap or drag file here</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-slate-100">
                                <h3 class="text-lg font-bold text-slate-800 mb-1">Unit Photos</h3>
                                <p class="text-xs text-slate-500 mb-4">Please provide clear images of your tricycle from all 4 angles.</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div v-for="(label, key) in {'unit_front_photo': 'Front View', 'unit_back_photo': 'Back View', 'unit_left_photo': 'Left Side', 'unit_right_photo': 'Right Side'}" :key="key" 
                                        class="relative border-2 border-dashed border-slate-300 rounded-xl p-3 hover:bg-blue-50 hover:border-blue-400 transition-colors cursor-pointer group flex flex-col items-center justify-center text-center aspect-square">
                                        <input type="file" @change="e => handleUnitPhoto(e, key)" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                        <svg class="w-6 h-6 text-slate-400 group-hover:text-blue-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span class="font-bold text-slate-700 text-xs">{{ label }} <span class="text-red-500">*</span></span>
                                        <span v-if="form.units[0][key]" class="text-[10px] text-green-600 font-bold mt-1 truncate max-w-full px-1">✓ Uploaded</span>
                                        <span v-else class="text-[10px] text-slate-400 mt-1">Tap to upload</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <Transition name="slide-fade" mode="out-in">
                        <div v-show="currentStep === 3" key="step3" class="space-y-6">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Required Documents</h2>
                                <p class="text-sm text-slate-500 mb-6">Upload the necessary clearances and forms to complete your application.</p>
                            </div>

                            <div v-for="(reqs, groupName) in requirements" :key="groupName" class="mb-8 last:mb-0">
                                <h3 class="font-bold text-sm tracking-wider uppercase text-blue-600 mb-4">{{ groupName }} Requirements</h3>
                                <div class="space-y-3">
                                    <div v-for="req in reqs" :key="req.id" class="relative flex flex-col sm:flex-row sm:items-center justify-between p-4 border border-slate-200 rounded-xl bg-white hover:border-blue-300 transition-colors shadow-sm group overflow-hidden">
                                        <input type="file" @change="e => handleRequirementFile(e, req.id)" accept="image/*,application/pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                                        
                                        <div class="flex items-start gap-3 mb-3 sm:mb-0 pr-4">
                                            <div class="mt-0.5 p-2 bg-slate-100 rounded-lg text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 text-sm">{{ req.name }} <span class="text-red-500">*</span></p>
                                                <p v-if="req.description" class="text-xs text-slate-500 mt-0.5">{{ req.description }}</p>
                                            </div>
                                        </div>

                                        <div class="shrink-0 flex items-center justify-end">
                                            <div v-if="form.requirement_files[req.id]" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-700 rounded-lg border border-green-200 text-xs font-bold w-full sm:w-auto justify-center z-20 relative">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                                <span class="truncate max-w-[150px]">{{ getFileName(form.requirement_files[req.id]) }}</span>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-slate-100 text-slate-600 rounded-lg border border-slate-200 text-xs font-bold w-full sm:w-auto justify-center group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-200 transition-colors">
                                                Browse File
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </Transition>

                    <div class="mt-10 pt-6 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="prevStep" class="inline-flex items-center px-4 py-2 text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors disabled:opacity-0" :disabled="currentStep === 1">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back
                        </button>
                        
                        <PrimaryButton v-if="currentStep < totalSteps" type="button" @click="nextStep" class="px-6 py-3 text-sm flex items-center gap-2">
                            Next Step
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </PrimaryButton>

                        <PrimaryButton v-if="currentStep === totalSteps" type="submit" :disabled="form.processing" class="px-8 py-3 text-sm shadow-md flex items-center gap-2 bg-green-600 hover:bg-green-700 focus:bg-green-700 active:bg-green-900">
                            <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ form.processing ? 'Submitting...' : 'Submit Application' }}
                        </PrimaryButton>
                    </div>

                </form>
            </div>
        </div>
    </div>
    
    <Footer />
</template>

<style scoped>
/* Smooth fade and slide transition for steps */
.slide-fade-enter-active {
  transition: all 0.4s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from {
  transform: translateX(20px);
  opacity: 0;
}
.slide-fade-leave-to {
  transform: translateX(-20px);
  opacity: 0;
}

/* Simple fade for alerts */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slimmer, less intrusive scrollbars */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
}
</style>