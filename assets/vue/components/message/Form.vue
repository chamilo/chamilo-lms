<template>
  <q-form>
    <v-container>
      <v-row>
        <v-col md="9">
          <q-input
            id="item_title"
            v-model="item.title"
            :error="v$.item.title.$error"
            :error-message="titleErrors"
            :placeholder="$t('Title')"
            @blur="v$.item.title.$touch()"
            @input="v$.item.title.$touch()"
          />
          <slot></slot>
        </v-col>
        <v-col md="3">
          <div v-text="$t('Atachments')" class="text-h6"/>

          <AudioRecorder></AudioRecorder>
        </v-col>
      </v-row>
    </v-container>
  </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import AudioRecorder from "../AudioRecorder";

export default {
  name: 'MessageForm',
  components: {AudioRecorder},
  setup() {
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
      receiversTo: [],
      receiversCc: [],
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    receiversErrors() {
      const errors = [];
      if (!this.v$.item.receiversTo.$dirty) return errors;
      has(this.violations, 'receiversTo') && errors.push(this.violations.receiversTo);

      if (this.v$.item.receiversTo.required) {
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
      receiversTo: {
        required,
      },
      /*receiversCc: {
        required,
      },*/
      content: {
        required,
      },
    }
  }
};
</script>
