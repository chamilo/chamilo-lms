<template>

        <q-input
          id="item_title"
          v-model="item.title"
          :error-messages="titleErrors"
          :placeholder="$t('Title')"
          required
          @input="$v.item.title.$touch()"
          @blur="$v.item.title.$touch()"
        />

</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
//import { mapActions } from 'vuex';
//import { mapFields } from 'vuex-map-fields';

export default {
  name: 'DocumentsForm',
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
    },
  },
  data() {
    return {
      title: null,
      parentResourceNodeId: null,
    };
  },
  computed: {
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
    violations() {
      return this.errors || {};
    }
  },
  created () {
  },
  methods: {
  },
  validations: {
    item: {
      title: {
        required,
      },
      parentResourceNodeId: {
      },
    }
  }
};
</script>
