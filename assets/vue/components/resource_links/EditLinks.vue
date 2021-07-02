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
          {{ $t('User') }}:
<!--          <q-avatar size="32px">-->
<!--            <img :src="link.user.illustrationUrl + '?w=80&h=80&fit=crop'" />-->
<!--          </q-avatar>-->
          {{ link.user.username }}
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

  <VueMultiselect
      placeholder="Share with User"
      v-model="selectedUsers"
      :loading="isLoading"
      :options="users"
      :multiple="true"
      :searchable="true"
      :internal-search="false"
      @search-change="asyncFind"
      limit-text="3"
      limit="3"
      label="username"
      track-by="id"
  />

  <q-btn
      no-caps
      class="btn btn-primary"
      @click="addUser"
  >
    <v-icon icon="mdi-cloud-upload"/>
    Share
  </q-btn>

</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>

import {computed, ref, toRefs} from "vue";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import VueMultiselect from 'vue-multiselect'
import isEmpty from 'lodash/isEmpty';
export default {
  name: 'EditLinks',
  components: {
    VueMultiselect,
  },
  props: {
    item: {
      type: Object,
      required: true
    },
  },
  setup (props) {
    const visibilityList = [
      {value: 2, label: 'Published'},
      {value: 0, label: 'Draft'},
    ];

    const users = ref([]);
    const selectedUsers = ref([]);
    const isLoading = ref(false);

    function addUser() {
      selectedUsers.value.forEach(userResult => {
        if (isEmpty(props.item.resourceLinkListFromEntity)) {
          props.item.resourceLinkListFromEntity = [];
        }
        props.item.resourceLinkListFromEntity.push(
            {
              uid: userResult.id,
              visibility: 2
            }
        );
      });
    }

    function asyncFind (query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoading.value = true;
      axios.get(ENTRYPOINT + 'users', {
        params: {
          username: query
        }
      }).then(response => {
        isLoading.value = false;
        let data = response.data;
        users.value = data['hydra:member'];
      }).catch(function (error) {
        isLoading.value = false;
        console.log(error);
      });
    }

    return {v$: useVuelidate(), visibilityList, users, selectedUsers, asyncFind, addUser, isLoading};
  },
};
</script>
