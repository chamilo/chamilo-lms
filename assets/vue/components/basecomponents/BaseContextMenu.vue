<script setup>
import { watch } from "vue"

const props = defineProps({
  visible: {
    type: Boolean,
    default: false,
  },
  position: {
    type: Object,
    default: () => ({ x: 0, y: 0 }),
  },
})

const emit = defineEmits(["close"])

const handleClickOutside = (event) => {
  emit("close")
}

watch(
  () => props.visible,
  (newVal) => {
    if (newVal) {
      setTimeout(() => {
        document.addEventListener("click", handleClickOutside)
      }, 0)
    } else {
      document.removeEventListener("click", handleClickOutside)
    }
  },
)
</script>

<template>
  <div
    v-if="visible"
    :style="{ top: `${position.y}px`, left: `${position.x}px` }"
    class="context-menu"
  >
    <slot />
  </div>
</template>
