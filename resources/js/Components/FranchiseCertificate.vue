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

    // CRITICAL FIX: Strip raw HTML source-code line breaks. 
    // Since the print view uses `white-space: pre-wrap`, any hidden database formatting 
    // newlines will forcefully push inline variables down to the next line.
    const cleanHTML = props.template.content.replace(/\r?\n|\r/g, '');

    const parser = new DOMParser();
    const doc = parser.parseFromString(cleanHTML, 'text/html');

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

        el.removeAttribute('data-text-variable');
        el.classList.add('certificate-variable'); 
        
        el.innerHTML = '';
        el.textContent = replacementText;

        const wrapType = el.getAttribute('data-wrap') || 'inline';
        const savedAlign = (el.getAttribute('data-text-align') || el.style.textAlign || 'left').trim();
        let savedWidth = (el.getAttribute('data-width') || el.style.width || '').trim();

        if (savedWidth && /^\d+(\.\d+)?$/.test(savedWidth)) {
            savedWidth = `${savedWidth}px`;
        }

        if (wrapType !== 'inline') {
            el.style.setProperty('display', 'block', 'important');
            el.style.setProperty('position', 'absolute', 'important');
            
            if (savedWidth && savedWidth !== 'auto') {
                el.style.setProperty('width', savedWidth, 'important');
            } else {
                const charCount = varName.replace(/_/g, ' ').length; 
                const estimatedPillWidth = (charCount * 7.5) + 16; 
                
                el.style.setProperty('width', `${estimatedPillWidth}px`, 'important');
            }

            el.style.setProperty('text-align', savedAlign, 'important');
        } else {
            // THE BASELINE FIX: 
            // If the layout is "In Line with Text", forcefully strip the 'inline-block' 
            // behavior and snap the baseline perfectly to the surrounding normal text!
            el.style.setProperty('display', 'inline', 'important');
            el.style.setProperty('vertical-align', 'baseline', 'important');
            el.style.setProperty('position', 'static', 'important');
        }

        // Restore underlines and bolding
        let parentNode = el.parentElement;
        while (parentNode && parentNode.tagName !== 'P' && parentNode.tagName !== 'DIV') {
            const tag = parentNode.tagName.toUpperCase();
            if (tag === 'U') el.style.setProperty('text-decoration', 'underline', 'important');
            if (tag === 'STRONG' || tag === 'B') el.style.setProperty('font-weight', 'bold', 'important');
            if (tag === 'EM' || tag === 'I') el.style.setProperty('font-style', 'italic', 'important');
            parentNode = parentNode.parentElement;
        }
    });

    // 2. Process Dynamic Images & Signatures
    const imageVars = doc.querySelectorAll('span[data-type="customImage"]');
    imageVars.forEach(span => {
        const varName = span.getAttribute('data-variable');
        const img = span.querySelector('img');
        
        if (img && varName) {
            // THE REAL FIX: TipTap saves the exact scaled width inside the `data-width` attribute 
            // of the wrapper, NOT the img tag. We must pull it from there!
            let editorWidth = span.getAttribute('data-width') || span.style.width || '150px';
            let editorHeight = span.getAttribute('data-height') || span.style.height || 'auto';

            // Safely add 'px' if it's missing
            if (/^\d+(\.\d+)?$/.test(editorWidth)) editorWidth = `${editorWidth}px`;
            if (/^\d+(\.\d+)?$/.test(editorHeight)) editorHeight = `${editorHeight}px`;

            const originalSrc = img.getAttribute('src');

            if (varName === 'lgu-logo') img.src = props.systemSetting?.lgu_logo_path ? `/storage/${props.systemSetting.lgu_logo_path}` : originalSrc;
            else if (varName === 'qr-code') img.src = props.franchise?.qr_code ? `/storage/qrcodes/${props.franchise.qr_code}` : originalSrc;
            else if (varName === 'tab-signature') img.src = props.tabApprover?.signature_photo ? `/storage/${props.tabApprover.signature_photo}` : originalSrc;
            else if (varName === 'sp-signature') img.src = props.spApprover?.signature_photo ? `/storage/${props.spApprover.signature_photo}` : originalSrc;
            
            const currentSrc = img.getAttribute('src');
            if (!currentSrc || currentSrc.includes('undefined') || currentSrc.includes('null') || currentSrc === '') {
                span.style.display = 'none';
            } else {
                const wrapType = span.getAttribute('data-wrap') || 'inline';

                if (wrapType !== 'inline') {
                    span.style.setProperty('display', 'block', 'important');
                    span.style.setProperty('position', 'absolute', 'important');
                    
                    // Force the safe wrapper dimensions
                    span.style.setProperty('width', editorWidth, 'important');
                    if (editorHeight !== 'auto') span.style.setProperty('height', editorHeight, 'important');
                    
                    span.style.setProperty('overflow', 'visible', 'important');
                    
                    // Prevent ghost spacing
                    span.style.setProperty('line-height', '0', 'important');
                    span.style.setProperty('font-size', '0', 'important');
                    span.style.setProperty('margin', '0', 'important');
                    span.style.setProperty('padding', '0', 'important');

                    if (wrapType === 'behind') {
                        span.style.setProperty('z-index', '-1', 'important');
                    } else if (wrapType === 'in-front') {
                        span.style.setProperty('z-index', '10', 'important');
                    }

                    // THE PRINT ILLUSION FIX: 
                    // Because the print engine shrinks the text body, we forcefully pull 
                    // the signatures UP by using a negative top margin. 
                    // This ONLY affects the print/view mode, so the editor stays perfect!
                    if (varName === 'tab-signature' || varName === 'sp-signature') {
                        // TWEAK THIS NUMBER: If they are still too low, change -30px to -40px or -50px.
                        // If they go too high, change it to -15px or -20px!
                        span.style.setProperty('margin-top', '-125px', 'important'); 
                    }

                    // THE PERFECT CONSTRAINT: Act like a picture frame!
                    // By setting height to 100% and using 'object-fit: contain', 
                    // the image is physically trapped inside your editor's bounding box 
                    // and will never stretch or spill out vertically!
                    img.style.setProperty('display', 'block', 'important');
                    img.style.setProperty('width', '100%', 'important');
                    img.style.setProperty('height', '100%', 'important');
                    img.style.setProperty('object-fit', 'contain', 'important');
                    img.style.setProperty('max-width', 'none', 'important');
                    img.style.setProperty('position', 'static', 'important');
                } else {
                    // Apply the same strict constraints to In-Line images like the QR Code
                    img.style.setProperty('width', editorWidth, 'important');
                    if (editorHeight !== 'auto') img.style.setProperty('height', editorHeight, 'important');
                    img.style.setProperty('object-fit', 'contain', 'important');
                    
                    span.style.setProperty('display', 'inline-block', 'important');
                    span.style.setProperty('position', 'static', 'important');
                    span.style.setProperty('vertical-align', 'middle', 'important'); 
                }
            }
        }
    });

    // 3. NATIVE TIPTAP EMPTY LINE FIX & LINE SPACING LOCK
    doc.querySelectorAll('p, h1, h2, h3, h4, h5, h6').forEach(el => {
        if (el.tagName === 'P' && !el.textContent.trim() && (el.children.length === 0 || (el.children.length === 1 && el.children[0].tagName === 'BR'))) {
            // Uses TipTap's native break to prevent vertical baseline inflation
            el.innerHTML = '<br class="ProseMirror-trailingBreak">'; 
        }

        // THE SPACING FIX: We forcefully apply !important to the exact inline line-height 
        // that TipTap saved. This guarantees the Print Engine physically cannot ignore your Double Spacing!
        if (el.style.lineHeight) {
            el.style.setProperty('line-height', el.style.lineHeight, 'important');
        }
    });

    // 4. Force List Styles while PRESERVING TipTap's injected font sizes and families
    // CRITICAL FIX: Using += appends the structural rules instead of erasing the editor's custom styles
    doc.querySelectorAll('ol').forEach(ol => {
        ol.style.cssText += ' display: block !important; list-style-type: decimal !important; padding-left: 2.5rem !important; margin: 1rem 0 !important;';
    });
    doc.querySelectorAll('ul').forEach(ul => {
        ul.style.cssText += ' display: block !important; list-style-type: disc !important; padding-left: 2.5rem !important; margin: 1rem 0 !important;';
    });
    doc.querySelectorAll('li').forEach(li => {
        li.style.cssText += ' display: list-item !important; margin-bottom: 0.25rem !important;';
    });

    return doc.body.innerHTML;
});

// CRITICAL FIX: Exact physical dimensions mapping (bypasses browser pixel scaling)
const paperDimensions = {
    'A4': { width: '210mm', minHeight: '297mm' },
    'Letter': { width: '8.5in', minHeight: '11in' },
    'Legal': { width: '8.5in', minHeight: '14in' }
};

const paperStyle = computed(() => {
    if (!props.template) return {};
    
    const size = paperDimensions[props.template.paper_size] || paperDimensions['A4'];
    const margins = props.template.margins || { top: 1, bottom: 1, left: 1, right: 1 };
    
    return {
        width: size.width,
        minHeight: size.minHeight,
        padding: `${margins.top || 0}in ${margins.right || 0}in ${margins.bottom || 0}in ${margins.left || 0}in`,
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

/* Kills the Browser Headers and Footers (Date, URL, Page Number) permanently */
@page {
    margin: 0 !important;
    size: auto;
}

.certificate-content {
    /* Lock the exact screen typography so the printer cannot shrink it */
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    
    font-size: 16px !important; 
    line-height: 1.15 !important; 
    
    -webkit-text-size-adjust: 100% !important;
    text-size-adjust: 100% !important;
    color: black;

    /* THE FIX: Create a strict stacking context. 
       This permanently prevents "Behind Text" (z-index: -1) images 
       from slipping underneath the white paper background! */
    isolation: isolate !important;
    z-index: 0 !important;
}

.certificate-content, .certificate-content * {
    box-sizing: border-box;
}

/* === EXACT CLONE OF TIPTAP EDITOR CSS === */
.editor-paper .ProseMirror { 
    outline: none; 
    min-height: 100%; 
    position: relative; 
    white-space: normal !important; 
    word-wrap: break-word;
    line-height: inherit !important; 
}

/* Safety lock: Guarantees any span or text formatting inside a paragraph STAYS inline */
.editor-paper .ProseMirror p span:not(.certificate-variable),
.editor-paper .ProseMirror p strong,
.editor-paper .ProseMirror p em,
.editor-paper .ProseMirror p u {
    display: inline !important;
    white-space: normal !important;
}

/* FIX: Allow variable containers to hold their physical width and text alignment */
.editor-paper .ProseMirror span.certificate-variable {
    display: inline-block !important;
}
.editor-paper .ProseMirror::after { content: ""; display: table; clear: both; }

.editor-paper .ProseMirror h1, 
.editor-paper .ProseMirror h2 { font-weight: bold; margin: 0; padding: 0; }
.editor-paper .ProseMirror ul { list-style-type: disc; padding-left: 1.5em; margin-bottom: 1em; }
.editor-paper .ProseMirror ol { list-style-type: decimal; padding-left: 1.5em; margin-bottom: 1em; }

/* Let paragraph spacing be completely determined by the TipTap Engine's inline styles */
.editor-paper .ProseMirror p { 
    margin: 0 !important; 
    padding: 0 !important; 
    text-align: inherit;
}

/* === NUCLEAR TABLE OVERRIDE === */
.editor-paper .ProseMirror table {
    display: table !important; 
    border-collapse: collapse !important; 
    table-layout: fixed !important; 
    width: 100% !important; 
    max-width: 100% !important; 
    margin: 1em auto !important; 
    overflow: hidden !important;
}
.editor-paper .ProseMirror table colgroup { display: table-column-group !important; }
.editor-paper .ProseMirror table col { display: table-column !important; }
.editor-paper .ProseMirror table thead { display: table-header-group !important; }
.editor-paper .ProseMirror table tbody { display: table-row-group !important; }
.editor-paper .ProseMirror table tr { 
    display: table-row !important; 
    width: auto !important; 
    flex-direction: row !important; 
}
.editor-paper .ProseMirror table td, .editor-paper .ProseMirror table th {
    display: table-cell !important; 
    min-width: 1em !important; 
    border: 1px solid #000 !important; 
    padding: 6px 8px !important; 
    vertical-align: top !important; 
    box-sizing: border-box !important; 
    position: relative !important; 
    word-wrap: break-word !important; 
    overflow-wrap: break-word !important;
    white-space: normal !important;
}
.editor-paper .ProseMirror table th { 
    font-weight: bold !important; 
    text-align: left !important; 
    background-color: transparent !important; 
}

/* Image Stabilization */
.editor-paper .ProseMirror img { max-width: 100%; display: inline-block; }
.editor-paper .ProseMirror span[data-wrap="behind"] img, 
.editor-paper .ProseMirror span[data-wrap="in-front"] img { max-width: none !important; }

/* Print Specific Overrides */
@media print {
    @page { margin: 0 !important; size: auto; }
    
    body * { visibility: hidden; }
    .print-container, .print-container * { visibility: visible; }

    .editor-paper {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important; 
        min-height: 100% !important;
        transform: none !important; 
        box-shadow: none !important;
        margin: 0 !important;
        border: none !important;
    }
    
    /* * THE SILVER BULLET: 
     * This absolutely stops Chrome/Edge from modifying your text rendering and spacing during print.
     */
    .editor-paper, .editor-paper * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        text-size-adjust: none !important;
        -webkit-text-size-adjust: none !important;
    }
}

/* Strictly enforce inline rendering for variables so they never break to a new line */
.editor-paper .ProseMirror .inline-variable {
    display: inline !important;
    vertical-align: baseline !important;
}
</style>