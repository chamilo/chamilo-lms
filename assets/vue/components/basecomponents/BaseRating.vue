<script setup>
import Rating from "primevue/rating"

const model = defineModel({
  type: Number,
})

defineProps({
  stars: {
    type: Number,
    default: 5,
    require: false,
  },
})

const emit = defineEmits(["change"])
</script>

<template>
  <Rating
    v-model="model"
    :cancel="false"
    :stars="stars"
    @change="emit('change', $event, model)"
  />
</template>
