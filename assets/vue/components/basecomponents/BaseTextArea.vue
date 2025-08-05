<template>
  <div class="field">
    <div class="p-float-label">
      <Textarea
        :id="id"
        :aria-label="label"
        :class="['w-full', { 'p-invalid': isInvalid }, $attrs.class]"
        v-bind="$attrs"
        :model-value="modelValue"
        type="text"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label
        v-t="Label"
        :for="id"
      />
    </div>
    <slot name="errors">
      <small
        v-if="isInvalid"
        v-t="Error message"
        class="p-error"
      />
    </slot>
  </div>
</template>

<script setup>
import Textarea from "primevue/textarea"

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
})

defineEmits(["update:modelValue"])
</script>
