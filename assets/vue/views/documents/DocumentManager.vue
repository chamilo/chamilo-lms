<template>
  <Toolbar class="p-mb-4">
    <template #left>
      <div v-if="isAuthenticated && isCurrentTeacher" class="flex flex-row gap-2" >
        <!--         <Button label="New" icon="pi pi-plus" class="p-button-primary p-button-sm p-mr-2" @click="openNew" />-->
        <Button label="New folder" icon="pi pi-plus" class="btn btn-primary" @click="openNew" />

        <!--         <Button label="New folder" icon="pi pi-plus" class="p-button-success p-mr-2" @click="addHandler()" />-->
        <!--         <Button label="New document" icon="pi pi-plus" class="p-button-sm p-button-primary p-mr-2" @click="addDocumentHandler()" />-->
        <Button label="Upload" icon="pi pi-plus" class="btn btn-primary" @click="uploadDocumentHandler()" />
      </div>
    </template>
  </Toolbar>

  <DataTable
      class="p-datatable-sm"
      :value="items"
      v-model:selection="selectedItems"
      dataKey="iid"
      v-model:filters="filters"
      filterDisplay="menu"
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
      :globalFilterFields="['resourceNode.title', 'resourceNode.updatedAt']"
  >

    <Column field="resourceNode.title" :header="$t('Title')" :sortable="true">
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.resourceFile">
          <ResourceFileLink :resource="slotProps.data" />
        </div>
        <div v-else>
          <a
              v-if="slotProps.data"
              @click="handleClick(slotProps.data)"
              class="cursor-pointer " >
            <FontAwesomeIcon
                icon="folder"
                size="lg"
            />
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>

      </template>

    </Column>
    <Column field="resourceNode.updatedAt" :header="$t('Modified')" :sortable="true">
      <template #body="slotProps">
        {{$luxonDateTime.fromISO(slotProps.data.resourceNode.updatedAt).toRelative() }}
      </template>
    </Column>

    <Column field="resourceNode.resourceFile.size" :header="$t('Size')" :sortable="true">
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size) : ''
        }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button label="Select" class="p-button-sm p-button p-mr-2" @click="returnToEditor(slotProps.data)" />
        </div>
      </template>
    </Column>
  </DataTable>

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

</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
//import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from './ResourceFileIcon.vue';
import ResourceFileLink from './ResourceFileLink.vue';

import { useRoute } from 'vue-router'
import DataFilter from '../../components/DataFilter';
import DocumentsFilterForm from '../../components/documents/Filter';
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import isEmpty from 'lodash/isEmpty';
import moment from "moment";

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    //8Toolbar,
    ActionCell,
    ResourceFileIcon,
    ResourceFileLink,
    DocumentsFilterForm,
    DataFilter
  },
  mixins: [ListMixin],
  data() {
    return {
      sortBy: 'title',
      sortDesc: false,
      columns: [
        { label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},
        { label: this.$i18n.t('Modified'), field: 'resourceNode.updatedAt', name: 'updatedAt', sortable: true},
        { label: this.$i18n.t('Size'), field: 'resourceNode.resourceFile.size', name: 'size', sortable: true},
        { label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [10, 20, 50, this.$i18n.t('All')],
      selected: [],
      isBusy: false,
      options: [],
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteMultipleDialog: false,
      item: {},
      filters: {},
      submitted: false,
    };
  },
  created() {
    console.log('created - vue/views/documents/List.vue');
    /*const route = useRoute();
    let nodeId = route.params['node'];
    if (!isEmpty(nodeId)) {
      this.findResourceNode('/api/resource_nodes/' + nodeId);
    }

    this.onUpdateOptions(this.options);*/
    //this.initFilters1();
  /*
    this.onRequest({
      pagination: this.pagination,
    });*/
  },
  mounted() {
    console.log('mounted - vue/views/documents/List.vue');
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
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),

    ...mapGetters('documents', {
      items: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('documents', {
      deletedItem: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  methods: {
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
          this.item.parentResourceNodeId = this.$route.params.node;
          this.item.resourceLinkList = JSON.stringify([{
            gid: this.$route.query.gid,
            sid: this.$route.query.sid,
            c_id: this.$route.query.cid,
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
    returnToEditor(item) {
      const url = item.contentUrl;

      // Tiny mce
      console.log(url);
      window.parent.postMessage({
        url: url
      }, '*');

      if (parent.tinymce) {
        parent.tinymce.activeEditor.windowManager.close();
      }

      // Ckeditor
      function getUrlParam(paramName) {
        var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i');
        var match = window.location.search.match(reParam);
        return (match && match.length > 1) ? match[1] : '';
      }

      var funcNum = getUrlParam('CKEditorFuncNum');
      if (window.opener.CKEDITOR) {
        window.opener.CKEDITOR.tools.callFunction(funcNum, url);
        window.close();
      }
    },
    async fetchItems() {
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
    onRowSelected(items) {
      this.selected = items
    },
    selectAllRows() {
      this.$refs.selectableTable.selectAllRows()
    },
    clearSelected() {
      this.$refs.selectableTable.clearSelected()
    },
    //...actions,
    // From ListMixin
    ...mapActions('documents', {
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
