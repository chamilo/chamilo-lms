<template>
  <div class="documents-list">
    <Toolbar
      :handle-add="addHandler"
      :handle-add-document="addDocumentHandler"
      :handle-upload-document="uploadDocumentHandler"
    />

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
        <b-pagination
          v-model="options.page"
          align="right"
          :total-rows="totalItems"
          :per-page="options.itemsPerPage"
          aria-controls="documents"
          @input="onUpdateOptions(options)"
        />
      </b-col>
    </b-row>
    <b-row>
      <b-col>
        <DataFilter
          :handle-filter="onSendFilter"
          :handle-reset="resetFilter"
        >
          <DocumentsFilterForm
            ref="filterForm"
            slot="filter"
            :values="filters"
          />
        </DataFilter>
        <br>
        <b-table
          id="documents"
          class="table table-bordered data_table"
          striped
          hover
          selectable
          select-mode="single"

          :fields="fields"
          :items="items"
          :per-page.sync="options.itemsPerPage"
          :current-page="options.page"
          :sort-desc.sync="options.sortDesc"
          :busy.sync="isLoading"
          :filters="filters"
          primary-key="iid"
        >
          <template
            v-slot:cell(resourceNode.title)="row"
          >
            <div v-if="row.item['resourceNode']['resourceFile']">
              <a
                data-fancybox="gallery"
                :href="row.item['contentUrl'] "
              >
                <font-awesome-icon icon="file" />
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
        </b-table>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell';
import DocumentsFilterForm from '../../components/documents/Filter';
import DataFilter from '../../components/DataFilter';
import Toolbar from '../../components/Toolbar';

export default {
    name: 'DocumentsList',
    servicePrefix: 'Documents',
    components: {
        Toolbar,
        ActionCell,
        DocumentsFilterForm,
        DataFilter
    },
    mixins: [ListMixin],
    data() {
        return {
          fields: [
                {label: 'Title', key: 'resourceNode.title', sortable: true},
                {label: 'Modified', key: 'resourceNode.updatedAt', sortable: true},
                {label: 'Size', key: 'resourceNode.resourceFile.size', sortable: true},
                {label: 'Actions', key: 'action', sortable: false}
            ],
            selected: [],
            pageOptions: [5, 10, 15, 20],
        };
    },
    created() {
        let nodeId = this.$route.params['node'];
        this.findResourceNode('/api/resource_nodes/'+ nodeId);
        this.onUpdateOptions(this.options);
    },
    computed: {
        // From crud.js list function
        ...mapGetters('documents', {
            items: 'list'
        }),
        ...mapGetters('resourcenode', {
            resourceNode: 'getResourceNode'
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
        // From ListMixin
        ...mapActions('documents', {
            getPage: 'fetchAll',
            deleteItem: 'del'
        }),
        ...mapActions('resourcenode', {
            findResourceNode: 'findResourceNode',
        }),
    }
};
</script>
