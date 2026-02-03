<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen flex bg-white">
        
        <div class="hidden lg:flex w-1/2 bg-gray-900 relative overflow-hidden">
            <div 
                class="absolute inset-0 bg-cover bg-center opacity-40" 
                style="background-image: url('/non_index_bg.png');"
            ></div>
            
            <div class="relative z-10 w-full p-12 flex flex-col justify-between text-white">
                <div class="text-3xl font-bold tracking-wider">
                    TRICY<span class="text-blue-500">SYS</span>
                </div>
                
                <div class="mb-10">
                    <h2 class="text-4xl font-bold leading-tight mb-4">
                        City Transport <br>Management System
                    </h2>
                    <p class="text-gray-300 text-lg max-w-md">
                        Secure access for Franchise Owners and City Administrators. Manage units, violations, and renewals in one place.
                    </p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative">
            
            <Link 
                href="/" 
                class="absolute top-8 left-8 flex items-center text-gray-500 hover:text-gray-900 transition-colors"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Home
            </Link>

            <div class="w-full max-w-md space-y-8 mt-12 lg:mt-0">
                
                <div class="text-center lg:text-left">
                    <div class="lg:hidden mb-8 text-3xl font-bold tracking-wider text-gray-900 inline-block">
                        TRI<span class="text-blue-600">APP</span>
                    </div>
                    
                    <h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Please enter your credentials to access your dashboard.
                    </p>
                </div>

                <div v-if="status" class="bg-green-50 border border-green-200 text-green-600 p-4 rounded-lg text-sm">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    
                    <div class="space-y-5">
                        <div>
                            <InputLabel for="email" value="Email Address" class="text-gray-700 font-semibold" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-3"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="admin@tricycle.com"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="Password" class="text-gray-700 font-semibold" />
                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 py-3"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-4 bg-gray-900 hover:bg-black transition-colors rounded-lg text-base font-bold tracking-wide"
                        :class="{ 'opacity-70': form.processing }"
                        :disabled="form.processing"
                    >
                        Sign In
                    </PrimaryButton>
                </form>

                <p class="mt-4 text-center text-sm text-gray-500">
                    Having trouble logging in? <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Contact IT Support</a>
                </p>
            </div>
        </div>
    </div>
</template>