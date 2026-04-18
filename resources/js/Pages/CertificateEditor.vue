<script setup>
import { onBeforeUnmount, computed, ref, watch } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Components/AuthenticatedLayout.vue';

// TipTap Core & Extensions
import { useEditor, EditorContent, VueNodeViewRenderer } from '@tiptap/vue-3';
import { Node, Extension, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Paragraph from '@tiptap/extension-paragraph';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';
import { Dropcursor } from '@tiptap/extension-dropcursor';
import TextAlign from '@tiptap/extension-text-align';
import HorizontalRule from '@tiptap/extension-horizontal-rule';
import Underline from '@tiptap/extension-underline';
import { Plugin } from '@tiptap/pm/state';

// Typography Extensions
import { TextStyle } from '@tiptap/extension-text-style';
import { FontFamily } from '@tiptap/extension-font-family';

// Import our custom node views
import ResizableImage from '@/Components/ResizableImage.vue';
import DraggableTextVariable from '@/Components/DraggableTextVariable.vue';

const props = defineProps({
    template: { type: Object, required: true },
    systemSetting: { type: Object, default: () => ({}) }
});

const form = useForm({
    content: props.template.content || '<p style="text-align: center;">Start formatting your franchise certificate here...</p>',
    paper_size: props.template.paper_size || 'A4',
    margins: props.template.margins || { top: 1, bottom: 1, left: 1, right: 1 },
});

// --- FLASH MESSAGE AUTO-HIDE ---
const page = usePage();
const showFlashMessage = ref(false);
let flashTimeout = null;

watch(() => page.props.flash?.success, (newVal) => {
    if (newVal) {
        showFlashMessage.value = true;
        if (flashTimeout) clearTimeout(flashTimeout);
        
        flashTimeout = setTimeout(() => {
            showFlashMessage.value = false;
            // Clear the flash prop so it triggers again on subsequent saves without page reload
            page.props.flash.success = null; 
        }, 3000);
    }
}, { immediate: true });

// --- ZOOM & MARGINS ---
const zoomLevel = ref(1.0); 
const zoomIn = () => zoomLevel.value = Math.min(zoomLevel.value + 0.1, 2.0);
const zoomOut = () => zoomLevel.value = Math.max(zoomLevel.value - 0.1, 0.4);

// 1. Use exact physical dimensions for robust printing
const paperDimensions = {
    'A4': { width: '210mm', minHeight: '297mm' },
    'Letter': { width: '8.5in', minHeight: '11in' },
    'Legal': { width: '8.5in', minHeight: '14in' }
};

const paperStyle = computed(() => {
    const size = paperDimensions[form.paper_size] || paperDimensions['A4'];
    return {
        width: size.width, 
        minHeight: size.minHeight,
        // 2. Use inches directly for perfect print-to-screen accuracy
        padding: `${form.margins.top || 0}in ${form.margins.right || 0}in ${form.margins.bottom || 0}in ${form.margins.left || 0}in`,
        // 3. Use transform instead of the buggy 'zoom' property
        transform: `scale(${zoomLevel.value})`,
        transformOrigin: 'top center',
        position: 'relative',
        backgroundColor: 'white'
    };
});

const marginGuideStyle = computed(() => ({
    top: `${(form.margins.top || 0) * 96}px`, right: `${(form.margins.right || 0) * 96}px`,
    bottom: `${(form.margins.bottom || 0) * 96}px`, left: `${(form.margins.left || 0) * 96}px`,
}));

const CustomParagraph = Paragraph.extend({
    renderHTML({ HTMLAttributes }) {
        return ['p', mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, { style: 'margin: 0;' }), 0];
    }
});

const FontSize = Extension.create({
    name: 'fontSize',
    // FIX: Tell the extension to target list structures
    addOptions() { return { types: ['textStyle', 'listItem', 'orderedList', 'bulletList'] } },
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                fontSize: {
                    default: null, parseHTML: el => el.style.fontSize?.replace(/['"]+/g, ''),
                    renderHTML: attrs => attrs.fontSize ? { style: `font-size: ${attrs.fontSize}` } : {}
                }
            }
        }]
    },
    addCommands() {
        return {
            setFontSize: fontSize => ({ chain, editor }) => {
                let c = chain().setMark('textStyle', { fontSize });
                // Automatically inject font sizes into active list wrappers
                if (editor.isActive('listItem')) c = c.updateAttributes('listItem', { fontSize });
                if (editor.isActive('orderedList')) c = c.updateAttributes('orderedList', { fontSize });
                if (editor.isActive('bulletList')) c = c.updateAttributes('bulletList', { fontSize });
                return c.run();
            },
            unsetFontSize: () => ({ chain, editor }) => {
                let c = chain().setMark('textStyle', { fontSize: null }).removeEmptyTextStyle();
                if (editor.isActive('listItem')) c = c.updateAttributes('listItem', { fontSize: null });
                if (editor.isActive('orderedList')) c = c.updateAttributes('orderedList', { fontSize: null });
                if (editor.isActive('bulletList')) c = c.updateAttributes('bulletList', { fontSize: null });
                return c.run();
            }
        }
    }
});

// CRITICAL FIX: Custom FontFamily extension to ensure numbers/bullets inherit text fonts
const CustomFontFamily = FontFamily.extend({
    addOptions() { return { types: ['textStyle', 'listItem', 'orderedList', 'bulletList'] } },
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                fontFamily: {
                    default: null, parseHTML: el => el.style.fontFamily?.replace(/['"]+/g, ''),
                    renderHTML: attrs => attrs.fontFamily ? { style: `font-family: ${attrs.fontFamily}` } : {}
                }
            }
        }]
    },
    addCommands() {
        return {
            setFontFamily: fontFamily => ({ chain, editor }) => {
                let c = chain().setMark('textStyle', { fontFamily });
                if (editor.isActive('listItem')) c = c.updateAttributes('listItem', { fontFamily });
                if (editor.isActive('orderedList')) c = c.updateAttributes('orderedList', { fontFamily });
                if (editor.isActive('bulletList')) c = c.updateAttributes('bulletList', { fontFamily });
                return c.run();
            },
            unsetFontFamily: () => ({ chain, editor }) => {
                let c = chain().setMark('textStyle', { fontFamily: null }).removeEmptyTextStyle();
                if (editor.isActive('listItem')) c = c.updateAttributes('listItem', { fontFamily: null });
                if (editor.isActive('orderedList')) c = c.updateAttributes('orderedList', { fontFamily: null });
                if (editor.isActive('bulletList')) c = c.updateAttributes('bulletList', { fontFamily: null });
                return c.run();
            }
        }
    }
});

const LineHeight = Extension.create({
    name: 'lineHeight',
    addOptions() { return { types: ['paragraph', 'heading'] } },
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                lineHeight: {
                    default: null, parseHTML: el => el.style.lineHeight || null,
                    renderHTML: attrs => attrs.lineHeight ? { style: `line-height: ${attrs.lineHeight}` } : {}
                }
            }
        }]
    },
    addCommands() {
        return {
            setLineHeight: lineHeight => ({ tr, state, dispatch }) => {
                const { selection } = state;
                tr.doc.nodesBetween(selection.from, selection.to, (node, pos) => {
                    if (this.options.types.includes(node.type.name)) {
                        if (dispatch) tr.setNodeMarkup(pos, undefined, { ...node.attrs, lineHeight });
                    }
                });
                return true;
            }
        }
    }
});

const CustomHR = HorizontalRule.extend({
    selectable: true, draggable: true,
    addAttributes() {
        return {
            width: { default: '100%', parseHTML: el => el.style.width || '100%' },
            thickness: { default: '1px', parseHTML: el => el.style.borderTopWidth || '1px' },
            align: { default: 'center', parseHTML: el => el.getAttribute('data-align') || 'center' }
        }
    },
    renderHTML({ HTMLAttributes }) {
        const w = HTMLAttributes.width || '100%';
        const t = HTMLAttributes.thickness || '1px';
        const a = HTMLAttributes.align || 'center';
        let margin = '1rem auto';
        if (a === 'left') margin = '1rem auto 1rem 0';
        if (a === 'right') margin = '1rem 0 1rem auto';

        return ['hr', mergeAttributes(this.options.HTMLAttributes, {
            'data-align': a,
            style: `width: ${w}; border: none; border-top: ${t} solid black; margin: ${margin}; cursor: pointer;`
        })];
    }
});

const textVariablesList = [
    { id: 'franchise_number', label: 'Franchise Number' },
    { id: 'zone_color', label: 'Zone Color' },
    { id: 'operator_name', label: 'Operator Name' },
    { id: 'operator_address', label: 'Operator Address' },
    { id: 'unit_make', label: 'Unit Make' },
    { id: 'model_year', label: 'Model Year' },
    { id: 'motor_no', label: 'Motor No.' },
    { id: 'chassis_no', label: 'Chassis No.' },
    { id: 'plate_no', label: 'Plate No.' },
    { id: 'tab_approver_name', label: 'TAB Approver Name' },
    { id: 'sp_approver_name', label: 'SP Approver Name' },
];

const TextVariable = Node.create({
    name: 'textVariable', group: 'inline', inline: true, atom: true, draggable: true, 
    addAttributes() {
        return {
            variable: { default: null, parseHTML: el => el.getAttribute('data-text-variable') },
            label: { default: '', parseHTML: el => el.getAttribute('data-label') },
            wrap: { default: 'inline', parseHTML: el => el.getAttribute('data-wrap') },
            x: { default: 0, parseHTML: el => parseFloat(el.getAttribute('data-x')) || 0 },
            y: { default: 0, parseHTML: el => parseFloat(el.getAttribute('data-y')) || 0 },
            width: { default: 'auto', parseHTML: el => el.getAttribute('data-width') || 'auto' },
            textAlign: { default: 'left', parseHTML: el => el.getAttribute('data-text-align') || 'left' }
        }
    },
    parseHTML() { return [{ tag: 'span[data-text-variable]' }] },
    renderHTML({ HTMLAttributes }) {
        const wrap = HTMLAttributes.wrap || 'inline';
        const x = HTMLAttributes.x || 0;
        const y = HTMLAttributes.y || 0;
        const w = HTMLAttributes.width || 'auto';
        const align = HTMLAttributes.textAlign || 'left';
        
        let style = `display: inline-block; width: ${w}; text-align: ${align}; vertical-align: top; box-sizing: border-box; `;
        
        if (wrap === 'square-left') style += ' float: left; margin: 0.5rem 1.5rem 0.5rem 0;';
        else if (wrap === 'square-right') style += ' float: right; margin: 0.5rem 0 0.5rem 1.5rem;';
        else if (wrap === 'top-bottom') style += ' display: block; clear: both; margin: 1rem auto;';
        else if (wrap === 'behind') style += ` position: absolute; z-index: 0; left: ${x}px; top: ${y}px; white-space: nowrap;`;
        else if (wrap === 'in-front') style += ` position: absolute; z-index: 10; left: ${x}px; top: ${y}px; white-space: nowrap;`;
        else style += ' margin: 0;'; 

        return ['span', { 
            'data-text-variable': HTMLAttributes.variable, 
            'data-label': HTMLAttributes.label,
            'data-wrap': wrap,
            'data-x': x,
            'data-y': y,
            'data-width': w,
            'data-text-align': align,
            style,
            // CRITICAL FIX: Prevent absolute elements from breaking text flow editing
            class: (wrap === 'behind' || wrap === 'in-front') ? 'absolute-node' : '',
            contenteditable: (wrap === 'behind' || wrap === 'in-front') ? "false" : "true"
        }, `[${HTMLAttributes.label}]`];
    },
    addNodeView() { return VueNodeViewRenderer(DraggableTextVariable) }
});

// --- ENHANCED CUSTOM TIPTAP IMAGE ENGINE ---
const CustomImage = Node.create({
    name: 'customImage', inline: true, group: 'inline', draggable: true, 
    addAttributes() {
        return {
            src: { default: null }, 
            width: { default: '200px' }, 
            height: { default: 'auto' }, // Support explicit heights
            wrap: { default: 'inline' },
            x: { default: 0 }, y: { default: 0 }, 'data-variable': { default: null }
        }
    },
    parseHTML() {
        return [
            { 
                tag: 'span[data-type="customImage"]',
                getAttrs: node => ({
                    src: node.getAttribute('data-src'), 
                    width: node.getAttribute('data-width') || '200px',
                    height: node.getAttribute('data-height') || 'auto',
                    wrap: node.getAttribute('data-wrap') || 'inline',
                    x: parseFloat(node.getAttribute('data-x')) || 0, y: parseFloat(node.getAttribute('data-y')) || 0,
                    'data-variable': node.getAttribute('data-variable')
                })
            },
            { tag: 'img[src]', getAttrs: node => node.closest('span[data-type="customImage"]') ? false : { src: node.getAttribute('src') } }
        ]
    },
    renderHTML({ HTMLAttributes }) {
        const wrap = HTMLAttributes.wrap || 'inline';
        const width = HTMLAttributes.width || '200px';
        const height = HTMLAttributes.height || 'auto';
        const x = HTMLAttributes.x || 0;
        const y = HTMLAttributes.y || 0;

        let style = `display: inline-block; width: ${width}; height: ${height}; vertical-align: top; box-sizing: border-box; `;
        let imgStyle = 'width: 100%; height: 100%; object-fit: contain; display: block;';
        
        if (wrap === 'square-left') style += ' float: left; margin: 0.5rem 1.5rem 0.5rem 0;';
        else if (wrap === 'square-right') style += ' float: right; margin: 0.5rem 0 0.5rem 1.5rem;';
        else if (wrap === 'top-bottom') style += ' display: block; clear: both; margin: 1rem auto;';
        else if (wrap === 'behind') { style += ` position: absolute; z-index: 0; left: ${x}px; top: ${y}px; white-space: nowrap;`; imgStyle += ' max-width: none;'; }
        else if (wrap === 'in-front') { style += ` position: absolute; z-index: 10; left: ${x}px; top: ${y}px; white-space: nowrap;`; imgStyle += ' max-width: none;'; }
        else style += ' margin: 0;'; 

        return ['span', { 
            'data-type': 'customImage', 
            'data-src': HTMLAttributes.src, 
            'data-width': width, 
            'data-height': height, 
            'data-wrap': wrap, 
            'data-x': x, 
            'data-y': y, 
            'data-variable': HTMLAttributes['data-variable'] || '', 
            style,
            // CRITICAL FIX: Lock node if absolutely positioned
            class: (wrap === 'behind' || wrap === 'in-front') ? 'absolute-node' : '',
            contenteditable: (wrap === 'behind' || wrap === 'in-front') ? "false" : "true"
        }, ['img', { src: HTMLAttributes.src, style: imgStyle }]];
    },
    addNodeView() { return VueNodeViewRenderer(ResizableImage) }
});

const dropPlugin = new Plugin({
    props: {
        handleDrop(view, event) {
            if (!event.dataTransfer?.files || event.dataTransfer.files.length === 0) return false;
            const file = event.dataTransfer.files[0];
            if (!file.type.startsWith('image/')) return false;

            event.preventDefault();
            const reader = new FileReader();
            reader.onload = e => editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: e.target.result, width: '300px' } }).run();
            reader.readAsDataURL(file);
            return true;
        },
    },
});

const isLayoutElementSelected = ref(false);
const selectedElementType = ref('');
const currentLayoutWrap = ref('inline');
const isHrSelected = ref(false);

const checkSelection = () => {
    if (!editor.value) return;
    const { selection } = editor.value.state;
    const node = selection?.node;
    
    isHrSelected.value = node?.type?.name === 'horizontalRule';
    
    const isImg = node?.type?.name === 'customImage';
    const isTextVar = node?.type?.name === 'textVariable';
    
    isLayoutElementSelected.value = isImg || isTextVar;
    if (isLayoutElementSelected.value) {
        selectedElementType.value = node.type.name;
        currentLayoutWrap.value = editor.value.getAttributes(node.type.name).wrap || 'inline';
    }
};

const setLayoutWrap = (wrapType) => {
    editor.value.chain().focus().updateAttributes(selectedElementType.value, { wrap: wrapType }).run();
    currentLayoutWrap.value = wrapType;
};

const editor = useEditor({
    content: form.content,
    extensions: [
        StarterKit.configure({ horizontalRule: false, paragraph: false }), 
        CustomParagraph, CustomHR, Table.configure({ resizable: true }), TableRow, TableHeader, TableCell, 
        CustomImage, TextVariable, TextStyle, CustomFontFamily, FontSize, LineHeight, Underline, Dropcursor,
        TextAlign.configure({ types: ['heading', 'paragraph'] }),
    ],
    editorProps: {
        plugins: [dropPlugin],
        handleClick() { setTimeout(checkSelection, 50); return false; }
    },
    onUpdate: ({ editor }) => { form.content = editor.getHTML(); checkSelection(); },
    onSelectionUpdate: () => checkSelection(),
    onTransaction: () => checkSelection(),
});

onBeforeUnmount(() => { if (editor.value) editor.value.destroy(); });

const addTable = () => editor.value.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
const addImageByUrl = () => {
    const url = window.prompt('Enter the URL of the image:');
    if (url) editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: url } }).run();
};

const insertLguLogo = () => editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: props.systemSetting?.lgu_logo ? `/storage/${props.systemSetting.lgu_logo}` : 'https://placehold.co/150x150/e2e8f0/475569?text=LGU+Logo', 'data-variable': 'lgu-logo', width: '150px' } }).run();
const insertQrCode = () => editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: 'https://placehold.co/120x120/e2e8f0/475569?text=Franchise+QR', 'data-variable': 'qr-code', width: '120px' } }).run();
const insertTabSignature = () => editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: 'https://placehold.co/200x80/f3e8ff/7e22ce?text=TAB+Signature', 'data-variable': 'tab-signature', width: '200px' } }).run();
const insertSpSignature = () => editor.value.chain().focus().insertContent({ type: 'customImage', attrs: { src: 'https://placehold.co/200x80/f3e8ff/7e22ce?text=SP+Signature', 'data-variable': 'sp-signature', width: '200px' } }).run();

const insertTextVar = (variable, label) => editor.value.chain().focus().insertContent({ type: 'textVariable', attrs: { variable, label } }).run();
const saveTemplate = () => form.post(route('certificate-template.update'), { preserveScroll: true });
</script>

<template>
    <Head title="Edit Certificate Format" />

    <AuthenticatedLayout>
        <!-- <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Certificate Format</h2>
                <Link :href="route('admin.franchises.index')" class="text-sm text-gray-600 hover:text-gray-900 underline">Back to Franchises</Link>
            </div>
        </template> -->

        <div class="h-[calc(100vh-6rem)]">
            <div class="h-full relative">
                
                <transition leave-active-class="transition ease-in duration-300" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="showFlashMessage && $page.props.flash?.success" class="fixed top-20 right-8 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ $page.props.flash.success }}
                        </div>
                    </div>
                </transition>

                <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 flex h-full overflow-hidden">
                    
                    <div class="w-80 bg-gray-50 border-r border-gray-200 flex flex-col shrink-0 overflow-y-auto custom-scrollbar">
                        <div class="p-5 space-y-6">
                            
                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Document Setup</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Paper Size</label>
                                        <select v-model="form.paper_size" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                            <option value="A4">A4 (210 x 297 mm)</option>
                                            <option value="Letter">Letter (8.5 x 11 in)</option>
                                            <option value="Legal">Legal (8.5 x 14 in)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Margins (inches)</label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div><span class="text-xs text-gray-500">Top</span><input type="number" step="0.1" v-model="form.margins.top" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                                            <div><span class="text-xs text-gray-500">Bottom</span><input type="number" step="0.1" v-model="form.margins.bottom" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                                            <div><span class="text-xs text-gray-500">Left</span><input type="number" step="0.1" v-model="form.margins.left" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                                            <div><span class="text-xs text-gray-500">Right</span><input type="number" step="0.1" v-model="form.margins.right" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-gray-200">

                            <template v-if="isHrSelected">
                                <div>
                                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-3 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16M4 8h16M4 16h16"></path></svg>
                                        Line Settings
                                    </h3>
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <div>
                                            <label class="block text-[10px] text-gray-600 mb-1">Width</label>
                                            <select @change="e => editor.chain().focus().updateAttributes('horizontalRule', { width: e.target.value }).run()" :value="editor.getAttributes('horizontalRule').width" class="w-full border-gray-300 text-xs py-1 px-2 rounded">
                                                <option value="100%">100%</option><option value="75%">75%</option><option value="50%">50%</option><option value="25%">25%</option>
                                                <option value="200px">200px</option><option value="300px">300px</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] text-gray-600 mb-1">Thickness</label>
                                            <select @change="e => editor.chain().focus().updateAttributes('horizontalRule', { thickness: e.target.value }).run()" :value="editor.getAttributes('horizontalRule').thickness" class="w-full border-gray-300 text-xs py-1 px-2 rounded">
                                                <option value="1px">1px (Thin)</option><option value="2px">2px</option><option value="3px">3px</option><option value="4px">4px (Thick)</option><option value="6px">6px</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-gray-600 mb-1">Alignment</label>
                                        <div class="flex gap-1">
                                            <button @click="editor.chain().focus().updateAttributes('horizontalRule', { align: 'left' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('horizontalRule').align === 'left'}" class="px-2 py-1 text-xs border rounded w-full">Left</button>
                                            <button @click="editor.chain().focus().updateAttributes('horizontalRule', { align: 'center' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('horizontalRule').align === 'center'}" class="px-2 py-1 text-xs border rounded w-full">Center</button>
                                            <button @click="editor.chain().focus().updateAttributes('horizontalRule', { align: 'right' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('horizontalRule').align === 'right'}" class="px-2 py-1 text-xs border rounded w-full">Right</button>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-gray-200">
                            </template>

                            <template v-if="isLayoutElementSelected">
                                <div>
                                    <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-3 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Element Layout
                                    </h3>
                                    <div class="grid grid-cols-2 gap-1 mb-3">
                                        <button type="button" @click="setLayoutWrap('inline')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'inline'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">In Line with Text</button>
                                        <button type="button" @click="setLayoutWrap('square-left')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'square-left'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">Square (Left)</button>
                                        <button type="button" @click="setLayoutWrap('square-right')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'square-right'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">Square (Right)</button>
                                        <button type="button" @click="setLayoutWrap('top-bottom')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'top-bottom'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">Top and Bottom</button>
                                        <button type="button" @click="setLayoutWrap('behind')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'behind'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">Behind Text</button>
                                        <button type="button" @click="setLayoutWrap('in-front')" :class="{'bg-blue-100 border-blue-400 font-bold': currentLayoutWrap === 'in-front'}" class="px-2 py-1.5 text-[10px] border rounded hover:bg-gray-100 text-left">In Front of Text</button>
                                    </div>
                                    
                                    <template v-if="selectedElementType === 'customImage'">
                                        <label class="block text-[10px] font-bold text-gray-600 mb-1 uppercase">Image Dimensions</label>
                                        <div class="grid grid-cols-2 gap-1 mb-1">
                                            <div>
                                                <span class="text-[9px] text-gray-500">Width</span>
                                                <input type="text" @change="e => editor.chain().focus().updateAttributes('customImage', { width: e.target.value }).run()" :value="editor.getAttributes('customImage').width" class="w-full border-gray-300 text-xs py-1 px-2 rounded" placeholder="e.g. 200px">
                                            </div>
                                            <div>
                                                <span class="text-[9px] text-gray-500">Height</span>
                                                <input type="text" @change="e => editor.chain().focus().updateAttributes('customImage', { height: e.target.value }).run()" :value="editor.getAttributes('customImage').height" class="w-full border-gray-300 text-xs py-1 px-2 rounded" placeholder="e.g. auto">
                                            </div>
                                        </div>
                                    </template>

                                    <template v-if="selectedElementType === 'textVariable'">
                                        <label class="block text-[10px] font-bold text-gray-600 mb-1 uppercase">Text Container Alignment</label>
                                        <div class="flex gap-1 mb-1">
                                            <button @click="editor.chain().focus().updateAttributes('textVariable', { textAlign: 'left' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('textVariable').textAlign === 'left'}" class="px-2 py-1 text-xs border rounded w-full">Left</button>
                                            <button @click="editor.chain().focus().updateAttributes('textVariable', { textAlign: 'center' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('textVariable').textAlign === 'center'}" class="px-2 py-1 text-xs border rounded w-full">Center</button>
                                            <button @click="editor.chain().focus().updateAttributes('textVariable', { textAlign: 'right' }).run()" :class="{'bg-blue-100 border-blue-400': editor.getAttributes('textVariable').textAlign === 'right'}" class="px-2 py-1 text-xs border rounded w-full">Right</button>
                                        </div>
                                    </template>
                                </div>
                                <hr class="border-gray-200">
                            </template>

                            <div>
                                <h3 class="text-xs font-bold text-green-600 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    Text Variables
                                </h3>
                                <p class="text-[10px] text-gray-500 mb-2 leading-tight">Click to insert dynamic text placeholders. They will be auto-filled during printing.</p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button v-for="v in textVariablesList" :key="v.id" @click="insertTextVar(v.id, v.label)" type="button" class="px-2 py-1 bg-yellow-50 border border-yellow-300 text-yellow-800 rounded hover:bg-yellow-100 text-[10px] font-semibold shadow-sm transition">
                                        + {{ v.label }}
                                    </button>
                                </div>
                            </div>

                            <hr class="border-gray-200">

                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Formatting</h3>
                                
                                <div class="flex gap-1 mb-2">
                                    <select @change="e => editor.chain().focus().setFontFamily(e.target.value).run()" :value="editor?.getAttributes('textStyle').fontFamily || ''" class="flex-1 border-gray-300 rounded text-xs py-1.5 px-2 focus:ring-blue-500">
                                        <option value="">Default Font</option>
                                        <option value="Arial, Helvetica, sans-serif">Arial</option>
                                        <option value="'Times New Roman', Times, serif">Times New Roman</option>
                                        <option value="'Courier New', Courier, monospace">Courier New</option>
                                        <option value="Georgia, serif">Georgia</option>
                                        <option value="Verdana, sans-serif">Verdana</option>
                                        <option value="Impact, sans-serif">Impact</option>
                                    </select>

                                    <select @change="e => editor.chain().focus().setFontSize(e.target.value).run()" :value="editor?.getAttributes('textStyle').fontSize || ''" class="w-16 border-gray-300 rounded text-xs py-1.5 px-1 focus:ring-blue-500">
                                        <option value="">Size</option>
                                        <option value="8pt">8 pt</option><option value="10pt">10 pt</option><option value="12pt">12 pt</option>
                                        <option value="14pt">14 pt</option><option value="18pt">18 pt</option><option value="24pt">24 pt</option>
                                        <option value="36pt">36 pt</option>
                                    </select>

                                    <select @change="e => editor.chain().focus().setLineHeight(e.target.value).run()" :value="editor?.getAttributes('paragraph').lineHeight || editor?.getAttributes('heading').lineHeight || ''" class="w-20 border-gray-300 rounded text-xs py-1.5 px-1 focus:ring-blue-500">
                                        <option value="">Spacing</option>
                                        <option value="0.5">0.5 (Extreme)</option>
                                        <option value="0.75">0.75 (Tight)</option>
                                        <option value="0.9">0.9</option>
                                        <option value="1">1.0 (Normal)</option>
                                        <option value="1.15">1.15</option>
                                        <option value="1.5">1.5</option>
                                        <option value="2">2.0 (Double)</option>
                                    </select>
                                </div>

                                <div class="flex flex-wrap gap-1 mb-2">
                                    <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('bold') }" class="w-8 h-8 border border-gray-200 rounded hover:bg-gray-200 font-bold">B</button>
                                    <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('italic') }" class="w-8 h-8 border border-gray-200 rounded hover:bg-gray-200 italic">I</button>
                                    <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('underline') }" class="w-8 h-8 border border-gray-200 rounded hover:bg-gray-200 underline">U</button>
                                    
                                    <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('heading', { level: 1 }) }" class="px-2 h-8 border border-gray-200 rounded hover:bg-gray-200 font-bold text-sm">H1</button>
                                    <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('heading', { level: 2 }) }" class="px-2 h-8 border border-gray-200 rounded hover:bg-gray-200 font-bold text-sm">H2</button>
                                </div>
                                
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <button type="button" @click="editor.chain().focus().setTextAlign('left').run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive({ textAlign: 'left' }) }" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded hover:bg-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"></path></svg></button>
                                    <button type="button" @click="editor.chain().focus().setTextAlign('center').run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive({ textAlign: 'center' }) }" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded hover:bg-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M7 12h10M4 18h16"></path></svg></button>
                                    <button type="button" @click="editor.chain().focus().setTextAlign('right').run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive({ textAlign: 'right' }) }" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded hover:bg-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M10 12h10M4 18h16"></path></svg></button>
                                    <button type="button" @click="editor.chain().focus().setTextAlign('justify').run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive({ textAlign: 'justify' }) }" class="w-8 h-8 flex items-center justify-center border border-gray-200 rounded hover:bg-gray-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>
                                </div>

                                <div class="flex flex-wrap gap-1">
                                    <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('bulletList') }" class="px-2 h-8 border border-gray-200 rounded hover:bg-gray-200 text-sm">&bull; List</button>
                                    <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'bg-gray-300 border-gray-400': editor?.isActive('orderedList') }" class="px-2 h-8 border border-gray-200 rounded hover:bg-gray-200 text-sm">1. List</button>
                                </div>
                            </div>

                            <hr class="border-gray-200">

                            <div>
                                <h3 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-3 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                    Dynamic Images
                                </h3>
                                <div class="space-y-2">
                                    <button type="button" @click="insertLguLogo" class="w-full flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 text-blue-700 rounded hover:bg-blue-100 text-sm font-medium transition">Insert LGU Logo</button>
                                    <button type="button" @click="insertQrCode" class="w-full flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-200 text-blue-700 rounded hover:bg-blue-100 text-sm font-medium transition">Insert QR Code</button>
                                    <button type="button" @click="insertTabSignature" class="w-full flex items-center gap-2 px-3 py-2 bg-purple-50 border border-purple-200 text-purple-700 rounded hover:bg-purple-100 text-sm font-medium transition">Insert TAB Signature</button>
                                    <button type="button" @click="insertSpSignature" class="w-full flex items-center gap-2 px-3 py-2 bg-purple-50 border border-purple-200 text-purple-700 rounded hover:bg-purple-100 text-sm font-medium transition">Insert SP Signature</button>
                                </div>
                            </div>

                            <hr class="border-gray-200">

                            <div>
                                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Insert</h3>
                                <div class="space-y-2">
                                    <button type="button" @click="addTable" class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        Insert Table (3x3)
                                    </button>
                                    
                                    <div v-if="editor?.isActive('table')" class="bg-gray-100 p-2 rounded border border-gray-200 mt-1">
                                        <p class="text-[10px] uppercase font-bold text-gray-500 mb-2 text-center tracking-wide">Table Options</p>
                                        <div class="grid grid-cols-2 gap-1 mb-2">
                                            <button type="button" @click="editor.chain().focus().addColumnBefore().run()" class="px-1 py-1 bg-white border rounded text-xs hover:bg-gray-50">+ Col</button>
                                            <button type="button" @click="editor.chain().focus().deleteColumn().run()" class="px-1 py-1 bg-white border rounded text-xs text-red-600 hover:bg-red-50">- Col</button>
                                            <button type="button" @click="editor.chain().focus().addRowBefore().run()" class="px-1 py-1 bg-white border rounded text-xs hover:bg-gray-50">+ Row</button>
                                            <button type="button" @click="editor.chain().focus().deleteRow().run()" class="px-1 py-1 bg-white border rounded text-xs text-red-600 hover:bg-red-50">- Row</button>
                                            <button type="button" @click="editor.chain().focus().mergeCells().run()" class="px-1 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-semibold rounded text-xs hover:bg-blue-100 col-span-2">Merge Selected Cells</button>
                                            <button type="button" @click="editor.chain().focus().splitCell().run()" class="px-1 py-1 bg-blue-50 border border-blue-200 text-blue-700 font-semibold rounded text-xs hover:bg-blue-100 col-span-2">Split Cell</button>
                                        </div>
                                    </div>

                                    <button type="button" @click="addImageByUrl" class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Insert Regular Image
                                    </button>

                                    <button type="button" @click="editor.chain().focus().setHorizontalRule().run()" class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16"></path></svg>
                                        Insert Signatory Line
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div class="mt-auto p-5 border-t border-gray-200 bg-gray-50">
                            <button @click="saveTemplate" type="button" class="w-full bg-blue-600 text-white px-4 py-2.5 rounded-md font-bold hover:bg-blue-700 transition shadow-sm flex justify-center items-center" :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Document' }}
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 bg-[#e5e7eb] overflow-y-auto relative p-8 flex justify-center custom-scrollbar">
                        <div class="fixed bottom-8 right-12 z-20 flex items-center bg-white shadow-xl border border-gray-300 rounded-full px-4 py-2 gap-4">
                            <button @click="zoomOut" class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition focus:outline-none">-</button>
                            <span class="text-sm font-bold text-gray-700 w-12 text-center select-none">{{ Math.round(zoomLevel * 100) }}%</span>
                            <button @click="zoomIn" class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold transition focus:outline-none">+</button>
                            <button @click="zoomLevel = 1" class="text-xs text-blue-600 hover:text-blue-800 font-semibold border-l pl-3 ml-1 focus:outline-none">Reset</button>
                        </div>
                        <div class="bg-white shadow-xl ring-1 ring-gray-900/5 editor-paper transition-all duration-200 relative" :style="paperStyle">
                            <div class="absolute border-2 border-dashed border-blue-200 pointer-events-none z-0" :style="marginGuideStyle"></div>
                            <EditorContent :editor="editor" class="h-full relative z-10" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Scrollbar Styles (Editor Only) */
.custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

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

.editor-paper .ProseMirror p.is-editor-empty:first-child::before {
    content: "Start typing your certificate content here..."; float: left; color: #adb5bd; pointer-events: none; height: 0;
}
.editor-paper .ProseMirror h1 { font-weight: bold; margin: 0; padding: 0; line-height: inherit;}
.editor-paper .ProseMirror h2 { font-weight: bold; margin: 0; padding: 0; line-height: inherit;}

/* --- NUCLEAR LIST OVERRIDE (Fixes missing numbers/bullets) --- */
.editor-paper .ProseMirror ul {
    display: block !important;
    list-style-type: disc !important;
    list-style-position: outside !important;
    padding-left: 2.5rem !important; /* Forces the physical space for markers */
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.editor-paper .ProseMirror ol {
    display: block !important;
    list-style-type: decimal !important;
    list-style-position: outside !important;
    padding-left: 2.5rem !important; /* Forces the physical space for numbers */
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
}
.editor-paper .ProseMirror li {
    display: list-item !important;
    margin-bottom: 0.25rem !important;
}
/* TipTap wraps list items in paragraphs, we must ensure they don't break the marker */
.editor-paper .ProseMirror li > p {
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* Ensure print paragraphs are strictly stripped of default margins to match editor tightness */
.editor-paper .ProseMirror > p { margin: 0; padding: 0; line-height: inherit; text-align: inherit;} 

/* Signatory Line Visual State */
.editor-paper .ProseMirror hr.ProseMirror-selectednode { outline: 2px solid #3b82f6; outline-offset: 2px; }

/* --- NUCLEAR TABLE OVERRIDE --- */
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
    flex-direction: row !important; /* Defeats Tailwind flex resets */
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
.editor-paper .ProseMirror table .selectedCell:after {
    z-index: 2; position: absolute; content: ""; left: 0; right: 0; top: 0; bottom: 0; background: rgba(200, 200, 255, 0.4); pointer-events: none;
}
.editor-paper .ProseMirror table .column-resize-handle {
    position: absolute; right: -5px; top: 0; bottom: 0; width: 10px; background-color: rgba(59, 130, 246, 0.1); pointer-events: auto; z-index: 20; cursor: col-resize;
}
.editor-paper .ProseMirror table .column-resize-handle::after {
    content: ""; position: absolute; right: 4px; top: 0; bottom: 0; width: 2px; background-color: #3b82f6; opacity: 0.8;
}

/* Base Image Styling */
.editor-paper .ProseMirror img { max-width: 100%; display: inline-block; cursor: pointer; }
.editor-paper .ProseMirror span.absolute img { max-width: none !important; }
.editor-paper .ProseMirror img.ProseMirror-selectednode { outline: 3px solid #3b82f6; }

/* Dynamic Variables Visual Helpers (Editor Only) */
.editor-paper .ProseMirror img[data-variable="lgu-logo"],
.editor-paper .ProseMirror img[data-variable="qr-code"],
.editor-paper .ProseMirror img[data-variable="tab-signature"],
.editor-paper .ProseMirror img[data-variable="sp-signature"] {
    border: 2px dashed #3b82f6; padding: 4px; border-radius: 8px; background-color: #eff6ff;
}

/* Robust Print Styles */
@media print {
    /* Tell the printer to use the dimensions defined by the div, not the browser defaults */
    @page {
        margin: 0; 
        size: auto;
    }
    
    /* Hide the entire application UI by default */
    body * {
        visibility: hidden;
    }

    /* ONLY show the editor canvas and its children */
    .editor-paper, .editor-paper * {
        visibility: visible;
    }

    .editor-paper {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important; /* Allow the print dialog to manage the physical boundaries */
        min-height: 100% !important;
        transform: none !important; /* Strip the zoom scale so it prints at 100% */
        box-shadow: none !important;
        margin: 0 !important;
        /* Note: Your inline padding set in inches will seamlessly create the print margins! */
    }

    /* Hide TipTap UI helpers */
    .ProseMirror-selectednode {
        outline: none !important;
    }
    
    /* Hide Vue/TipTap resizing handles */
    .resize-handle, .column-resize-handle {
        display: none !important;
    }
}
</style>