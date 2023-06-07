<template>
  <div class="field">
    <div class="p-float-label">
      <InputText
        :id="id"
        :model-value="modelValue"
        :class="{ 'p-invalid': isInvalid }"
        type="text"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label v-t="label" :class="{ 'p-error': isInvalid }" :for="id" />
    </div>
    <small v-if="isInvalid" v-t="helpText" class="p-error" />
  </div>
</template>

<script setup>
import InputText from "primevue/inputtext";

defineProps({
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
  helpText: {
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
