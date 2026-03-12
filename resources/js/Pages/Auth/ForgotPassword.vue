<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen flex bg-white">
        
        <div class="hidden lg:flex w-1/2 bg-gray-900 relative overflow-hidden">
            <div class="relative z-10 w-full p-12 flex flex-col justify-between text-white">
                <div class="text-3xl font-bold tracking-wider">
                    TRICY<span class="text-blue-500">SYS</span>
                </div>
                
                <div class="mb-10">
                    <h2 class="text-4xl font-bold leading-tight mb-4">
                        Account Recovery
                    </h2>
                    <p class="text-gray-300 text-lg max-w-md">
                        Get back into your account safely and securely.
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
                    
                    <h2 class="text-3xl font-bold text-gray-900">Forgot Password?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
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
                    </div>

                    <PrimaryButton
                        class="w-full flex justify-center py-4 bg-gray-900 hover:bg-black transition-colors rounded-lg text-base font-bold tracking-wide mt-4"
                        :class="{ 'opacity-70': form.processing }"
                        :disabled="form.processing"
                    >
                        Email Password Reset Link
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </div>
</template>