<template>
  <Toolbar class="p-mb-4">
    <template #left>
      <div
        v-if="isAuthenticated && isCurrentTeacher"
        class="flex flex-row gap-2"
      >
        <!--         <Button label="New" icon="pi pi-plus" class="p-button-primary p-button-sm p-mr-2" @click="openNew" />-->
        <Button
          class="btn btn--primary"
          icon="pi pi-plus"
          label="New folder"
          @click="openNew"
        />

        <!--         <Button label="New folder" icon="pi pi-plus" class="p-button-success p-mr-2" @click="addHandler()" />-->
        <!--         <Button label="New document" icon="pi pi-plus" class="p-button-sm p-button-primary p-mr-2" @click="addDocumentHandler()" />-->
        <Button
          class="btn btn--primary"
          icon="pi pi-plus"
          label="Upload"
          @click="uploadDocumentHandler()"
        />
      </div>
    </template>
  </Toolbar>

  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedItems"
    :globalFilterFields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="10"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    :totalRecords="totalItems"
    :value="items"
    class="p-datatable-sm"
    currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
    dataKey="iid"
    filterDisplay="menu"
    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsiveLayout="scroll"
    @page="onPage($event)"
    @sort="sortingChanged($event)"
  >
    <Column
      :header="$t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.resourceFile">
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
      :header="$t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column
      :header="$t('Size')"
      :sortable="true"
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile
            ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size)
            : ""
        }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button
            class="p-button-sm p-button p-mr-2"
            label="Select"
            @click="returnToEditor(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog
    v-model:visible="itemDialog"
    :header="$t('New folder')"
    :modal="true"
    :style="{ width: '450px' }"
    class="p-fluid"
  >
    <div class="p-field">
      <label for="name">{{ $t("Name") }}</label>
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{ 'p-invalid': submitted && !item.title }"
        autocomplete="off"
        autofocus
        required="true"
      />
      <small
        v-if="submitted && !item.title"
        class="p-error"
        >$t('Title is required')</small
      >
    </div>

    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="Cancel"
        @click="hideDialog"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Save"
        @click="saveItem"
      />
    </template>
  </Dialog>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ListMixin from "../../mixins/ListMixin"
import ActionCell from "../../components/ActionCell.vue"
//import Toolbar from '../../components/Toolbar.vue';
import ResourceIcon from "../../components/documents/ResourceIcon.vue"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"
import DataFilter from "../../components/DataFilter"
import DocumentsFilterForm from "../../components/documents/Filter"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"

export default {
  name: "DocumentForHtmlEditor",
  servicePrefix: "Documents",
  components: {
    //8Toolbar,
    ActionCell,
    ResourceIcon,
    ResourceFileLink,
    DocumentsFilterForm,
    DataFilter,
  },
  mixins: [ListMixin],
  setup() {
    const { t } = useI18n()
    const { relativeDatetime } = useFormatDate()

    const data = {
      sortBy: "title",
      sortDesc: false,
      columns: [
        { label: t("Title"), field: "title", name: "title", sortable: true },
        { label: t("Modified"), field: "resourceNode.updatedAt", name: "updatedAt", sortable: true },
        { label: t("Size"), field: "resourceNode.resourceFile.size", name: "size", sortable: true },
        { label: t("Actions"), name: "action", sortable: false },
      ],
      pageOptions: [10, 20, 50, t("All")],
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
      relativeDatetime,
    }

    return data
  },
  created() {
    console.log("created - vue/views/documents/DocumentsList.vue")
    this.filters["loadNode"] = 1
  },
  mounted() {
    console.log("mounted - vue/views/documents/DocumentsList.vue")
    this.filters["loadNode"] = 1
    this.onUpdateOptions(this.options)
  },
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      isAdmin: "security/isAdmin",
      isCurrentTeacher: "security/isCurrentTeacher",
    }),

    ...mapGetters("documents", {
      items: "list",
    }),

    //...getters

    // From ListMixin
    ...mapFields("documents", {
      deletedResource: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),
  },
  methods: {
    sortingChanged(event) {
      console.log("sortingChanged")
      console.log(event)
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1

      this.onUpdateOptions(this.options)
      // ctx.sortBy   ==> Field key for sorting by (or null for no sorting)
      // ctx.sortDesc ==> true if sorting descending, false otherwise
    },
    openNew() {
      this.item = {}
      this.submitted = false
      this.itemDialog = true
    },
    hideDialog() {
      this.itemDialog = false
      this.submitted = false
    },
    saveItem() {
      this.submitted = true

      if (this.item.title.trim()) {
        if (this.item.id) {
        } else {
          //this.products.push(this.product);
          this.item.parentResourceNodeId = this.$route.params.node
          this.item.resourceLinkList = JSON.stringify([
            {
              gid: this.$route.query.gid,
              sid: this.$route.query.sid,
              cid: this.$route.query.cid,
              visibility: RESOURCE_LINK_PUBLISHED, // visible by default
            },
          ])

          this.create(this.item)
          this.showMessage("Saved")
        }

        this.itemDialog = false
        this.item = {}
      }
    },
    editItem(item) {
      this.item = { ...item }
      this.itemDialog = true
    },
    returnToEditor(item) {
      const url = item.contentUrl

      // Tiny mce.
      window.parent.postMessage(
        {
          url: url,
        },
        "*",
      )

      if (parent.tinymce) {
        parent.tinymce.activeEditor.windowManager.close()
      }

      // Ckeditor
      function getUrlParam(paramName) {
        var reParam = new RegExp("(?:[\?&]|&amp;)" + paramName + "=([^&]+)", "i")
        var match = window.location.search.match(reParam)
        return match && match.length > 1 ? match[1] : ""
      }

      var funcNum = getUrlParam("CKEditorFuncNum")
      if (window.opener.CKEDITOR) {
        window.opener.CKEDITOR.tools.callFunction(funcNum, url)
        window.close()
      }
    },
    async fetchItems() {
      console.log("fetchItems")
      /* No need to call if all items retrieved */
      if (this.items.length === this.totalItems) return

      /* Enable busy state */
      this.isBusy = true

      /* Missing error handling if call fails */
      let currentPage = this.options.page
      console.log(currentPage)
      const startIndex = currentPage++ * this.options.itemsPerPage
      const endIndex = startIndex + this.options.itemsPerPage

      console.log(this.items.length)
      console.log(this.totalItems)
      console.log(startIndex, endIndex)

      this.options.page = currentPage

      await this.fetchNewItems(this.options)

      //const newItems = await this.callDatabase(startIndex, endIndex);

      /* Add new items to existing ones */
      //this.items = this.items.concat(newItems);

      /* Disable busy state */
      this.isBusy = false
      return true
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
    ...mapActions("documents", {
      getPage: "fetchAll",
      create: "create",
      deleteItem: "del",
      deleteMultipleAction: "delMultiple",
    }),
    ...mapActions("resourcenode", {
      findResourceNode: "findResourceNode",
    }),
  },
}
</script>