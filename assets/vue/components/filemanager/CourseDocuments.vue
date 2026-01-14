<template>
  <div class="filemanager-container">
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
            @click="openNewDialog"
          />
          <Button
            class="btn btn--primary"
            icon="fa fa-file-upload"
            label="Upload"
            @click="uploadDocumentHandler"
          />
          <Button
            v-if="selectedFiles.length"
            class="btn btn--danger"
            icon="pi pi-trash"
            label="Delete"
            @click="confirmDeleteMultiple"
          />
          <Button
            :icon="viewModeIcon"
            class="btn btn--primary"
            @click="toggleViewMode"
          />
          <Button
            v-if="previousFolders.length"
            class="btn btn--primary"
            icon="pi pi-arrow-left"
            label="Back"
            @click="goBack"
          />
        </div>
      </div>
      <div class="breadcrumbs">
        <span
          v-for="(folder, index) in previousFolders"
          :key="index"
        >
          <span>{{ folder.title }}</span> /
        </span>
        <span>{{ currentFolderTitle }}</span>
      </div>
    </div>

    <div v-if="viewMode === 'list'">
      <BaseTable
        v-model:filters="filters"
        v-model:selected-items="selectedFiles"
        :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
        :is-loading="isLoading"
        :total-items="totalFiles"
        :values="filteredFiles"
        data-key="iid"
        lazy
        @page="onFilesPage"
        @sort="sortingFilesChanged"
      >
        <Column
          :header="$t('Title')"
          :sortable="true"
          field="resourceNode.title"
        >
          <template #body="slotProps">
            <div>
              <span
                v-if="!slotProps.data.resourceNode.firstResourceFile"
                @click="handleClickFile(slotProps.data)"
              >
                {{ slotProps.data.resourceNode.title }} folder
              </span>
              <span v-else>
                {{ slotProps.data.resourceNode.title }}
              </span>
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
                v-if="slotProps.data.resourceNode.firstResourceFile"
                class="p-button-sm p-button p-mr-2"
                label="Select"
                @click="returnToEditor(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </div>

    <div v-else>
      <div class="thumbnails">
        <div
          v-for="file in filteredFiles"
          :key="file.iid"
          class="thumbnail-item"
          @click="onThumbnailClick(file)"
          @contextmenu.prevent="showContextMenu($event, file)"
        >
          <div class="thumbnail-icon">
            <template v-if="isImage(file)">
              <img
                :alt="file.resourceNode.title"
                :src="getFileUrl(file)"
                :title="file.resourceNode.title"
                class="thumbnail-image"
              />
            </template>
            <template v-else>
              <span
                :class="['mdi', getIcon(file)]"
                class="mdi-icon"
              ></span>
            </template>
          </div>
          <div class="thumbnail-title">{{ file.resourceNode.title }}</div>
        </div>
      </div>
      <div
        v-if="totalPages > 1"
        class="flex justify-center mt-4 space-x-4"
      >
        <button
          :disabled="filters.page === 1"
          class="btn btn--plain px-4 py-2 rounded-md hover:bg-blue-600 disabled:bg-gray-300"
          @click="previousPage"
        >
          Previous
        </button>
        <span class="text-gray-700 font-semibold">Page {{ filters.page }} of {{ totalPages }}</span>
        <button
          :disabled="filters.page === totalPages"
          class="btn btn--plain px-4 py-2 rounded-md hover:bg-blue-600 disabled:bg-gray-300"
          @click="nextPage"
        >
          Next
        </button>
      </div>
      <BaseContextMenu
        :position="contextMenuPosition"
        :visible="contextMenuVisible"
        @close="contextMenuVisible = false"
      >
        <ul>
          <li @click="selectFile(contextMenuFile)">
            <span class="mdi mdi-file-check-outline"></span>
            {{ $t("Select") }}
          </li>
          <li @click="confirmDeleteItem(contextMenuFile)">
            <span class="mdi mdi-delete-outline"></span>
            {{ $t("Delete") }}
          </li>
        </ul>
      </BaseContextMenu>
    </div>

    <Dialog
      v-model:visible="dialog"
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
          required
        />
        <small
          v-if="submitted && !item.title"
          class="p-error"
          >{{ $t("Title is required") }}</small
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
      v-model:visible="deleteDialog"
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
          @click="deleteDialog = false"
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
        ></i>
        <span>{{ $t("Are you sure you want to delete the selected items?") }}</span>
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
        <p><strong>Title:</strong> {{ selectedItem.title }}</p>
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
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import { useFileManager } from "../../composables/useFileManager"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import BaseContextMenu from "../basecomponents/BaseContextMenu.vue"
import prettyBytes from "pretty-bytes"
import BaseTable from "../basecomponents/BaseTable.vue"
import { pickUrlForTinyMce } from "../../utils/tinyPickerBridge"

const route = useRoute()
const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const isTinyPicker = computed(() => String(route.query.picker || "") === "tinymce")
const cbId = computed(() => String(route.query.cbId || ""))

const {
  filteredFiles: baseFilteredFiles,
  totalFiles,
  isLoading,
  selectedFiles,
  dialog,
  deleteDialog,
  deleteMultipleDialog,
  detailsDialogVisible,
  selectedItem,
  itemToDelete,
  item,
  submitted,
  filters,
  viewMode,
  contextMenuVisible,
  contextMenuPosition,
  contextMenuFile,
  previousFolders,
  currentFolderTitle,
  handleClickFile,
  goBack,
  returnToEditor: baseReturnToEditor,
  toggleViewMode,
  viewModeIcon,
  isImage,
  getFileUrl,
  getIcon,
  showContextMenu,
  openNewDialog,
  hideDialog,
  saveItem,
  confirmDeleteItem,
  confirmDeleteMultiple,
  deleteMultipleItems,
  deleteItemButton,
  onFilesPage,
  sortingFilesChanged,
  closeDetailsDialog,
  uploadDocumentHandler,
  onMountedCallback,
  isAuthenticated,
  selectFile: baseSelectFile,
  nextPage,
  previousPage,
  totalPages,
} = useFileManager("documents", "/api/documents", "CourseDocumentsUploadFile", true)

const filterType = computed(() => {
  const raw = String(route.query.type || "files").toLowerCase()
  return ["files", "images", "media"].includes(raw) ? raw : "files"
})

function toAbsoluteUrl(raw) {
  const v = String(raw || "").trim()
  if (!v) return ""
  try {
    return new URL(v, window.location.origin).href
  } catch {
    return v
  }
}

function resolveItemUrl(entry) {
  if (!entry?.resourceNode?.firstResourceFile) return ""
  return (
    entry?.contentUrl || entry?.downloadUrl || entry?.url || entry?.resourceNode?.firstResourceFile?.contentUrl || ""
  )
}

function isFolderEntry(entry) {
  return !entry?.resourceNode?.firstResourceFile
}

function getFilename(entry) {
  return (
    entry?.resourceNode?.firstResourceFile?.filename ||
    entry?.resourceNode?.firstResourceFile?.name ||
    entry?.resourceNode?.title ||
    ""
  )
}

function getMimeType(entry) {
  return String(entry?.resourceNode?.firstResourceFile?.mimeType || "").toLowerCase()
}

function matchesFilter(entry, type) {
  if (isFolderEntry(entry)) return true
  if (type === "files") return true

  const mime = getMimeType(entry)
  const name = String(getFilename(entry)).toLowerCase()

  const isImg = mime.startsWith("image/") || /\.(png|jpe?g|gif|svg|webp|bmp|tiff?)$/.test(name)
  const isMed =
    mime.startsWith("video/") || mime.startsWith("audio/") || /\.(mp4|webm|ogg|mov|avi|mkv|mp3|wav|m4a)$/.test(name)

  if (type === "images") return isImg
  if (type === "media") return isMed
  return true
}

const filteredFiles = computed(() => {
  const type = filterType.value
  return (baseFilteredFiles.value || []).filter((f) => matchesFilter(f, type))
})

function returnToEditor(entry) {
  const url = toAbsoluteUrl(resolveItemUrl(entry))
  if (!url) {
    console.warn("[FILEMANAGER PICKER] No URL found for the selected entry")
    return
  }

  if (isTinyPicker.value) {
    pickUrlForTinyMce(url, { cbId: cbId.value, close: true, logPrefix: "[FILEMANAGER PICKER]" })
    return
  }

  baseReturnToEditor(entry)
}

function selectFile(entry) {
  if (isTinyPicker.value && entry?.resourceNode?.firstResourceFile) {
    contextMenuVisible.value = false
    returnToEditor(entry)
    return
  }
  baseSelectFile(entry)
}

function onThumbnailClick(entry) {
  if (!entry?.resourceNode?.firstResourceFile) {
    handleClickFile(entry)
    return
  }

  if (isTinyPicker.value) {
    returnToEditor(entry)
    return
  }

  handleClickFile(entry)
}

onMountedCallback()
</script>
