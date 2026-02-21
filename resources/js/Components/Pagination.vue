<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links && links.length > 0" class="flex items-center gap-2">
        <template v-for="(link, key) in links" :key="key">
            
            <template v-if="key === 0 || key === links.length - 1">
                
                <div 
                    v-if="link.url === null"
                    class="px-3 py-1.5 border border-gray-200 rounded-md text-sm text-gray-400 bg-gray-50 cursor-not-allowed"
                    v-html="link.label"
                ></div>
                
                <Link 
                    v-else
                    :href="link.url"
                    class="px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium bg-white text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors shadow-sm"
                    v-html="link.label"
                />
                
            </template>
            
        </template>
    </div>
</template>