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
import {mapGetters, useStore} from "vuex";

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
    },
    linksType: {
      type: String,
      required: true,
      default: 'user'
    }
  },
  setup(props) {
    const users = ref([]);
    const selectedUsers = ref([]);
    const isLoading = ref(false);
    const store = useStore();

    function addUser(userResult) {
      if (isEmpty(props.item.resourceLinkListFromEntity)) {
        props.item.resourceLinkListFromEntity = [];
      }

      const someLink = props.item.resourceLinkListFromEntity.some(link => link.user.username === userResult.username);

      if (someLink) {
        return;
      }

      props.item.resourceLinkListFromEntity.push(
          {
            uid: userResult.id,
            user: {username: userResult.username},
            visibility: 2
          }
      );
    }

    function findUsers(query) {
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

    function findUserRelUsers(query) {
      const currentUser = computed(() => store.getters['security/getUser']);

      axios
          .get(ENTRYPOINT + 'user_rel_users', {
            params: {
              user: currentUser.value['id'],
              'friend.username': query
            }
          })
          .then(response => {
            isLoading.value = false;

            users.value = response.data['hydra:member'].map(member => member.friend);
          })
          .catch(function (error) {
            isLoading.value = false;
          });
    }

    function asyncFind(query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoading.value = true;

      switch (props.linksType) {
        case 'users':
          findUsers(query);
          break;

        case 'user_rel_users':
          findUserRelUsers(query);
          break;
      }
    }

    return {v$: useVuelidate(), users, selectedUsers, asyncFind, addUser, isLoading};
  },
  computed: {
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    })
  }
};
</script>
