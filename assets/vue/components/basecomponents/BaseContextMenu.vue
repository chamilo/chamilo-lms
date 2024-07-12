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

<style scoped>
.context-menu {
  position: absolute;
  background-color: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  border-radius: 5px;
  padding: 5px 0;
  min-width: 150px;
  font-family: Arial, sans-serif;
  font-size: 14px;
}

.context-menu ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.context-menu li {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  cursor: pointer;
  text-align: center;
  transition: background-color 0.2s, box-shadow 0.2s;
}

.context-menu li:hover {
  background-color: #e0e0e0;
  box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
}

.context-menu li .mdi {
  margin-right: 10px;
}
</style>
