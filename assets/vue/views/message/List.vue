<template>
  <Toolbar>
    <template v-slot:right>
      <v-btn
        icon
        tile
        @click="composeHandler">
        <v-icon icon="mdi-email-plus-outline" />
      </v-btn>

      <v-btn
        :loading="isLoading"
        icon
        tile
        @click="reloadHandler">
        <v-icon icon="mdi-refresh" />
      </v-btn>

      <v-btn
        :class="[ !selectedItems || !selectedItems.length ? 'hidden': '']"
        icon
        tile
        @click="confirmDeleteMultiple"
      >
        <v-icon icon="mdi-delete" />
      </v-btn>
      <!--        :disabled="!selectedItems || !selectedItems.length"-->

      <v-btn
        :class="[ !selectedItems || !selectedItems.length ? 'hidden': '']"
        icon
        tile
        @click="markAsReadMultiple"
      >
        <v-icon icon="mdi-email" />
      </v-btn>

      <v-btn
        :class="[ !selectedItems || !selectedItems.length ? 'hidden': '']"
        icon
        tile
        @click="markAsUnReadMultiple"
      >
        <v-icon icon="mdi-email-open" />
      </v-btn>

    </template>
  </Toolbar>

  <div class="flex flex-row pt-2">
    <div class="w-1/5 ">
      <v-card
        max-width="300"
        tile
      >
        <v-list dense>
          <!--      v-model="selectedItem"-->
          <v-list-item-group
            color="primary"
          >
            <v-list-item @click="goToInbox">
              <v-list-item-icon>
                <v-icon icon="mdi-inbox"></v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title>Inbox</v-list-item-title>
              </v-list-item-content>
            </v-list-item>

            <v-list-item @click="goToSent">
              <v-list-item-icon>
                <v-icon icon="mdi-send-outline"></v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title>Sent</v-list-item-title>
              </v-list-item-content>
            </v-list-item>

            <v-list-item @click="goToUnread">
              <v-list-item-icon>
                <v-icon icon="mdi-email-outline"></v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title>Unread</v-list-item-title>
              </v-list-item-content>
            </v-list-item>
            <v-list-item
              v-for="(tag, i) in tags"
              :key="i"
              @click="goToTag(tag)"
            >
              <v-list-item-icon>
                <v-icon icon="mdi-label-outline"></v-icon>
              </v-list-item-icon>
              <v-list-item-content>
                <v-list-item-title v-text="tag.tag"></v-list-item-title>
              </v-list-item-content>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-card>
    </div>
    <div class="w-4/5 pl-4">
      <div class="text-h4 q-mb-md">{{ title }}</div>
      <DataTable
        v-model:filters="filters"
        v-model:selection="selectedItems"
        :global-filter-fields="['title', 'sendDate']"
        :lazy="true"
        :loading="isLoading"
        :paginator="true"
        :rows="10"
        :rows-per-page-options="[5, 10, 20, 50]"
        :totalRecords="totalItems"
        :value="items"
        class="p-datatable-sm"
        current-page-report-template="Showing {first} to {last} of {totalRecords}"
        dataKey="id"
        filter-display="menu"
        paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsive-layout="scroll"
        sortBy="sendDate"
        sort-order="asc"
        @page="onPage($event)"
        @sort="sortingChanged($event)">

        <Column :exportable="false" selectionMode="multiple" style="width: 3rem"></Column>

        <Column :header="$t('From')" :sortable="false" field="sender">
          <template #body="slotProps">
            <q-avatar size="40px">
              <img :src="slotProps.data.sender.illustrationUrl + '?w=80&h=80&fit=crop'" />
            </q-avatar>

            <a
              v-if="slotProps.data"
              :class="{ 'font-semibold': index == 'inbox' && !slotProps.data.firstReceiver.read }"
              class="cursor-pointer"
              @click="showHandler(slotProps.data)"
            >
              {{ slotProps.data.sender.username }}
            </a>

          </template>
        </Column>

        <Column :header="$t('Title')" :sortable="false" field="title">
          <template #body="slotProps">
            <a
              v-if="slotProps.data"
              :class="{ 'font-semibold': index == 'inbox' &&  !slotProps.data.firstReceiver.read }"
              class="cursor-pointer"
              @click="showHandler(slotProps.data)"
            >
              {{ slotProps.data.title }}
            </a>

            <div
              v-if="index == 'inbox' && slotProps.data.firstReceiver"
              class="flex flex-row"
            >
              <v-chip v-for="tag in slotProps.data.firstReceiver.tags">
                {{ tag.tag }}
              </v-chip>
            </div>
          </template>

          <!--         <template #filter="{filterModel}">-->
          <!--           <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Search by name"/>-->
          <!--         </template>-->
          <!--         -->

          <!--      <template #filter="{filterModel}">-->
          <!--        <InputText type="text" v-model="filterModel.value" class="p-column-filter" placeholder="Search by title"/>-->
          <!--      </template>-->
          <!--      <template #filterclear="{filterCallback}">-->
          <!--        <Button type="button" icon="pi pi-times" @click="filterCallback()" class="p-button-secondary"></Button>-->
          <!--      </template>-->
          <!--      <template #filterapply="{filterCallback}">-->
          <!--        <Button type="button" icon="pi pi-check" @click="filterCallback()" class="p-button-success"></Button>-->
          <!--      </template>-->
        </Column>

        <Column :header="$t('Send date')" :sortable="false" field="sendDate">
          <template #body="slotProps">
            {{ $luxonDateTime.fromISO(slotProps.data.sendDate).toRelative() }}
          </template>
        </Column>

        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex flex-row gap-2">
              <v-btn
                icon
                tile
                @click="confirmDeleteItem(slotProps.data)">
                <v-icon icon="mdi-delete" />
              </v-btn>
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </div>

  <!--  Dialogs-->

  <Dialog
    v-model:visible="itemDialog"
    :header="$t('New folder')"
    :modal="true"
    :style="{width: '450px'}"
    class="p-fluid"
  >
    <div class="p-field">
      <label for="name">{{ $t('Name') }}</label>
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{'p-invalid': submitted && !item.title}"
        autocomplete="off"
        autofocus
        required="true"
      />
      <small
        v-if="submitted && !item.title"
        class="p-error"
      >
        $t('Title is required')
      </small>
    </div>

    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Save"
        @click="saveItem"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteItemDialog"
    :modal="true"
    :style="{width: '450px'}"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">Are you sure you want to delete <b>{{ item.title }}</b>?</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteItemDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteItemButton(item)"
      />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteMultipleDialog" :modal="true" :style="{width: '450px'}" header="Confirm">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">Are you sure you want to delete the selected items?</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import {mapFields} from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
import Toolbar from '../../components/Toolbar.vue';
import {ref} from 'vue';
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility";
import {MESSAGE_TYPE_INBOX} from "../../components/message/msgType";
import useNotification from "../../components/Notification";

import {useI18n} from "vue-i18n";

export default {
  name: 'MessageList',
  servicePrefix: 'Message',
  components: {
    Toolbar,
    ActionCell,
  },
  mixins: [ListMixin],
  setup() {
    const store = useStore();
    const deleteItemDialog = ref(false);
    const deleteMultipleDialog = ref(false);

    const filters = ref([]);
    const itemToDelete = ref([]);
    const selectedItems = ref([]);

    const user = store.getters["security/getUser"];
    const tags = ref([]);
    const title = ref('Inbox');
    const index = ref('inbox');

    const {showNotification} = useNotification();
    const {t} = useI18n();

    // Inbox
    const inBoxFilter = {
      msgType: MESSAGE_TYPE_INBOX,
      'receivers.receiver': user.id,
      'order[sendDate]': 'desc',
    };

    // Get user tags.
    axios.get(ENTRYPOINT + 'message_tags', {
      params: {
        user: user['@id']
      }
    }).then(response => {
      let data = response.data;
      tags.value = data['hydra:member'];
    });

    function goToInbox() {
      filters.value = inBoxFilter;
      title.value = 'Inbox';
      index.value = 'inbox';
      store.dispatch('message/resetList');
      store.dispatch('message/fetchAll', inBoxFilter);
    }

    function goToUnread() {
      title.value = 'Unread';
      index.value = 'unread';
      const unReadFilter = {
        msgType: MESSAGE_TYPE_INBOX,
        'receivers.receiver': user.id,
        read: false
      };
      filters.value = unReadFilter;
      store.dispatch('message/resetList');
      store.dispatch('message/fetchAll', unReadFilter);
    }

    function goToSent() {
      title.value = 'Sent';
      index.value = 'sent';
      const sentFilter = {
        sender: user.id
      };
      filters.value = sentFilter;
      store.dispatch('message/resetList');
      store.dispatch('message/fetchAll', sentFilter);
    }

    function goToTag(tag) {
      title.value = tag.tag;
      index.value = 'tag';
      const tagFilter = {
        msgType: MESSAGE_TYPE_INBOX,
        'receivers.receiver': user.id,
        'receivers.tags.tag': [tag.tag]
      };
      filters.value = tagFilter;
      store.dispatch('message/resetList');
      store.dispatch('message/fetchAll', tagFilter);
    }

    function deleteItemButton() {
      if (itemToDelete.value.sender['@id'] === user['@id']) {
        itemToDelete.value.status = 3;
        store.dispatch('message/update', itemToDelete.value);
      } else {
        let myReceiver = itemToDelete.value.receivers.find(receiver => receiver.receiver['@id'] === user['@id']) || {};

        if (myReceiver) {
          console.log('deleteItem');
          store.dispatch('messagereluser/del', myReceiver);
        }
      }

      deleteItemDialog.value = false;

      showNotification(t('Deleted'));

      goToInbox();
    }

    function confirmDeleteItem(item) {
      itemToDelete.value = item;
      deleteItemDialog.value = true;
    }

    function confirmDeleteMultiple() {
      deleteMultipleDialog.value = true;
    }

    function markAsReadMultiple() {
      selectedItems.value.forEach(message => {
        let myReceiver = {};
        message.receivers.forEach(receiver => {
          if (receiver.receiver['@id'] === user['@id']) {
            myReceiver = receiver;
            myReceiver.read = true;
            store.dispatch('messagereluser/update', myReceiver);
          }
        });
      });
      selectedItems.value = [];
      goToInbox();
    }

    function markAsUnReadMultiple() {
      selectedItems.value.forEach(message => {
        let myReceiver = {};
        message.receivers.forEach(receiver => {
          if (receiver.receiver['@id'] === user['@id']) {
            myReceiver = receiver;
            myReceiver.read = false;
            store.dispatch('messagereluser/update', myReceiver);
          }
        });
      });
      selectedItems.value = [];
      goToInbox();
    }

    function deleteMultipleItems() {
      let items = [];

      selectedItems.value.forEach(message => {
        let myReceiver = {};
        message.receivers.forEach(receiver => {
          if (receiver.receiver['@id'] === user['@id']) {
            myReceiver = receiver;
            items.push(myReceiver);
          }
        });
      });

      let promise = store.dispatch('messagereluser/delMultiple', items);

      deleteMultipleDialog.value = false;
      selectedItems.value = [];
      promise.then(() => {
        showNotification('Deleted');
        goToInbox();
      });
    }

    goToInbox();

    return {
      markAsUnReadMultiple,
      markAsReadMultiple,
      deleteMultipleItems,
      deleteItemButton,
      confirmDeleteMultiple,
      goToInbox,
      goToSent,
      goToUnread,
      goToTag,
      confirmDeleteItem,
      deleteMultipleDialog,
      selectedItems,
      deleteItemDialog,
      tags,
      filters,
      title,
      index,
    }
  },
  data() {
    return {
      columns: [
        {label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},
        {label: this.$i18n.t('Sender'), field: 'sender', name: 'sender', sortable: true},
        {label: this.$i18n.t('Modified'), field: 'sendDate', name: 'updatedAt', sortable: true},
        {label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [10, 20, 50, this.$i18n.t('All')],
      selected: [],
      isBusy: false,
      options: {
        sortBy: 'sendDate',
        sortDesc: 'asc',
      },
      // prime vue
      itemDialog: false,
      item: {},
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
    composeHandler() {
      let folderParams = this.$route.query;
      this.$router.push({name: `${this.$options.servicePrefix}Create`, query: folderParams});
    },
    reloadHandler() {
      this.onUpdateOptions();
    },
    // prime
    onPage(event) {
      this.options.itemsPerPage = event.rows;
      this.options.page = event.page + 1;
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

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
    openNew() {
      this.item = {};
      this.submitted = false;
      this.itemDialog = true;
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
      console.log('deleteSelected');
      /*for (let i = 0; i < this.selected.length; i++) {
        let item = this.selected[i];
        //this.deleteHandler(item);
        this.deleteItem(item);
      }*/

      this.deleteMultipleAction(this.selected);
      this.onRequest({
        pagination: this.pagination,
      });
    },
    //...actions,
    // From ListMixin
    ...mapActions('message', {
      getPage: 'fetchAll',
      create: 'create',
      update: 'update',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
    ...mapActions('messagereluser', {
      deleteMessageRelUser: 'del',
      deleteMessageRelUserMultipleAction: 'delMultiple'
    }),
  }
};
</script>
