<template>
  <Button
    :label="$t('Back')"
    icon="pi pi-chevron-left"
    @click="goBack"
  />
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
    :value="itemsShared"
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
      :header="$t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.firstResourceFile">
          <ResourceFileLink :resource="slotProps.data" />
        </div>
        <div v-else>
          <a
            v-if="slotProps.data"
            class="cursor-pointer"
            @click="handleClick(slotProps.data)"
          >
            <v-icon icon="mdi-folder" />
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>
      </template>
    </Column>

    <Column
      :header="$t('Size')"
      :sortable="true"
      field="resourceNode.firstResourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.firstResourceFile
            ? prettyBytes(slotProps.data.resourceNode.firstResourceFile.size)
            : ""
        }}
      </template>
    </Column>

    <Column
      :header="$t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button
            class="btn btn--primary"
            icon="pi pi-info-circle"
            @click="showHandler(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ActionCell from "../../components/ActionCell.vue"
import ResourceIcon from "../../components/documents/ResourceIcon.vue"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"
import DataFilter from "../../components/DataFilter"
import isEmpty from "lodash/isEmpty"
import { useFormatDate } from "../../composables/formatDate"
import prettyBytes from "pretty-bytes"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

export default {
  name: "PersonalFileShared",
  servicePrefix: "PersonalFile",
  components: {
    //8Toolbar,
    ActionCell,
    ResourceIcon,
    ResourceFileLink,
    DataFilter,
  },
  data() {
    const securityStore = useSecurityStore()
    const { t } = useI18n()
    const { relativeDatetime } = useFormatDate()

    const { isAdmin, user, isAuthenticated } = storeToRefs(securityStore)

    return {
      sortBy: "title",
      sortDesc: false,
      columns: [
        { label: t("Title"), field: "title", name: "title", sortable: true },
        { label: t("Modified"), field: "resourceNode.updatedAt", name: "updatedAt", sortable: true },
        { label: t("Size"), field: "resourceNode.firstResourceFile.size", name: "size", sortable: true },
        { label: t("Actions"), name: "action", sortable: false },
      ],
      pageOptions: [10, 20, 50, t("All")],
      selected: [],
      isBusy: true,
      options: [],
      selectedItems: [],
      // prime vue
      itemDialog: false,
      deleteItemDialog: false,
      deleteMultipleDialog: false,
      item: {},
      filters: { shared: 1, loadNode: 0 },
      submitted: false,
      relativeDatetime,
      prettyBytes,
      t,
      isAuthenticated,
      isAdmin,
      currentUser: user,
    }
  },
  created() {
    this.resetList = true
    console.log("CREATED SHARED")
  },
  mounted() {
    console.log("MOUNTED SHARED")
    this.resetList = true
  },
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),

    ...mapGetters("personalfile", {
      itemsShared: "list",
    }),

    //...getters

    // From ListMixin
    ...mapFields("personalfile", {
      deletedResource: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),
  },
  methods: {
    goBack() {
      this.$router.go(-1)
    },
    // This is a copy of the ListMixin, it doesnt adds the resourceNode
    onUpdateOptions({ page, itemsPerPage, sortBy, sortDesc, totalItems } = {}) {
      console.log("onUpdateOptions")
      this.resetList = true
      let params = {
        ...this.filters,
      }

      if (itemsPerPage > 0) {
        params = { ...params, itemsPerPage, page }
      }

      // prime
      if (!isEmpty(sortBy)) {
        params[`order[${sortBy}]`] = sortDesc ? "desc" : "asc"
      }

      let type = this.$route.query.type

      params = { ...params, type }

      /*if (!isEmpty(sortBy) && !isEmpty(sortDesc)) {
        params[`order[${sortBy[0]}]`] = sortDesc[0] ? 'desc' : 'asc'
      }*/
      console.log(params)

      this.getPage(params).then(() => {
        this.options.sortBy = sortBy
        this.options.sortDesc = sortDesc
        this.options.itemsPerPage = itemsPerPage
        this.options.totalItems = totalItems
      })
    },
    showHandler(item) {
      let folderParams = this.$route.query
      if (item) {
        folderParams["id"] = item["@id"]
      }
      console.log(folderParams)

      this.$router.push({
        name: `${this.$options.servicePrefix}Show`,
        query: folderParams,
      })
    },
    // prime
    onPage(event) {
      console.log("onPage")
      console.log(event)
      console.log(event.page)
      console.log(event.sortField)
      console.log(event.sortOrder)

      this.options.itemsPerPage = event.rows
      this.options.page = event.page + 1
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1

      this.onUpdateOptions(this.options)
    },
    sortingChanged(event) {
      console.log("sortingChanged")
      console.log(event)
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1

      this.onUpdateOptions(this.options)
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    //...actions,
    // From ListMixin
    ...mapActions("personalfile", {
      getPage: "fetchAll",
      deleteItem: "del",
      deleteMultipleAction: "delMultiple",
    }),
    ...mapActions("resourcenode", {
      findResourceNode: "findResourceNode",
    }),
  },
}
</script>
