<template>
  <v-row>
    <v-col
      cols="12"
      sm="6"
      md="6"
    >
      <div v-if="item">
        <div v-if="item['resourceLinkListFromEntity']">
          <ul>
            <li
              v-for="link in item['resourceLinkListFromEntity']"
            >
              <div v-if="link['course']">
                {{ $t('Course') }}:  {{ link.course.resourceNode.title }}
              </div>

              <div v-if="link['session']">
                {{ $t('Session') }}:  {{ link.session.name }}
              </div>

              <div v-if="link['group']">
                {{ $t('Group') }}: {{ link.session.resourceNode.title }}
              </div>

              <v-select
                v-model="link.visibility"
                :options="visibilityList"
                label="Status"
                persistent-hint
              />
            </li>
          </ul>
        </div>
      </div>
    </v-col>
  </v-row>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';

export default {
  name: 'ResourceLinkForm',
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
