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



  <!--  multiple="multiple"-->
  <!--  searchable="true"-->
  <VueMultiselect
      placeholder="Share with User"
      v-model="selectedUsers"
      :options="users"
      :multiple="true"
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

    //const { item } = toRefs(props);
    const users = ref([]);
    const selectedUsers = ref([]);

    //const { item } = toRefs(props);
    //const item = props.item;

    /*const item = computed(
        () => props.item
    );*/

    /*const itemProp = computed(
        () => item
    );*/

    console.log('2222');
    console.log(props.item);


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

      axios.get(ENTRYPOINT + 'users', {
        params: {
          username: query
        }
      }).then(response => {
        let data = response.data;
        data['hydra:member'].forEach(userResult => {

          if (users.value.indexOf(userResult) >= 0) {
            return;
          }

          users.value.push(userResult);
        });
      }).catch(function (error) {
        console.log(error);
      });
    }

    return {v$: useVuelidate(), visibilityList, users, selectedUsers, asyncFind, addUser};
  },
};
</script>
