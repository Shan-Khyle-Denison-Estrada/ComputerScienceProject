<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.force-change.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Update Password" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
            <div>
                <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900 tracking-tight">
                    Update Your Password
                </h2>
                <div class="mt-4 bg-blue-50 border border-blue-200 p-4 rounded-md">
                    <p class="text-sm text-blue-800 text-center">
                        For your security, you must change your system-generated password before accessing your account.
                    </p>
                </div>
            </div>
            
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="space-y-4">
                    <div>
                        <InputLabel for="password" value="New Password" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autofocus
                            autocomplete="new-password"
                            placeholder="Enter a strong password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirm New Password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Repeat password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>

                <div>
                    <PrimaryButton 
                        class="w-full flex justify-center py-3 text-base" 
                        :class="{ 'opacity-75': form.processing }" 
                        :disabled="form.processing"
                    >
                        Update Password & Continue
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </div>
</template>