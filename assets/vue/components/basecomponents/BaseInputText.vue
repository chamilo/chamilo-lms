<template>
  <div class="field">
    <div class="p-float-label">
      <InputText
        :id="id"
        :model-value="modelValue"
        :class="{ 'p-invalid': isInvalid }"
        :aria-label="label"
        type="text"
        :required="required"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label
        v-t="label"
        :class="{ 'p-error': isInvalid }"
        :for="id"
        class="text-primary/40"
      />
    </div>
    <slot name="errors">
      <small
        v-if="isInvalid"
        v-t="errorText"
        class="p-error"
      />
    </slot>
  </div>
</template>

<script setup>
import InputText from "primevue/inputtext"

const props = defineProps({
  id: {
    type: String,
    require: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  modelValue: {
    type: String,
    required: true,
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
  required: Boolean,
})

defineEmits(["update:modelValue"])
</script>
