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

      <v-col cols="12" sm="6" md="6">
        <v-text-field
          v-model="item.code"
          :label="$t('code')"
          type="text"
        />
      </v-col>
    </v-row>

    <v-row>
      <v-col cols="12" sm="6" md="6">
        <v-combobox
              v-model="item.category"
              :items="categorySelectItems"
              :no-data-text="$t('No results')"
              :label="$t('category')"
              item-text="name"
              item-value="@id"
          chips
        />
      </v-col>
    
      <v-row cols="12"></v-row>
    </v-row>

  </v-container>
</template>

<script>

import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'CourseFilter',
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
    this.categoryGetSelectItems();
  },
  methods: {
    ...mapActions({
      categoryGetSelectItems: 'coursecategory/fetchSelectItems'
    }),
  },

  computed: {
    ...mapFields('coursecategory', {
      categorySelectItems: 'selectItems'
    }),

    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values;
    }
  }
};
</script>
