<template>
  <div v-if="item && item['resourceLinkListFromEntity']">
    <v-card>
      <v-list-item
          v-for="link in item['resourceLinkListFromEntity']"
      >
        <v-list-item-content>
          <div v-if="link['course']">
            <v-icon icon="mdi-book"/>
            {{ $t('Course') }}: {{ link.course.resourceNode.title }}
          </div>

          <div v-if="link['session']">
            <v-icon icon="mdi-book-open"/>
            {{ $t('Session') }}: {{ link.session.name }}
          </div>

          <div v-if="link['group']">
            <v-icon icon="mdi-people"/>
            {{ $t('Group') }}: {{ link.group.resourceNode.title }}
          </div>

          <div v-if="link['userGroup']">
            {{ $t('Class') }}: {{ link.userGroup.resourceNode.title }}
          </div>

          <div v-if="link['user']">
            <v-icon icon="mdi-account"/>
            <!--          <q-avatar size="32px">-->
            <!--            <img :src="link.user.illustrationUrl + '?w=80&h=80&fit=crop'" />-->
            <!--          </q-avatar>-->
            {{ $t('User') }}: {{ link.user.username }}
          </div>

          <div v-if="showStatus">
            {{ $t('Status') }}: {{ link.visibilityName }}
          </div>

          <q-select
              filled
              v-model="link.visibility"
              :options="visibilityList"
              label="Status"
              emit-value
              map-options
              v-if="editStatus"
          />

        </v-list-item-content>
      </v-list-item>
    </v-card>
  </div>
</template>
<script>

import {toRefs} from "vue";

export default {
  name: 'ShowLinks',
  setup (props) {
    const visibilityList = [
      {value: 2, label: 'Published'},
      {value: 0, label: 'Draft'},
    ];

    const { editStatus } = toRefs(props);

    return {
      visibilityList,
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
    },
    editStatus: {
      type: Boolean,
      required: false,
      default: false
    }
  }
};
</script>
