<script setup>
import { Head } from '@inertiajs/vue3';
import NavBar from "../Components/NavBar.vue";
import Footer from "../Components/Footer.vue";
</script>

<template>
    <Head title="About Us" />
    <NavBar />

    <div class="relative bg-slate-900 isolate overflow-hidden pt-24 pb-20 sm:pt-32 sm:pb-24">
        <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"></div>

        <div class="mx-auto max-w-7xl px-6 lg:px-8 text-center relative z-10">
            <div class="flex justify-center mb-8">
                <img 
                    src="/tab_seal.png" 
                    alt="TAB Seal" 
                    class="h-32 w-auto drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                />
            </div>
            
            <h1 class="text-4xl font-black tracking-tight text-white sm:text-6xl mb-6">
                Tricycle Adjudication Board
            </h1>
            
            <div class="flex justify-center mb-8">
                <div class="h-1 w-24 bg-blue-500 rounded-full"></div>
            </div>

            <p class="mt-6 text-lg leading-8 text-slate-300 max-w-3xl mx-auto text-justify sm:text-center">
                The Tricycle Adjudication Board (TAB) of Zamboanga City, Philippines, serves as the regulatory body responsible for managing and resolving disputes related to tricycle operations in the city. It ensures the proper implementation of laws, ordinances, and regulations governing tricycles, safeguarding the welfare of operators, drivers, and commuters.
            </p>
        </div>
    </div>

    <div class="bg-slate-50 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
                
                <div class="flex flex-col rounded-3xl bg-white shadow-xl ring-1 ring-gray-900/10 transition-all hover:shadow-2xl hover:-translate-y-1">
                    <div class="p-10">
                        <div class="flex items-center gap-x-4 border-b border-gray-100 pb-6 mb-6">
                            <div class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Our Mission</h2>
                        </div>
                        <p class="flex-auto text-base leading-7 text-gray-600 text-justify">
                            To provide fair, efficient, and transparent adjudication services for tricycle operators and drivers while fostering a harmonious relationship between all stakeholders. The TAB is dedicated to upholding the rights and responsibilities of the tricycle sector and ensuring compliance with local transportation laws to improve public service and safety.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col rounded-3xl bg-white shadow-xl ring-1 ring-gray-900/10 transition-all hover:shadow-2xl hover:-translate-y-1">
                    <div class="p-10">
                        <div class="flex items-center gap-x-4 border-b border-gray-100 pb-6 mb-6">
                            <div class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-red-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Our Vision</h2>
                        </div>
                        <p class="flex-auto text-base leading-7 text-gray-600 text-justify">
                            To be a leading model of excellence in public transportation governance, fostering an equitable, sustainable, and commuter-friendly tricycle transportation system in Zamboanga City, aligned with the city's development goals and the needs of its people.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <Footer />
</template>