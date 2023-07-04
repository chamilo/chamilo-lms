<template>
  <div class="field">
    <div class="p-float-label">
      <Dropdown
        :class="{ 'p-invalid': isInvalid }"
        :input-id="inputId"
        :model-value="modelValue"
        :name="name"
        :option-label="optionLabel"
        :options="options"
        :placeholder="placeholder"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label :for="inputId" v-text="label" />
    </div>
    <small v-if="isInvalid" :class="{ 'p-error': isInvalid }" v-text="errorText" />
  </div>
</template>

<script setup>
import Dropdown from "primevue/dropdown";

defineProps({
  name: {
    type: String,
    required: true,
  },
  modelValue: {
    type: Object,
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
});

defineEmits(["update:modelValue"]);
</script>
