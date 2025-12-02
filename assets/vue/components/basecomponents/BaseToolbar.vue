<template>
  <Toolbar :class="toolbarClass">
    <template
      v-if="!hasStartSlot && !hasEndSlot"
      #start
    >
      <slot></slot>
    </template>
    <template
      v-if="hasStartSlot"
      v-slot:start
    >
      <slot name="start"></slot>
    </template>
    <template
      v-if="hasEndSlot"
      v-slot:end
    >
      <slot name="end"></slot>
    </template>
  </Toolbar>
</template>

<script setup>
import Toolbar from "primevue/toolbar"
import { computed, onMounted, ref, useSlots } from "vue"

const props = defineProps({
  showTopBorder: {
    type: Boolean,
    default: false,
  },
})

const toolbarClass = computed(() => {
  return props.showTopBorder ? "pt-5 border-t border-b" : "p-toolbar"
})

const slots = useSlots()
const hasStartSlot = ref(false)
const hasEndSlot = ref(false)

onMounted(() => {
  hasStartSlot.value = !!slots.start
  hasEndSlot.value = !!slots.end
})
</script>
<style scoped>
/* Ensure 8px spacing between items in PrimeVue Toolbar groups */
/* PrimeVue ≥9 (group-* names) */
:deep(.p-toolbar-group-start),
:deep(.p-toolbar-group-center),
:deep(.p-toolbar-group-end) {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem; /* 8px */
}

/* PrimeVue 8.x fallback (start/end names) */
:deep(.p-toolbar-start),
:deep(.p-toolbar-center),
:deep(.p-toolbar-end) {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}
</style>
