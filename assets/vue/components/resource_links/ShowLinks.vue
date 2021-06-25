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
          {{ $t('Group') }}: {{ link.group.resourceNode.title }}
        </div>

        <div v-if="link['userGroup']">
          {{ $t('Class') }}: {{ link.userGroup.resourceNode.title }}
        </div>

        <div v-if="link['user']">
          {{ $t('User') }}: {{ link.user.username }}
        </div>
        <div>
        {{ $t('Status') }}: {{ link.visibilityName }}
        </div>
      </li>
    </ul>
  </div>
</template>

<script>

import {computed, ref, toRefs} from "vue";
import isEmpty from "lodash/isEmpty";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";

export default {
  name: 'ShowLinks',
  setup (props) {
    //const { item } = toRefs(props);

    return {
    };
  },
  props: {
    item: {
      type: Object,
      required: true
    },
  }
};
</script>
