<template>
  <Toolbar
     :handle-add="addHandler"
     :handle-add-document="addDocumentHandler"
     :handle-upload-document="uploadDocumentHandler"
     :filters="filters"
     :on-send-filter="onSendFilter"
     :reset-filter="resetFilter"
   />

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
    <q-table
        dense
        :rows="items"
        :columns="columns"
        row-key="@id"
        @request="onRequest"
        v-model:pagination="pagination"
        :no-data-label="$t('Data unavailable')"
        :no-results-label="$t('No results')"
        :loading-label="$t('Loading...')"
        :rows-per-page-label="$t('Records per page:')"
        :rows-per-page-options="[10, 20, 50, 0]"
        :loading="isLoading"
        selection="multiple"
        v-model:selected="selectedItems"
    >
       <template v-slot:body="props">
          <q-tr :props="props">
            <q-td auto-width>
              <q-checkbox dense v-model="props.selected" />
            </q-td>

            <q-td key="resourceNode.title" :props="props">
              <div v-if="props.row.resourceNode.resourceFile">
                <a
                    data-fancybox="gallery"
                    :href="props.row.contentUrl"
                >
                  <ResourceFileIcon :file="props.row" />
                  {{ props.row.title }}
                </a>
              </div>
              <div v-else>
                <a @click="handleClick(props.row)" class="cursor-pointer" >
                  <font-awesome-icon
                      icon="folder"
                      size="lg"
                  />
                  {{ props.row.resourceNode.title }}
                </a>
              </div>
            </q-td>

            <q-td key="resourceNode.updatedAt" :props="props">
              {{$luxonDateTime.fromISO(props.row.resourceNode.updatedAt).toRelative() }}
            </q-td>

            <q-td key="resourceNode.resourceFile.size" :props="props">
              <span v-if="props.row.resourceNode.resourceFile">
              {{
                $filters.prettyBytes(props.row.resourceNode.resourceFile.size)
              }}
              </span>
            </q-td>

            <q-td key="action" :props="props">
            <ActionCell
                :handle-show="() => showHandler(props.row)"
                :handle-edit="() => editHandler(props.row)"
                :handle-delete="() => deleteHandler(props.row)"
            />
            </q-td>
          </q-tr>
        </template>
      </q-table>

</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
import Toolbar from '../../components/Toolbar.vue';
import ResourceFileIcon from './ResourceFileIcon.vue';
import { useRoute } from 'vue-router'
import DataFilter from '../../components/DataFilter';
import DocumentsFilterForm from '../../components/documents/Filter';
import { ref, reactive, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import isEmpty from 'lodash/isEmpty';

export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    Toolbar,
    ActionCell,
    ResourceFileIcon,
    DocumentsFilterForm,
    DataFilter
  },
  mixins: [ListMixin],
  data() {
    return {
      sortBy: 'title',
      sortDesc: false,
      columns: [
        {align: 'left', name: 'resourceNode.title', label: this.$i18n.t('Title'), field: 'resourceNode.title', sortable: true},
        {align: 'left', name: 'resourceNode.updatedAt', label: this.$i18n.t('Modified'), field: 'resourceNode.updatedAt', sortable: true},
        {name: 'resourceNode.resourceFile.size', label: this.$i18n.t('Size'), field: 'resourceNode.resourceFile.size', sortable: true},
        {name: 'action', label: this.$i18n.t('Actions'), field: 'action', sortable: false}
      ],
      //pageOptions: [5, 10, 15, 20, this.$i18n.t('All')],
      selected: [],
      isBusy: false,
      options: [],
      selectedItems: [],
    };
  },
  created() {

  },
  mounted() {
    console.log('vue/views/documents/List.vue');
    const route = useRoute()
    let nodeId = route.params['node'];
    if (!isEmpty(nodeId)) {
      this.findResourceNode('/api/resource_nodes/' + nodeId);
    }

    this.onRequest({
      pagination: this.pagination,
    });
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
