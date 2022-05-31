<template>
  <DataTable
      class="p-datatable-sm"
      :value="itemsShared"
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
            <v-icon icon="mdi-folder"/>
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>
      </template>
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
        {{ $filters.relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button icon="fa fa-info-circle"  class="btn btn--primary " @click="showHandler(slotProps.data)" />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ListMixin from '../../mixins/ListMixin';
import ActionCell from '../../components/ActionCell.vue';
import ResourceFileIcon from '../../components/documents/ResourceFileIcon.vue';
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue';

import { useRoute } from 'vue-router'
import DataFilter from '../../components/DataFilter';
import isEmpty from 'lodash/isEmpty';
import toInteger from "lodash/toInteger";

export default {
  name: 'PersonalFileShared',
  servicePrefix: 'PersonalFile',
  components: {
    //8Toolbar,
    ActionCell,
    ResourceFileIcon,
    ResourceFileLink,
    DataFilter
  },
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
      isBusy: true,
      options: [],
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteMultipleDialog: false,
      item: {},
      filters: {shared: 1, loadNode: 0},
      submitted: false,
    };
  },
  created() {
    this.resetList = true;
    console.log('CREATED SHARED');
  },
  mounted() {
    console.log('MOUNTED SHARED');
    this.resetList = true;
  },
  computed: {
    // From crud.js list function
    ...mapGetters('resourcenode', {
      resourceNode: 'getResourceNode'
    }),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'isAdmin': 'security/isAdmin',
      'currentUser': 'security/getUser',
    }),

    ...mapGetters('personalfile', {
      itemsShared: 'list',
    }),

    //...getters

    // From ListMixin
    ...mapFields('personalfile', {
      deletedResource: 'deleted',
      error: 'error',
      isLoading: 'isLoading',
      resetList: 'resetList',
      totalItems: 'totalItems',
      view: 'view'
    }),
  },
  methods: {
    // This is a copy of the ListMixin, it doesnt adds the resourceNode
    onUpdateOptions({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      console.log('onUpdateOptions');
      this.resetList = true;
      let params = {
        ...this.filters
      }

      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page };
      }

      // prime
      if (!isEmpty(sortBy)) {
        params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc'
      }

      let type = this.$route.query.type;

      params = { ...params, type };

      // vuetify
      /*if (!isEmpty(sortBy) && !isEmpty(sortDesc)) {
        params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
      }*/
      console.log(params);

      this.getPage(params).then(() => {
        this.options.sortBy = sortBy;
        this.options.sortDesc = sortDesc;
        this.options.itemsPerPage = itemsPerPage;
        this.options.totalItems = totalItems;
      });
    },
    showHandler(item) {
      let folderParams = this.$route.query;
      if (item) {
        folderParams['id'] = item['@id'];
      }
      console.log(folderParams);

      this.$router.push({
        name: `${this.$options.servicePrefix}Show`,
        query: folderParams
      });
    },
    // prime
    onPage(event) {
      console.log('onPage');
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
    sortingChanged(event) {
      console.log('sortingChanged');
      console.log(event);
      this.options.sortBy = event.sortField;
      this.options.sortDesc = event.sortOrder === -1;

      this.onUpdateOptions(this.options);
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    //...actions,
    // From ListMixin
    ...mapActions('personalfile', {
      getPage: 'fetchAll',
      deleteItem: 'del',
      deleteMultipleAction: 'delMultiple'
    }),
    ...mapActions('resourcenode', {
      findResourceNode: 'findResourceNode',
    }),
  }
};
</script>
