<script setup>
import { computed } from 'vue';

const props = defineProps({
    franchise: Object,
    currentOwner: Object,
    currentUnit: Object,
    systemSetting: Object, 
    tabApprover: Object, 
    spApprover: Object,  
    template: Object, 
});

const ownerName = computed(() => {
    if (!props.currentOwner || !props.currentOwner.user) return 'UNASSIGNED';
    return `${props.currentOwner.user.first_name} ${props.currentOwner.user.last_name}`;
});

const ownerAddress = computed(() => {
    if (!props.currentOwner || !props.currentOwner.user) return 'N/A';
    const user = props.currentOwner.user;
    const addressParts = [user.street_address, user.barangay, user.city].filter(Boolean);
    return addressParts.length > 0 ? addressParts.join(', ') : 'N/A';
});

// The Engine: Parse HTML, inject real data, completely preserve Editor CSS & Layouts
const parsedContent = computed(() => {
    if (!props.template || !props.template.content) {
        return '<div style="text-align: center; padding: 2rem; color: #6b7280; font-family: sans-serif;">No certificate template configured. Please set it up in the settings.</div>';
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(props.template.content, 'text/html');

    // 1. Process Text Variables
    const textVars = doc.querySelectorAll('span[data-text-variable]');
    textVars.forEach(el => {
        const varName = el.getAttribute('data-text-variable');
        let replacementText = '';

        if (varName === 'franchise_number') replacementText = props.franchise?.franchise_number || 'N/A';
        if (varName === 'zone_color') replacementText = props.franchise?.zone?.color || 'N/A';
        if (varName === 'operator_name') replacementText = ownerName.value;
        if (varName === 'operator_address') replacementText = ownerAddress.value;
        if (varName === 'unit_make') replacementText = props.currentUnit?.make?.name || 'N/A';
        if (varName === 'model_year') replacementText = props.currentUnit?.model_year || 'N/A';
        if (varName === 'motor_no') replacementText = props.currentUnit?.motor_number || 'N/A';
        if (varName === 'chassis_no') replacementText = props.currentUnit?.chassis_number || 'N/A';
        if (varName === 'plate_no') replacementText = props.currentUnit?.plate_number || 'N/A';
        if (varName === 'tab_approver_name') replacementText = props.tabApprover ? `${props.tabApprover.first_name} ${props.tabApprover.last_name}` : 'N/A';
        if (varName === 'sp_approver_name') replacementText = props.spApprover ? `${props.spApprover.first_name} ${props.spApprover.last_name}` : 'N/A';

        // Clean up editor-only attributes
        // NOTE: We do NOT touch el.style or el.className anymore. The database now holds the pure CSS inline styles!
        el.removeAttribute('data-text-variable');
        
        // Inject the real text
        el.textContent = replacementText;
    });

    // 2. Process Dynamic Images & Signatures
    const imageVars = doc.querySelectorAll('span[data-type="customImage"]');
    imageVars.forEach(span => {
        const varName = span.getAttribute('data-variable');
        const img = span.querySelector('img');
        
        if (img && varName) {
            const originalSrc = img.getAttribute('src');

            if (varName === 'lgu-logo') img.src = props.systemSetting?.lgu_logo_path ? `/storage/${props.systemSetting.lgu_logo_path}` : originalSrc;
            else if (varName === 'qr-code') img.src = props.franchise?.qr_code ? `/storage/qrcodes/${props.franchise.qr_code}` : originalSrc;
            else if (varName === 'tab-signature') img.src = props.tabApprover?.signature_photo ? `/storage/${props.tabApprover.signature_photo}` : originalSrc;
            else if (varName === 'sp-signature') img.src = props.spApprover?.signature_photo ? `/storage/${props.spApprover.signature_photo}` : originalSrc;
            
            const currentSrc = img.getAttribute('src');
            if (!currentSrc || currentSrc.includes('undefined') || currentSrc.includes('null') || currentSrc === '') {
                span.style.display = 'none';
            }
        }
    });

    // 3. CRITICAL FIX: Inject physical space into empty lines! 
    // This stops elements (like the signature line) from shooting upwards during print parsing.
    doc.querySelectorAll('p').forEach(p => {
        if (!p.textContent.trim() && p.children.length === 0) {
            p.innerHTML = '&nbsp;'; 
        }
    });

    return doc.body.innerHTML;
});

const paperDimensions = {
    'A4': { width: 794, height: 1123 },
    'Letter': { width: 816, height: 1056 },
    'Legal': { width: 816, height: 1344 }
};

const paperStyle = computed(() => {
    if (!props.template) return {};
    
    const size = paperDimensions[props.template.paper_size] || paperDimensions['A4'];
    const margins = props.template.margins || { top: 1, bottom: 1, left: 1, right: 1 };
    
    const pTop = margins.top * 96;
    const pRight = margins.right * 96;
    const pBottom = margins.bottom * 96;
    const pLeft = margins.left * 96;

    return {
        width: `${size.width}px`,
        minHeight: `${size.height}px`,
        padding: `${pTop}px ${pRight}px ${pBottom}px ${pLeft}px`,
        margin: '0 auto',
        backgroundColor: 'white',
        position: 'relative',
        boxSizing: 'border-box',
    };
});
</script>

<template>
    <div class="print-container">
        <div class="certificate-content text-black editor-paper" :style="paperStyle">
            <div class="ProseMirror" v-html="parsedContent"></div>
        </div>
    </div>
</template>

<style>
.print-container {
    width: 100%;
    background-color: white;
    display: flex;
    justify-content: center;
}

.certificate-content {
    font-family: inherit;
    line-height: 1.5;
    color: black;
}

.certificate-content, .certificate-content * {
    box-sizing: border-box;
}

/* === EXACT CLONE OF TIPTAP EDITOR CSS === */
.editor-paper .ProseMirror { outline: none; min-height: 100%; position: relative; }
.editor-paper .ProseMirror::after { content: ""; display: table; clear: both; }

.editor-paper .ProseMirror h1 { font-weight: bold; margin: 0; padding: 0; line-height: inherit;}
.editor-paper .ProseMirror h2 { font-weight: bold; margin: 0; padding: 0; line-height: inherit;}
.editor-paper .ProseMirror ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1em; }
.editor-paper .ProseMirror ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 1em; }

/* Strictly match editor tightness */
.editor-paper .ProseMirror p { margin: 0; padding: 0; line-height: inherit; text-align: inherit;} 

/* Stable Table Styles */
.editor-paper .ProseMirror table {
    border-collapse: collapse; table-layout: fixed; width: 100% !important; max-width: 100% !important; margin: 1em auto; overflow: hidden;
}
.editor-paper .ProseMirror table td, .editor-paper .ProseMirror table th {
    min-width: 1em; border: 1px solid #000; padding: 6px 8px; vertical-align: top; box-sizing: border-box; position: relative; word-wrap: break-word; overflow-wrap: break-word;
}
.editor-paper .ProseMirror table th { font-weight: bold; text-align: left; background-color: transparent; }

/* Image Stabilization */
.editor-paper .ProseMirror img { max-width: 100%; display: inline-block; }
.editor-paper .ProseMirror span[data-wrap="behind"] img, 
.editor-paper .ProseMirror span[data-wrap="in-front"] img { max-width: none !important; }

/* Print Specific Overrides */
@media print {
    @page { margin: 0; size: auto; }
    
    .print-container { padding: 0; background-color: transparent; align-items: flex-start !important; }
    .certificate-content { box-shadow: none !important; border: none !important; page-break-after: avoid; page-break-inside: avoid; }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>