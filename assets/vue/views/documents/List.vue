<template>
<!--  <Toolbar-->
<!--     :handle-add="addHandler"-->
<!--     :handle-add-document="addDocumentHandler"-->
<!--     :handle-upload-document="uploadDocumentHandler"-->
<!--     :handle-download-document="downloadDocumentHandler"-->
<!--     :filters="filters"-->
<!--     :on-send-filter="onSendFilter"-->
<!--     :reset-filter="resetFilter"-->
<!--   />-->

<!--  <DataFilter-->
<!--      :handle-filter="onSendFilter"-->
<!--      :handle-reset="resetFilter"-->
<!--  >-->
<!--    <DocumentsFilterForm-->
<!--        ref="filterForm"-->
<!--        slot="filter"-->
<!--        :values="filters"-->
<!--    />-->
<!--  </DataFilter>-->

<!--  :filter="filter"-->
<!--  :no-data-label="$t('Data unavailable')"-->

<!--  <div class="q-pa-md" >-->
<!--    <q-table-->
<!--        dense-->
<!--        :rows="items"-->
<!--        :columns="columns"-->
<!--        row-key="@id"-->
<!--        @request="onRequest"-->
<!--        v-model:pagination="pagination"-->
<!--        :no-results-label="$t('No results')"-->
<!--        :loading-label="$t('Loading')"-->
<!--        :rows-per-page-label="$t('Records per page:')"-->
<!--        :rows-per-page-options="[10, 20, 50, 0]"-->
<!--        :loading="isLoading"-->
<!--        selection="multiple"-->
<!--        v-model:selected="selectedItems"-->
<!--    >-->
<!--       <template v-slot:body="props">-->
<!--          <q-tr :props="props">-->
<!--            <q-td auto-width>-->
<!--              <q-checkbox dense v-model="props.selected" />-->
<!--            </q-td>-->

<!--            <q-td key="resourceNode.title" :props="props">-->
<!--              <div v-if="props.row.resourceNode.resourceFile">-->
<!--                <a-->
<!--                    data-fancybox="gallery"-->
<!--                    :href="props.row.contentUrl"-->
<!--                >-->
<!--                  <ResourceFileIcon :file="props.row" />-->
<!--                  {{ props.row.title }}-->
<!--                </a>-->
<!--              </div>-->
<!--              <div v-else>-->
<!--                <a @click="handleClick(props.row)" class="cursor-pointer" >-->
<!--                  <v-icon-->
<!--                      icon="folder"-->
<!--                      size="lg"-->
<!--                  />-->
<!--                  {{ props.row.resourceNode.title }}-->
<!--                </a>-->
<!--              </div>-->
<!--            </q-td>-->

<!--            <q-td key="resourceNode.updatedAt" :props="props">-->
<!--              {{$luxonDateTime.fromISO(props.row.resourceNode.updatedAt).toRelative() }}-->
<!--            </q-td>-->

<!--            <q-td key="resourceNode.resourceFile.size" :props="props">-->
<!--              <span v-if="props.row.resourceNode.resourceFile">-->
<!--              {{-->
<!--                $filters.prettyBytes(props.row.resourceNode.resourceFile.size)-->
<!--              }}-->
<!--              </span>-->
<!--            </q-td>-->

<!--            <q-td key="action" :props="props">-->
<!--            <ActionCell-->
<!--                :handle-show="() => showHandler(props.row)"-->
<!--                :handle-edit="() => editHandler(props.row)"-->
<!--                :handle-delete="() => deleteHandler(props.row)"-->
<!--            />-->
<!--            </q-td>-->
<!--          </q-tr>-->
<!--        </template>-->
<!--      </q-table>-->
<!--  </div>-->
  <div v-if="isAuthenticated && isCurrentTeacher"  class="q-card">
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2" >
        <!--         <Button label="New" icon="pi pi-plus" class="p-button-primary p-button-sm p-mr-2" @click="openNew" />-->
        <Button class="btn btn-primary" @click="openNew">
          <v-icon icon="mdi-folder-plus"/>
          {{ $t('New folder') }}
        </Button>

        <!--         <Button label="New folder" icon="pi pi-plus" class="p-button-success p-mr-2" @click="addHandler()" />-->
        <!--         <Button label="New document" icon="pi pi-plus" class="p-button-sm p-button-primary p-mr-2" @click="addDocumentHandler()" />-->
        <Button label="{{ $t('New document') }}" class="btn btn-primary" @click="addDocumentHandler()" >
          <v-icon icon="mdi-file-plus"/>
          {{ $t('New document') }}
        </Button>
        <Button label="{{ $t('Upload') }}" class="btn btn-primary" @click="uploadDocumentHandler()">
          <v-icon icon="mdi-file-upload"/>
          {{ $t('Upload') }}
        </Button>
        <!--
        <Button label="{{ $t('Download') }}" class="btn btn-primary" @click="downloadDocumentHandler()" :disabled="!selectedItems || !selectedItems.length">
          <v-icon icon="mdi-file-download"/>
          {{ $t('Download') }}
        </Button>
        -->
        <Button label="{{ $t('Delete selected') }}" class="btn btn-danger " @click="confirmDeleteMultiple" :disabled="!selectedItems || !selectedItems.length">
          <v-icon icon="mdi-delete"/>
          {{ $t('Delete selected') }}
        </Button>
      </div>
    </div>

    <!--       <template #right>-->
    <!--         <FileUpload mode="basic" accept="image/*" :maxFileSize="1000000" label="Import" chooseLabel="Import" class="p-mr-2 p-d-inline-block" />-->
    <!--         <Button label="Export" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)"  />-->
    <!--       </template>-->
  </div>

  <!--      :filter-change="filterCallback"-->
  <!--      :filter-apply="filterCallback"-->
  <!--      :onLazyLoad ="filterCallback($event)"-->

<!--  :scrollable="true"-->
<!--  scrollHeight="height:100%"-->
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
      :globalFilterFields="['resourceNode.title', 'resourceNode.updatedAt']">

    <span v-if="isCurrentTeacher">
      <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
    </span>

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
            <v-icon icon="mdi-folder"/>
            {{ slotProps.data.resourceNode.title }}
          </a>
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

    <Column field="resourceNode.resourceFile.size" :header="$t('Size')" :sortable="true">
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size) : ''
        }}
      </template>
    </Column>

    <Column field="resourceNode.updatedAt" :header="$t('Modified')" :sortable="true">
      <template #body="slotProps">
        {{$luxonDateTime.fromISO(slotProps.data.resourceNode.updatedAt).toRelative() }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button class="btn btn-primary" @click="showHandler(slotProps.data)">
            <v-icon icon="mdi-information"/>
          </Button>

          <Button v-if="isAuthenticated && isCurrentTeacher" class="btn btn-primary" @click="changeVisibilityHandler(slotProps.data, slotProps)">
            <v-icon v-if="RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility" icon="mdi-eye"/>
            <v-icon v-if="RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility" icon="mdi-eye-off"/>
          </Button>

          <Button v-if="isAuthenticated && isCurrentTeacher" class="btn btn-primary p-mr-2" @click="editHandler(slotProps.data)">
            <v-icon icon="mdi-pencil"/>
          </Button>

          <Button v-if="isAuthenticated && isCurrentTeacher" class="btn btn-danger" @click="confirmDeleteItem(slotProps.data)" >
            <v-icon icon="mdi-delete"/>
          </Button>
        </div>
      </template>
    </Column>

<!--    <template #paginatorLeft>-->
<!--      <Button type="button" icon="pi pi-refresh" class="p-button-text" />-->
<!--    </template>-->
<!--    <template #paginatorRight>-->
<!--      <Button type="button" icon="pi pi-cloud" class="p-button-text" />-->
<!--    </template>-->
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

  <!--          <template v-slot:body="props">-->
  <!--            <q-tr :props="props">-->
  <!--              <q-td key="title" :props="props">-->
  <!--                {{ props.row.title }}-->
  <!--              </q-td>-->
  <!--              <q-td key="resourceNode.updatedAt" :props="props">-->
  <!--                {{ props.row.resourceNode.updatedAt }}-->
  <!--              </q-td>-->
  <!--              <q-td key="resourceNode.resourceFile.size" :props="props">-->
  <!--                {{ props.row.resourceNode.resourceFile.size }}-->
  <!--              </q-td>-->
  <!--            </q-tr>-->
  <!--          </template>-->

  <!--          <template v-slot:body-cell-updatedAt="props">-->
  <!--            <q-td slot="body-cell-updatedAt" auto-width>-->
  <!--              {{-->
  <!--                  moment(props.row.resourceNode.updatedAt).fromNow()-->
  <!--              }}-->
  <!--            </q-td>-->
  <!--          </template>-->

  <!--          <template v-slot:body-cell-size="props">-->
  <!--            <q-td slot="body-cell-updatedAt" auto-width>-->
  <!--              <span v-if="props.row.resourceNode.resourceFile">-->
  <!--                 {{ $filters.prettyBytes(props.row.resourceNode.resourceFile.size)  }}-->
  <!--              </span>-->
  <!--            </q-td>-->
  <!--          </template>-->

  <!--          <template v-slot:body-cell-action="props">-->
  <!--            <ActionCell-->
  <!--                slot="body-cell-action"-->
  <!--                slot-scope="props"-->
  <!--                :handle-show="() => showHandler(props.row)"-->
  <!--                :handle-edit="() => editHandler(props.row)"-->
  <!--                :handle-delete="() => deleteHandler(props.row)"-->
  <!--            />-->
  <!--          </template>-->
  <!--        </q-table>-->

</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
//import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from '../../components/documents/ResourceFileIcon.vue';
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue';
import DataFilter from '../../components/DataFilter';
import DocumentsFilterForm from '../../components/documents/Filter';
import {RESOURCE_LINK_PUBLISHED, RESOURCE_LINK_DRAFT} from "../../components/resource_links/visibility";

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    ActionCell,
    ResourceFileIcon,
    ResourceFileLink,
    DocumentsFilterForm,
    DataFilter
  },
  mixins: [ListMixin],
  data() {
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
    //console.log('created - vue/views/documents/List.vue');
    this.filters['loadNode'] = 1;
  },
  mounted() {
    this.filters['loadNode'] = 1;
    this.onUpdateOptions(this.options);

    // Detect when scrolled to bottom.
    /*const listElm = document.querySelector('#documents');
    listElm.addEventListener('scroll', e => {
      console.log('aaa');
      if(listElm.scrollTop + listElm.clientHeight >= listElm.scrollHeight) {
        this.onScroll();
      }
    });*/
    //const tableScrollBody = this.$refs['selectableTable'].$el;
    /* Consider debouncing the event call */
    //tableScrollBody.addEventListener("scroll", this.onScroll);
    //window.addEventListener('scroll', this.onScroll)
    window.addEventListener('scroll', () =>{
      /*if(window.top.scrollY > window.outerHeight){
        if (!this.isBusy) {
          this.fetchItems();
        }
      }*/
    });
    /*const tableScrollBody = this.$refs['documents'];
    tableScrollBody.addEventListener("scroll", this.onScroll);*/
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
  methods: {
    // prime
    onPage(event) {
      console.log('onPage');
      console.log(event.page);
      console.log(event.sortField);
      console.log(event.sortOrder);

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

          this.createWithFormData(this.item);
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
