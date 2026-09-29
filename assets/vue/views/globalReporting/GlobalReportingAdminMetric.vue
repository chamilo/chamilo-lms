<template>
  <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-3 py-2">
    <span class="flex items-center gap-2 text-gray-60">
      <BaseIcon
        :icon="icon"
        size="small"
      />
      {{ label }}
    </span>
    <span class="font-medium text-gray-90">{{ value }}</span>
  </div>
</template>

<script setup>
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

defineProps({
  label: {
    type: String,
    required: true,
  },
  value: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
})
</script>
