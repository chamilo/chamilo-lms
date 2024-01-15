<template>
  <div class="field">
    <div class="p-float-label">
      <Calendar
        :class="{ 'p-invalid': isInvalid }"
        :input-id="id"
        :model-value="modelValue"
        :show-time="showTime"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label :for="id" v-text="label" />
    </div>
    <small v-if="isInvalid" :class="{ 'p-error': isInvalid }" v-text="errorText" />
  </div>
</template>

<script setup>
import Calendar from "primevue/calendar";

defineProps({
  modelValue: {
    type: String,
    required: true,
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
});

defineEmits(["update:modelValue"]);
</script>
