<script setup>
import { Head, router, usePage, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import NavBar from "@/Components/NavBar.vue";
import Footer from "@/Components/Footer.vue";

// --- State ---
const html5QrCode = ref(null);
const isCameraRunning = ref(false);
const isLoading = ref(true); 
const isProcessingFile = ref(false); // New state for file upload loading
const fileInput = ref(null); 

// Errors
const page = usePage();
const serverError = ref(page.props.errors.qr_code || null);
const clientError = ref(null);

// --- 1. Submission Logic ---
const submitCode = (code) => {
    // Ensure scanner is stopped
    stopScanner();
    
    router.post(route('verify.lookup'), { 
        qr_code: code 
    }, {
        onError: (errors) => {
            serverError.value = errors.qr_code;
            // Allow user to try again (restart camera)
            isLoading.value = false;
        },
        onFinish: () => {
            isProcessingFile.value = false;
        }
    });
};

// --- 2. Camera Logic ---
const startScanner = async () => {
    clientError.value = null;
    serverError.value = null;
    isLoading.value = true;

    // Ensure we don't have a zombie instance
    if (html5QrCode.value && isCameraRunning.value) {
        await stopScanner();
    }

    try {
        // Initialize if not already done
        if (!html5QrCode.value) {
            html5QrCode.value = new Html5Qrcode("reader");
        }

        const devices = await Html5Qrcode.getCameras();
        
        if (devices && devices.length) {
            await html5QrCode.value.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                (decodedText) => {
                    submitCode(decodedText);
                },
                (errorMessage) => {
                    // Ignore frame errors
                }
            );
            isCameraRunning.value = true;
        } else {
            clientError.value = "No camera found on this device.";
        }
    } catch (err) {
        clientError.value = "Camera permission denied or unavailable.";
    } finally {
        isLoading.value = false;
    }
};

const stopScanner = async () => {
    if (html5QrCode.value && isCameraRunning.value) {
        try {
            await html5QrCode.value.stop();
            isCameraRunning.value = false;
            // Clear the canvas to remove the frozen video frame
            html5QrCode.value.clear(); 
        } catch (err) {
            console.error("Failed to stop scanner", err);
        }
    }
};

// --- 3. File Upload Logic ---
const triggerFileUpload = () => {
    fileInput.value.click();
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // FIX: We MUST stop the camera before scanning a file
    // The library cannot handle video stream + file scan simultaneously on same instance
    if (isCameraRunning.value) {
        await stopScanner();
    }

    isProcessingFile.value = true;
    clientError.value = null;
    serverError.value = null;

    // Re-ensure instance exists (in case stopScanner cleared it too aggressively)
    if (!html5QrCode.value) {
        html5QrCode.value = new Html5Qrcode("reader");
    }

    try {
        // scanFile(file, showImage?) -> Set showImage to false to avoid canvas conflicts
        const result = await html5QrCode.value.scanFile(file, false);
        submitCode(result);
    } catch (err) {
        console.error(err);
        clientError.value = "Could not find a valid QR code in this image.";
        isProcessingFile.value = false;
        // Allow user to retry
    } finally {
        // Reset input so same file can be selected again if needed
        event.target.value = '';
    }
};

// --- Lifecycle ---
onMounted(() => {
    startScanner();
});

onUnmounted(() => {
    stopScanner();
});
</script>

<template>
    <Head title="Verify Franchise" />
    <NavBar />

    <div class="min-h-screen bg-slate-50 relative">
        <div class="pt-24 pb-12 max-w-md mx-auto px-4">
            
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-slate-900">Scan QR Code</h1>
                <p class="text-slate-500 text-sm">Point camera or upload screenshot</p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200 relative">
                
                <div v-if="serverError || clientError" class="absolute top-4 left-4 right-4 z-20 animate-fade-in-down">
                    <div class="bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-xl text-sm font-medium shadow-sm flex items-start gap-2">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>{{ serverError || clientError }}</span>
                    </div>
                </div>

                <div class="relative bg-slate-900 min-h-[350px] flex items-center justify-center overflow-hidden">
                    
                    <div v-if="isLoading" class="absolute z-10 text-white flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 mb-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium tracking-wide">Starting Camera...</span>
                    </div>

                    <div v-if="isProcessingFile" class="absolute z-30 inset-0 bg-slate-900/90 flex flex-col items-center justify-center text-white">
                        <svg class="animate-spin h-10 w-10 mb-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium tracking-wide">Analyzing Image...</span>
                    </div>

                    <div v-if="!isLoading && !isCameraRunning && !isProcessingFile" class="absolute z-10 text-center px-6">
                        <p class="text-white/60 mb-4 text-sm">Scanner is paused</p>
                        <button @click="startScanner" class="bg-blue-600 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-blue-500 transition shadow-lg">
                            Tap to Scan
                        </button>
                    </div>

                    <div id="reader" class="w-full h-full object-cover"></div>

                    <div v-if="isCameraRunning" class="absolute inset-0 pointer-events-none z-10 flex items-center justify-center">
                        <div class="w-64 h-64 border-2 border-white/50 rounded-xl relative">
                            <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-blue-500 rounded-tl-xl"></div>
                            <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-blue-500 rounded-tr-xl"></div>
                            <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-blue-500 rounded-bl-xl"></div>
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-blue-500 rounded-br-xl"></div>
                            <div class="absolute w-full h-0.5 bg-red-500/80 top-1/2 animate-pulse shadow-[0_0_8px_rgba(239,68,68,0.8)]"></div>
                        </div>
                    </div>
                </div>

                <div class="p-5 bg-white border-t border-slate-100">
                    <div class="flex flex-col items-center gap-3">
                        <button 
                            @click="triggerFileUpload" 
                            :disabled="isProcessingFile"
                            class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-slate-200 text-slate-600 font-bold hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-all group disabled:opacity-50"
                        >
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ isProcessingFile ? 'Uploading...' : 'Upload QR Image' }}
                        </button>
                        
                        <input 
                            type="file" 
                            ref="fileInput" 
                            class="hidden" 
                            accept="image/*"
                            @change="handleFileUpload"
                        >
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                <Link href="/" class="text-slate-400 hover:text-slate-600 text-sm font-medium transition-colors">
                    &larr; Back to Home
                </Link>
            </div>
        </div>
    </div>
    
    <Footer />
</template>

<style>
/* Ensure video fills the container nicely */
#reader video {
    object-fit: cover;
    width: 100% !important;
    height: 100% !important;
    border-radius: 0; 
}
.animate-fade-in-down {
    animation: fadeInDown 0.3s ease-out;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>