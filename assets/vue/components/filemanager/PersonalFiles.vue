<template>
  <div class="filemanager-container">
    <div
      v-if="isAuthenticated"
      class="q-card"
    >
      <div class="p-4 flex flex-row gap-1 mb-2">
        <div class="flex flex-row gap-2">
          <Button
            :label="t('New folder')"
            class="btn btn--success"
            icon="fa fa-folder-plus"
            @click="openNewDialog"
          />
          <Button
            :label="t('Upload')"
            class="btn btn--primary"
            icon="fa fa-file-upload"
            @click="uploadDocumentHandler"
          />
          <Button
            v-if="selectedFiles.length"
            :label="t('Delete')"
            class="btn btn--danger"
            icon="mdi mdi-delete"
            @click="confirmDeleteMultiple"
          />
          <Button
            :icon="viewModeIcon"
            class="btn btn--primary"
            @click="toggleViewMode"
          />
          <Button
            v-if="previousFolders.length"
            :label="t('Back')"
            class="btn btn--primary"
            icon="mdi mdi-arrow-left"
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
                icon="mdi mdi-delete"
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
                :label="t('Select')"
                class="p-button-sm p-button p-mr-2"
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
          {{ t("Previous") }}
        </button>
        <span class="text-gray-700 font-semibold">
          {{ t("Page") }} {{ filters.page }} {{ t("of") }} {{ totalPages }}
        </span>
        <button
          :disabled="filters.page === totalPages"
          class="btn btn--plain px-4 py-2 rounded-md hover:bg-blue-600 disabled:bg-gray-300"
          @click="nextPage"
        >
          {{ t("Next") }}
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
            {{ t("Select") }}
          </li>
          <li @click="confirmDeleteItem(contextMenuFile)">
            <span class="mdi mdi-delete-outline"></span>
            {{ t("Delete") }}
          </li>
        </ul>
      </BaseContextMenu>
    </div>

    <Dialog
      v-model:visible="dialog"
      :header="$t('Add a new folder')"
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
          :label="t('Cancel')"
          class="p-button-text"
          icon="mdi mdi-close"
          @click="hideDialog"
        />
        <Button
          :label="t('Save')"
          class="p-button-text"
          icon="mdi mdi-check"
          @click="saveItem"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="deleteDialog"
      :header="t('Confirm')"
      :modal="true"
      :style="{ width: '450px' }"
    >
      <div class="confirmation-content">
        <i
          class="mdi mdi-alert p-mr-3"
          style="font-size: 2rem"
        ></i>
        <span>{{ t("Are you sure you want to delete {0}?", [itemToDelete.title]) }}</span>
      </div>
      <template #footer>
        <Button
          :label="t('No')"
          class="p-button-text"
          icon="mdi mdi-close"
          @click="deleteDialog = false"
        />
        <Button
          :label="t('Yes')"
          class="p-button-text"
          icon="mdi mdi-check"
          @click="deleteItemButton"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="deleteMultipleDialog"
      :header="t('Confirm')"
      :modal="true"
      :style="{ width: '450px' }"
    >
      <div class="confirmation-content">
        <i
          class="mdi mdi-alert p-mr-3"
          style="font-size: 2rem"
        ></i>
        <span>{{ $t("Are you sure you want to delete the selected items?") }}</span>
      </div>
      <template #footer>
        <Button
          :label="t('No')"
          class="p-button-text"
          icon="mdi mdi-close"
          @click="deleteMultipleDialog = false"
        />
        <Button
          :label="t('Yes')"
          class="p-button-text"
          icon="mdi mdi-check"
          @click="deleteMultipleItems"
        />
      </template>
    </Dialog>

    <Dialog
      v-model:visible="detailsDialogVisible"
      :header="selectedItem.title || t('Item Details')"
      :modal="true"
      :style="{ width: '50%' }"
    >
      <div v-if="Object.keys(selectedItem).length > 0">
        <p>
          <strong>{{ $t("Title") }}:</strong> {{ selectedItem.title }}
        </p>
        <p>
          <strong>{{ $t("Modified") }}:</strong> {{ relativeDatetime(selectedItem.resourceNode.updatedAt) }}
        </p>
        <p>
          <strong>{{ $t("Size") }}:</strong> {{ prettyBytes(selectedItem.resourceNode.firstResourceFile.size) }}
        </p>
        <p>
          <strong>URL:</strong>
          <a
            :href="selectedItem.contentUrl"
            target="_blank"
            >{{ $t("Open File") }}</a
          >
        </p>
      </div>
      <template #footer>
        <Button
          :label="t('Close')"
          class="p-button-text"
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
  files,
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
} = useFileManager("personalfile", "/api/personal_files", "FileManagerUploadFile")

/**
 * Picker filter type:
 *  - "files"  (default)
 *  - "images" (only images)
 *  - "media"  (only audio/video)
 */
const filterType = computed(() => {
  const raw = String(route.query.type || "files").toLowerCase()
  return ["files", "images", "media"].includes(raw) ? raw : "files"
})

function resolveItemUrl(entry) {
  if (!entry?.resourceNode?.firstResourceFile) {
    return ""
  }

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
  return (files.value || []).filter((f) => matchesFilter(f, type))
})

function returnToEditor(entry) {
  const url = resolveItemUrl(entry)

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
  // In TinyMCE picker mode, "Select" should return the file URL to the editor.
  if (isTinyPicker.value && entry?.resourceNode?.firstResourceFile) {
    contextMenuVisible.value = false
    returnToEditor(entry)
    return
  }
  baseSelectFile(entry)
}

function onThumbnailClick(entry) {
  // Keep folder navigation as-is
  if (!entry?.resourceNode?.firstResourceFile) {
    handleClickFile(entry)
    return
  }

  // If opened as a TinyMCE picker, clicking a file should select it.
  if (isTinyPicker.value) {
    returnToEditor(entry)
    return
  }

  // Default behavior
  handleClickFile(entry)
}
onMountedCallback()
</script>
