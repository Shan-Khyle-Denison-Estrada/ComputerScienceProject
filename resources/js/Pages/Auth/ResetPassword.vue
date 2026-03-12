<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="min-h-screen flex bg-white">
        
        <div class="hidden lg:flex w-1/2 bg-gray-900 relative overflow-hidden">
            <div class="relative z-10 w-full p-12 flex flex-col justify-between text-white">
                <div class="text-3xl font-bold tracking-wider">
                    TRICY<span class="text-blue-500">SYS</span>
                </div>
                
                <div class="mb-10">
                    <h2 class="text-4xl font-bold leading-tight mb-4">
                        Secure Your Account
                    </h2>
                    <p class="text-gray-300 text-lg max-w-md">
                        Please choose a strong password to protect your City Transport Management System dashboard.
                    </p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative">
            
            <Link 
                :href="route('login')" 
                class="absolute top-8 left-8 flex items-center text-gray-500 hover:text-gray-900 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Login
            </Link>

            <div class="w-full max-w-md space-y-8 mt-12 lg:mt-0">
                
                <div class="text-center lg:text-left">
                    <div class="lg:hidden mb-8 text-3xl font-bold tracking-wider text-gray-900 inline-block">
                        TRICY<span class="text-blue-600">SYS</span>
                    </div>
                    
                    <h2 class="text-3xl font-bold text-gray-900">Create New Password</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Enter your new password below.
                    </p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    <div class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Email Address" class="text-gray-700 font-semibold" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-50 text-gray-500 py-3"
                                v-model="form.email"
                                required
                                readonly
                                autocomplete="username"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="New Password" class="text-gray-700 font-semibold" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-3"
                                v-model="form.password"
                                required
                                autofocus
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirm New Password" class="text-gray-700 font-semibold" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-3"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-4 bg-gray-900 hover:bg-black transition-colors rounded-lg text-base font-bold tracking-wide mt-4"
                        :class="{ 'opacity-70': form.processing }"
                        :disabled="form.processing"
                    >
                        Reset Password
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </div>
</template>