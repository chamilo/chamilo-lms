<template>
  <b-form>
    <b-row>
      <b-col
        cols="12"
        sm="6"
        md="6"
      >
        <b-form-file
          v-model="item.uploadFile"
          show-size
          placeholder="File upload"
          drop-placeholder="Drop file here..."
        />
      </b-col>
    </b-row>
  </b-form>
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
    }
  },
  data() {
    return {
      parentResourceNodeId: null,
      uploadFile: null,
      resourceLinkList: null,
      filetype: null
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
  methods: {
  },
  validations: {
    item: {
      parentResourceNodeId: {
      },
      uploadFile: {
      },
      resourceLinkList:{
      },
      filetype:{
      }
    }
  }
};
</script>
