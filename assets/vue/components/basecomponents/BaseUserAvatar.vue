<template>
  <Avatar :image="`${imageUrl}?w=${imageSize}&h=${imageSize}&fit=crop`" :shape="shape" />
</template>

<script setup>
import Avatar from "primevue/avatar";
import { computed } from "vue";

const props = defineProps({
  imageUrl: {
    type: String,
    require: true,
    default: "",
  },
  size: {
    type: String,
    require: false,
    default: "normal",
    validator: (value) => ["normal", "large", "xlarge"].includes(value),
  },
  shape: {
    type: String,
    require: false,
    default: "circle",
    validator: (value) => ["circle", "square"].includes(value),
  },
});

const imageSize = computed(() => {
  if ('large' === props.size) {
    return 56;
  }

  if ('xlarge' === props.size) {
    return 42;
  }

  return 28;
});
</script>
