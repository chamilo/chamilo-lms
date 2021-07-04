<template>
  <Toolbar  >
    <template v-slot:right>
      <v-btn
          tile
          icon
          :loading="isLoading"
          @click="reloadHandler">
        <v-icon icon="mdi-account-plus-outline" />
      </v-btn>
    </template>
  </Toolbar>

  <div class="flex flex-row pt-2">
    <div class="w-full">
      <div class="text-h4 q-mb-md">Search</div>

      <VueMultiselect
          placeholder="Add"

          :loading="isLoadingSelect"
          :options="users"
          :multiple="true"
          :searchable="true"
          :internal-search="false"
          @search-change="asyncFind"

          @select="addFriend"
          limit-text="3"
          limit="3"
          label="username"
          track-by="id"
      />


    </div>
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import Toolbar from '../../components/Toolbar.vue';

import VueMultiselect from 'vue-multiselect'
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";

export default {
  name: 'UserRelUserAdd',
  servicePrefix: 'userreluser',
  components: {
    Toolbar,
    VueMultiselect
  },
  mixins: [ListMixin],
  setup() {
    const users = ref([]);
    const isLoadingSelect = ref(false);
    const store = useStore();
    const user = store.getters["security/getUser"];

    function asyncFind (query) {
      if (query.toString().length < 3) {
        return;
      }

      isLoadingSelect.value = true;
      axios.get(ENTRYPOINT + 'users', {
        params: {
          username: query
        }
      }).then(response => {
        isLoadingSelect.value = false;
        let data = response.data;
        users.value = data['hydra:member'];
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    function addFriend(friend) {
      axios.post(ENTRYPOINT + 'user_rel_users', {
        user: user['@id'],
        friend: friend['@id'],
        relationType: 10,
      }).then(response => {
        console.log(response);
        isLoadingSelect.value = false;
      }).catch(function (error) {
        isLoadingSelect.value = false;
        console.log(error);
      });
    }

    return {v$: useVuelidate(), users, asyncFind, addFriend, isLoadingSelect};
  },
  data() {
    return {
      selected: [],
      isBusy: false,
      options: {
        sortBy: 'sendDate',
        sortDesc: 'asc',
      },
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteMultipleDialog: false,
      item: {},
      submitted: false,
    };
  },
  mounted() {
    this.onUpdateOptions(this.options);
  },
  computed: {
    // From crud.js list function
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode'
    }),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),

    ...mapGetters('message', {
      items: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('message', {
      deletedItem: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  methods: {
    deleteItemButton() {
      console.log('deleteItem');
      this.deleteItem(this.item);
      //this.items = this.items.filter(val => val.iid !== this.item.iid);
      this.deleteItemDialog = false;
      this.item = {};
      this.onUpdateOptions(this.options);
    },
    onRowSelected(items) {
      this.selected = items
    },
    selectAllRows() {
      this.$refs.selectableTable.selectAllRows()
    },
    clearSelected() {
      this.$refs.selectableTable.clearSelected()
    },
    async deleteSelected() {
      this.deleteMultipleAction(this.selected);
      this.onRequest({
        pagination: this.pagination,
      });
    },
    //...actions,
    // From ListMixin
    ...mapActions('userreluser', {
      getPage: 'fetchAll',
      create: 'create',
      update: 'update',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
