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

<script>

import PageCard from './PageCard.vue';

export default {
  name: 'PageCardList',
  components: {
    PageCard
  },
  props: {
    pages: Array,
  },
};
</script>
