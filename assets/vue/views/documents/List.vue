<template>
   <div>
<!--    <Toolbar-->
<!--           :handle-add="addHandler"-->
<!--           :handle-add-document="addDocumentHandler"-->
<!--           :handle-upload-document="uploadDocumentHandler"-->

<!--           :filters="filters"-->
<!--           :on-send-filter="onSendFilter"-->
<!--           :reset-filter="resetFilter"-->
<!--         />-->


<!--        <q-table-->
<!--            id="documents"-->
<!--            ref="selectableTable"-->
<!--            title="Documents"-->
<!--            :rows="items"-->
<!--            :columns="fields"-->
<!--            row-key="iid"-->
<!--        />-->
<!--        :pagination.sync="pagination"-->
<!--            @request="onRequest"-->
<!--:loading="isLoading"-->
<!--        <q-table-->
<!--            :rows="items"-->
<!--            :columns="columns"-->
<!--            row-key="iid"-->
<!--            @request="onRequest"-->
<!--            :no-data-label="$t('Data unavailable')"-->
<!--            :no-results-label="$t('No results')"-->
<!--            :loading-label="$t('Loading...')"-->
<!--            :rows-per-page-label="$t('Records per page:')"-->
<!--            :loading="isLoading"-->
<!--        >-->
<!--     { label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},-->
<!--     { label: this.$i18n.t('Modified'), field: 'resourceNode.updatedAt', name: 'updatedAt', sortable: true},-->
<!--     { label: this.$i18n.t('Size'), field: 'resourceNode.resourceFile.size', name: 'size', sortable: true},-->
<!--     { label: this.$i18n.t('Actions'), name: 'action', sortable: false}-->

     <Toolbar class="p-mb-4">
       <template #left>
<!--         <Button label="New" icon="pi pi-plus" class="p-button-primary p-button-sm p-mr-2" @click="openNew" />-->
         <Button label="New" icon="pi pi-plus" class="btn btn-primary" @click="openNew" />

<!--         <Button label="New folder" icon="pi pi-plus" class="p-button-success p-mr-2" @click="addHandler()" />-->
<!--         <Button label="New document" icon="pi pi-plus" class="p-button-sm p-button-primary p-mr-2" @click="addDocumentHandler()" />-->
         <Button label="New document" icon="pi pi-plus" class="btn btn-primary" @click="addDocumentHandler()" />

         <Button label="Upload" icon="pi pi-plus" class="btn btn-primary" @click="uploadDocumentHandler()" />
         <Button label="Delete" icon="pi pi-trash" class="btn btn-danger " @click="confirmDeleteSelected" :disabled="!selectedItems || !selectedItems.length" />
       </template>

<!--       <template #right>-->
<!--         <FileUpload mode="basic" accept="image/*" :maxFileSize="1000000" label="Import" chooseLabel="Import" class="p-mr-2 p-d-inline-block" />-->
<!--         <Button label="Export" icon="pi pi-upload" class="p-button-help" @click="exportCSV($event)"  />-->
<!--       </template>-->
     </Toolbar>

     <DataTable
         :value="items"
         v-model:selection="selectedItems"
         dataKey="iid"
         :filters="filters"
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
     >
       <Column selectionMode="multiple" style="width: 3rem" :exportable="false"></Column>
       <Column field="resourceNode.title" :header="$t('Title')" :sortable="true"></Column>
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
           <Button label="Show" class="p-button-sm p-button p-button-success p-mr-2" @click="showHandler(slotProps.data)" />
           <Button label="Edit" icon="pi pi-pencil" class="p-button-sm p-button p-button-success p-mr-2" @click="editHandler(slotProps.data)" />
           <Button label="Delete" icon="pi pi-trash" class="p-button-sm p-button p-button-danger" @click="confirmDeleteSelected(slotProps.data)" />
         </template>
       </Column>

       <template #paginatorLeft>
         <Button type="button" icon="pi pi-refresh" class="p-button-text" />
       </template>
       <template #paginatorRight>
         <Button type="button" icon="pi pi-cloud" class="p-button-text" />
       </template>
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
         <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteItem" />
       </template>
     </Dialog>

     <Dialog v-model:visible="deleteItemsDialog" :style="{width: '450px'}" header="Confirm" :modal="true">
       <div class="confirmation-content">
         <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
         <span v-if="item">Are you sure you want to delete the selected products?</span>
       </div>
       <template #footer>
         <Button label="No" icon="pi pi-times" class="p-button-text" @click="deleteItemsDialog = false"/>
         <Button label="Yes" icon="pi pi-check" class="p-button-text" @click="deleteSelectedItems" />
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
   </div>
</template>

<script>
//import { list } from '../../utils/vuexer';
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from './ResourceFileIcon.vue';
import { useRoute } from 'vue-router'
import moment from 'moment'

//import { useToast } from 'primevue/usetoast';
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';

/*const servicePrefix = 'documents';
const { getters, actions } = list(servicePrefix);*/

import isEmpty from 'lodash/isEmpty';

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    Toolbar,
    ActionCell,
    ResourceFileIcon,
  },
  mixins: [ListMixin],
  data() {
    return {
      sortBy: 'title',
      sortDesc: false,
      columns: [
        //{ name: 'action' },
        //{ name: 'id', field: '@id', label: this.$t('iid') },
        { label: this.$i18n.t('Title'), field: 'title', name: 'title', sortable: true},
        { label: this.$i18n.t('Modified'), field: 'resourceNode.updatedAt', name: 'updatedAt', sortable: true},
        { label: this.$i18n.t('Size'), field: 'resourceNode.resourceFile.size', name: 'size', sortable: true},
        { label: this.$i18n.t('Actions'), name: 'action', sortable: false}
      ],
      pageOptions: [5, 10, 15, 20, this.$i18n.t('All')],
      selected: [],
      selectMode: 'multi',
      isBusy: false,
      options: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteItemsDialog: false,
      item: {},
      selectedItems: null,
      filters: {},
      submitted: false,
    };
  },
  created() {
    console.log('vue/views/documents/List.vue');
    this.moment = moment;
    const route = useRoute()
    let nodeId = route.params['node'];
    if (!isEmpty(nodeId)) {
      this.findResourceNode('/api/resource_nodes/' + nodeId);
    }
    this.options.getPage = this.getPage;
    this.onUpdateOptions(this.options);
  },
  mounted() {
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
    ...mapGetters('documents', {
      items: 'list',
    }),
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode'
    }),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'isCurrentTeacher': 'security/isCurrentTeacher',
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
    onPage(event) {
      console.log(event);
      console.log(event.page);
      console.log(event.sortField);
      console.log(event.sortOrder);

      this.options.itemsPerPage = event.rows;
      this.options.page = event.page + 1;
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
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
          //this.products[this.findIndexById(this.product.id)] = this.product;
          //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Product Updated', life: 3000});
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
          //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Product Created', life: 3000});
          this.showMessage('Saved');
        }

        this.itemDialog = false;
        this.item = {};
      }
    },
    editItem(product) {
      this.product = {...product};
      this.itemDialog = true;
    },
    confirmDeleteItem(product) {
      this.product = product;
      this.deleteProductDialog = true;
    },
    deleteItem() {
      console.log('deleteItem');
      this.deleteItemAction(this.item);
      this.items = this.items.filter(val => val.iid !== this.item.iid);
      this.deleteProductDialog = false;
      this.item = {};
    },
    findIndexById(id) {
      let index = -1;
      for (let i = 0; i < this.products.length; i++) {
        if (this.products[i].id === id) {
          index = i;
          break;
        }
      }

      return index;
    },
    exportCSV() {
      this.$refs.dt.exportCSV();
    },
    confirmDeleteSelected() {
      this.deleteItemsDialog = true;
    },
    deleteSelectedItems() {
      this.deleteMultipleAction(this.selectedItems);
      this.onUpdateOptions(this.options);
      /*this.products = this.products.filter(val => !this.selectedProducts.includes(val));*/
      this.deleteItemsDialog = false;
      this.selectedItems = null;
      //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
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
    allSelected() {
    },
    toggleSelected() {
    },
    async deleteSelected() {
      console.log('deleteSelected');
      /*for (let i = 0; i < this.selected.length; i++) {
        let item = this.selected[i];
        //this.deleteHandler(item);
        this.deleteItem(item);
      }*/

      this.deleteMultipleAction(this.selected);
      this.onUpdateOptions(this.options);

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
    sortingChanged(event) {
      console.log('sortingChanged');
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    //...actions,
    // From ListMixin
    ...mapActions('documents', {
      getPage: 'fetchAll',
      create: 'create',
      deleteItemAction: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
