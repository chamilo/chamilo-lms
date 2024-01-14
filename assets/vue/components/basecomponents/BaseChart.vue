<template>
  <PrimeChart :data="data" :options="{}" class="w-full md:w-30rem" type="pie" />
</template>

<script setup>
import PrimeChart from "primevue/chart";

defineProps({
  data: {
    type: Object,
    required: true,
  },
});
</script>
