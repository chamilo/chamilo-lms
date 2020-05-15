<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12" sm="6" md="6">
        <v-text-field
                v-model="item.name"
                :label="$t('name')"
                type="text"
        />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>

export default {
  name: 'CourseCategoryFilter',
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
    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values;
    }
  },
  methods: {
  }
};
</script>
