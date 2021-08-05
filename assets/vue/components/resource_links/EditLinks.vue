<template>
  <ShowLinks
      :edit-status="editStatus"
      :item="item"
      :show-status="showStatus"
  />

  <VueMultiselect
      v-model="selectedUsers"
      :internal-search="false"
      :loading="isLoading"
      :multiple="true"
      :options="users"
      :searchable="true"
      label="username"
      limit="3"
      limit-text="3"
      placeholder="Share with User"
      track-by="id"
      @select="addUser"
      @search-change="asyncFind"
  />
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>

import ShowLinks from "../../components/resource_links/ShowLinks.vue";
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
    ShowLinks
  },
  props: {
    item: {
      type: Object,
      required: true
    },
    editStatus: {
      type: Boolean,
      required: false,
      default: true
    },
    showStatus: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  setup(props) {
    const users = ref([]);
    const selectedUsers = ref([]);
    const isLoading = ref(false);

    function addUser(userResult) {
      if (isEmpty(props.item.resourceLinkListFromEntity)) {
        props.item.resourceLinkListFromEntity = [];
      }

      props.item.resourceLinkListFromEntity.push(
          {
            uid: userResult.id,
            user: {username: userResult.username},
            visibility: 2
          }
      );
    }

    function asyncFind(query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoading.value = true;

      axios
          .get(ENTRYPOINT + 'users', {
            params: {
              username: query
            }
          })
          .then(response => {
            isLoading.value = false;
            let data = response.data;
            users.value = data['hydra:member'];
          })
          .catch(function (error) {
            isLoading.value = false;
            console.log(error);
          });
    }

    return {v$: useVuelidate(), users, selectedUsers, asyncFind, addUser, isLoading};
  },
};
</script>
