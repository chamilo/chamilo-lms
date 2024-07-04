<template>
  <div
    v-if="isAuthenticated"
    class="q-card"
  >
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2">
        <Button
          class="btn btn--primary"
          icon="fa fa-folder-plus"
          label="New folder"
          @click="openNew"
        />
        <Button
          class="btn btn--primary"
          icon="fa fa-file-upload"
          label="Upload"
          @click="uploadDocumentHandler()"
        />
        <Button
          v-if="selectedItems.length"
          class="btn btn--danger"
          icon="pi pi-trash"
          label="Delete"
          @click="confirmDeleteMultiple"
        />
      </div>
    </div>
  </div>
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
    :value="items"
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
          <v-icon
            v-if="slotProps.data.resourceLinkListFromEntity && slotProps.data.resourceLinkListFromEntity.length > 0"
            icon="mdi-link"
          />
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
            v-if="isAuthenticated"
            class="btn btn--danger"
            icon="pi pi-trash"
            @click="confirmDeleteItem(slotProps.data)"
          />
        </div>
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
      <label for="title">{{ $t("Name") }}</label>
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

  <Dialog
    v-model:visible="deleteItemDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      ></i>
      <span
        >Are you sure you want to delete <b>{{ itemToDelete?.title }}</b
        >?</span
      >
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteItemDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteItemButton"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :modal="true"
    :style="{ width: '450px' }"
    header="Confirm"
  >
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      />
      <span v-if="item">{{ $t("Are you sure you want to delete the selected items?") }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        label="No"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        label="Yes"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="detailsDialogVisible"
    :header="selectedItem.title || 'Item Details'"
    :modal="true"
    :style="{ width: '50%' }"
  >
    <div v-if="Object.keys(selectedItem).length > 0">
      <p><strong>Title:</strong> {{ selectedItem.resourceNode.title }}</p>
      <p><strong>Modified:</strong> {{ relativeDatetime(selectedItem.resourceNode.updatedAt) }}</p>
      <p><strong>Size:</strong> {{ prettyBytes(selectedItem.resourceNode.firstResourceFile.size) }}</p>
      <p>
        <strong>URL:</strong>
        <a
          :href="selectedItem.contentUrl"
          target="_blank"
          >Open File</a
        >
      </p>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        label="Close"
        @click="closeDetailsDialog"
      />
    </template>
  </Dialog>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ListMixin from "../../mixins/ListMixin"
import ActionCell from "../../components/ActionCell.vue"
import ResourceIcon from "../../components/documents/ResourceIcon.vue"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"
import DataFilter from "../../components/DataFilter"
import isEmpty from "lodash/isEmpty"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import prettyBytes from "pretty-bytes"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

export default {
  name: "FileManagerList",
  servicePrefix: "FileManager",
  components: {
    ActionCell,
    ResourceIcon,
    ResourceFileLink,
    DataFilter,
  },
  mixins: [ListMixin],
  setup() {
    const { t } = useI18n()
    const { relativeDatetime } = useFormatDate()
    const securityStore = useSecurityStore()

    const { isAuthenticated, isAdmin, user } = storeToRefs(securityStore)

    const data = {
      sortBy: "title",
      sortDesc: false,
      columnsQua: [
        { align: "left", name: "resourceNode.title", label: t("Title"), field: "resourceNode.title", sortable: true },
        {
          align: "left",
          name: "resourceNode.updatedAt",
          label: t("Modified"),
          field: "resourceNode.updatedAt",
          sortable: true,
        },
        {
          name: "resourceNode.firstResourceFile.size",
          label: t("Size"),
          field: "resourceNode.firstResourceFile.size",
          sortable: true,
        },
        { name: "action", label: t("Actions"), field: "action", sortable: false },
      ],
      columns: [
        { label: t("Title"), field: "title", name: "title", sortable: true },
        { label: t("Modified"), field: "resourceNode.updatedAt", name: "updatedAt", sortable: true },
        { label: t("Size"), field: "resourceNode.firstResourceFile.size", name: "size", sortable: true },
        { label: t("Actions"), name: "action", sortable: false },
      ],
      pageOptions: [10, 20, 50, t("All")],
      selected: [],
      isBusy: false,
      options: [],
      selectedItems: [],
      // prime vue
      deleteMultipleDialog: false,
      item: {},
      filters: { shared: 0, loadNode: 1 },
      submitted: false,
      prettyBytes,
      relativeDatetime,
      t,
      currentUser: user,
      isAdmin,
      isAuthenticated,
    }

    return data
  },
  created() {
    this.resetList = true
    this.onUpdateOptions(this.options)
    this.isFromEditor = window.location.search.includes("editor=tinymce")
  },
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),

    ...mapGetters("personalfile", {
      items: "list",
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
  data() {
    return {
      itemDialog: false,
      detailsDialogVisible: false,
      deleteItemDialog: false,
      selectedItem: {},
      itemToDelete: null,
      isFromEditor: false,
    }
  },
  methods: {
    showHandler(item) {
      this.selectedItem = item
      this.detailsDialogVisible = true
    },
    closeDetailsDialog() {
      this.detailsDialogVisible = false
    },
    // prime
    onPage(event) {
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
          let resourceNodeId = this.currentUser.resourceNode["id"]
          if (!isEmpty(this.$route.params.node)) {
            resourceNodeId = this.$route.params.node
          }
          this.item.filetype = "folder"
          this.item.parentResourceNodeId = resourceNodeId
          this.item.resourceLinkList = JSON.stringify([
            {
              gid: 0,
              sid: 0,
              cid: 0,
              visibility: RESOURCE_LINK_PUBLISHED,
            },
          ])

          this.createWithFormData(this.item)
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
    confirmDeleteItem(item) {
      console.log("confirmDeleteItem :::", item)
      this.item = { ...item }
      this.itemToDelete = { ...item }
      this.deleteItemDialog = true
    },
    confirmDeleteMultiple() {
      this.deleteMultipleDialog = true
    },
    deleteMultipleItems() {
      console.log("deleteMultipleItems")
      console.log(this.selectedItems)
      this.deleteMultipleAction(this.selectedItems)
      this.onRequest({
        pagination: this.pagination,
      })
      this.deleteMultipleDialog = false
      this.selectedItems = null
    },
    deleteItemButton() {
      console.log("deleteItem", this.itemToDelete)
      if (this.itemToDelete && this.itemToDelete.id) {
        this.deleteItem(this.itemToDelete)
          .then(() => {
            this.$toast.add({
              severity: "success",
              summary: "Success",
              detail: "Item deleted successfully",
              life: 3000,
            })
            this.deleteItemDialog = false
            this.itemToDelete = null
            this.onUpdateOptions(this.options)
          })
          .catch((error) => {
            console.error("Error deleting the item:", error)
            this.$toast.add({
              severity: "error",
              summary: "Error",
              detail: "An error occurred while deleting the item",
              life: 3000,
            })
          })
      } else {
        console.error("No item to delete or item ID is missing")
      }
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
    async deleteSelected() {
      console.log("deleteSelected")
      this.deleteMultipleAction(this.selected)
      this.onRequest({
        pagination: this.pagination,
      })
      console.log("end -- deleteSelected")
    },
    ...mapActions("personalfile", {
      getPage: "fetchAll",
      createWithFormData: "createWithFormData",
      deleteItem: "del",
      deleteMultipleAction: "delMultiple",
    }),
    ...mapActions("resourcenode", {
      findResourceNode: "findResourceNode",
    }),
  },
}
</script>
