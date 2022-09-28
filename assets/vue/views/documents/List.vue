<template>
  <div
    v-if="isAuthenticated && isCurrentTeacher"
    class="q-card"
  >
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2">
        <!--         <Button label="New" icon="pi pi-plus" class="p-button-primary p-button-sm p-mr-2" @click="openNew" />-->
        <Button
          class="btn btn--primary"
          @click="openNew"
        >
          <v-icon icon="mdi-folder-plus" />
          {{ $t('New folder') }}
        </Button>

        <!--         <Button label="New folder" icon="pi pi-plus" class="p-button-success p-mr-2" @click="addHandler()" />-->
        <!--         <Button label="New document" icon="pi pi-plus" class="p-button-sm p-button-primary p-mr-2" @click="addDocumentHandler()" />-->
        <Button
          class="btn btn--primary"
          label="{{ $t('New document') }}"
          @click="addDocumentHandler()"
        >
          <v-icon icon="mdi-file-plus" />
          {{ $t('New document') }}
        </Button>
        <Button
          class="btn btn--primary"
          label="{{ $t('Upload') }}"
          @click="uploadDocumentHandler()"
        >
          <v-icon icon="mdi-file-upload" />
          {{ $t('Upload') }}
        </Button>
        <!--
        <Button label="{{ $t('Download') }}" class="btn btn--primary" @click="downloadDocumentHandler()" :disabled="!selectedItems || !selectedItems.length">
          <v-icon icon="mdi-file-download"/>
          {{ $t('Download') }}
        </Button>
        -->
        <Button
          :disabled="!selectedItems || !selectedItems.length"
          class="btn btn--danger "
          label="{{ $t('Delete selected') }}"
          @click="confirmDeleteMultiple"
        >
          <v-icon icon="mdi-delete" />
          {{ $t('Delete selected') }}
        </Button>
      </div>
    </div>

  </div>

  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedItems"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="10"
    :rows-per-page-options="[5, 10, 20, 50]"
    :total-records="totalItems"
    :value="items"
    class="p-datatable-sm"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    data-key="iid"
    filter-display="menu"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsive-layout="scroll"
    @page="onPage($event)"
    @sort="sortingChanged($event)"
  >
    <span v-if="isCurrentTeacher">
      <Column
        :exportable="false"
        selection-mode="multiple"
        style="width: 3rem"
      />
    </span>

    <Column
      :header="$t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.resourceFile">
          <ResourceFileLink :resource="slotProps.data" />
        </div>
        <div v-else>
          <a
            v-if="slotProps.data"
            class="cursor-pointer "
            @click="handleClick(slotProps.data)"
          >
            <v-icon icon="mdi-folder" />
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>
      </template>
    </Column>

    <Column
      :header="$t('Size')"
      :sortable="true"
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size) : ''
        }}
      </template>
    </Column>

    <Column
      :header="$t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ $filters.relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button
            class="btn btn--primary"
            @click="showHandler(slotProps.data)"
          >
            <v-icon icon="mdi-information" />
          </Button>

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="btn btn--primary"
            @click="changeVisibilityHandler(slotProps.data, slotProps)"
          >
            <v-icon
              v-if="RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility"
              icon="mdi-eye"
            />
            <v-icon
              v-if="RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility"
              icon="mdi-eye-off"
            />
          </Button>

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="btn btn--primary p-mr-2"
            @click="editHandler(slotProps.data)"
          >
            <v-icon icon="mdi-pencil" />
          </Button>

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="btn btn--danger"
            @click="confirmDeleteItem(slotProps.data)"
          >
            <v-icon icon="mdi-delete" />
          </Button>
        </div>
      </template>
    </Column>
  </DataTable>

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
      >$t('Title is required')</small>
    </div>

    <template #footer>
      <Button
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        class="p-button-secondary"
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
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item">Are you sure you want to delete <b>{{ item.title }}</b>?</span>
    </div>
    <template #footer>
      <Button
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        label="No"
        @click="deleteItemDialog = false"
      />
      <Button
        class="p-button-secondary"
        icon="pi pi-check"
        label="Yes"
        @click="deleteItemButton"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :modal="true"
    :style="{width: '450px'}"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item">{{ $t('Are you sure you want to delete the selected items?') }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-outlined p-button-plain"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-secondary"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>
</template>

<script>
import { mapActions, mapGetters, useStore } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue';
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from '../../components/resource_links/visibility';
import isEmpty from 'lodash/isEmpty';
import toInteger from 'lodash/toInteger';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Dialog from 'primevue/dialog';
import { ref } from 'vue';

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    ResourceFileLink,
    Dialog,
  },
  mixins: [ListMixin],
  setup () {
    const store = useStore();
    const route = useRoute();
    const { t } = useI18n();

    // Set resource node.
    let nodeId = route.params.node;
    if (isEmpty(nodeId)) {
      nodeId = route.query.node;
    }
    let cid = toInteger(route.query.cid);
    let courseIri = '/api/courses/' + cid;
    store.dispatch('course/findCourse', { id: courseIri });
    store.dispatch('resourcenode/findResourceNode', { id: '/api/resource_nodes/' + nodeId });

    let sid = toInteger(route.query.sid);
    if (sid) {
      let sessionIri = '/api/sessions/' + sid;
      store.dispatch('session/findSession', { id: sessionIri });
    }

    const item = ref({});

    const itemDialog = ref(false);
    const deleteItemDialog = ref(false);
    const deleteMultipleDialog = ref(false);

    const isBusy = ref(false);

    const submitted = ref(false);

    const filters = { 'loadNode': 1 };

    return {
      RESOURCE_LINK_PUBLISHED: RESOURCE_LINK_PUBLISHED,
      RESOURCE_LINK_DRAFT: RESOURCE_LINK_DRAFT,
      sortBy: 'title',
      sortDesc: false,
      // columnsQua: [
      //   {align: 'left', name: 'resourceNode.title', label: this.$i18n.t('Title'), field: 'resourceNode.title', sortable: true},
      //   {align: 'left', name: 'resourceNode.updatedAt', label: this.$i18n.t('Modified'), field: 'resourceNode.updatedAt', sortable: true},
      //   {name: 'resourceNode.resourceFile.size', label: this.$i18n.t('Size'), field: 'resourceNode.resourceFile.size', sortable: true},
      //   {name: 'action', label: this.$i18n.t('Actions'), field: 'action', sortable: false}
      // ],
      columns: [
        { label: t('Title'), field: 'title', name: 'title', sortable: true },
        { label: t('Modified'), field: 'resourceNode.updatedAt', name: 'updatedAt', sortable: true },
        { label: t('Size'), field: 'resourceNode.resourceFile.size', name: 'size', sortable: true },
        { label: t('Actions'), name: 'action', sortable: false }
      ],
      pageOptions: [10, 20, 50, t('All')],
      selected: [],
      isBusy,
      options: [],
      selectedItems: [],
      // prime vue
      itemDialog,
      deleteItemDialog,
      deleteMultipleDialog,
      item,
      filters,
      submitted,
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
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),

    ...mapGetters('documents', {
      items: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('documents', {
      deletedResource: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  mounted () {
    this.filters['loadNode'] = 1;
    this.onUpdateOptions(this.options);
  },
  methods: {
    // prime
    onPage (event) {
      this.options.itemsPerPage = event.rows;
      this.options.page = event.page + 1;
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
    },
    sortingChanged (event) {
      console.log('sortingChanged');
      console.log(event);
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    openNew () {
      this.item = {};
      this.submitted = false;
      this.itemDialog = true;
    },
    hideDialog () {
      this.itemDialog = false;
      this.submitted = false;
    },
    saveItem () {
      this.submitted = true;

      if (this.item.title.trim()) {
        if (!this.item.id) {
          this.item.filetype = 'folder';
          this.item.parentResourceNodeId = this.$route.params.node;
          this.item.resourceLinkList = JSON.stringify([{
            gid: this.$route.query.gid,
            sid: this.$route.query.sid,
            cid: this.$route.query.cid,
            visibility: RESOURCE_LINK_PUBLISHED, // visible by default
          }]);

          this.createWithFormData(this.item);
          this.showMessage('Saved');
        }
        this.itemDialog = false;
        this.item = {};
      }
    },
    editItem (item) {
      this.item = { ...item };
      this.itemDialog = true;
    },
    confirmDeleteItem (item) {
      this.item = item;
      this.deleteItemDialog = true;
    },
    confirmDeleteMultiple () {
      this.deleteMultipleDialog = true;
    },
    deleteMultipleItems () {
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
    deleteItemButton () {
      console.log('deleteItem');
      this.deleteItem(this.item);
      //this.items = this.items.filter(val => val.iid !== this.item.iid);
      this.deleteItemDialog = false;
      this.item = {};
      this.onUpdateOptions(this.options);
    },
    async fetchItems () {
      console.log('fetchItems');
      /* No need to call if all items retrieved */
      if (this.items.length === this.totalItems) return;

      /* Enable busy state */
      this.isBusy = true;

      /* Missing error handling if call fails */
      let currentPage = this.options.page;
      console.log(currentPage);
      const startIndex = currentPage++ * this.options.itemsPerPage;
      const endIndex = startIndex + this.options.itemsPerPage;

      console.log(this.items.length);
      console.log(this.totalItems);
      console.log(startIndex, endIndex);

      this.options.page = currentPage;

      await this.fetchNewItems(this.options);

      //const newItems = await this.callDatabase(startIndex, endIndex);

      /* Add new items to existing ones */
      //this.items = this.items.concat(newItems);

      /* Disable busy state */
      this.isBusy = false;
      return true;
    },
    onRowSelected (items) {
      this.selected = items;
    },
    selectAllRows () {
      this.$refs.selectableTable.selectAllRows();
    },
    clearSelected () {
      this.$refs.selectableTable.clearSelected();
    },
    async deleteSelected () {
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

      /*const promises = this.selected.map(async item => {
        const result = await this.deleteItem(item);

        console.log('item');
        return result;
      });

      const result = await Promise.all(promises);

      console.log(result);
      if (result) {
        console.log(result);
        //this.onUpdateOptions(this.options);
      }
*/
      console.log('end -- deleteSelected');
    },
    //...actions,
    // From ListMixin
    ...mapActions('documents', {
      getPage: 'fetchAll',
      createWithFormData: 'createWithFormData',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
