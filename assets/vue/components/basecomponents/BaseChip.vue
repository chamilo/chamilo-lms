<template>
  <Chip
    :image="value[imageField]"
    :label="value[labelField]"
    :removable="isRemovable"
    @remove="$emit('remove', value)"
  >
    <template #removeicon="{ removeCallback, keydownCallback }">
      <BaseIcon
        class="p-icon p-chip-remove-icon"
        icon="close"
        @click="removeCallback"
        @keydownk="keydownCallback"
      />
    </template>
  </Chip>
</template>

<script setup>
import Chip from "primevue/chip"
import BaseIcon from "./BaseIcon.vue"

defineProps({
  value: {
    type: Object,
    required: true,
  },
  labelField: {
    type: String,
    required: true,
  },
  imageField: {
    type: String,
    required: false,
    default: null,
  },
  isRemovable: {
    type: Boolean,
    required: false,
    default: false,
  },
})

defineEmits(["remove"])
</script>
