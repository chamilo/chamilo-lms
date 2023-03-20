<template>
  <v-form>
    <v-container fluid>
      <v-row>
        <v-col cols="12" sm="6" md="6">
          <v-text-field
                  v-model="item.name"
                  :error-messages="nameErrors"
                  :label="$t('name')"
                  required
                  @input="$v.item.name.$touch()"
                  @blur="$v.item.name.$touch()"
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

    </v-container>
  </v-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';

export default {
  name: 'CourseCategoryForm',
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
  mounted() {
  },
  data() {
    return {
    };
  },
  computed: {

    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values;
    },

    nameErrors() {
      const errors = [];

      if (!this.$v.item.name.$dirty) return errors;

      has(this.violations, 'name') && errors.push(this.violations.name);

      !this.$v.item.name.required && errors.push(this.$t('Field is required'));

      return errors;
    },
    codeErrors() {
      const errors = [];

      if (!this.$v.item.code.$dirty) return errors;

      has(this.violations, 'code') && errors.push(this.violations.code);

      !this.$v.item.code.required && errors.push(this.$t('Field is required'));

      return errors;
    },


    violations() {
      return this.errors || {};
    }
  },
  methods: {
  },
  validations: {
    item: {
       name: {
          required,
        },
      code: {
        required,
      },
    }
  }
};
</script>
