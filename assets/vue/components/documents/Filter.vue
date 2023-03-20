<template>
    <q-input
      id="search_filter"
      v-model="item.title"
      :placeholder="$t('title')"
      type="text"
    />
</template>

<script>

export default {
  name: 'DocumentsFilter',
  props: {
    values: {
      type: Object,
      required: true
    }
  },
  data() {
    return {};
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    }
  },
  mounted() {
  },
  methods: {
  }
};
</script>
