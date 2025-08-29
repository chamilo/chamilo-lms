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
      <label :for="id">{{ t(label) }}</label>
    </div>
    <slot name="errors">
      <small v-if="isInvalid" class="p-error">
        {{ t(errorText || 'Error message') }}
      </small>
    </slot>
  </div>
</template>

<script setup>
import Textarea from "primevue/textarea"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  id: {
    type: String,
    required: true,
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
