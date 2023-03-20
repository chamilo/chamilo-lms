<template>
  <v-form>
    <v-container fluid>
      <v-row>
        <v-col cols="12" sm="6" md="6">
          <v-text-field
                  v-model="item.title"
                  :error-messages="titleErrors"
                  :label="$t('Title')"
                  required
                  @input="$v.item.title.$touch()"
                  @blur="$v.item.title.$touch()"
          />
        </v-col>

        <v-col cols="12" sm="6" md="6">
          <v-text-field
                  v-model="item.code"
                  :error-messages="codeErrors"
                  :label="$t('code')"
                  required
                  @input="$v.item.code.$touch()"
                  @blur="$v.item.code.$touch()"
          />
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12" sm="6" md="6">
          <v-combobox
                  v-model="item.category"
                  :items="categorySelectItems"
                  :error-messages="categoryErrors"
                  :no-data-text="$t('No results')"
                  :label="$t('category')"
                  item-text="name"
                  item-value="@id"
          />
        </v-col>

        <v-col cols="12" sm="6" md="6">
          <v-text-field
                  v-model.number="item.visibility"
                  :error-messages="visibilityErrors"
                  :label="$t('visibility')"
                  required
                  @input="$v.item.visibility.$touch()"
                  @blur="$v.item.visibility.$touch()"
          />
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script>
  import has from 'lodash/has';
  import useVuelidate from '@vuelidate/core';
  import { required } from '@vuelidate/validators';
  import { mapActions } from 'vuex';
  import { mapFields } from 'vuex-map-fields';

  export default {
    name: 'CourseForm',
    setup () {
      return { v$: useVuelidate() }
    },
    props: {
      values: {
        type: Object,
        required: true
      },

      errors: {
        type: Object,
        default: () => {}
      },

      initialValues: {
        type: Object,
        default: () => {}
      }
    },
    data() {
      return {
        title: null,
        code: null,
        category: null,
        visibility: null,
      };
    },
    computed: {
      ...mapFields('coursecategory', {
        categorySelectItems: 'selectItems'
      }),

      // eslint-disable-next-line
      item() {
        return this.initialValues || this.values;
      },

      titleErrors() {
        const errors = [];

        if (!this.$v.item.title.$dirty) return errors;

        has(this.violations, 'title') && errors.push(this.violations.title);

        !this.$v.item.title.required && errors.push(this.$t('Field is required'));

        return errors;
      },
      codeErrors() {
        const errors = [];

        if (!this.$v.item.code.$dirty) return errors;

        has(this.violations, 'code') && errors.push(this.violations.code);

        !this.$v.item.code.required && errors.push(this.$t('Field is required'));

        return errors;
      },
      categoryErrors() {
        const errors = [];

        if (!this.$v.item.category.$dirty) return errors;

        has(this.violations, 'category') && errors.push(this.violations.category);


        return errors;
      },
      visibilityErrors() {
        const errors = [];

        if (!this.$v.item.visibility.$dirty) return errors;

        has(this.violations, 'visibility') && errors.push(this.violations.visibility);

        !this.$v.item.visibility.required && errors.push(this.$t('Field is required'));

        return errors;
      },

      violations() {
        return this.errors || {};
      }
    },
    mounted() {
      this.categoryGetSelectItems();
    },
    methods: {
      ...mapActions({
        categoryGetSelectItems: 'coursecategory/load'
      }),
    },
    validations: {
      item: {
        title: {
          required,
        },
        code: {
          required,
        },
        category: {
        },
        visibility: {
          required,
        },
      }
    }
  };
</script>
