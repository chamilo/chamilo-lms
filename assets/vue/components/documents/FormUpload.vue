<template>
  <v-form>
    <v-container fluid>
      <v-row>
        <v-col cols="12" sm="6" md="6">
          <v-file-input v-if="typeIsFile" v-model="item.resourceFile" show-size label="File upload"></v-file-input>
          <input type="hidden" v-model="item.parentResourceNode" />
        </v-col>
      </v-row>
    </v-container>
  </v-form>
</template>

<script>
import has from 'lodash/has';
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';
import { mapActions } from 'vuex';
import { mapFields } from 'vuex-map-fields';

export default {
  name: 'DocumentsFormUpload',
  mixins: [validationMixin],
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
    type: {
      type: String,
    }
  },
  created () {
  },
  data() {
    return {
      parentResourceNode: null,
      resourceFile: null,
    };
  },
  computed: {
    // eslint-disable-next-line
    item() {
      return this.initialValues || this.values;
    },
    titleErrors() {
      const errors = [];
      console.log('errors');
      if (this.typeIsFile) {
        console.log('empty');
        return errors;
      }

      if (!this.$v.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);
      !this.$v.item.title.required && errors.push(this.$t('Field is required'));

      return errors;
    },
    typeIsFile() {
      return this.type === 'file';
    },
    violations() {
      return this.errors || {};
    }
  },
  methods: {
  },
  validations: {
    item: {
      parentResourceNode: {
      },
      resourceFile: {
      }
    }
  }
};
</script>
