<template>
  <div class="flex items-center">
    <Checkbox
      :model-value="modelValue"
      :binary="true"
      :name="name"
      :input-id="id"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <label :for="id" class="ml-2 cursor-pointer">{{ label }}</label>
  </div>
</template>

<script setup>
import Checkbox from "primevue/checkbox";

defineProps({
  id: {
    type: String,
    required: true,
  },
  modelValue: {
    type: null,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true
  }
})

defineEmits(['update:modelValue'])
</script>
