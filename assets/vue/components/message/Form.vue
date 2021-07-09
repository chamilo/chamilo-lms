<template>
  <q-form>
    <q-input
        id="item_title"
        v-model="item.title"
        :placeholder="$t('Title')"
        :error="v$.item.title.$error"
        @input="v$.item.title.$touch()"
        @blur="v$.item.title.$touch()"
        :error-message="titleErrors"
    />
    <slot></slot>
  </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';

export default {
  name: 'MessageForm',
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
      receivers: []
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    receiversErrors() {
      const errors = [];
      if (!this.v$.item.receivers.$dirty) return errors;
      has(this.violations, 'receivers') && errors.push(this.violations.receivers);

      if (this.v$.item.receivers.required) {
        return this.$t('Field is required')
      }

      return errors;
    },
    titleErrors() {
      const errors = [];
      if (!this.v$.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);

      if (this.v$.item.title.required) {
        return this.$t('Field is required')
      }

      return errors;
    },

    violations() {
      return this.errors || {};
    }
  },
  validations: {
    item: {
      title: {
        required,
      },
      receivers: {
        required,
      },
      content: {
        required,
      },
    }
  }
};
</script>
