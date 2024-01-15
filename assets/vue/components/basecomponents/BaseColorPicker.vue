<template>
  <div class="flex flex-col justify-center">
    <span v-if="label">{{ label }}</span>
    <ColorPicker
      format="rgb"
      :model-value="modelValue"
      @update:model-value="emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import ColorPicker from 'primevue/colorpicker'

defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
  label: {
    type: String,
    default: '',
  }
})

const emit = defineEmits(["update:modelValue"])
</script>
