<template>
  <div class="field-checkbox">
    <Checkbox
      :model-value="modelValue"
      :binary="true"
      :name="name"
      :input-id="id"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <template #icon="{checked}">
        <BaseIcon
          v-if="checked"
          icon="confirm"
          size="small"
          class="bg-primary text-white rounded"
        />
      </template>
    </Checkbox>
    <label :for="id" class="ml-2 cursor-pointer">{{ label }}</label>
  </div>
</template>

<script setup>
import Checkbox from "primevue/checkbox";
import BaseIcon from "./BaseIcon.vue";

defineProps({
  id: {
    type: String,
    required: true,
  },
  modelValue: {
    type: null,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true
  }
})

defineEmits(['update:modelValue'])
</script>
