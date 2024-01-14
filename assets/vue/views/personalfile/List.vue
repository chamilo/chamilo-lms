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
          class="btn btn--success"
          icon="fa fa-file-upload"
          label="Shared"
          @click="sharedDocumentHandler()"
        />
        <Button
          :disabled="!selectedItems || !selectedItems.length"
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
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile
            ? prettyBytes(slotProps.data.resourceNode.resourceFile.size)
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
            icon="fa fa-info-circle"
            @click="showHandler(slotProps.data)"
          />
          <Button
            v-if="isAuthenticated"
            class="btn btn--primary p-mr-2"
            icon="pi pi-pencil"
            @click="editHandler(slotProps.data)"
          />
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

    <!--    <template #paginatorLeft>-->
    <!--      <Button type="button" icon="pi pi-refresh" class="p-button-text" />-->
    <!--    </template>-->
    <!--    <template #paginatorRight>-->
    <!--      <Button type="button" icon="pi pi-cloud" class="p-button-text" />-->
    <!--    </template>-->
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
      />
      <span v-if="item"
        >Are you sure you want to delete <b>{{ item.title }}</b
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
      <span v-if="item">Are you sure you want to delete the selected items?</span>
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

export default {
  name: "PersonalFileList",
  servicePrefix: "PersonalFile",
  components: {
    //8Toolbar,
    ActionCell,
    ResourceIcon,
    ResourceFileLink,
    //DocumentsFilterForm,
    DataFilter,
  },
  mixins: [ListMixin],
  setup() {
    const { t } = useI18n()
    const { relativeDatetime } = useFormatDate()

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
          name: "resourceNode.resourceFile.size",
          label: t("Size"),
          field: "resourceNode.resourceFile.size",
          sortable: true,
        },
        { name: "action", label: t("Actions"), field: "action", sortable: false },
      ],
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
      filters: { shared: 0, loadNode: 1 },
      submitted: false,
      prettyBytes,
      relativeDatetime
    }

    return data
  },
  created() {
    this.resetList = true
    this.onUpdateOptions(this.options)
  },
  /*mounted() {
    this.resetList = true;
    this.onUpdateOptions(this.options);
  },*/
  computed: {
    // From crud.js list function
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),
    ...mapGetters({
      isAuthenticated: "security/isAuthenticated",
      isAdmin: "security/isAdmin",
      currentUser: "security/getUser",
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
  methods: {
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
              visibility: RESOURCE_LINK_PUBLISHED, // visible by default
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
      this.item = item
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
      //this.$toast.add({severity:'success', summary: 'Successful', detail: 'Products Deleted', life: 3000});*/
    },
    deleteItemButton() {
      console.log("deleteItem")
      this.deleteItem(this.item)
      //this.items = this.items.filter(val => val.iid !== this.item.iid);
      this.deleteItemDialog = false
      this.item = {}
      this.onUpdateOptions(this.options)
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
      /*for (let i = 0; i < this.selected.length; i++) {
        let item = this.selected[i];
        //this.deleteHandler(item);
        this.deleteItem(item);
      }*/

      this.deleteMultipleAction(this.selected)
      this.onRequest({
        pagination: this.pagination,
      })
      console.log("end -- deleteSelected")
    },
    //...actions,
    // From ListMixin
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