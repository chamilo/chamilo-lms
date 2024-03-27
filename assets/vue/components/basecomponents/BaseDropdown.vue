<template>
  <div class="field">
    <div class="p-float-label">
      <Dropdown
        :class="{ 'p-invalid': isInvalid }"
        :input-id="inputId"
        :model-value="modelValue"
        :name="name"
        :option-label="optionLabel"
        :option-value="optionValue"
        :options="options"
        :placeholder="placeholder"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label :for="inputId" v-text="label" />
    </div>
    <small v-if="isInvalid" :class="{ 'p-error': isInvalid }" v-text="errorText" />
    <small v-if="helpText" class="form-text text-muted">{{ helpText }}</small>
  </div>
</template>

<script setup>
import Dropdown from "primevue/dropdown"

defineProps({
  name: {
    type: String,
    required: true,
  },
  // type null allow all kind of values, like prime vue does
  modelValue: {
    type: null,
    required: true,
    default: () => {},
  },
  options: {
    type: Array,
    required: true,
    default: () => [],
  },
  placeholder: {
    type: String,
    required: false,
    default: "",
  },
  optionLabel: {
    type: String,
    required: true,
    default: "",
  },
  optionValue: {
    type: String,
    required: false,
    default: () => null,
  },
  inputId: {
    type: String,
    required: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  errorText: {
    type: String,
    required: false,
    default: null,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  helpText: String,
})

defineEmits(["update:modelValue"])
</script>
