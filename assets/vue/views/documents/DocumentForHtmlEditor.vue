<template>
  <div class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm">
    <BaseToolbar class="border-b border-gray-25 bg-support-2">
      <div class="flex items-center gap-2">
        <BaseButton
          v-if="showNewFolderButton"
          :label="t('New folder')"
          :title="t('New folder')"
          icon="folder-plus"
          only-icon
          type="success"
          class="!flex !h-10 !w-10 !items-center !justify-center !rounded-xl !p-0"
          @click="openNew"
        />

        <BaseButton
          v-if="showUploadButton"
          :label="t('Upload')"
          :title="t('Upload')"
          icon="file-upload"
          only-icon
          type="black"
          class="!flex !h-10 !w-10 !items-center !justify-center !rounded-xl !p-0"
          @click="uploadDocumentHandler"
        />

        <input
          ref="uploadInput"
          type="file"
          class="hidden"
          :accept="uploadAccept"
          @change="handleUploadSelected"
        />
      </div>
    </BaseToolbar>

    <div class="border-b border-gray-25 bg-white px-4 py-4">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <BaseButton
              icon="compass"
              only-icon
              type="black"
              :disabled="isAtRoot"
              :title="t('Root')"
              class="!flex !h-10 !w-10 !items-center !justify-center !rounded-xl !border !border-gray-25 !bg-white !p-0"
              @click="goToRoot"
            />

            <BaseButton
              icon="back"
              only-icon
              type="black"
              :disabled="!canGoUp"
              :title="t('Up')"
              class="!flex !h-10 !w-10 !items-center !justify-center !rounded-xl !border !border-gray-25 !bg-white !p-0"
              @click="goUpOneLevel"
            />

            <div class="min-w-0 rounded-xl bg-support-2 px-3 py-2">
              <div class="text-tiny font-semibold uppercase tracking-wide text-gray-50">
                {{ t("Location") }}
              </div>
              <div class="truncate text-body-2 font-semibold text-gray-90">
                {{ currentFolderLabel }}
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="folderTrail.length"
          class="min-w-0 max-w-full rounded-xl border border-gray-25 bg-gray-10 px-3 py-2 text-caption text-gray-50"
          :title="folderTrail.map((c) => c.title).join(' / ')"
        >
          <span
            v-for="(crumb, idx) in folderTrail"
            :key="`${crumb.id}-${idx}`"
            class="inline-flex items-center"
          >
            <a
              v-if="idx < folderTrail.length - 1"
              class="cursor-pointer font-medium text-support-4 hover:text-primary hover:underline"
              @click="goToNode(crumb.id)"
            >
              {{ crumb.title }}
            </a>

            <span
              v-else
              class="font-semibold text-gray-90"
            >
              {{ crumb.title }}
            </span>

            <span
              v-if="idx < folderTrail.length - 1"
              class="mx-2 text-gray-50"
            >
              /
            </span>
          </span>
        </div>
      </div>
    </div>

    <div
      v-if="showFilteredEmptyNotice"
      class="mx-4 mt-4 rounded-xl border border-warning bg-support-6 px-4 py-3 text-body-2 text-gray-90"
    >
      {{ emptyFilterMessage }}
    </div>

    <div class="px-4 pb-4 pt-4">
      <BaseTable
        v-model:filters="filters"
        v-model:rows="options.itemsPerPage"
        v-model:selected-items="selectedItems"
        :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
        :is-loading="isLoading"
        :total-items="displayTotalItems"
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
            <div class="py-1">
              <div class="flex min-w-0 items-center gap-3">
                <div
                  :class="getEntryIconContainerClass(slotProps.data)"
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border"
                >
                  <v-icon
                    :icon="getEntryIcon(slotProps.data)"
                    class="text-lg"
                  />
                </div>

                <div class="min-w-0 flex-1">
                  <template v-if="slotProps.data?.resourceNode?.firstResourceFile">
                    <button
                      type="button"
                      class="block max-w-full truncate text-left text-body-2 font-semibold text-gray-90 transition hover:text-primary"
                      @click="returnToEditor(slotProps.data)"
                    >
                      {{ slotProps.data.resourceNode.title }}
                    </button>

                    <div class="mt-1 flex flex-wrap items-center gap-2">
                      <span
                        :class="getEntryBadgeClass(slotProps.data)"
                        class="inline-flex rounded-full px-2.5 py-0.5 text-tiny font-semibold uppercase tracking-wide"
                      >
                        {{ getEntryTypeLabel(slotProps.data) }}
                      </span>

                      <span
                        v-if="getMimeType(slotProps.data)"
                        class="truncate text-caption text-gray-50"
                      >
                        {{ getMimeType(slotProps.data) }}
                      </span>
                    </div>
                  </template>

                  <template v-else>
                    <button
                      type="button"
                      class="block max-w-full truncate text-left text-body-2 font-semibold text-gray-90 transition hover:text-primary"
                      @click="handleClick(slotProps.data)"
                    >
                      {{ slotProps.data.resourceNode.title }}
                    </button>

                    <div class="mt-1">
                      <span
                        class="inline-flex rounded-full bg-support-1 px-2.5 py-0.5 text-tiny font-semibold uppercase tracking-wide text-support-4"
                      >
                        {{ t("Folder") }}
                      </span>
                    </div>
                  </template>
                </div>
              </div>
            </div>
          </template>
        </Column>

        <Column
          :header="$t('Modified')"
          :sortable="true"
          field="resourceNode.updatedAt"
        >
          <template #body="slotProps">
            <div class="py-1">
              <div class="text-body-2 font-medium text-gray-90">
                {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
              </div>
            </div>
          </template>
        </Column>

        <Column
          :header="$t('Size')"
          :sortable="true"
          field="resourceNode.firstResourceFile.size"
        >
          <template #body="slotProps">
            <div class="py-1">
              <span
                v-if="slotProps.data.resourceNode.firstResourceFile"
                class="inline-flex rounded-full bg-gray-15 px-2.5 py-1 text-caption font-medium text-gray-90"
              >
                {{ prettyBytes(slotProps.data.resourceNode.firstResourceFile.size) }}
              </span>
            </div>
          </template>
        </Column>

        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex flex-row justify-end py-1">
              <Button
                v-if="slotProps.data?.resourceNode?.firstResourceFile"
                class="!rounded-xl !border-0 !bg-primary !px-4 !py-2 !text-white hover:!bg-primary"
                :label="t('Select')"
                @click="returnToEditor(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>

  <BaseDialogConfirmCancel
    v-model:is-visible="itemDialog"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('New folder')"
    @confirm-clicked="saveItem"
    @cancel-clicked="hideDialog"
  >
    <div class="space-y-2">
      <label
        for="title"
        class="block text-body-2 font-semibold text-gray-90"
      >
        {{ $t("Name") }}
      </label>
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{ 'p-invalid': submitted && !item.title }"
        autocomplete="off"
        autofocus
        required="true"
        class="w-full"
      />
      <small
        v-if="submitted && !item.title"
        class="block text-caption text-danger"
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
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
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
      folderTrail: ref([]),
      trailRootId: ref(null),
      currentFolderTitle: ref(""),
      uploadInput: ref(null),
      nodeCache: new Map(),
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
    async currentRouteNodeParam(newVal, oldVal) {
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
    currentRouteNodeParam() {
      return this.$route?.params?.node ?? this.$route?.params?.id ?? null
    },
    routeNodeParamName() {
      if (this.$route?.params?.node !== undefined) return "node"
      if (this.$route?.params?.id !== undefined) return "id"
      return "node"
    },
    pickerType() {
      const raw = String(this.$route?.query?.type || "files").toLowerCase()
      return ["files", "images", "media"].includes(raw) ? raw : "files"
    },
    uploadAccept() {
      if (this.pickerType === "images") return "image/*"
      if (this.pickerType === "media") return "video/*,audio/*"
      return "*/*"
    },
    filteredItems() {
      const type = this.pickerType
      const list = Array.isArray(this.items) ? this.items : []
      return list.filter((entry) => this.matchesPickerFilter(entry, type))
    },
    displayTotalItems() {
      if (this.pickerType === "files") {
        return this.totalItems
      }

      return this.filteredItems.length
    },
    showFilteredEmptyNotice() {
      return (
        !this.isLoading &&
        this.pickerType !== "files" &&
        Array.isArray(this.items) &&
        this.items.length > 0 &&
        this.filteredItems.length === 0
      )
    },
    emptyFilterMessage() {
      if (this.pickerType === "media") {
        return "No audio or video files were found in this folder."
      }

      if (this.pickerType === "images") {
        return "No image files were found in this folder."
      }

      return "No matching files were found in this folder."
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
    onPage(event) {
      this.options.itemsPerPage = event.rows
      this.options.page = event.page + 1

      this.$store.commit("documents/RESET_LIST")
      this.resetList = true
      this.onUpdateOptions(this.options)
    },
    sortingChanged(event) {
      this.options.sortBy = event.sortField
      this.options.sortDesc = event.sortOrder === -1
      this.onUpdateOptions(this.options)
    },

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
    getFileExtension(name) {
      const value = String(name || "").toLowerCase()
      const parts = value.split(".")
      return parts.length > 1 ? parts.pop() : ""
    },
    isImageLike(entryOrFile) {
      const mime = String(entryOrFile?.type || this.getMimeType(entryOrFile) || "").toLowerCase()
      const name = String(entryOrFile?.name || this.getFilename(entryOrFile) || "").toLowerCase()
      const ext = this.getFileExtension(name)

      return (
        mime.startsWith("image/") || ["png", "jpg", "jpeg", "gif", "svg", "webp", "bmp", "tif", "tiff"].includes(ext)
      )
    },
    isVideoLike(entryOrFile) {
      const mime = String(entryOrFile?.type || this.getMimeType(entryOrFile) || "").toLowerCase()
      const name = String(entryOrFile?.name || this.getFilename(entryOrFile) || "").toLowerCase()
      const ext = this.getFileExtension(name)

      return mime.startsWith("video/") || ["mp4", "webm", "mov", "avi", "mkv", "ogv"].includes(ext)
    },
    isAudioLike(entryOrFile) {
      const mime = String(entryOrFile?.type || this.getMimeType(entryOrFile) || "").toLowerCase()
      const name = String(entryOrFile?.name || this.getFilename(entryOrFile) || "").toLowerCase()
      const ext = this.getFileExtension(name)

      return mime.startsWith("audio/") || ["mp3", "wav", "m4a", "aac", "flac", "oga", "ogg"].includes(ext)
    },
    isMediaLike(entryOrFile) {
      return this.isVideoLike(entryOrFile) || this.isAudioLike(entryOrFile)
    },
    matchesPickerFilter(entry, type) {
      if (this.isFolderEntry(entry)) return true
      if (type === "files") return true

      if (type === "images") return this.isImageLike(entry)
      if (type === "media") return this.isMediaLike(entry)

      return true
    },
    matchesSelectedFileForPicker(file) {
      if (!file) return false
      if (this.pickerType === "files") return true
      if (this.pickerType === "images") return this.isImageLike(file)
      if (this.pickerType === "media") return this.isMediaLike(file)
      return true
    },
    getInvalidUploadMessage() {
      if (this.pickerType === "images") {
        return "Only image files are allowed in this picker."
      }

      if (this.pickerType === "media") {
        return "Only audio and video files are allowed in this picker."
      }

      return "The selected file type is not allowed in this picker."
    },
    notifyError(message) {
      if (!message) return

      if (this.$toast?.add) {
        this.$toast.add({
          severity: "error",
          summary: "Error",
          detail: message,
          life: 4000,
        })
        return
      }

      console.error("[DOC PICKER]", message)

      if (typeof window !== "undefined" && typeof window.alert === "function") {
        window.alert(message)
      }
    },
    getEntryType(entry) {
      if (this.isFolderEntry(entry)) return "folder"
      if (this.isImageLike(entry)) return "image"
      if (this.isVideoLike(entry)) return "video"
      if (this.isAudioLike(entry)) return "audio"
      return "file"
    },
    getEntryTypeLabel(entry) {
      const type = this.getEntryType(entry)

      if (type === "folder") return this.t("Folder")
      if (type === "image") return this.t("Image")
      if (type === "video") return this.t("Video")
      if (type === "audio") return this.t("Audio")

      return this.t("File")
    },
    getEntryIcon(entry) {
      const type = this.getEntryType(entry)

      if (type === "folder") return "mdi-folder"
      if (type === "image") return "mdi-file-image-outline"
      if (type === "video") return "mdi-file-video-outline"
      if (type === "audio") return "mdi-file-music-outline"

      return "mdi-file-document-outline"
    },
    getEntryIconContainerClass(entry) {
      const type = this.getEntryType(entry)

      if (type === "folder") {
        return "border-support-3 bg-support-1 text-support-4"
      }

      if (type === "image") {
        return "border-info bg-support-2 text-info"
      }

      if (type === "video") {
        return "border-secondary bg-support-6 text-secondary"
      }

      if (type === "audio") {
        return "border-primary bg-support-2 text-primary"
      }

      return "border-gray-25 bg-gray-10 text-gray-50"
    },
    getEntryBadgeClass(entry) {
      const type = this.getEntryType(entry)

      if (type === "image") {
        return "bg-support-2 text-info"
      }

      if (type === "video") {
        return "bg-support-6 text-secondary"
      }

      if (type === "audio") {
        return "bg-support-2 text-primary"
      }

      return "bg-gray-15 text-gray-90"
    },
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
      const raw = this.$route?.params?.node ?? this.$route?.params?.id
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
        const parentRaw = data?.parent
        const parentId = this.normalizeNodeId(parentRaw)

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

        if (!info.parentId) break
        if (info.resourceTypeTitle === "courses") break

        cursor = info.parentId
        safety++
      }

      chain.reverse()

      if (!chain.length) {
        chain.push({ id: currentId, title: `#${currentId}` })
      }

      this.folderTrail = chain.map((x) => ({ id: x.id, title: x.title }))
      this.trailRootId = this.folderTrail[0]?.id || currentId
      this.currentFolderTitle = this.folderTrail[this.folderTrail.length - 1]?.title || `#${currentId}`
    },

    navigateToNode(nodeId) {
      const id = this.normalizeNodeId(nodeId)
      if (!id) return

      this.$router.push({
        name: this.$route.name,
        params: {
          ...this.$route.params,
          [this.routeNodeParamName]: id,
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
    resetUploadInput(target) {
      try {
        if (target) {
          target.value = ""
        }
      } catch {
        // Ignore
      }
    },

    async handleUploadSelected(e) {
      const file = e?.target?.files?.[0]
      if (!file) return

      if (!this.matchesSelectedFileForPicker(file)) {
        this.notifyError(this.getInvalidUploadMessage())
        this.resetUploadInput(e?.target)
        return
      }

      const { cid, sid, gid } = this.getContextIds()

      const payload = {
        title: file.name,
        filetype: "file",
        uploadFile: file,
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
        this.resetUploadInput(e?.target)

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
      } catch {
        // Ignore
      }

      try {
        window.parent.postMessage({ url }, "*")
      } catch {
        // Ignore
      }

      try {
        if (parent?.tinymce?.activeEditor?.windowManager) {
          parent.tinymce.activeEditor.windowManager.close()
        }
      } catch {
        // Ignore
      }

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
