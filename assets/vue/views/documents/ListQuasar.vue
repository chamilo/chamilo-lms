<template>
   <div class="q-pa-md">
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
        <q-table
            :rows="items"
            :columns="columns"
            row-key="iid"
            @request="onRequest"
            :no-data-label="$t('Data unavailable')"
            :no-results-label="$t('No results')"
            :loading-label="$t('Loading...')"
            :rows-per-page-label="$t('Records per page:')"
            :loading="isLoading"
        >

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

          <template v-slot:body-cell-updatedAt="props">
            <q-td slot="body-cell-updatedAt" auto-width>
              {{
                  moment(props.row.resourceNode.updatedAt).fromNow()
              }}
            </q-td>
          </template>

          <template v-slot:body-cell-size="props">
            <q-td slot="body-cell-updatedAt" auto-width>
              <span v-if="props.row.resourceNode.resourceFile">
                 {{ $filters.prettyBytes(props.row.resourceNode.resourceFile.size)  }}
              </span>
            </q-td>
          </template>

          <template v-slot:body-cell-action="props">
            <ActionCell
                slot="body-cell-action"
                slot-scope="props"
                :handle-show="() => showHandler(props.row)"
                :handle-edit="() => editHandler(props.row)"
                :handle-delete="() => deleteHandler(props.row)"
            />
          </template>
        </q-table>
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

/*const servicePrefix = 'documents';
const { getters, actions } = list(servicePrefix);*/
export default {
  name: 'DocumentsList',
  servicePrefix: 'Documents',
  components: {
    Toolbar,
    ActionCell,
    ResourceFileIcon,
  },
  setup() {
    //this.moment = moment;
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
      isBusy: false
    };
  },
  created() {
    //console.log('created assets/vue/views/documents/List.vue');
    this.moment = moment;
    const route = useRoute()
    let nodeId = route.params['node'];
    this.findResourceNode('/api/resource_nodes/' + nodeId);
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

      this.deleteMultipleItem(this.selected);
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
    sortingChanged(ctx) {
      this.options.sortDesc = ctx.sortDesc;
      this.options.sortBy = ctx.sortBy;
      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    //...actions,
    // From ListMixin
    ...mapActions('documents', {
      getPage: 'fetchAll',
      deleteItem: 'del',
      deleteMultipleItem: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
