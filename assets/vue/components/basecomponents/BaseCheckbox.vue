<template>
  <div class="field-checkbox">
    <Checkbox
      :binary="true"
      :input-id="id"
      :model-value="modelValue"
      :name="name"
      @update:model-value="$emit('update:modelValue', $event)"
    >
      <template #icon="{ checked }">
        <BaseIcon
          v-if="checked"
          class="bg-primary text-white rounded"
          icon="confirm"
          size="small"
        />
      </template>
    </Checkbox>
    <label
      :for="id"
      class="ml-2 cursor-pointer"
      >{{ label }}</label
    >
  </div>
</template>

<script setup>
import Checkbox from "primevue/checkbox"
import BaseIcon from "./BaseIcon.vue"

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
    required: true,
  },
})

defineEmits(["update:modelValue"])
</script>
