<template>
  <Tag
    :severity="normalizedType"
    :value="label"
    :icon="icon"
  />
</template>

<script setup>
import Tag from "primevue/tag"
import { computed } from "vue"

const props = defineProps({
  type: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: false,
    default: undefined,
  },
})

const normalizedType = computed(() => {
  if ("warning" === props.type) {
    return "warn"
  }

  return props.type
})
</script>
