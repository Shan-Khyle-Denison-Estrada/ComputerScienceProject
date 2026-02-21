<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// --- PROPS ---
const props = defineProps({
    settings: {
        type: Object,
        default: () => ({
            fiscal_year_end: '', // Expected format: 'MM-DD'
            surcharge_rate: '',
            interest_rate: '',
            theme_color: '#3B82F6',
            lgu_name: '',
            lgu_logo_path: null,
            office_name: '',
            office_logo_path: null,
            hero_image_path: null,
            contact_number: '',
            address: '',
            about_us: '',
            mission: '',
            vision: '',
            ordinances: [], 
            faqs: [] 
        })
    }
});

// --- DATE LOGIC ---
// Split existing 'MM-DD' format if available, otherwise default to January 1st
const initialMonth = props.settings.fiscal_year_end ? props.settings.fiscal_year_end.split('-')[0] : '01';
const initialDay = props.settings.fiscal_year_end ? props.settings.fiscal_year_end.split('-')[1] : '01';

const selectedMonth = ref(initialMonth);
const selectedDay = ref(initialDay);

// --- STATE & FORM ---
const form = useForm({
    fiscal_year_end: `${selectedMonth.value}-${selectedDay.value}`, // Initialize with combined value
    surcharge_rate: props.settings.surcharge_rate,
    interest_rate: props.settings.interest_rate,
    theme_color: props.settings.theme_color,
    lgu_name: props.settings.lgu_name,
    lgu_logo: null,
    office_name: props.settings.office_name,
    office_logo: null,
    hero_image: null,
    contact_number: props.settings.contact_number,
    address: props.settings.address,
    about_us: props.settings.about_us,
    mission: props.settings.mission,
    vision: props.settings.vision,
    ordinances: props.settings.ordinances || [], 
    faqs: props.settings.faqs || [], 
    _method: 'PUT'
});

// Automatically update form's fiscal_year_end when month or day changes
watch([selectedMonth, selectedDay], ([newMonth, newDay]) => {
    form.fiscal_year_end = `${newMonth}-${newDay}`;
});

// Image Previews
const lguLogoPreview = ref(props.settings.lgu_logo_path ? `/storage/${props.settings.lgu_logo_path}` : null);
const officeLogoPreview = ref(props.settings.office_logo_path ? `/storage/${props.settings.office_logo_path}` : null);
const heroImagePreview = ref(props.settings.hero_image_path ? `/storage/${props.settings.hero_image_path}` : null);

const isSaved = ref(false);
const activeTab = ref('operations');

// --- MODAL STATES ---
const showAddOrdinanceModal = ref(false);
const showAddFaqModal = ref(false);

// --- FAQs LOGIC ---
const newFaq = ref({ question: '', answer: '' });

const closeFaqModal = () => {
    showAddFaqModal.value = false;
    newFaq.value = { question: '', answer: '' };
};

const addFaq = () => {
    if (form.faqs.length >= 5) return; 
    if (newFaq.value.question.trim() && newFaq.value.answer.trim()) {
        form.faqs.push({ ...newFaq.value });
        closeFaqModal();
    }
};

const removeFaq = (index) => {
    form.faqs.splice(index, 1);
};

// --- ORDINANCES LOGIC ---
const newOrdinance = ref({ title: '', subtitle: '', summary: '', file: null });

const closeOrdinanceModal = () => {
    showAddOrdinanceModal.value = false;
    newOrdinance.value = { title: '', subtitle: '', summary: '', file: null };
    
    // Reset file input element visually
    const fileInput = document.getElementById('ordinance_file_input');
    if (fileInput) fileInput.value = '';
};

const handleOrdinanceFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        newOrdinance.value.file = file;
    }
};

const addOrdinance = () => {
    if (newOrdinance.value.title.trim() && newOrdinance.value.summary.trim() && newOrdinance.value.file) {
        form.ordinances.push({ ...newOrdinance.value });
        closeOrdinanceModal();
    }
};

const removeOrdinance = (index) => {
    form.ordinances.splice(index, 1);
};

// Helper function to safely evaluate file instances
const getOrdinanceFileName = (file) => {
    if (file instanceof File) {
        return file.name;
    }
    return 'Attached PDF';
};

// --- GLOBAL ACTIONS ---
const handleLguLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.lgu_logo = file;
        lguLogoPreview.value = URL.createObjectURL(file);
    }
};

const handleOfficeLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.office_logo = file;
        officeLogoPreview.value = URL.createObjectURL(file);
    }
};

const handleHeroImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.hero_image = file;
        heroImagePreview.value = URL.createObjectURL(file);
    }
};

const submitSettings = () => {
    form.post(route('admin.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isSaved.value = true;
            setTimeout(() => isSaved.value = false, 3000);
        }
    });
};
</script>

<template>
    <Head title="System Settings" />

    <AuthenticatedLayout>
        <div class="flex flex-col h-[calc(100vh-6rem)] w-full">
            
            <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-4 flex-shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage global system configurations, visual themes, and public content.</p>
                </div>
                <div class="flex items-center gap-3">
                    <PrimaryButton @click="submitSettings" :disabled="form.processing" class="flex items-center gap-2 shadow-sm">
                        <svg v-if="!form.processing" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Saving...' : 'Save Settings' }}
                    </PrimaryButton>
                </div>
            </div>

            <div v-if="isSaved" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm flex-shrink-0 transition-opacity">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">Settings have been successfully updated.</span>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-0">
                
                <div class="border-b border-gray-200 bg-gray-50 flex-shrink-0 px-2 sm:px-6">
                    <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                        <button 
                            @click="activeTab = 'operations'"
                            :class="[activeTab === 'operations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors']"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Operations
                        </button>

                        <button 
                            @click="activeTab = 'appearance'"
                            :class="[activeTab === 'appearance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors']"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            Branding & Appearance
                        </button>

                        <button 
                            @click="activeTab = 'content'"
                            :class="[activeTab === 'content' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors']"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            Public Content
                        </button>
                    </nav>
                </div>

                <div class="flex-1 overflow-y-auto p-6 sm:p-8 custom-scrollbar">
                    
                    <div v-show="activeTab === 'operations'" class="w-full">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Operational Settings</h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex flex-col">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-md font-bold text-gray-800">Renewal Schedule</h4>
                                </div>
                                <div class="flex-1">
                                    <InputLabel>Annual Franchise Due Date<span class="text-red-600"> *</span></InputLabel>
                                    <p class="text-xs text-gray-500 mb-3">The system-wide deadline (Month & Day) for tricycle franchise renewals.</p>
                                    
                                    <div class="flex items-center gap-2 mt-1">
                                        <select v-model="selectedMonth" class="block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                            <option value="01">January</option>
                                            <option value="02">February</option>
                                            <option value="03">March</option>
                                            <option value="04">April</option>
                                            <option value="05">May</option>
                                            <option value="06">June</option>
                                            <option value="07">July</option>
                                            <option value="08">August</option>
                                            <option value="09">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                        
                                        <select v-model="selectedDay" class="block w-24 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                            <option v-for="day in 31" :key="day" :value="String(day).padStart(2, '0')">
                                                {{ day }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="form.errors.fiscal_year_end" class="text-red-500 text-xs mt-1">{{ form.errors.fiscal_year_end }}</div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex flex-col">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-green-100 text-green-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-md font-bold text-gray-800">Financial Rates & Penalties</h4>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 flex-1">
                                    <div>
                                        <InputLabel>Surcharge Rate (%)<span class="text-red-600"> *</span></InputLabel>
                                        <p class="text-xs text-gray-500 mb-2">Penalty applied for late franchise renewals.</p>
                                        <div class="relative">
                                            <TextInput type="number" step="0.01" v-model="form.surcharge_rate" class="mt-1 block w-full pr-8" placeholder="25" required />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 pointer-events-none mt-1">%</span>
                                        </div>
                                        <div v-if="form.errors.surcharge_rate" class="text-red-500 text-xs mt-1">{{ form.errors.surcharge_rate }}</div>
                                    </div>

                                    <div>
                                        <InputLabel>Interest Rate (%)<span class="text-red-600"> *</span></InputLabel>
                                        <p class="text-xs text-gray-500 mb-2">Monthly interest applied to outstanding balances.</p>
                                        <div class="relative">
                                            <TextInput type="number" step="0.01" v-model="form.interest_rate" class="mt-1 block w-full pr-8" placeholder="2" required />
                                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 pointer-events-none mt-1">%</span>
                                        </div>
                                        <div v-if="form.errors.interest_rate" class="text-red-500 text-xs mt-1">{{ form.errors.interest_rate }}</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div v-show="activeTab === 'appearance'" class="w-full">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Branding & Visuals</h3>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            <div class="flex flex-col gap-6">
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex-1 flex flex-col">
                                    <InputLabel>Local Government Unit (LGU) Name</InputLabel>
                                    <TextInput v-model="form.lgu_name" placeholder="e.g. Dapitan City" class="mt-1 w-full text-sm mb-4" />
                                    <div v-if="form.errors.lgu_name" class="text-red-500 text-xs mt-1 mb-4">{{ form.errors.lgu_name }}</div>
                                    
                                    <InputLabel>LGU Logo</InputLabel>
                                    <p class="text-xs text-gray-500 mb-3">Upload the official seal or logo of the municipality.</p>
                                    <div class="flex items-center gap-4 mt-auto">
                                        <div class="relative w-20 h-20 rounded-full border-2 border-white bg-white flex items-center justify-center overflow-hidden shadow-sm shrink-0">
                                            <img v-if="lguLogoPreview" :src="lguLogoPreview" class="absolute inset-0 w-full h-full object-contain p-1.5" />
                                            <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <label class="cursor-pointer inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                Upload LGU Logo
                                                <input type="file" class="hidden" accept="image/*" @change="handleLguLogoChange" />
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.lgu_logo" class="text-red-500 text-xs mt-2">{{ form.errors.lgu_logo }}</div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex-1 flex flex-col">
                                    <InputLabel>Franchising Office Name</InputLabel>
                                    <TextInput v-model="form.office_name" placeholder="e.g. Tricycle Franchising Board" class="mt-1 w-full text-sm mb-4" />
                                    <div v-if="form.errors.office_name" class="text-red-500 text-xs mt-1 mb-4">{{ form.errors.office_name }}</div>
                                    
                                    <InputLabel>Office Logo</InputLabel>
                                    <p class="text-xs text-gray-500 mb-3">Upload the specific logo for the franchising office.</p>
                                    <div class="flex items-center gap-4 mt-auto">
                                        <div class="relative w-20 h-20 rounded-full border-2 border-white bg-white flex items-center justify-center overflow-hidden shadow-sm shrink-0">
                                            <img v-if="officeLogoPreview" :src="officeLogoPreview" class="absolute inset-0 w-full h-full object-contain p-1.5" />
                                            <svg v-else class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <label class="cursor-pointer inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                Upload Office Logo
                                                <input type="file" class="hidden" accept="image/*" @change="handleOfficeLogoChange" />
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.office_logo" class="text-red-500 text-xs mt-2">{{ form.errors.office_logo }}</div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-6">
                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex-1 flex flex-col">
                                    <InputLabel>Hero Landing Image</InputLabel>
                                    <p class="text-xs text-gray-500 mb-3">Upload a background image for the public homepage.</p>
                                    
                                    <div class="relative w-full aspect-square max-h-64 rounded-lg border-2 border-white bg-white flex items-center justify-center overflow-hidden shadow-sm shrink-0 mb-4 mt-auto">
                                        <img v-if="heroImagePreview" :src="heroImagePreview" class="absolute inset-0 w-full h-full object-cover" />
                                        <svg v-else class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    
                                    <label class="cursor-pointer inline-flex items-center justify-center px-3 py-2 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors w-full">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                        Upload Hero Image
                                        <input type="file" class="hidden" accept="image/*" @change="handleHeroImageChange" />
                                    </label>
                                    <div v-if="form.errors.hero_image" class="text-red-500 text-xs mt-2">{{ form.errors.hero_image }}</div>
                                </div>

                                <div class="bg-gray-50 p-6 rounded-lg border border-gray-100 flex-none">
                                    <InputLabel>System Theme Color</InputLabel>
                                    <p class="text-xs text-gray-500 mb-3">Accent color used across the public & admin interface.</p>
                                    <div class="flex items-center gap-4">
                                        <div class="relative w-12 h-12 rounded-lg border border-gray-200 overflow-hidden cursor-pointer shadow-sm shrink-0">
                                            <input type="color" v-model="form.theme_color" class="absolute -inset-2 w-16 h-16 cursor-pointer" />
                                        </div>
                                        <TextInput type="text" v-model="form.theme_color" class="w-full max-w-[150px] font-mono text-sm uppercase" placeholder="#HEXCODE" />
                                    </div>
                                    <div v-if="form.errors.theme_color" class="text-red-500 text-xs mt-1">{{ form.errors.theme_color }}</div>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <div v-show="activeTab === 'content'" class="w-full">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Public Content & Policies</h3>
                        
                        <div class="space-y-8">
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <div>
                                    <InputLabel>Mission Statement</InputLabel>
                                    <textarea v-model="form.mission" rows="5" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 resize-y" placeholder="Enter the organization's mission..."></textarea>
                                    <div v-if="form.errors.mission" class="text-red-500 text-xs mt-1">{{ form.errors.mission }}</div>
                                </div>

                                <div>
                                    <InputLabel>Vision Statement</InputLabel>
                                    <textarea v-model="form.vision" rows="5" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 resize-y" placeholder="Enter the organization's vision..."></textarea>
                                    <div v-if="form.errors.vision" class="text-red-500 text-xs mt-1">{{ form.errors.vision }}</div>
                                </div>

                                <div>
                                    <InputLabel>About Us</InputLabel>
                                    <textarea v-model="form.about_us" rows="5" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 resize-y" placeholder="Brief history, context, and purpose..."></textarea>
                                    <div v-if="form.errors.about_us" class="text-red-500 text-xs mt-1">{{ form.errors.about_us }}</div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-100">
                                <h4 class="text-md font-bold text-gray-800 mb-4">Contact Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel>Contact Number</InputLabel>
                                        <TextInput v-model="form.contact_number" placeholder="e.g. +63 912 345 6789 or (065) 123-4567" class="mt-1 w-full text-sm" />
                                        <div v-if="form.errors.contact_number" class="text-red-500 text-xs mt-1">{{ form.errors.contact_number }}</div>
                                    </div>
                                    <div>
                                        <InputLabel>Office Address</InputLabel>
                                        <TextInput v-model="form.address" placeholder="e.g. City Hall, Dapitan City, Zamboanga del Norte" class="mt-1 w-full text-sm" />
                                        <div v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-100">
                                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
                                    <div>
                                        <h4 class="text-md font-bold text-gray-800">City Ordinances & Rules</h4>
                                        <p class="text-sm text-gray-500">Manage the list of ordinances displayed publicly. Ensure a PDF is attached.</p>
                                    </div>
                                    <PrimaryButton type="button" @click="showAddOrdinanceModal = true" class="shrink-0">
                                        + Add Ordinance
                                    </PrimaryButton>
                                </div>
                                
                                <div v-if="form.ordinances.length > 0" class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Ordinance</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/4">Summary</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="(ord, index) in form.ordinances" :key="index" class="hover:bg-gray-50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="text-sm font-bold text-gray-900">{{ ord.title }}</div>
                                                    <div v-if="ord.subtitle" class="text-sm text-gray-500 mt-1">{{ ord.subtitle }}</div>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600">
                                                    {{ ord.summary }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-xs font-medium">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ getOrdinanceFileName(ord.file) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button type="button" @click="removeOrdinance(index)" class="text-red-500 hover:text-red-700">Delete</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center py-8 text-sm text-gray-400 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                                    No ordinances added yet.
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-100">
                                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
                                    <div>
                                        <h4 class="text-md font-bold text-gray-800">Frequently Asked Questions (FAQs)</h4>
                                        <p class="text-sm text-gray-500">Provide answers to common queries. Limited to 5 items.</p>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-md" :class="form.faqs.length >= 5 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                                            {{ form.faqs.length }} / 5
                                        </span>
                                        <PrimaryButton type="button" @click="showAddFaqModal = true" :disabled="form.faqs.length >= 5">
                                            + Add FAQ
                                        </PrimaryButton>
                                    </div>
                                </div>

                                <div v-if="form.faqs.length > 0" class="space-y-3">
                                    <div v-for="(faq, index) in form.faqs" :key="index" class="p-4 border border-gray-200 rounded-lg flex justify-between gap-4 items-start bg-white shadow-sm hover:shadow-md transition-shadow">
                                        <div>
                                            <p class="font-bold text-sm text-gray-800">Q: {{ faq.question }}</p>
                                            <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">A: {{ faq.answer }}</p>
                                        </div>
                                        <button type="button" @click="removeFaq(index)" title="Remove FAQ" class="text-red-400 hover:text-red-600 p-1.5 rounded-md hover:bg-red-50 transition-colors shrink-0">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-sm text-gray-400 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                                    No FAQs added yet.
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <Modal :show="showAddOrdinanceModal" @close="closeOrdinanceModal" max-width="2xl">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Add New Ordinance</h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel>Title <span class="text-red-500">*</span></InputLabel>
                        <TextInput v-model="newOrdinance.title" placeholder="e.g. Ordinance No. 123" class="mt-1 w-full text-sm" />
                    </div>
                    <div>
                        <InputLabel>Subtitle</InputLabel>
                        <TextInput v-model="newOrdinance.subtitle" placeholder="e.g. Tricycle Franchising Code" class="mt-1 w-full text-sm" />
                    </div>
                </div>
                
                <div>
                    <InputLabel>Brief Summary <span class="text-red-500">*</span></InputLabel>
                    <textarea v-model="newOrdinance.summary" rows="3" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 resize-y" placeholder="Provide a short description..."></textarea>
                </div>
                
                <div>
                    <InputLabel>Full Ordinance Document (PDF) <span class="text-red-500">*</span></InputLabel>
                    <input id="ordinance_file_input" type="file" accept="application/pdf" @change="handleOrdinanceFileChange" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-colors border border-gray-200 rounded-md p-1" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <SecondaryButton @click="closeOrdinanceModal">Cancel</SecondaryButton>
                <PrimaryButton type="button" @click="addOrdinance" :disabled="!newOrdinance.title || !newOrdinance.summary || !newOrdinance.file">
                    Add Ordinance
                </PrimaryButton>
            </div>
        </div>
    </Modal>

    <Modal :show="showAddFaqModal" @close="closeFaqModal" max-width="lg">
        <div class="p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Add New FAQ</h2>
            
            <div class="space-y-4">
                <div>
                    <InputLabel>Question <span class="text-red-500">*</span></InputLabel>
                    <TextInput v-model="newFaq.question" placeholder="e.g. How do I renew my franchise?" class="mt-1 w-full text-sm" />
                </div>
                <div>
                    <InputLabel>Answer <span class="text-red-500">*</span></InputLabel>
                    <textarea v-model="newFaq.answer" rows="4" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3 resize-y" placeholder="Provide the complete answer..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100">
                <SecondaryButton @click="closeFaqModal">Cancel</SecondaryButton>
                <PrimaryButton type="button" @click="addFaq" :disabled="!newFaq.question || !newFaq.answer">
                    Add FAQ
                </PrimaryButton>
            </div>
        </div>
    </Modal>

</template>

<style scoped>
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