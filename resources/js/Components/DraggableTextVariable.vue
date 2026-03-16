<template>
  <node-view-wrapper 
    :style="computedStyle" 
    :class="['group', isAbsolute ? 'absolute' : 'relative inline-block']"
    as="span"
    @mousedown="onAbsoluteDragStart"
  >
    <span 
      class="inline-block bg-yellow-200 text-yellow-900 px-1 rounded cursor-move select-none align-baseline transition-shadow"
      :class="{ 'outline outline-2 outline-blue-500 shadow-md': selected }"
      :style="{ width: node.attrs.width, textAlign: node.attrs.textAlign, boxSizing: 'border-box' }"
    >
      [{{ node.attrs.label }}]
    </span>

    <div 
      v-if="selected" 
      class="absolute -right-2 -bottom-2 w-4 h-4 bg-blue-600 border-2 border-white rounded-full cursor-se-resize shadow-md z-50 hover:scale-125 transition-transform" 
      @mousedown.stop.prevent="startResize"
      title="Drag to resize text container"
    ></div>
  </node-view-wrapper>
</template>

<script setup>
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3'
import { computed } from 'vue'

const props = defineProps(nodeViewProps)

const isAbsolute = computed(() => ['behind', 'in-front'].includes(props.node.attrs.wrap))

const computedStyle = computed(() => {
    const wrap = props.node.attrs.wrap;
    let style = {};

    if (wrap === 'square-left') {
        style.float = 'left'; style.margin = '0.5rem 1.5rem 0.5rem 0';
    } else if (wrap === 'square-right') {
        style.float = 'right'; style.margin = '0.5rem 0 0.5rem 1.5rem';
    } else if (wrap === 'top-bottom') {
        style.display = 'block'; style.width = 'max-content'; style.clear = 'both'; style.margin = '1rem auto';
    } else if (wrap === 'behind') {
        style.zIndex = props.selected ? 50 : 0; style.left = `${props.node.attrs.x || 0}px`; style.top = `${props.node.attrs.y || 0}px`;
    } else if (wrap === 'in-front') {
        style.zIndex = props.selected ? 50 : 10; style.left = `${props.node.attrs.x || 0}px`; style.top = `${props.node.attrs.y || 0}px`;
    } else {
        style.margin = '0'; 
    }
    return style;
})

const onAbsoluteDragStart = (event) => {
    if (typeof props.getPos === 'function') props.editor.commands.setNodeSelection(props.getPos());
    if (!isAbsolute.value) return; 
    
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

const startResize = (event) => {
    event.preventDefault();
    event.stopPropagation();
    const startX = event.clientX;
    const startWidth = props.node.attrs.width === 'auto' ? 150 : parseInt(props.node.attrs.width);

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