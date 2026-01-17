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
    <input
      ref="uploadInput"
      type="file"
      class="hidden"
      :accept="uploadAccept"
      @change="handleUploadSelected"
    />
  </BaseToolbar>
  <div class="px-4 pt-2 pb-1 flex items-center justify-between gap-3">
    <div class="flex items-center gap-2 min-w-0">
      <BaseButton
        icon="home"
        only-icon
        type="black"
        :disabled="isAtRoot"
        :title="t('Root')"
        @click="goToRoot"
      />
      <BaseButton
        icon="back"
        only-icon
        type="black"
        :disabled="!canGoUp"
        :title="t('Up')"
        @click="goUpOneLevel"
      />

      <div class="text-sm text-gray-600 truncate">
        <span class="font-semibold">Location:</span>
        <span class="ml-2">{{ currentFolderLabel }}</span>
      </div>
    </div>

    <div
      v-if="folderTrail.length"
      class="text-sm text-gray-600 truncate"
      :title="folderTrail.map((c) => c.title).join(' / ')"
    >
      <span
        v-for="(crumb, idx) in folderTrail"
        :key="`${crumb.id}-${idx}`"
        class="inline-flex items-center"
      >
        <a
          v-if="idx < folderTrail.length - 1"
          class="cursor-pointer hover:underline"
          @click="goToNode(crumb.id)"
        >
          {{ crumb.title }}
        </a>
        <span v-else>{{ crumb.title }}</span>

        <span
          v-if="idx < folderTrail.length - 1"
          class="mx-2"
        >
          /
        </span>
      </span>
    </div>
  </div>
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
      relativeDatetime,
      prettyBytes,
      isAuthenticated,
      isAdmin,
      isCurrentTeacher,
      showNewFolderButton,
      showUploadButton,
      selectedItems: [],
      itemDialog: ref(false),
      item: {},
      filters: {},
      submitted: false,
      options: {
        page: 1,
        itemsPerPage: 20,
      },
      folderTrail: ref([]), // [{ id, title }]
      trailRootId: ref(null),
      currentFolderTitle: ref(""),
      uploadInput: ref(null),
      nodeCache: new Map(), // id -> { id, title, parentId, resourceTypeTitle }
    }
  },
  created() {
    this.filters.loadNode = 1
  },
  async mounted() {
    this.filters.loadNode = 1
    await this.refreshNavigation()
    this.onUpdateOptions(this.options)
  },
  watch: {
    async "$route.params.node"(newVal, oldVal) {
      if (String(newVal) === String(oldVal)) return

      if (!this.options || typeof this.options !== "object" || Array.isArray(this.options)) {
        this.options = { page: 1, itemsPerPage: 20 }
      }

      this.options.page = 1
      this.filters.loadNode = 1

      await this.refreshNavigation()
      this.onUpdateOptions(this.options)
    },
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
    uploadAccept() {
      if (this.pickerType === "images") return "image/*"
      if (this.pickerType === "media") return "image/*,video/*,audio/*"
      return "*/*"
    },
    filteredItems() {
      const type = this.pickerType
      const list = Array.isArray(this.items) ? this.items : []
      return list.filter((entry) => this.matchesPickerFilter(entry, type))
    },
    canGoUp() {
      return Array.isArray(this.folderTrail) && this.folderTrail.length > 1
    },
    isAtRoot() {
      const nodeId = this.getCurrentNodeId()
      const rootId = this.getRootNodeId()
      return !!rootId && String(nodeId) === String(rootId)
    },
    currentFolderLabel() {
      if (this.currentFolderTitle) return this.currentFolderTitle
      const last = this.folderTrail?.[this.folderTrail.length - 1]
      return last?.title || `#${this.getCurrentNodeId()}`
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

    // ---- ID normalization (supports numeric, "123", or "/api/resource_nodes/123") ----
    normalizeNodeId(value) {
      if (value == null) return null
      if (typeof value === "number" && Number.isFinite(value) && value > 0) return value

      if (typeof value === "string") {
        const s = value.trim()
        if (!s) return null
        if (/^\d+$/.test(s)) return Number(s)

        const m = s.match(/\/api\/resource_nodes\/(\d+)/)
        if (m) return Number(m[1])
      }

      if (typeof value === "object") {
        const id = value?.id ?? value?.["@id"]
        return this.normalizeNodeId(id)
      }

      return null
    },

    getCurrentNodeId() {
      const raw = this.$route?.params?.node
      const n = this.normalizeNodeId(raw)
      return n || 1
    },

    getContextIds() {
      const q = this.$route?.query || {}
      const cid = this.normalizeNodeId(q.cid) || 0
      const sid = this.normalizeNodeId(q.sid) || 0
      const gid = this.normalizeNodeId(q.gid) || 0
      return { cid, sid, gid }
    },

    getRootNodeId() {
      return this.trailRootId || this.folderTrail?.[0]?.id || null
    },

    // ---- Navigation: build breadcrumb from real ResourceNode parents ----
    async fetchNodeInfo(nodeId) {
      const id = this.normalizeNodeId(nodeId)
      if (!id) return null

      if (this.nodeCache?.has(id)) {
        return this.nodeCache.get(id)
      }

      try {
        const resp = await fetch(`/api/resource_nodes/${id}`, {
          headers: { Accept: "application/ld+json" },
          credentials: "same-origin",
        })

        if (!resp.ok) {
          console.warn("[DOC PICKER] Failed to fetch resource node info", { id, status: resp.status })
          return null
        }

        const data = await resp.json()

        const title = String(data?.title || "").trim() || `#${id}`

        // parent can be IRI or embedded object
        const parentRaw = data?.parent
        const parentId = this.normalizeNodeId(parentRaw)

        // resourceType can be embedded; we only need title to detect course root
        const rtTitle =
          String(data?.resourceType?.title || data?.resourceType || "")
            .trim()
            .toLowerCase() || ""

        const info = { id, title, parentId, resourceTypeTitle: rtTitle }
        this.nodeCache.set(id, info)
        return info
      } catch (e) {
        console.warn("[DOC PICKER] Resource node fetch error", e)
        return null
      }
    },

    async refreshNavigation() {
      const currentId = this.getCurrentNodeId()

      const chain = []
      let cursor = currentId
      let safety = 0

      while (cursor && safety < 30) {
        const info = await this.fetchNodeInfo(cursor)
        if (!info) break

        chain.push({ id: info.id, title: info.title, parentId: info.parentId, rt: info.resourceTypeTitle })

        // Stop when we reach the course root (or when there's no parent)
        if (!info.parentId) break
        if (info.resourceTypeTitle === "courses") break

        cursor = info.parentId
        safety++
      }

      // If we did not include a "courses" node but we have parents, keep walking until parentId is null.
      // (Already handled above)

      chain.reverse()

      // Ensure we always have at least the current node
      if (!chain.length) {
        chain.push({ id: currentId, title: `#${currentId}` })
      }

      this.folderTrail = chain.map((x) => ({ id: x.id, title: x.title }))
      this.trailRootId = this.folderTrail[0]?.id || currentId
      this.currentFolderTitle = this.folderTrail[this.folderTrail.length - 1]?.title || `#${currentId}`
    },

    // ---- Navigation actions ----
    navigateToNode(nodeId) {
      const id = this.normalizeNodeId(nodeId)
      if (!id) return

      this.$router.push({
        name: this.$route.name,
        params: {
          ...this.$route.params,
          node: id,
        },
        query: {
          ...this.$route.query,
        },
      })
    },

    goToNode(nodeId) {
      this.navigateToNode(nodeId)
    },

    goToRoot() {
      const rootId = this.getRootNodeId()
      if (!rootId) return
      this.navigateToNode(rootId)
    },

    goUpOneLevel() {
      if (!this.canGoUp) return
      const prev = this.folderTrail[this.folderTrail.length - 2]
      if (!prev?.id) return
      this.navigateToNode(prev.id)
    },

    async handleClick(entry) {
      if (!entry?.resourceNode) {
        console.warn("[DOC PICKER] Invalid folder entry clicked")
        return
      }

      // Folder navigation
      if (!entry.resourceNode.firstResourceFile) {
        const nextNodeId = this.normalizeNodeId(entry.resourceNode.id || entry.resourceNode["@id"])
        if (!nextNodeId) {
          console.warn("[DOC PICKER] Invalid next folder id")
          return
        }
        this.navigateToNode(nextNodeId)
      }
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
    async saveItem() {
      this.submitted = true
      if (!this.item.title?.trim()) return

      const { cid, sid, gid } = this.getContextIds()

      try {
        this.item.filetype = "folder"
        this.item.parentResourceNodeId = this.getCurrentNodeId()
        this.item.resourceLinkList = JSON.stringify([
          {
            gid,
            sid,
            cid,
            visibility: RESOURCE_LINK_PUBLISHED,
          },
        ])

        await this.create(this.item)

        this.showMessage("Saved")
        this.itemDialog = false
        this.item = {}

        // Refresh list and breadcrumb
        await this.refreshNavigation()
        this.onUpdateOptions(this.options)
      } catch (e) {
        console.error("[DOC PICKER] Failed to create folder:", e)
      }
    },
    uploadDocumentHandler() {
      try {
        const el = this.uploadInput
        if (el && typeof el.click === "function") {
          el.click()
        }
      } catch (e) {
        console.warn("[DOC PICKER] Upload input click failed", e)
      }
    },

    async handleUploadSelected(e) {
      const file = e?.target?.files?.[0]
      if (!file) return

      const { cid, sid, gid } = this.getContextIds()

      const payload = {
        title: file.name,
        filetype: "file",
        uploadFile: file, // createWithFormData should map this to multipart "uploadFile"
        parentResourceNodeId: this.getCurrentNodeId(),
        resourceLinkList: JSON.stringify([
          {
            gid,
            sid,
            cid,
            visibility: RESOURCE_LINK_PUBLISHED,
          },
        ]),
      }

      try {
        await this.create(payload)
        this.showMessage("Saved")

        // Clear input so selecting the same file again triggers change
        try {
          e.target.value = ""
        } catch {}

        await this.refreshNavigation()
        this.onUpdateOptions(this.options)
      } catch (err) {
        console.error("[DOC PICKER] Upload failed:", err)
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

      try {
        const qs = new URLSearchParams(window.location.search)
        const cbId = qs.get("cbId")
        const registry = window.parent?.__chamiloTinyPickerCallbacks

        if (cbId && registry && typeof registry[cbId] === "function") {
          registry[cbId](url)
          delete registry[cbId]

          if (parent?.tinymce?.activeEditor?.windowManager) {
            parent.tinymce.activeEditor.windowManager.close()
          }
          return
        }
      } catch (e) {
        console.warn("[DOC MANAGER] Callback registry failed", e)
      }

      const payload = { mceAction: "fileSelected", content: { url } }

      try {
        window.parent.postMessage(payload, "*")
      } catch {}
      try {
        window.parent.postMessage({ url }, "*")
      } catch {}

      try {
        if (parent?.tinymce?.activeEditor?.windowManager) {
          parent.tinymce.activeEditor.windowManager.close()
        }
      } catch {}

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
      currentPage++
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
