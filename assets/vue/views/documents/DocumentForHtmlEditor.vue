<template>
  <BaseToolbar>
    <BaseButton
      v-if="showNewFolderButton"
      :label="t('New folder')"
      icon="folder-plus"
      only-icon
      type="black"
      @click="openNew"
    />

    <BaseButton
      v-if="showUploadButton"
      :label="t('Upload')"
      icon="file-upload"
      only-icon
      type="black"
      @click="uploadDocumentHandler"
    />
  </BaseToolbar>

  <BaseTable
    v-model:filters="filters"
    v-model:selected-items="selectedItems"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :is-loading="isLoading"
    :total-items="totalItems"
    :values="filteredItems"
    data-key="iid"
    lazy
    @page="onPage"
    @sort="sortingChanged"
  >
    <Column
      :header="$t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div v-if="slotProps.data?.resourceNode?.firstResourceFile">
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

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button
            v-if="slotProps.data?.resourceNode?.firstResourceFile"
            class="p-button-sm p-button p-mr-2"
            label="Select"
            @click="returnToEditor(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>

  <BaseDialogConfirmCancel
    v-model:is-visible="itemDialog"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('New folder')"
    @confirm-clicked="saveItem"
    @cancel-clicked="hideDialog"
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
      >
        {{ $t("Title is required") }}
      </small>
    </div>
  </BaseDialogConfirmCancel>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import ListMixin from "../../mixins/ListMixin"
import ActionCell from "../../components/ActionCell.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import ResourceIcon from "../../components/documents/ResourceIcon.vue"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"
import DataFilter from "../../components/DataFilter"
import DocumentsFilterForm from "../../components/documents/Filter"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import prettyBytes from "pretty-bytes"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"
import { ref } from "vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useDocumentActionButtons } from "../../composables/document/documentActionButtons"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"

export default {
  name: "DocumentForHtmlEditor",
  servicePrefix: "Documents",
  components: {
    BaseTable,
    BaseDialogConfirmCancel,
    BaseButton,
    BaseToolbar,
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
    const securityStore = useSecurityStore()
    const { isAuthenticated, isAdmin, isCurrentTeacher } = storeToRefs(securityStore)
    const { showUploadButton, showNewFolderButton } = useDocumentActionButtons()

    return {
      t,
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
      isBusy: false,
      options: [],
      selectedItems: [],
      itemDialog: ref(false),
      item: {},
      filters: {},
      submitted: false,
      relativeDatetime,
      prettyBytes,
      isAuthenticated,
      isAdmin,
      isCurrentTeacher,
      showNewFolderButton,
      showUploadButton,
    }
  },
  created() {
    this.filters.loadNode = 1
  },
  mounted() {
    this.filters.loadNode = 1
    this.onUpdateOptions(this.options)
  },
  computed: {
    ...mapGetters("resourcenode", {
      resourceNode: "getResourceNode",
    }),
    ...mapGetters("documents", {
      items: "list",
    }),
    ...mapFields("documents", {
      deletedResource: "deleted",
      error: "error",
      isLoading: "isLoading",
      resetList: "resetList",
      totalItems: "totalItems",
      view: "view",
    }),

    pickerType() {
      const raw = String(this.$route?.query?.type || "files").toLowerCase()
      return ["files", "images", "media"].includes(raw) ? raw : "files"
    },

    filteredItems() {
      const type = this.pickerType
      const list = Array.isArray(this.items) ? this.items : []
      return list.filter((entry) => this.matchesPickerFilter(entry, type))
    },
  },
  methods: {
    sortingChanged(event) {
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1
      this.onUpdateOptions(this.options)
    },

    // ---- Picker filtering helpers ----
    isFolderEntry(entry) {
      return !entry?.resourceNode?.firstResourceFile
    },
    getFilename(entry) {
      return (
        entry?.resourceNode?.firstResourceFile?.filename ||
        entry?.resourceNode?.firstResourceFile?.name ||
        entry?.resourceNode?.title ||
        ""
      )
    },
    getMimeType(entry) {
      return String(entry?.resourceNode?.firstResourceFile?.mimeType || "").toLowerCase()
    },
    matchesPickerFilter(entry, type) {
      // Always keep folders for navigation
      if (this.isFolderEntry(entry)) return true
      if (type === "files") return true

      const mime = this.getMimeType(entry)
      const name = String(this.getFilename(entry)).toLowerCase()

      const isImg = mime.startsWith("image/") || /\.(png|jpe?g|gif|svg|webp|bmp|tiff?)$/.test(name)
      const isMed =
        mime.startsWith("video/") || mime.startsWith("audio/") || /\.(mp4|webm|ogg|mov|avi|mkv|mp3|wav|m4a)$/.test(name)

      if (type === "images") return isImg
      if (type === "media") return isMed
      return true
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
      if (this.item.title?.trim()) {
        if (!this.item.id) {
          this.item.filetype = "folder"
          this.item.parentResourceNodeId = this.$route.params.node
          this.item.resourceLinkList = JSON.stringify([
            {
              gid: this.$route.query.gid,
              sid: this.$route.query.sid,
              cid: this.$route.query.cid,
              visibility: RESOURCE_LINK_PUBLISHED,
            },
          ])
          this.create(this.item)
          this.showMessage("Saved")
        }
        this.itemDialog = false
        this.item = {}
      }
    },

    toAbsoluteUrl(url) {
      const raw = String(url || "").trim()
      if (!raw) return ""
      try {
        return new URL(raw, window.location.origin).href
      } catch {
        return raw
      }
    },
    returnToEditor(item) {
      const url = this.toAbsoluteUrl(item?.contentUrl)
      if (!url) return

      // 1) Preferred: direct callback registry (most reliable)
      try {
        const qs = new URLSearchParams(window.location.search)
        const cbId = qs.get("cbId")
        const registry = window.parent?.__chamiloTinyPickerCallbacks

        if (cbId && registry && typeof registry[cbId] === "function") {
          registry[cbId](url)
          delete registry[cbId]

          // Close TinyMCE dialog if possible
          if (parent?.tinymce?.activeEditor?.windowManager) {
            parent.tinymce.activeEditor.windowManager.close()
          }
          return
        }
      } catch (e) {
        console.warn("[DOC MANAGER] Callback registry failed", e)
      }

      // 2) Fallback: postMessage (keep compatibility)
      const payload = { mceAction: "fileSelected", content: { url } }

      try {
        window.parent.postMessage(payload, "*")
      } catch {}
      try {
        window.parent.postMessage({ url }, "*")
      } catch {}

      // Close TinyMCE dialog if possible
      try {
        if (parent?.tinymce?.activeEditor?.windowManager) {
          parent.tinymce.activeEditor.windowManager.close()
        }
      } catch {}

      // CKEditor legacy support
      function getUrlParam(paramName) {
        const reParam = new RegExp("(?:[\\?&]|&amp;)" + paramName + "=([^&]+)", "i")
        const match = window.location.search.match(reParam)
        return match && match.length > 1 ? match[1] : ""
      }

      const funcNum = getUrlParam("CKEditorFuncNum")
      try {
        if (window.opener?.CKEDITOR) {
          window.opener.CKEDITOR.tools.callFunction(funcNum, url)
          window.close()
        }
      } catch (e) {
        console.warn("[DOC MANAGER] CKEditor callback failed", e)
      }
    },
    async fetchItems() {
      if (this.items.length === this.totalItems) return
      this.isBusy = true
      let currentPage = this.options.page
      currentPage++ // keep existing paging behavior
      this.options.page = currentPage
      await this.fetchNewItems(this.options)
      this.isBusy = false
      return true
    },
    onRowSelected(items) {
      this.selected = items
    },

    ...mapActions("documents", {
      getPage: "fetchAll",
      create: "createWithFormData",
      deleteItem: "del",
      deleteMultipleAction: "delMultiple",
    }),
    ...mapActions("resourcenode", {
      findResourceNode: "findResourceNode",
    }),
  },
}
</script>
