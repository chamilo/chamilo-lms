<template>
  <b-row>
    <b-col cols="12">
      <b-form-input
        v-model="item.title"
        :placeholder="$t('title')"
        type="text"
      />
    </b-col>
  </b-row>
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
