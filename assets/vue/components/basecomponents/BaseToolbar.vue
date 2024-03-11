<template>
  <Toolbar :class="toolbarClass">
    <template v-if="!hasStartSlot && !hasEndSlot" #start>
      <slot></slot>
    </template>
    <template v-if="hasStartSlot" v-slot:start>
      <slot name="start"></slot>
    </template>
    <template v-if="hasEndSlot" v-slot:end>
      <slot name="end"></slot>
    </template>
  </Toolbar>
</template>

<script setup>
import Toolbar from "primevue/toolbar"
import { computed, onMounted, ref } from "vue"
import { useSlots } from 'vue'

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
