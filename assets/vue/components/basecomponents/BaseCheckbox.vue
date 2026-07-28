<template>
  <div class="field-checkbox">
    <Checkbox
      v-model="modelValue"
      :binary="value === undefined"
      :disabled="disabled"
      :inputId="id"
      :name="name"
      :value="value"
    />
    <label
      :for="id"
      :class="['ml-2', disabled ? 'cursor-not-allowed text-gray-50' : 'cursor-pointer']"
      >{{ label }}</label
    >
  </div>
</template>

<script setup>
import Checkbox from "primevue/checkbox"

const modelValue = defineModel({
  type: [Boolean, Array],
  required: true,
})

defineProps({
  id: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  value: {
    type: [String, Number, Object],
    default: undefined,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})
</script>
