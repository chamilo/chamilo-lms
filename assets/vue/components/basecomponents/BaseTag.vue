<template>
  <Tag :severity="type" :value="label" />
</template>

<script setup>
import Tag from 'primevue/tag';

defineProps({
  type: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
});
</script>
