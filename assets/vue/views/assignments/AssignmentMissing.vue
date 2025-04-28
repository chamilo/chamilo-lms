<template>
  <div>
    <h1 class="text-xl font-bold">Users Without Submission for Assignment {{ id }}</h1>
  </div>
</template>

<script setup>
defineProps({
  id: [String, Number],
});
</script>
