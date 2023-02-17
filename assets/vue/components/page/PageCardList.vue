<template>
  <div
    v-for="page in pages"
    :key="page.id"
  >
    <PageCard
      :page="page"
    />
  </div>
</template>

<script setup>
import PageCard from './PageCard.vue';

defineProps({
  pages: {
    type: Array,
    default () {
      return [];
    }
  },
});
</script>
