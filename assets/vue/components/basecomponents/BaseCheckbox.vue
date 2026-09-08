<template>
  <div class="field-checkbox">
    <Checkbox
      v-model="modelValue"
      :aria-label="ariaLabel"
      :binary="value === undefined"
      :disabled="disabled"
      :indeterminate="indeterminate"
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
  // Accessible name for checkboxes rendered without a visible label (e.g. a table selection column).
  ariaLabel: {
    type: String,
    default: undefined,
  },
  indeterminate: {
    type: Boolean,
    default: false,
  },
})
</script>
