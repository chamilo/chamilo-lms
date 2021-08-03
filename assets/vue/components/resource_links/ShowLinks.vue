<template>
  <div v-if="item && item['resourceLinkListFromEntity']">
    <v-card>
      <v-list-item
          v-for="link in item['resourceLinkListFromEntity']"
      >
        <v-list-item-content>
          <div v-if="link['course']">
            {{ $t('Course') }}: {{ link.course.resourceNode.title }}
          </div>

          <div v-if="link['session']">
            {{ $t('Session') }}: {{ link.session.name }}
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
          <div v-if="showStatus">
            {{ $t('Status') }}: {{ link.visibilityName }}
          </div>
        </v-list-item-content>

      </v-list-item>
    </v-card>
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
    showStatus: {
      type: Boolean,
      required: false,
      default: true
    }
  }
};
</script>
