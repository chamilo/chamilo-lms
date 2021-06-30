<template>
  <div v-if="isAuthenticated"  class="q-card">
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2" >
        <Button label="Compose" icon="fa fa-file-alt" class="btn btn-primary" @click="composeHandler()" />
        <Button label="Delete" icon="mdi-delete" class="btn btn-danger " @click="confirmDeleteMultiple" :disabled="!selectedItems || !selectedItems.length" />
      </div>
    </div>
  </div>

  <div>
    <q-splitter
        v-model="splitterModel"
        style="height: 100%"
    >

      <template v-slot:before>
        <q-tabs
            v-model="tab"
            vertical
            inline-label
            no-caps
        >
          <q-tab name="inbox" icon="inbox" label="Inbox" />
          <q-tab name="outbox" icon="mdi-send-outline" label="Sent" />
        </q-tabs>
      </template>

      <template v-slot:after>
        <q-tab-panels
            v-model="tab"
            animated
            swipeable
            vertical
            transition-prev="jump-up"
            transition-next="jump-up"
        >
          <q-tab-panel name="inbox">
            <div class="text-h4 q-mb-md">Inbox</div>
            <DataTable
                class="p-datatable-sm"
                :value="items"
                v-model:selection="selectedItems"
                dataKey="id"
                v-model:filters="filters"
                filterDisplay="menu"
                sortBy="sendDate"
                sortOrder="asc"
                :lazy="true"
                :paginator="true"
                :rows="10"
                :totalRecords="totalItems"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="sortingChanged($event)"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                responsiveLayout="scroll"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                :globalFilterFields="['title', 'sendDate']">

              <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

              <Column field="title" :header="$t('Title')" :sortable="true">
                <template #body="slotProps">
                  <a
                      v-if="slotProps.data"
                      @click="showHandler(slotProps.data)"
                      class="cursor-pointer " >
                    {{ slotProps.data.title }}
                  </a>
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
              <Column field="userSender" :header="$t('Sender')" :sortable="true">
                <template #body="slotProps">
                  <q-avatar size="32px">
                    <img :src="slotProps.data.userSender.illustrationUrl + '?w=80&h=80&fit=crop'" />
                  </q-avatar>
                  {{ slotProps.data.userSender.username }}
                </template>
              </Column>

              <Column field="sendDate" :header="$t('Send date')" :sortable="true">
                <template #body="slotProps">
                  {{$luxonDateTime.fromISO(slotProps.data.sendDate).toRelative() }}
                </template>
              </Column>

              <Column :exportable="false">
                <template #body="slotProps">
                  <div class="flex flex-row gap-2">
                    <Button icon="fa fa-info-circle"  class="btn btn-primary " @click="showHandler(slotProps.data)" />
                    <Button v-if="isAuthenticated"  class="btn btn-danger" @click="confirmDeleteItem(slotProps.data)" >
                      <v-icon icon="mdi-delete"/>
                    </Button>
                  </div>
                </template>
              </Column>
            </DataTable>

          </q-tab-panel>

          <q-tab-panel name="outbox">
            <div class="text-h4 q-mb-md">Sent</div>


            <DataTable
                class="p-datatable-sm"
                :value="items"
                v-model:selection="selectedItems"
                dataKey="id"
                v-model:filters="filtersSent"
                filterDisplay="menu"
                sortBy="sendDate"
                sortOrder="asc"
                :lazy="true"
                :paginator="true"
                :rows="10"
                :totalRecords="totalItems"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="sortingChanged($event)"
                paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                :rowsPerPageOptions="[5, 10, 20, 50]"
                responsiveLayout="scroll"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
                :globalFilterFields="['title', 'sendDate']">

              <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>

              <Column field="title" :header="$t('Title')" :sortable="true">
                <template #body="slotProps">
                  <a
                      v-if="slotProps.data"
                      @click="showHandler(slotProps.data)"
                      class="cursor-pointer " >
                    {{ slotProps.data.title }}
                  </a>
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
              <Column field="userSender" :header="$t('Sender')" :sortable="true">
                <template #body="slotProps">
                  <q-avatar size="32px">
                    <img :src="slotProps.data.userSender.illustrationUrl + '?w=80&h=80&fit=crop'" />
                  </q-avatar>
                  {{ slotProps.data.userSender.username }}
                </template>
              </Column>

              <Column field="sendDate" :header="$t('Send date')" :sortable="true">
                <template #body="slotProps">
                  {{$luxonDateTime.fromISO(slotProps.data.sendDate).toRelative() }}
                </template>
              </Column>

              <Column :exportable="false">
                <template #body="slotProps">
                  <div class="flex flex-row gap-2">
                    <Button icon="fa fa-info-circle"  class="btn btn-primary " @click="showHandler(slotProps.data)" />
                    <Button v-if="isAuthenticated"  class="btn btn-danger" @click="confirmDeleteItem(slotProps.data)" >
                      <v-icon icon="mdi-delete"/>
                    </Button>
                  </div>
                </template>
              </Column>
            </DataTable>


          </q-tab-panel>
        </q-tab-panels>
      </template>

    </q-splitter>
  </div>



<!--  Dialogs-->

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
      <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteItemButton" />
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
//import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from '../../components/documents/ResourceFileIcon.vue';
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue';

import {useRoute, useRouter} from 'vue-router'
import DataFilter from '../../components/DataFilter';
import DocumentsFilterForm from '../../components/documents/Filter';
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import isEmpty from 'lodash/isEmpty';
import moment from "moment";
import toInteger from "lodash/toInteger";
import useState from "../../hooks/useState";

export default {
  name: 'MessageList',
  servicePrefix: 'Message',
  components: {
    //8Toolbar,
    ActionCell,
    ResourceFileIcon,
    ResourceFileLink,
    DocumentsFilterForm,
    DataFilter
  },
  mixins: [ListMixin],
  setup() {
    const route = useRoute();
    const router = useRouter();
    const store = useStore();

    const filters = ref([]);
    const filtersSent = ref([]);


    const user = store.getters["security/getUser"]

    filtersSent.value = {
      msgType: 2,
      userSender: user.id
    }

    // inbox
    filters.value = {
      msgType: 1,
      userReceiver: user.id
    };

    return {
      filters,
      filtersSent,
      tab: ref('inbox'),
      splitterModel: ref(20)
    }
  },
  data() {
    return {
      columns: [
        { label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},
        { label: this.$i18n.t('Sender'), field: 'userSender', name: 'userSender', sortable: true},
        { label: this.$i18n.t('Modified'), field: 'sendDate', name: 'updatedAt', sortable: true},
        { label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [10, 20, 50, this.$i18n.t('All')],
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
    composeHandler() {
      let folderParams = this.$route.query;
      this.$router.push({ name: `${this.$options.servicePrefix}Create` , query: folderParams});
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
            visibility: 2, // visible by default
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
      console.log('deleteMultipleItems');
      console.log(this.selectedItems);
      this.deleteMultipleAction(this.selectedItems);
      this.onRequest({
        pagination: this.pagination,
      });
      this.deleteMultipleDialog = false;
      this.selectedItems = null;
      //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
    },
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
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
