<template>
  <b-row>
    <b-col
      cols="12"
      sm="6"
      md="6"
    >
      <div v-if="item">
        <div v-if="item['resourceLinkList']">
          <ul>
            <li
              v-for="link in item['resourceLinkList']"
            >
              <div v-if="link['course']">
                Course: {{ link.course.resourceNode.title }}
              </div>

              <div v-if="link['session']">
                Session: {{ link.session.resourceNode.title }}
              </div>

              <b-form-select
                v-model="link.visibility"
                :options="visibilityList"
                label="Status"
                persistent-hint
              />
            </li>
          </ul>
        </div>
      </div>
    </b-col>
  </b-row>
</template>

<script>
import has from 'lodash/has';
import { validationMixin } from 'vuelidate';
import { required } from 'vuelidate/lib/validators';

export default {
  name: 'ResourceLinkForm',
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
            // See ResourceLink entity constants.
            visibilityList: [
                {value: 2, text: 'Published'},
                {value: 0, text: 'Draft'},
            ],
        };
    },
  computed: {
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
    }
  }
};
</script>
