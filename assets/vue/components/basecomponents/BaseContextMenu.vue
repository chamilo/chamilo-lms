<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  position: {
    type: Object,
    default: () => ({ x: 0, y: 0 }),
  }
})

const emit = defineEmits(['close'])

const handleClickOutside = (event) => {
  emit('close')
}

watch(() => props.visible, (newVal) => {
  if (newVal) {
    setTimeout(() => {
      document.addEventListener('click', handleClickOutside)
    }, 0)
  } else {
    document.removeEventListener('click', handleClickOutside)
  }
})
</script>

<template>
  <div class="context-menu" v-if="visible" :style="{ top: `${position.y}px`, left: `${position.x}px` }">
    <slot />
  </div>
</template>
