<template>
  <div>
    <h1 class="text-xl font-bold">Upload Corrections for Assignment {{ id }}</h1>
  </div>
</template>

<script setup>
defineProps({
  id: [String, Number],
});
</script>
