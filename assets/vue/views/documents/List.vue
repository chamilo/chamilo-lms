<template>
  <div
    v-if="isAuthenticated && isCurrentTeacher"
    class="flex flex-row gap-2 mb-3"
  >
    <Button
      class="btn btn--primary"
      icon="mdi mdi-folder-plus"
      :label="t('New folder')"
      @click="openNew"
    />
    <Button
      class="btn btn--primary"
      :label="t('New document')"
      icon="mdi mdi-file-plus"
      @click="addDocumentHandler()"
    />
    <Button
      class="btn btn--primary"
      :label="t('Upload')"
      icon="mdi mdi-file-upload"
      @click="uploadDocumentHandler()"
    />
    <!--
    <Button label="{{ $t('Download') }}" class="btn btn--primary" @click="downloadDocumentHandler()" :disabled="!selectedItems || !selectedItems.length">
      <v-icon icon="mdi-file-download"/>
      {{ $t('Download') }}
    </Button>
    -->
    <Button
      :disabled="!selectedItems || !selectedItems.length"
      class="btn btn--danger "
      :label="t('Delete selected')"
      icon="mdi mdi-delete"
      @click="confirmDeleteMultiple"
    />
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
    <Column
      v-if="isCurrentTeacher"
      :exportable="false"
      selection-mode="multiple"
      header-style="width: 3rem"
    />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.resourceFile">
          <ResourceFileLink :resource="slotProps.data" />
        </div>
        <div v-else>
          <Button
            v-if="slotProps.data"
            class="p-button-text p-button-plain"
            icon="mdi mdi-folder"
            :label="slotProps.data.resourceNode.title"
            @click="handleClick(slotProps.data)"
          />
        </div>
      </template>
    </Column>

    <Column
      :header="t('Size')"
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
      :header="t('Modified')"
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
            class="p-button-icon-only"
            icon="mdi mdi-information"
            @click="showHandler(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="p-button-icon-only"
            :icon="RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'mdi mdi-eye' : (RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility ? 'mdi mdi-eye-off' : '')"
            @click="changeVisibilityHandler(slotProps.data, slotProps)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="p-button-icon-only"
            icon="mdi mdi-pencil"
            @click="editHandler(slotProps.data)"
          />

          <Button
            v-if="isAuthenticated && isCurrentTeacher"
            class="p-button-icon-only"
            icon="mdi mdi-delete"
            @click="confirmDeleteItem(slotProps.data)"
          />
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
    <div class="form__field">
      <div class="p-float-label">
        <InputText
          id="title"
          v-model.trim="item.title"
          :class="{'p-invalid': submitted && !item.title}"
          autocomplete="off"
          autofocus
          required="true"
        />
        <label
          v-t="'Name'"
          for="name"
        />
      </div>
      <small
        v-if="submitted && !item.title"
        v-t="'Title is required'"
        class="p-error"
      />
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
import { isEmpty } from 'lodash';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import Dialog from 'primevue/dialog';
import { computed, inject, ref } from 'vue';
import { useCidReq } from '../../composables/cidReq';
import { useList } from '../../mixins/list';

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

    const {
      pagination,
      filters,
      options,
      onRequest,
      onUpdateOptions
    } = useList('documents');

    const flashMessageList = inject('flashMessageList');

    const { cid, sid, gid } = useCidReq();

    // Set resource node.
    let nodeId = route.params.node;
    if (isEmpty(nodeId)) {
      nodeId = route.query.node;
    }

    store.dispatch('course/findCourse', { id: `/api/courses/${cid}` });
    store.dispatch('resourcenode/findResourceNode', { id: `/api/resource_nodes/${nodeId}` });

    if (sid) {
      store.dispatch('session/findSession', { id: `/api/sessions/${sid}` });
    }

    const item = ref({});

    const itemDialog = ref(false);
    const deleteItemDialog = ref(false);
    const deleteMultipleDialog = ref(false);

    const isBusy = ref(false);

    const submitted = ref(false);

    filters.loadNode = 1;

    const selected = ref([]);
    const selectedItems = ref([]);

    const isAuthenticated = computed(() => store.getters['security/isAuthenticated']);
    const isAdmin = computed(() => store.getters['security/isAdmin']);
    const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher']);

    const resourceNode = computed(() => store.getters['resourcenode/getResourceNode']);

    const items = computed(() => store.getters['documents/list']);
    const isLoading = computed(() => store.getters['documents/isLoading']);

    function openNew () {
      item.value = {};
      submitted.value = false;
      itemDialog.value = true;
    }

    function hideDialog () {
      itemDialog.value = false;
      submitted.value = false;
    }

    function saveItem () {
      submitted.value = true;

      if (item.value.title.trim()) {
        if (!item.value.id) {
          item.value.filetype = 'folder';
          item.value.parentResourceNodeId = route.params.node;
          item.value.resourceLinkList = JSON.stringify([{
            gid,
            sid,
            cid,
            visibility: RESOURCE_LINK_PUBLISHED, // visible by default
          }]);

          store.dispatch('documents/createWithFormData', item.value)
            .then(() => flashMessageList.value.push({
              severity: 'success',
              detail: t('Saved')
            }));
        }
        itemDialog.value = false;
        item.value = {};
      }
    }

    function confirmDeleteMultiple () {
      deleteMultipleDialog.value = true;
    }

    function confirmDeleteItem (newItem) {
      item.value = newItem;
      deleteItemDialog.value = true;
    }

    function deleteMultipleItems () {
      store.dispatch('documents/delMultiple', selectedItems)
        .then(() => {
          deleteMultipleDialog.value = false;
          selectedItems.value = [];
        });

      onRequest({
        pagination: pagination,
      });
      //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
    }

    function deleteItemButton () {
      store.dispatch('documents/del', item.value)
        .then(() => {
          deleteItemDialog.value = false;
          item.value = {};
        })
      //this.items = this.items.filter(val => val.iid !== this.item.iid);
      //this.onUpdateOptions(options.value);
    }

    function onPage (event) {
      options.value = {
        itemsPerPage: event.rows,
        page: event.page + 1,
        sortBy: event.sortField,
        sortDesc: event.sortOrder === -1
      };

      onUpdateOptions(options.value);
    }

    return {
      RESOURCE_LINK_PUBLISHED,
      RESOURCE_LINK_DRAFT,

      isAuthenticated,
      isAdmin,
      isCurrentTeacher,

      resourceNode,
      items,

      isLoading,

      openNew,
      hideDialog,
      saveItem,
      confirmDeleteItem,
      confirmDeleteMultiple,
      deleteMultipleItems,
      deleteItemButton,

      onPage,

      sortBy: 'title',
      sortDesc: false,
      selected,
      isBusy,
      options,
      selectedItems,
      // prime vue
      itemDialog,
      deleteItemDialog,
      deleteMultipleDialog,
      item,
      filters,
      submitted,
      t,
    };
  },
  computed: {
    // From crud.js list function
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode'
    }),

    //...getters

    // From ListMixin
    ...mapFields('documents', {
      deletedResource: 'deleted',
      error: 'error',
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
    sortingChanged (event) {
      console.log('sortingChanged');
      console.log(event);
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    editItem (item) {
      this.item = { ...item };
      this.itemDialog = true;
    },
    //...actions,
    // From ListMixin
    ...mapActions('documents', {
      getPage: 'fetchAll',
      deleteItem: 'del',
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
