<template>
  <div class="p-4">
    <img
      :src="block?.src || 'https://via.placeholder.com/150'"
      :alt="block?.alt || 'Image Block'"
      class="max-w-full rounded shadow"
    />
  </div>
</template>

<script setup>
defineProps({
  block: Object
})
</script>
