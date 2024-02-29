<script setup>
import Calendar from "primevue/calendar"

defineProps({
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: [null, String, Date, Array],
    required: true,
  },
  id: {
    type: String,
    require: true,
    default: "",
  },
  type: {
    type: String,
    required: false,
    default: "single",
    validator: (value) => ["single", "range"].includes(value),
  },
  showIcon: {
    type: Boolean,
    required: false,
    default: false,
  },
  showTime: {
    type: Boolean,
    required: false,
    default: false,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
})

defineEmits(["update:modelValue"])
</script>

<template>
  <div class="field">
    <div class="p-float-label">
      <Calendar
        :id="id"
        :class="{ 'p-invalid': isInvalid }"
        :manual-input="type !== 'range'"
        :model-value="modelValue"
        :selection-mode="type"
        :show-icon="showIcon"
        :show-time="showTime"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label v-text="label" />
    </div>
    <small></small>
  </div>
</template>
