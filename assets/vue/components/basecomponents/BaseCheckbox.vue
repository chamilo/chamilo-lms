<template>
  <div class="field-checkbox">
    <Checkbox
      v-model="modelValue"
      :binary="true"
      :inputId="id"
      :name="name"
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
  type: Boolean,
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
})
</script>
