<template>
  <div class="documents-list">
    <Toolbar
      :handle-add="addHandler"
      :handle-add-document="addDocumentHandler"
      :handle-upload-document="uploadDocumentHandler"
    />
    <b-container fluid>
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
            striped
            hover

            :fields="fields"
            :items="items"
            :per-page="0"
            :current-page="options.page"
            :sort-desc.sync="options.sortDesc"
            :busy.sync="isLoading"
            :filters="filters"
            primary-key="iid"
            @input="onUpdateOptions()"
          >
            <template
              v-slot:cell(resourceNode.title)="row"
            >
              <div v-if="row.item['resourceNode']['resourceFile']">
                <a
                  data-fancybox="gallery"
                  :href="row.item['contentUrl'] "
                >
                  <v-icon
                    left
                    color="primary"
                  >mdi-file</v-icon> {{ row.item['resourceNode']['title'] }}
                </a>
              </div>
              <div v-else>
                <a @click="handleClick(row.item)">
                  <v-icon left>mdi-folder</v-icon>{{ row.item['resourceNode']['title'] }}
                </a>
              </div>
            </template>

            <template
              v-slot:cell(resourceNode.updatedAt)="row"
            >
              {{ row.item.resourceNode.updatedAt | moment("from", "now") }}
            </template>

            <ActionCell
              slot="action"
              slot-scope="props"
              :handle-show="() => showHandler(props.item)"
              :handle-edit="() => editHandler(props.item)"
              :handle-delete="() => deleteHandler(props.item)"
            />
          </b-table>

          <b-pagination
            :v-model="options.page"
            :total-rows="totalItems"
            :per-page="options.itemsPerPage"
            aria-controls="documents"
            @input="onUpdateOptions()"
          />
        </b-col>
      </b-row>
    </b-container>
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
        };
    },
    created() {
        let nodeId = this.$route.params['node'];
        this.findResourceNode('/api/resource_nodes/'+ nodeId);
        this.onUpdateOptions();
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
