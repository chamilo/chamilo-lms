<template>
  <div class="field">
    <FloatLabel variant="on">
      <DatePicker
        :class="{ 'p-invalid': isInvalid }"
        :input-id="id"
        :model-value="modelValue"
        :show-time="showTime"
        fluid
        icon-display="input"
        show-icon
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label
        :for="id"
        v-text="label"
      />
    </FloatLabel>
    <small
      v-if="isInvalid"
      :class="{ 'p-error': isInvalid }"
      v-text="errorText"
    />
  </div>
</template>

<script setup>
import FloatLabel from "primevue/floatlabel"
import DatePicker from "primevue/datepicker"

defineProps({
  modelValue: {
    type: String,
    default: () => "",
  },
  id: {
    type: String,
    required: true,
    default: () => "",
  },
  label: {
    type: String,
    required: true,
    default: () => "",
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
  showTime: {
    type: Boolean,
    required: false,
    default: () => false,
  },
})

defineEmits(["update:modelValue"])
</script>
