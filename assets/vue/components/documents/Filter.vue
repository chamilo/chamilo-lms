<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12" sm="6" md="6">
        <v-text-field
          v-model="item.title"
          :label="$t('title')"
          type="text"
        />
      </v-col>

      <v-row cols="12"></v-row>
    </v-row>

  </v-container>
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
  mounted() {
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    }
  },
  methods: {
  }
};
</script>
