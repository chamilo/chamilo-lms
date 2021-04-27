<template>
  <div v-if="item && item['resourceLinkListFromEntity']">
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

        <q-separator />

        <q-select
          v-model="link.visibility"
          :options="visibilityList"
          emit-value
          label="Status"
          persistent-hint
        />
      </li>
    </ul>
  </div>
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
                {value: 2, label: 'Published'},
                {value: 0, label: 'Draft'},
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
  validations: {
    item: {
    }
  }
};
</script>
