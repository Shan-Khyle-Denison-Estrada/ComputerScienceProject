<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    application: Object,
    requirements: Array,
    zones: Array,
    unitMakes: Array
});

const page = usePage();
const settings = computed(() => page.props.settings || {});
const currentThemeColor = computed(() => settings.value.theme_color || '#2563eb');

const getEmptyUnit = () => ({
    make_id: '', zone_id: '', model_year: '', plate_number: '', cr_number: '', motor_number: '', chassis_number: '',
    unit_front_photo: null, unit_back_photo: null, unit_left_photo: null, unit_right_photo: null,
    cr_photo: null, or_photo: null
});

const form = useForm({
    documents: {},
    units: props.application.application_type === 'New Franchise' ? [getEmptyUnit()] : [],
    _method: 'POST', 
});

const filePreviews = ref({});
const unitPhotoPreviews = ref([{ front: null, back: null, left: null, right: null, cr: null, or: null }]);
const expandedUnitIndex = ref(0);

const handleFileChange = (event, reqId) => {
    const file = event.target.files[0];
    if (file) { form.documents[reqId] = file; filePreviews.value[reqId] = file.name; form.clearErrors(`documents.${reqId}`); }
};

const handleUnitFileChange = (event, index, field, previewField) => {
    const file = event.target.files[0];
    if (file) { form.units[index][field] = file; unitPhotoPreviews.value[index][previewField] = URL.createObjectURL(file); form.clearErrors(`units.${index}.${field}`); }
};

const addUnit = () => {
    form.units.push(getEmptyUnit());
    unitPhotoPreviews.value.push({ front: null, back: null, left: null, right: null, cr: null, or: null });
    expandedUnitIndex.value = form.units.length - 1;
};

const removeUnit = (index) => {
    if (form.units.length > 1) { form.units.splice(index, 1); unitPhotoPreviews.value.splice(index, 1); }
};

const toggleUnit = (index) => { expandedUnitIndex.value = expandedUnitIndex.value === index ? -1 : index; };

const areAllRequirementsMet = computed(() => {
    const docsMet = props.requirements.length === 0 || props.requirements.every(req => form.documents[req.id]);
    const unitsMet = props.application.application_type !== 'New Franchise' || form.units.every(u => 
        u.make_id && u.zone_id && u.model_year && u.plate_number && u.motor_number && u.chassis_number && u.cr_number && 
        u.unit_front_photo && u.cr_photo && u.or_photo
    );
    return docsMet && unitsMet;
});

const submit = () => {
    const urlParams = new URLSearchParams(window.location.search);
    form.post(route('application.complete.submit', { application: props.application.id, expires: urlParams.get('expires'), signature: urlParams.get('signature') }), { preserveScroll: true });
};
</script>

<template>
    <Head title="Complete Application - TRICYSYS" />

    <div class="min-h-screen bg-slate-50 font-sans selection:bg-black selection:text-white pb-20">

        <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Complete Your Application</h1>
                <p class="mt-4 text-lg text-slate-500 max-w-2xl mx-auto">Provide the required details below to finalize your <span class="font-semibold text-slate-700">{{ application.application_type }}</span> application.</p>
            </div>

            <div class="bg-white shadow-2xl shadow-slate-200/50 rounded-3xl overflow-hidden border border-slate-100">
                <div class="theme-bg px-8 py-6 flex flex-col md:flex-row md:items-center justify-between relative overflow-hidden">
                    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-white via-transparent to-transparent"></div>
                    <div class="relative z-10"><p class="text-white/80 text-xs font-bold uppercase tracking-widest mb-1">Reference Number</p><p class="text-white font-mono font-bold text-2xl tracking-tight">{{ application.reference_number }}</p></div>
                    <div class="mt-5 md:mt-0 md:text-right relative z-10"><p class="text-white/80 text-xs font-bold uppercase tracking-widest mb-1">Applicant</p><p class="text-white font-bold text-xl">{{ application.first_name }} {{ application.last_name }}</p></div>
                </div>

                <div class="p-8 sm:p-10">
                    <form @submit.prevent="submit">
                        <div v-if="form.errors.error" class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-red-700 text-sm font-medium">{{ form.errors.error }}</span>
                        </div>

                        <div v-if="application.application_type === 'New Franchise'" class="mb-12">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                                <h3 class="text-xl font-bold text-slate-900">Proposed Tricycle Units</h3>
                                <button type="button" @click="addUnit" class="text-sm theme-bg opacity-90 px-4 py-2 rounded-lg text-white font-medium hover:opacity-100 transition-opacity">+ Add Unit</button>
                            </div>

                            <div class="space-y-4">
                                <div v-for="(unit, index) in form.units" :key="index" class="border border-slate-200 rounded-xl bg-white overflow-hidden shadow-sm">
                                    <div @click="toggleUnit(index)" class="flex items-center justify-between p-4 cursor-pointer hover:bg-slate-50 transition-colors bg-slate-50">
                                        <div class="flex items-center gap-4">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm bg-blue-100 text-blue-700">{{ index + 1 }}</div>
                                            <div><h3 class="font-bold text-slate-700">{{ unit.make_id ? unitMakes.find(m => m.id === unit.make_id)?.name : 'New Unit Details' }}</h3><p class="text-xs text-slate-500">{{ unit.plate_number || 'Provide plate info' }}</p></div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button v-if="form.units.length > 1" type="button" @click.stop="removeUnit(index)" class="text-red-500 text-sm font-medium hover:underline">Remove</button>
                                            <svg class="w-5 h-5 text-slate-400 transform transition-transform" :class="{'rotate-180': expandedUnitIndex === index}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>

                                    <div v-show="expandedUnitIndex === index" class="p-6 border-t border-slate-100">
                                        <div class="mb-6 p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                                            <div class="flex items-start gap-1"><InputLabel value="Target Route Zone" /><span class="text-red-600 font-bold">*</span></div>
                                            <select v-model="unit.zone_id" @change="form.clearErrors(`units.${index}.zone_id`)" class="mt-1 block w-full border-blue-200 rounded-lg focus:border-blue-500 focus:ring-blue-500">
                                                <option value="" disabled>Select Zone</option><option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.description }}</option>
                                            </select>
                                            <InputError :message="form.errors[`units.${index}.zone_id`]" class="mt-2" />
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                                            <div><div class="flex items-start gap-1"><InputLabel value="Make" /><span class="text-red-600 font-bold">*</span></div><select v-model="unit.make_id" class="mt-1 block w-full border-slate-300 rounded-lg"><option value="" disabled>Select</option><option v-for="make in unitMakes" :key="make.id" :value="make.id">{{ make.name }}</option></select><InputError :message="form.errors[`units.${index}.make_id`]" class="mt-2" /></div>
                                            <div><div class="flex items-start gap-1"><InputLabel value="Model Year" /><span class="text-red-600 font-bold">*</span></div><TextInput type="number" v-model="unit.model_year" placeholder="e.g. 2024" class="mt-1 block w-full" /><InputError :message="form.errors[`units.${index}.model_year`]" class="mt-2" /></div>
                                            <div><div class="flex items-start gap-1"><InputLabel value="Plate No." /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="unit.plate_number" class="mt-1 block w-full uppercase" /><InputError :message="form.errors[`units.${index}.plate_number`]" class="mt-2" /></div>
                                            <div><div class="flex items-start gap-1"><InputLabel value="Motor No." /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="unit.motor_number" class="mt-1 block w-full uppercase" /><InputError :message="form.errors[`units.${index}.motor_number`]" class="mt-2" /></div>
                                            <div><div class="flex items-start gap-1"><InputLabel value="Chassis No." /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="unit.chassis_number" class="mt-1 block w-full uppercase" /><InputError :message="form.errors[`units.${index}.chassis_number`]" class="mt-2" /></div>
                                            <div><div class="flex items-start gap-1"><InputLabel value="CR Number" /><span class="text-red-600 font-bold">*</span></div><TextInput v-model="unit.cr_number" class="mt-1 block w-full uppercase" /><InputError :message="form.errors[`units.${index}.cr_number`]" class="mt-2" /></div>
                                        </div>

                                        <div class="pt-6 border-t border-slate-200">
                                            <h4 class="font-bold text-slate-800 mb-4">Unit Photos & Official Documents</h4>
                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                                <div v-for="side in ['front', 'back', 'left', 'right']" :key="side" class="border rounded-xl p-3 text-center bg-slate-50 relative overflow-hidden group">
                                                    <div class="flex justify-center gap-1 mb-2"><p class="text-xs text-slate-500 uppercase font-bold">{{ side }}</p><span v-if="side === 'front'" class="text-red-600 font-bold text-xs">*</span></div>
                                                    <div v-if="unitPhotoPreviews[index][side]" class="mb-3 relative group rounded overflow-hidden">
                                                        <img :src="unitPhotoPreviews[index][side]" class="w-full h-24 object-cover" />
                                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><span class="text-white text-xs font-medium">Change</span></div>
                                                    </div>
                                                    <label class="cursor-pointer inline-flex items-center justify-center w-full px-3 py-2 border border-slate-300 shadow-sm text-xs font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-100 transition-colors">
                                                        <span>{{ unitPhotoPreviews[index][side] ? 'Replace Photo' : 'Upload Photo' }}</span>
                                                        <input type="file" @change="e => handleUnitFileChange(e, index, `unit_${side}_photo`, side)" class="hidden" accept="image/*">
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div v-for="(docInfo, docKey) in { cr_photo: {label: 'OR Document', key: 'cr'}, or_photo: {label: 'CR Document', key: 'or'} }" :key="docKey" class="border border-dashed border-slate-300 rounded-xl p-5 bg-white text-center hover:border-blue-400 transition-colors">
                                                    <div class="flex justify-center gap-1 mb-2"><span class="text-sm font-semibold text-slate-700">{{ docInfo.label }}</span><span class="text-red-600 font-bold">*</span></div>
                                                    <p v-if="unitPhotoPreviews[index][docInfo.key]" class="text-xs text-emerald-600 font-bold mb-3 truncate px-2 bg-emerald-50 py-1 rounded-md">File Uploaded</p>
                                                    <label class="cursor-pointer inline-flex items-center justify-center px-4 py-2 border shadow-sm text-xs font-semibold rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors w-full border-blue-200">
                                                        <span>{{ unitPhotoPreviews[index][docInfo.key] ? 'Change File' : 'Upload PDF/Image' }}</span>
                                                        <input type="file" @change="e => handleUnitFileChange(e, index, docKey, docInfo.key)" class="hidden" accept=".pdf,image/*">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
                            <h3 class="text-xl font-bold text-slate-900">General Requirements</h3>
                            <span class="text-sm font-medium text-slate-500">{{ Object.keys(filePreviews).length }} of {{ requirements.length }} Uploaded</span>
                        </div>

                        <div v-if="requirements.length === 0" class="text-center py-16 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50">
                            <div class="mx-auto w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-4"><svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div>
                            <p class="text-slate-600 font-medium">No evaluation documents are needed for this application type.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="req in requirements" :key="req.id" class="relative p-5 rounded-xl transition-all duration-300 border-2 overflow-hidden group" :class="filePreviews[req.id] ? 'border-emerald-500 bg-emerald-50/30' : 'border-slate-200 bg-white'">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 relative z-10">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <div v-if="filePreviews[req.id]" class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg></div>
                                            <div v-else class="w-6 h-6 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0"><span class="text-xs font-bold">{{ requirements.indexOf(req) + 1 }}</span></div>
                                            <p class="text-base font-bold text-slate-900">{{ req.name }} <span class="text-red-500">*</span></p>
                                        </div>
                                        <div v-if="filePreviews[req.id]" class="mt-2 ml-8 flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-emerald-100 shadow-sm w-fit max-w-full"><svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg><span class="text-sm text-emerald-700 font-medium truncate">{{ filePreviews[req.id] }}</span></div>
                                        <p v-else class="mt-1 ml-8 text-sm text-slate-500">Please upload a clear PDF or Image file.</p>
                                    </div>
                                    <div class="flex-shrink-0 sm:ml-4 pl-8 sm:pl-0">
                                        <label class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border shadow-sm text-sm font-semibold rounded-xl transition-all" :class="filePreviews[req.id] ? 'border-emerald-200 text-emerald-700 bg-emerald-100 hover:bg-emerald-200' : 'border-slate-300 text-slate-700 bg-white hover:bg-slate-50'">
                                            <span>{{ filePreviews[req.id] ? 'Replace File' : 'Browse File' }}</span><input type="file" @change="e => handleFileChange(e, req.id)" class="hidden" accept=".pdf,image/*">
                                        </label>
                                    </div>
                                </div>
                                <InputError :message="form.errors[`documents.${req.id}`]" class="mt-3 ml-8" />
                            </div>
                        </div>

                        <div class="mt-12 pt-8 border-t border-slate-100">
                            <button type="submit" :disabled="form.processing || !areAllRequirementsMet" :class="{'opacity-50 grayscale cursor-not-allowed': form.processing || !areAllRequirementsMet}" class="w-full flex justify-center items-center py-4 px-4 rounded-xl shadow-lg text-lg font-bold text-white theme-btn transition-all duration-300">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ form.processing ? 'Submitting Application...' : 'Submit Application' }}
                            </button>
                            <p v-if="!areAllRequirementsMet" class="text-center text-sm text-red-500 mt-4 font-medium flex items-center justify-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>Upload all required documents to proceed.</p>
                        </div>
                    </form>
                </div>
            </div>
            <p class="text-center text-sm text-slate-400 mt-8 font-medium">&copy; {{ new Date().getFullYear() }} TRICYSYS Franchise Management System. All rights reserved.</p>
        </div>
    </div>
</template>

<style scoped>
.theme-bg { background-color: v-bind(currentThemeColor); }
.theme-text { color: v-bind(currentThemeColor); }
.theme-btn { background-color: v-bind(currentThemeColor); }
.theme-btn:hover:not(:disabled) { filter: brightness(0.90); box-shadow: 0 10px 20px -5px v-bind('currentThemeColor + "60"'); transform: translateY(-1px); }
</style>