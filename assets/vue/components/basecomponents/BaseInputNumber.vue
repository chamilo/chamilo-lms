<template>
  <div class="field">
    <div class="p-float-label">
      <InputNumber
        :input-id="id"
        :class="{ 'p-invalid': isInvalid }"
        :disabled="disabled"
        :max="max"
        :min="min"
        :model-value="modelValue"
        :step="step"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label
        :for="id"
        v-text="label"
      />
    </div>
    <small
      v-if="smallText"
      :class="{ 'p-error': isInvalid }"
      v-text="smallText"
    />
  </div>
</template>

<script setup>
import InputNumber from "primevue/inputnumber"
import { computed } from "vue"

const props = defineProps({
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
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  },
  helpText: {
    type: String,
    required: false,
    default: null,
  },
})

defineEmits(["update:modelValue"])

const smallText = computed(() => {
  if (props.errorText && props.isInvalid) {
    return props.errorText
  }

  return props.helpText
})
</script>
