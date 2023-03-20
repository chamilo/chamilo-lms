<template>
  <Toolbar  >
    <template v-slot:right>

      <v-btn
          tile
          icon
          :loading="isLoading"
          :to="'/resources/friends/add'"
      >
        <v-icon icon="mdi-account-plus-outline" />
      </v-btn>

        <v-btn
            tile
            icon
            :loading="isLoading"
            @click="reloadHandler"
        >
          <v-icon icon="mdi-refresh" />
        </v-btn>

         <v-btn
            tile
            icon
            @click="confirmDeleteMultiple"
            :class="[ !selectedItems || !selectedItems.length ? 'hidden': '']"
         >
          <v-icon icon="mdi-delete" />
        </v-btn>
<!--        :disabled="!selectedItems || !selectedItems.length"-->

    </template>
  </Toolbar>

  <div v-if="friendRequests.length">
    <div class="text-h4 q-mb-md">
      Requests
    </div>

    <v-card
        v-for="request in friendRequests"
    >
      <q-avatar size="40px">
        <img :src="request.user.illustrationUrl + '?w=80&h=80&fit=crop'" />
      </q-avatar>
      {{ request.user.username }}

      <v-btn
          tile
          icon
          @click="addFriend(request)" >
        <v-icon icon="mdi-plus" />
      </v-btn>
    </v-card>
  </div>

  <v-card
      v-for="request in waitingRequests"
  >
    <q-avatar size="40px">
      <img :src="request.friend.illustrationUrl + '?w=80&h=80&fit=crop'" />
    </q-avatar>
    {{ request.friend.username }}

    <v-chip>Waiting</v-chip>
  </v-card>

  <div class="flex flex-row pt-2">
    <div class="w-full">
      <div class="text-h4 q-mb-md">Friends</div>

<!--      :loading="isLoading"-->
      <DataTable
        class="p-datatable-sm"
        :value="items"
        v-model:selection="selectedItems"
        dataKey="id"
        v-model:filters="friendFilter"
        sortBy="sendDate"
        sortOrder="asc"
        :lazy="true"
        :paginator="false"
        :totalRecords="totalItems"
        @page="onPage($event)"
        @sort="sortingChanged($event)"
        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        responsiveLayout="scroll"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
      >

      <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

      <Column field="sender" :header="$t('User')" :sortable="false">
        <template #body="slotProps">
          <q-avatar size="40px">
            <img :src="slotProps.data.friend.illustrationUrl + '?w=80&h=80&fit=crop'" />
          </q-avatar>
            {{ slotProps.data.friend.username }}
        </template>
      </Column>

      <Column field="createdAt" :header="$t('Sent date')" :sortable="true">
        <template #body="slotProps">
          {{ $filters.relativeDatetime(slotProps.data.createdAt) }}
        </template>
      </Column>

      <Column :exportable="false">
        <template #body="slotProps">
<!--          class="flex flex-row gap-2"-->
<!--            <v-icon   v-if="slotProps.data.relationType == 3" icon="mdi-check" />-->
          <v-btn
              tile
              icon
              @click="confirmDeleteItem(slotProps.data)" >
            <v-icon icon="mdi-delete" />
          </v-btn>
        </template>

      </Column>
    </DataTable>
    </div>
  </div>

  <Dialog v-model:visible="itemDialog" :style="{width: '450px'}" :header="$t('New folder')" :modal="true" class="p-fluid">
    <div class="p-field">
      <label for="name">{{ $t('Name') }}</label>
      <InputText
          autocomplete="off"
          id="title"
          v-model.trim="item.title"
          required="true"
          autofocus
          :class="{'p-invalid': submitted && !item.title}"
      />
      <small class="p-error" v-if="submitted && !item.title">$t('Title is required')</small>
    </div>

    <template #footer>
      <Button label="Cancel" icon="pi pi-times" class="p-button-text" @click="hideDialog"/>
      <Button label="Save" icon="pi pi-check" class="p-button-text" @click="saveItem" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteItemDialog" :style="{width: '450px'}" header="Confirm" :modal="true">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">Are you sure you want to delete <b>{{item.title}}</b>?</span>
    </div>
    <template #footer>
      <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteItemDialog = false"/>
      <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteItemButton(item)" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteMultipleDialog" :style="{width: '450px'}" header="Confirm" :modal="true">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">Are you sure you want to delete the selected items?</span>
    </div>
    <template #footer>
      <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteMultipleDialog = false"/>
      <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteMultipleItems" />
    </template>
  </Dialog>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from '../../components/documents/ResourceFileIcon.vue';
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue';

import DataFilter from '../../components/DataFilter';
import DocumentsFilterForm from '../../components/documents/Filter';
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility";

export default {
  name: 'UserRelUserList',
  servicePrefix: 'userreluser',
  components: {
    Toolbar,
  },
  mixins: [ListMixin],
  setup() {
    const store = useStore();
    const user = store.getters["security/getUser"];
    const isLoadingSelect = ref(false);
    const deleteItemDialog = ref(false);
    const item = ref({});
    const friendRequests = ref([]);
    const waitingRequests = ref([]);

    const friendRequestFilter = {
      friend: user.id,
      relationType: 10  // friend request
    };

    const waitingFilter = {
      user: user.id,
      relationType: 10
    };

    const friendFilter = {
      user: user.id,
      relationType: 3, // friend status
    };

    function addFriend(friend) {
      // Change from request to friend
      axios.put(friend['@id'], {
        relationType: 3,
      }).then(response => {
        console.log(response);
        reloadHandler();
      }).catch(function (error) {
        console.log(error);
      });
    }

    function reloadHandler() {
      store.dispatch('userreluser/resetList');
      store.dispatch('userreluser/fetchAll', friendFilter);
      store.dispatch('userreluser/findAll', friendRequestFilter).then(response => {
        friendRequests.value = response;
      });
      store.dispatch('userreluser/findAll', waitingFilter).then(response => {
        waitingRequests.value = response;
      });
    }

    function deleteItemButton(item) {
      store.dispatch('userreluser/del', item);
      deleteItemDialog.value = false;
      reloadHandler();
    }

    reloadHandler();

    return {
      addFriend,
      reloadHandler,
      deleteItemButton,
      item,
      friendRequests,
      waitingRequests,
      friendFilter,
      deleteItemDialog
    }
  },
  data() {
    return {
      columns: [
        { label: this.$i18n.t('User'), field: 'friend.username', name: 'friend', sortable: true},
        { label: this.$i18n.t('Sent'), field: 'createdAt', name: 'createdAt', sortable: true},
        { label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [10, 20, 50, this.$i18n.t('All')],
      selected: [],
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteMultipleDialog: false,
      submitted: false,
    };
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

    ...mapGetters('userreluser', {
      items: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('userreluser', {
      deletedItem: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  methods: {
    // prime
    onPage(event) {
      this.options.itemsPerPage = event.rows;
      this.options.page = event.page + 1;
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;
      this.filters = {
        user: this.currentUser.id,
        relationType: 3
      };
      this.onUpdateOptions(this.options);
    },
    sortingChanged(event) {
      console.log('sortingChanged');
      console.log(event);
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    hideDialog() {
      this.itemDialog = false;
      this.submitted = false;
    },
    saveItem() {
      this.submitted = true;

      if (this.item.title.trim()) {
        if (this.item.id) {
        } else {
          //this.products.push(this.product);
          this.item.filetype = 'folder';
          this.item.parentResourceNodeId = this.$route.params.node;
          this.item.resourceLinkList = JSON.stringify([{
            gid: this.$route.query.gid,
            sid: this.$route.query.sid,
            cid: this.$route.query.cid,
            visibility: RESOURCE_LINK_PUBLISHED, // visible by default
          }]);

          this.create(this.item);
          this.showMessage('Saved');
        }

        this.itemDialog = false;
        this.item = {};
      }
    },
    editItem(item) {
      this.item = {...item};
      this.itemDialog = true;
    },
    confirmDeleteItem(item) {
      this.item = item;
      this.deleteItemDialog = true;
    },
    confirmDeleteMultiple() {
      this.deleteMultipleDialog = true;
    },
    deleteMultipleItems() {
      this.deleteMultipleAction(this.selectedItems);
      this.onRequest({
        pagination: this.pagination,
      });
      this.deleteMultipleDialog = false;
      this.selectedItems = null;
      //this.onUpdateOptions(this.options);
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
