<template>
  <node-view-wrapper 
    :style="computedStyle" 
    :class="['group', isAbsolute ? 'absolute' : 'relative inline-block']"
    as="span"
    @mousedown="onAbsoluteDragStart"
  >
    <img 
      :src="node.attrs.src" 
      :style="{ 
          width: node.attrs.width, 
          height: 'auto', 
          display: isAbsolute || node.attrs.wrap === 'top-bottom' ? 'block' : 'inline-block',
          maxWidth: isAbsolute ? 'none' : '100%' 
      }" 
      :data-variable="node.attrs['data-variable']"
      :data-drag-handle="!isAbsolute ? '' : null"
      class="transition-shadow cursor-pointer" 
      :class="{ 
          'outline outline-3 outline-blue-500 shadow-lg': selected, 
          'border-2 border-dashed border-blue-400 bg-blue-50 p-1': node.attrs['data-variable'] 
      }" 
    />

    <div 
      v-if="selected" 
      class="absolute -right-2 -bottom-2 w-4 h-4 bg-blue-600 border-2 border-white rounded-full cursor-se-resize shadow-md z-50 hover:scale-125 transition-transform" 
      @mousedown.stop.prevent="startResize"
      title="Drag to resize"
    ></div>
  </node-view-wrapper>
</template>

<script setup>
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3'
import { computed } from 'vue'

const props = defineProps(nodeViewProps)

const isAbsolute = computed(() => ['behind', 'in-front'].includes(props.node.attrs.wrap))

// Map Layout configurations directly to CSS
const computedStyle = computed(() => {
    const wrap = props.node.attrs.wrap;
    let style = {};

    if (wrap === 'square-left') {
        style.float = 'left';
        style.margin = '0.5rem 1.5rem 0.5rem 0';
    } else if (wrap === 'square-right') {
        style.float = 'right';
        style.margin = '0.5rem 0 0.5rem 1.5rem';
    } else if (wrap === 'top-bottom') {
        style.display = 'block';
        style.width = 'max-content'; // <-- FIX: Shrink-wraps the container tightly to the image width!
        style.clear = 'both';
        style.margin = '1rem auto';
    } else if (wrap === 'behind') {
        style.zIndex = props.selected ? 50 : 0; 
        style.left = `${props.node.attrs.x || 0}px`;
        style.top = `${props.node.attrs.y || 0}px`;
    } else if (wrap === 'in-front') {
        style.zIndex = props.selected ? 50 : 10;
        style.left = `${props.node.attrs.x || 0}px`;
        style.top = `${props.node.attrs.y || 0}px`;
    } else {
        style.margin = '0 0.5rem'; // Inline
    }

    return style;
})

// Custom Drag for absolutely positioned images
const onAbsoluteDragStart = (event) => {
    // <-- FIX: Manually force TipTap to select this node! 
    // This ensures the sidebar settings never disappear when interacting with Absolute images.
    if (typeof props.getPos === 'function') {
        props.editor.commands.setNodeSelection(props.getPos());
    }

    if (!isAbsolute.value) return; 
    if (event.target.tagName !== 'IMG') return;
    
    // Stop native dragging
    event.preventDefault(); 
    event.stopPropagation();
    
    const startX = event.clientX;
    const startY = event.clientY;
    const initialNodeX = props.node.attrs.x || 0;
    const initialNodeY = props.node.attrs.y || 0;

    const paper = document.querySelector('.editor-paper');
    const zoom = paper ? parseFloat(paper.style.zoom || 1) : 1;

    const onMouseMove = (e) => {
        const dx = (e.clientX - startX) / zoom;
        const dy = (e.clientY - startY) / zoom;
        props.updateAttributes({ x: initialNodeX + dx, y: initialNodeY + dy });
    }

    const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}

// Drag-to-resize mechanism
const startResize = (event) => {
    event.preventDefault();
    event.stopPropagation();
    const startX = event.clientX;
    const startWidth = props.node.attrs.width ? parseInt(props.node.attrs.width) : 200;

    const paper = document.querySelector('.editor-paper');
    const zoom = paper ? parseFloat(paper.style.zoom || 1) : 1;

    const onMouseMove = (e) => {
        const newWidth = Math.max(50, startWidth + ((e.clientX - startX) / zoom));
        props.updateAttributes({ width: `${newWidth}px` });
    }

    const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}
</script>