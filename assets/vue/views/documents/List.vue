<template>
  <span class="documents-list">
    <Toolbar
      :handle-add="addHandler"
      :handle-add-document="addDocumentHandler"
      :handle-upload-document="uploadDocumentHandler"

      :filters="filters"
      :on-send-filter="onSendFilter"
      :reset-filter="resetFilter"
    />

    <br>
    <b-row class="text-center">
      <b-col>
        <form class="form-inline">
          <div class="form-group mb-2">
            <b-form-select
              id="perPageSelect"
              v-model="options.itemsPerPage"
              size="sm"
              :options="pageOptions"
              @input="onUpdateOptions(options)"
            />
          </div>
        </form>
      </b-col>
      <b-col />
      <b-col>
<!--        <div v-if="this.selected.length > 0" >-->
<!--          <b-button variant="info" size="sm" @click="deleteSelected">-->
<!--              {{ $t('Info') }}-->
<!--            </b-button>-->

<!--           <b-button variant="secondary" size="sm" @click="deleteSelected">-->
<!--              {{ $t('Edit') }}-->
<!--            </b-button>-->

<!--           <b-button variant="danger" size="sm" @click="deleteSelected">-->
<!--              {{ $t('Delete') }}-->
<!--            </b-button>-->
<!--        </div>-->

        <b-pagination
          v-model="options.page"
          :disabled.sync="isLoading"
          :total-rows="totalItems"
          :per-page="options.itemsPerPage"
          aria-controls="documents"
          align="right"
          size="sm"
          @input="onUpdateOptions(options)"
        />
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <b-table
          id="documents"
          ref="selectableTable"
          striped
          hover
          no-local-sorting
          responsive="sm"
          :per-page="0"
          :fields="fields"
          :items="items"
          @row-selected="onRowSelected"
          selectable
          small
          :current-page.sync="options.page"
          :sort-by.sync="options.sortBy"
          :sort-desc.sync="options.sortDesc"

          :busy.sync="isLoading"
          :filters="filters"
          primary-key="iid"
          @sort-changed="sortingChanged"
        >
          <template v-slot:table-busy>
            <div class="text-center my-2">
              <b-spinner class="align-middle" />
              <strong>{{ $t('Loading ...') }}</strong>
            </div>
          </template>

<!--          <template v-slot:cell(selected)="{ rowSelected }">-->
<!--            <template v-if="rowSelected">-->
<!--              <span aria-hidden="true">&check;</span>-->
<!--              <span class="sr-only">{{ $t('Selected') }}</span>-->
<!--            </template>-->
<!--            <template v-else>-->
<!--              <span aria-hidden="true">&nbsp;</span>-->
<!--              <span class="sr-only">{{ $t('Not selected') }}</span>-->
<!--            </template>-->
<!--          </template>-->

          <template
            v-slot:cell(resourceNode.title)="row"
          >
            <div v-if="row.item['resourceNode']['resourceFile']">
              <a
                data-fancybox="gallery"
                :href="row.item['contentUrl'] "
              >
                <ResourceFileIcon :file="row.item['resourceNode']['resourceFile']" />

                {{ row.item['resourceNode']['title'] }}
              </a>
            </div>
            <div v-else>
              <a @click="handleClick(row.item)">
                <font-awesome-icon icon="folder" />
                {{ row.item['resourceNode']['title'] }}
              </a>
            </div>
          </template>

          <template
            v-slot:cell(resourceNode.updatedAt)="row"
          >
            {{ row.item.resourceNode.updatedAt | moment("from", "now") }}
          </template>

          <template
            v-slot:cell(resourceNode.resourceFile.size)="row"
          >
            <span
              v-if="row.item['resourceNode']['resourceFile']"
            >
              {{ row.item.resourceNode.resourceFile.size | prettyBytes }}
            </span>

          </template>

          <template
              v-if="isAuthenticated && (isCurrentTeacher || isAdmin)"
            v-slot:cell(action)="row"
          >
            <ActionCell
              slot="action"
              :row="row"
              :handle-show="() => showHandler(row.item)"
              :handle-edit="() => editHandler(row.item)"
              :handle-delete="() => deleteHandler(row.item)"
            />
          </template>

            <template
                v-else
                v-slot:cell(action)="row"
            >
            <ActionCell
                slot="action"
                :row="row"
                :handle-show="() => showHandler(row.item)"
            />
          </template>


        </b-table>

        <p>
          <b-button size="sm" @click="selectAllRows">{{ $t('Select all') }}</b-button>
          <b-button size="sm" @click="clearSelected">{{ $t('Clear selected') }}</b-button>
            <b-button v-if="this.selected.length > 0" variant="danger" size="sm" @click="deleteSelected">
              {{ $t('Delete') }}
            </b-button>
        </p>
      </b-col>
    </b-row>
  </span>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell';
import Toolbar from '../../components/Toolbar';
import ResourceFileIcon from './ResourceFileIcon';

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
      fields: [
        {label: this.$i18n.t('Title'), key: 'resourceNode.title', sortable: true},
        {label: this.$i18n.t('Modified'), key: 'resourceNode.updatedAt', sortable: true},
        {label: this.$i18n.t('Size'), key: 'resourceNode.resourceFile.size', sortable: true},
        {label: this.$i18n.t('Actions'), key: 'action', sortable: false}
      ],
      pageOptions: [5, 10, 15, 20, this.$i18n.t('All')],
      selected: [],
      selectMode: 'multi',
      isBusy: false
    };

  },
  created() {
    let nodeId = this.$route.params['node'];
    this.findResourceNode('/api/resource_nodes/' + nodeId);
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
