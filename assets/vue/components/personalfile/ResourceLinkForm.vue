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
          filled
          v-model="link.visibility"
          :options="visibilityList"
          label="Status"
          emit-value
          map-options
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
    const visibilityList = [
      {value: 2, label: 'Published'},
      {value: 0, label: 'Draft'},
    ];
    return {v$: useVuelidate(), visibilityList};
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
  computed: {
    item() {
      return this.initialValues || this.values;
    },
  },
};
</script>
