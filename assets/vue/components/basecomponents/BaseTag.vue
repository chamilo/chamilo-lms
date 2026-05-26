<template>
  <Tag
    :severity="normalizedType"
    :value="label"
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
})

const normalizedType = computed(() => {
  if ("warning" === props.type) {
    return "warn"
  }

  return props.type
})
</script>
