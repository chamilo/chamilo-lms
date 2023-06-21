<template>
  <Chip
    :image="value[imageField]"
    :label="value[labelField]"
    :removable="isRemovable"
    @remove="$emit('remove', value)"
  />
</template>

<script setup>
import Chip from "primevue/chip";

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
});

defineEmits(["remove"]);
</script>
