<template>
  <div class="field">
    <div class="p-float-label">
      <InputNumber
        :id="id"
        :class="{ 'p-invalid': isInvalid }"
        :max="max"
        :min="min"
        :model-value="modelValue"
        :step="step"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label :for="id" v-text="label" />
    </div>
    <small v-if="isInvalid" :class="{ 'p-error': isInvalid }" v-text="errorText" />
  </div>
</template>

<script setup>
import InputNumber from "primevue/inputnumber";

defineProps({
  modelValue: {
    type: Number,
    required: true,
  },
  id: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  step: {
    type: Number,
    required: false,
    default: () => 1,
  },
  min: {
    type: Number,
    required: false,
    default: () => undefined,
  },
  max: {
    type: Number,
    required: false,
    default: () => undefined,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: () => false,
  },
  errorText: {
    type: String,
    required: false,
    default: "",
  },
});

defineEmits(["update:modelValue"]);
</script>