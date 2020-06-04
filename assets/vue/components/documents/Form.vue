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
          <v-file-input v-if="typeIsFile" v-model="item.resourceFile" show-size label="File input"></v-file-input>
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
  name: 'DocumentsForm',
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
      title: null,
      parentResourceNode: null,
      resourceFile: null
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
      title: {
        required,
      },
      parentResourceNode: {
      },
      resourceFile: {
      }
    }
  }
};
</script>
