<script setup>
import Calendar from "primevue/calendar"

defineProps({
  label: {
    type: String,
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

const model = defineModel({
  type: [null, String, Date, Array],
  required: true,
})
</script>

<template>
  <div class="field">
    <div class="p-float-label">
      <Calendar
        :id="id"
        v-model="model"
        :class="{ 'p-invalid': isInvalid }"
        :manual-input="type !== 'range'"
        :selection-mode="type"
        :show-icon="showIcon"
        :show-time="showTime"
      />
      <label v-text="label" />
    </div>
    <small></small>
  </div>
</template>
