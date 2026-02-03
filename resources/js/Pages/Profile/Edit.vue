<script setup>
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;

// --- PROFILE INFO FORM ---
const form = useForm({
    first_name: user.first_name,
    middle_name: user.middle_name,
    last_name: user.last_name,
    email: user.email,
    contact_number: user.contact_number,
    street_address: user.street_address,
    barangay: user.barangay,
    city: user.city,
    photo: null,
    _method: 'PATCH',
});

// --- PASSWORD UPDATE FORM ---
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// --- PHOTO LOGIC ---
const photoPreview = ref(user.user_photo ? `/storage/${user.user_photo}` : null);
const photoInput = ref(null);

const triggerPhotoUpload = () => photoInput.value.click();
const handlePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

const updateProfile = () => {
    form.post(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: Show success notification
        },
    });
};

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <Head title="My Profile" />

    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white p-4 sm:p-8 shadow sm:rounded-lg">
                <section class="max-w-4xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
                        <p class="mt-1 text-sm text-gray-600">Update your account's profile information and email address.</p>
                    </header>

                    <form @submit.prevent="updateProfile" class="mt-6 space-y-6">
                        
                        <div class="flex flex-col md:flex-row gap-6">
                            <div class="shrink-0">
                                <div class="relative group cursor-pointer w-24 h-24" @click="triggerPhotoUpload">
                                    <img v-if="photoPreview" :src="photoPreview" class="h-24 w-24 object-cover rounded-full border border-gray-300 shadow-sm" />
                                    <div v-else class="h-24 w-24 bg-blue-100 text-blue-600 flex items-center justify-center rounded-full font-bold text-3xl border border-blue-200">
                                        {{ form.first_name ? form.first_name.charAt(0) : 'U' }}
                                    </div>
                                    <div class="absolute inset-0 bg-black bg-opacity-20 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </div>
                                </div>
                                <input type="file" ref="photoInput" class="hidden" @change="handlePhotoChange" />
                                <button type="button" @click="triggerPhotoUpload" class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-500 block text-center w-full">Change Photo</button>
                            </div>

                            <div class="flex-1 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="col-span-1">
                                        <InputLabel for="first_name" value="First Name" />
                                        <TextInput id="first_name" type="text" class="mt-1 block w-full" v-model="form.first_name" required autocomplete="given-name" />
                                        <InputError class="mt-2" :message="form.errors.first_name" />
                                    </div>
                                    <div class="col-span-1">
                                        <InputLabel for="middle_name" value="Middle Name" />
                                        <TextInput id="middle_name" type="text" class="mt-1 block w-full" v-model="form.middle_name" autocomplete="additional-name" />
                                        <InputError class="mt-2" :message="form.errors.middle_name" />
                                    </div>
                                    <div class="col-span-1">
                                        <InputLabel for="last_name" value="Last Name" />
                                        <TextInput id="last_name" type="text" class="mt-1 block w-full" v-model="form.last_name" required autocomplete="family-name" />
                                        <InputError class="mt-2" :message="form.errors.last_name" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="email" value="Email" />
                                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required autocomplete="username" />
                                        <InputError class="mt-2" :message="form.errors.email" />
                                    </div>
                                    <div>
                                        <InputLabel for="contact_number" value="Contact Number" />
                                        <TextInput id="contact_number" type="text" class="mt-1 block w-full" v-model="form.contact_number" />
                                        <InputError class="mt-2" :message="form.errors.contact_number" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-200 my-6" />

                        <div>
                            <h3 class="text-md font-medium text-gray-900 mb-4">Address Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="col-span-1">
                                    <InputLabel for="street_address" value="Street Address" />
                                    <TextInput id="street_address" type="text" class="mt-1 block w-full" v-model="form.street_address" autocomplete="street-address" />
                                    <InputError class="mt-2" :message="form.errors.street_address" />
                                </div>
                                <div class="col-span-1">
                                    <InputLabel for="barangay" value="Barangay" />
                                    <TextInput id="barangay" type="text" class="mt-1 block w-full" v-model="form.barangay" />
                                    <InputError class="mt-2" :message="form.errors.barangay" />
                                </div>
                                <div class="col-span-1">
                                    <InputLabel for="city" value="City" />
                                    <TextInput id="city" type="text" class="mt-1 block w-full" v-model="form.city" autocomplete="address-level2" />
                                    <InputError class="mt-2" :message="form.errors.city" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                            </Transition>
                        </div>
                    </form>
                </section>
            </div>

            <div class="bg-white p-4 sm:p-8 shadow sm:rounded-lg">
                <section class="max-w-xl">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Update Password</h2>
                        <p class="mt-1 text-sm text-gray-600">Ensure your account is using a long, random password to stay secure.</p>
                    </header>

                    <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
                        <div>
                            <InputLabel for="current_password" value="Current Password" />
                            <TextInput id="current_password" ref="currentPasswordInput" type="password" class="mt-1 block w-full" v-model="passwordForm.current_password" autocomplete="current-password" />
                            <InputError :message="passwordForm.errors.current_password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password" value="New Password" />
                            <TextInput id="password" ref="passwordInput" type="password" class="mt-1 block w-full" v-model="passwordForm.password" autocomplete="new-password" />
                            <InputError :message="passwordForm.errors.password" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirm Password" />
                            <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="passwordForm.password_confirmation" autocomplete="new-password" />
                            <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="passwordForm.processing">Save Password</PrimaryButton>
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <p v-if="passwordForm.recentlySuccessful" class="text-sm text-gray-600">Saved.</p>
                            </Transition>
                        </div>
                    </form>
                </section>
            </div>

        </div>
    </AuthenticatedLayout>
</template>