<template>
  <div
    v-if="isLoading"
    class="t-3 grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-4"
  >
    <Skeleton v-for="el in 4" :key="el" class="h-44 xl:h-60"/>
  </div>
</template>

<script setup>
import Skeleton from "primevue/skeleton"

defineProps({
  isLoading: {
    type: Boolean,
    required: true,
  }
})
</script>
