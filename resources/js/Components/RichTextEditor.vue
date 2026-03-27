<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue']);
const editor = ref(null);

const format = (command, value = null) => {
    document.execCommand(command, false, value);
    editor.value.focus();
    emit('update:modelValue', editor.value.innerHTML);
};

const handleInput = () => {
    emit('update:modelValue', editor.value.innerHTML);
};

// Handle font size change from the select dropdown
const changeFontSize = (event) => {
    format('fontSize', event.target.value);
    event.target.value = ''; // Reset dropdown to "Size" label after applying
};

// FIX: Override the Enter key to insert a clean line break exactly at the cursor
const handleKeydown = (e) => {
    if (e.key === 'Enter') {
        e.preventDefault(); // Stop the browser's buggy default paragraph wrapping
        document.execCommand('insertLineBreak');
        handleInput(); // Update the v-model
    }
};

// FIX: Force plain text when pasting to strip out invisible formatting that breaks the editor
const handlePaste = (e) => {
    e.preventDefault();
    const text = e.clipboardData.getData('text/plain');
    document.execCommand('insertText', false, text);
    handleInput(); // Update the v-model
};

watch(() => props.modelValue, (newVal) => {
    if (editor.value && newVal !== editor.value.innerHTML) {
        editor.value.innerHTML = newVal || '';
    }
});

onMounted(() => {
    if (editor.value) {
        editor.value.innerHTML = props.modelValue || '';
    }
});
</script>

<template>
    <div class="border border-gray-300 rounded-lg shadow-sm overflow-hidden bg-white mt-1">
        <div class="bg-gray-50 border-b border-gray-200 p-2 flex items-center gap-2">
            <button type="button" @click.prevent="format('bold')" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-gray-700 font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" title="Bold">
                B
            </button>
            <button type="button" @click.prevent="format('italic')" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-100 text-gray-700 italic font-serif focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" title="Italic">
                I
            </button>
            
            <div class="h-6 border-l border-gray-300 mx-1"></div>

            <select @change="changeFontSize" class="block w-28 pl-2 pr-8 py-1 text-sm border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded text-gray-700 cursor-pointer">
                <option value="" disabled selected>Size</option>
                <option value="1">Small</option>
                <option value="3">Normal</option>
                <option value="5">Large</option>
                <option value="7">Huge</option>
            </select>
        </div>
        
        <div 
            ref="editor" 
            contenteditable="true" 
            @input="handleInput" 
            @blur="handleInput"
            @keydown="handleKeydown"
            @paste="handlePaste"
            class="p-4 min-h-[250px] focus:outline-none prose max-w-none text-gray-700 outline-none"
        ></div>
    </div>
</template>

<style scoped>
/* Native execCommand uses <font size="X"> tags. 
   These styles ensure those legacy tags render beautifully across your app. 
*/
div[contenteditable] :deep(font[size="1"]) { font-size: 0.875rem; line-height: 1.25rem; } /* text-sm */
div[contenteditable] :deep(font[size="3"]) { font-size: 1rem; line-height: 1.5rem; }     /* text-base */
div[contenteditable] :deep(font[size="5"]) { font-size: 1.25rem; line-height: 1.75rem; font-weight: 600; } /* text-xl */
div[contenteditable] :deep(font[size="7"]) { font-size: 1.875rem; line-height: 2.25rem; font-weight: 700; } /* text-3xl */
</style>