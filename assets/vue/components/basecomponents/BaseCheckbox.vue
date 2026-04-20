<template>
  <div class="field-checkbox">
    <Checkbox
      v-model="modelValue"
      :binary="value === undefined"
      :inputId="id"
      :name="name"
      :value="value"
    />
    <label
      :for="id"
      class="ml-2 cursor-pointer"
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
})
</script>
