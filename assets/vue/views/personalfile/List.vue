<template>
  <div
    v-if="isAuthenticated"
    class="overflow-hidden rounded-2xl border border-gray-25 bg-white shadow-sm"
  >
    <div class="border-b border-gray-25 bg-support-2 px-4 py-4">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
          <div class="text-tiny font-semibold uppercase tracking-wide text-gray-50">
            {{ t("Location") }}
          </div>
          <div class="truncate text-body-1 font-semibold text-gray-90">
            {{ currentFolderTitle || t("Root") }}
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span
            v-if="isTinyPicker"
            class="inline-flex rounded-full bg-support-1 px-3 py-1 text-tiny font-semibold uppercase tracking-wide text-support-4"
          >
            {{ pickerTypeLabel }}
          </span>

          <Button
            class="!rounded-xl !border !border-gray-25 !bg-white !px-4 !py-2 !text-gray-90 hover:!bg-gray-10"
            icon="mdi mdi-home"
            :label="$t('Root')"
            @click="resetToRoot"
          />

          <Button
            v-if="previousFolders.length"
            class="!rounded-xl !border !border-gray-25 !bg-white !px-4 !py-2 !text-gray-90 hover:!bg-gray-10"
            icon="mdi mdi-arrow-left"
            :label="$t('Back')"
            @click="goBack"
          />

          <Button
            class="!rounded-xl !border-0 !bg-success !px-4 !py-2 !text-success-button-text hover:!bg-success"
            icon="fa fa-folder-plus"
            :label="t('New folder')"
            @click="openNewDialog"
          />

          <Button
            class="!rounded-xl !border-0 !bg-primary !px-4 !py-2 !text-white hover:!bg-primary"
            icon="fa fa-file-upload"
            :label="t('Upload')"
            @click="openUploadDialog"
          />

          <Button
            v-if="selectedFiles.length"
            class="!rounded-xl !border-0 !bg-danger !px-4 !py-2 !text-danger-button-text hover:!bg-danger"
            icon="mdi mdi-delete"
            :label="t('Delete')"
            @click="confirmDeleteMultiple"
          />
        </div>
      </div>
    </div>

    <div
      v-if="showFilteredEmptyNotice"
      class="mx-4 mt-4 rounded-xl border border-warning bg-support-6 px-4 py-3 text-body-2 text-gray-90"
    >
      {{ emptyFilterMessage }}
    </div>

    <div class="p-4">
      <BaseTable
        v-model:filters="filters"
        v-model:selected-items="selectedFiles"
        :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
        :is-loading="isLoading"
        :total-items="displayTotalItems"
        :values="visibleFiles"
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
                      @click="isTinyPicker ? returnToEditor(slotProps.data) : showHandler(slotProps.data)"
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
                        v-if="slotProps.data.resourceLinkListFromEntity?.length"
                        class="inline-flex rounded-full bg-gray-15 px-2.5 py-0.5 text-tiny font-semibold uppercase tracking-wide text-gray-90"
                      >
                        {{ t("Linked") }}
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
                      @click="handleClickFile(slotProps.data)"
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

        <Column
          :header="$t('Modified')"
          :sortable="true"
          field="resourceNode.updatedAt"
        >
          <template #body="slotProps">
            <div class="py-1 text-body-2 font-medium text-gray-90">
              {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
            </div>
          </template>
        </Column>

        <Column :exportable="false">
          <template #body="slotProps">
            <div class="flex flex-row justify-end gap-2 py-1">
              <Button
                v-if="isTinyPicker && slotProps.data?.resourceNode?.firstResourceFile"
                class="!rounded-xl !border-0 !bg-primary !px-4 !py-2 !text-white hover:!bg-primary"
                :label="$t('Select')"
                @click="returnToEditor(slotProps.data)"
              />

              <Button
                v-if="slotProps.data?.resourceNode?.firstResourceFile"
                class="!rounded-xl !border-0 !bg-danger !px-3 !py-2 !text-danger-button-text hover:!bg-danger"
                icon="mdi mdi-delete"
                @click="confirmDeleteItem(slotProps.data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>

  <Dialog
    v-model:visible="dialog"
    :header="$t('New folder')"
    :modal="true"
    :style="{ width: '450px' }"
    class="p-fluid"
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

    <template #footer>
      <Button
        class="p-button-text"
        icon="mdi mdi-close"
        :label="$t('Cancel')"
        @click="hideDialog"
      />
      <Button
        class="p-button-text"
        icon="mdi mdi-check"
        :label="$t('Save')"
        @click="saveItem"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteDialog"
    :modal="true"
    :style="{ width: '450px' }"
    :header="$t('Confirm')"
  >
    <div class="flex items-center gap-3">
      <i
        class="mdi mdi-alert text-warning"
        style="font-size: 2rem"
      />
      <span>{{ $t("Are you sure you want to delete {0}?", [itemToDeleteLabel]) }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="mdi mdi-close"
        :label="$t('No')"
        @click="deleteDialog = false"
      />
      <Button
        class="p-button-text"
        icon="mdi mdi-check"
        :label="$t('Yes')"
        @click="deleteItemButton"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="deleteMultipleDialog"
    :modal="true"
    :style="{ width: '450px' }"
    :header="$t('Confirm')"
  >
    <div class="flex items-center gap-3">
      <i
        class="mdi mdi-alert text-warning"
        style="font-size: 2rem"
      />
      <span>{{ $t("Are you sure you want to delete the selected items?") }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="mdi mdi-close"
        :label="$t('No')"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="mdi mdi-check"
        :label="$t('Yes')"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="detailsDialogVisible"
    :header="selectedItem?.resourceNode?.title || $t('Item details')"
    :modal="true"
    :style="{ width: '50%' }"
  >
    <div
      v-if="Object.keys(selectedItem || {}).length > 0"
      class="space-y-2"
    >
      <p>
        <strong>{{ $t("Title") }}:</strong> {{ selectedItem.resourceNode.title }}
      </p>
      <p>
        <strong>{{ $t("Modified") }}:</strong> {{ relativeDatetime(selectedItem.resourceNode.updatedAt) }}
      </p>
      <p v-if="selectedItem.resourceNode.firstResourceFile">
        <strong>{{ $t("Size") }}:</strong> {{ prettyBytes(selectedItem.resourceNode.firstResourceFile.size) }}
      </p>
      <p>
        <strong>{{ $t("URL") }}:</strong>
        <a
          :href="selectedItem.contentUrl"
          target="_blank"
          class="text-primary underline"
        >
          {{ $t("Open file") }}
        </a>
      </p>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        :label="$t('Close')"
        @click="closeDetailsDialog"
      />
    </template>
  </Dialog>
  <Dialog
    v-model:visible="uploadDialogVisible"
    :header="uploadDialogTitle"
    :modal="true"
    :style="{ width: '680px', maxWidth: '95vw' }"
    class="p-fluid"
    @hide="refreshList"
  >
    <Upload
      :key="
        'upload-' +
        String(filters['resourceNode.parent']) +
        '-' +
        String(uploadDialogVisible) +
        '-' +
        String(embeddedUploadFiletype)
      "
      embedded
      :parent-resource-node-id="filters['resourceNode.parent']"
      :filetype="embeddedUploadFiletype"
      @done="onUploaded"
      @cancel="closeUploadDialog"
    />
    <template #footer>
      <Button
        class="p-button-text"
        icon="mdi mdi-close"
        :label="$t('Cancel')"
        @click="closeUploadDialog"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useFileManager } from "../../composables/useFileManager"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import prettyBytes from "pretty-bytes"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import Upload from "../filemanager/Upload.vue"

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const route = useRoute()

const uploadDialogVisible = ref(false)

function normalizeNodeId(raw) {
  if (raw === null || raw === undefined || raw === "") {
    return null
  }

  const value = Number(raw)
  return Number.isFinite(value) && value > 0 ? value : null
}

const {
  files,
  visibleFiles,
  filterType,
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
  handleClickFile,
  returnToEditor,
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
  onMountedCallback,
  isAuthenticated,
  showHandler,
  goBack,
  previousFolders,
  onUpdateOptions,
  resetToRoot,
  currentFolderTitle,
} = useFileManager("personalfile", "/api/personal_files", "FileManagerUploadFile")

async function syncParentNodeFromRoute() {
  const routeNodeId = normalizeNodeId(route.params.node)

  if (!routeNodeId) {
    return
  }

  const currentParent = normalizeNodeId(filters.value["resourceNode.parent"])

  if (currentParent === routeNodeId) {
    return
  }

  filters.value["resourceNode.parent"] = routeNodeId
  await onUpdateOptions()
}

onMountedCallback()
void syncParentNodeFromRoute()

watch(
  () => route.params.node,
  () => {
    void syncParentNodeFromRoute()
  },
)

const isTinyPicker = computed(() => String(route.query.picker || "") === "tinymce")

const pickerTypeLabel = computed(() => {
  if (filterType.value === "images") return t("Images")
  if (filterType.value === "media") return t("Media")
  return t("Files")
})

const embeddedUploadFiletype = computed(() => {
  if (filterType.value === "images") return "image"
  if (filterType.value === "media") return "media"
  return "file"
})

const uploadDialogTitle = computed(() => {
  if (filterType.value === "images") return t("Upload image")
  if (filterType.value === "media") return t("Upload media")
  return t("Upload")
})

const displayTotalItems = computed(() => {
  if (filterType.value === "files") {
    return totalFiles.value
  }

  return visibleFiles.value.length
})

const showFilteredEmptyNotice = computed(() => {
  return (
    !isLoading.value &&
    filterType.value !== "files" &&
    Array.isArray(files.value) &&
    files.value.length > 0 &&
    visibleFiles.value.length === 0
  )
})

const emptyFilterMessage = computed(() => {
  if (filterType.value === "media") {
    return t("No audio or video files were found in this folder.")
  }

  if (filterType.value === "images") {
    return t("No image files were found in this folder.")
  }

  return t("No matching files were found in this folder.")
})

const itemToDeleteLabel = computed(() => {
  return itemToDelete.value?.resourceNode?.title || itemToDelete.value?.title || ""
})

function getMimeType(entry) {
  return String(entry?.resourceNode?.firstResourceFile?.mimeType || "").toLowerCase()
}

function getFilename(entry) {
  return (
    entry?.resourceNode?.firstResourceFile?.filename ||
    entry?.resourceNode?.firstResourceFile?.name ||
    entry?.resourceNode?.title ||
    ""
  )
}

function getFileExtension(name) {
  const value = String(name || "").toLowerCase()
  const parts = value.split(".")
  return parts.length > 1 ? parts.pop() : ""
}

function isFolderEntry(entry) {
  return !entry?.resourceNode?.firstResourceFile
}

function isImageLike(entryOrFile) {
  const mime = String(entryOrFile?.type || getMimeType(entryOrFile) || "").toLowerCase()
  const name = String(entryOrFile?.name || getFilename(entryOrFile) || "").toLowerCase()
  const ext = getFileExtension(name)

  return mime.startsWith("image/") || ["png", "jpg", "jpeg", "gif", "svg", "webp", "bmp", "tif", "tiff"].includes(ext)
}

function isVideoLike(entryOrFile) {
  const mime = String(entryOrFile?.type || getMimeType(entryOrFile) || "").toLowerCase()
  const name = String(entryOrFile?.name || getFilename(entryOrFile) || "").toLowerCase()
  const ext = getFileExtension(name)

  return mime.startsWith("video/") || ["mp4", "webm", "mov", "avi", "mkv", "ogv"].includes(ext)
}

function isAudioLike(entryOrFile) {
  const mime = String(entryOrFile?.type || getMimeType(entryOrFile) || "").toLowerCase()
  const name = String(entryOrFile?.name || getFilename(entryOrFile) || "").toLowerCase()
  const ext = getFileExtension(name)

  return mime.startsWith("audio/") || ["mp3", "wav", "m4a", "aac", "flac", "oga", "ogg"].includes(ext)
}

function getEntryType(entry) {
  if (isFolderEntry(entry)) return "folder"
  if (isImageLike(entry)) return "image"
  if (isVideoLike(entry)) return "video"
  if (isAudioLike(entry)) return "audio"
  return "file"
}

function getEntryTypeLabel(entry) {
  const type = getEntryType(entry)

  if (type === "folder") return t("Folder")
  if (type === "image") return t("Image")
  if (type === "video") return t("Video")
  if (type === "audio") return t("Audio")
  return t("File")
}

function getEntryIcon(entry) {
  const type = getEntryType(entry)

  if (type === "folder") return "mdi-folder"
  if (type === "image") return "mdi-file-image-outline"
  if (type === "video") return "mdi-file-video-outline"
  if (type === "audio") return "mdi-file-music-outline"
  return "mdi-file-document-outline"
}

function getEntryIconContainerClass(entry) {
  const type = getEntryType(entry)

  if (type === "folder") return "border-support-3 bg-support-1 text-support-4"
  if (type === "image") return "border-info bg-support-2 text-info"
  if (type === "video") return "border-secondary bg-support-6 text-secondary"
  if (type === "audio") return "border-primary bg-support-2 text-primary"

  return "border-gray-25 bg-gray-10 text-gray-50"
}

function getEntryBadgeClass(entry) {
  const type = getEntryType(entry)

  if (type === "image") return "bg-support-2 text-info"
  if (type === "video") return "bg-support-6 text-secondary"
  if (type === "audio") return "bg-support-2 text-primary"
  if (type === "folder") return "bg-support-1 text-support-4"

  return "bg-gray-15 text-gray-90"
}

function openUploadDialog() {
  uploadDialogVisible.value = true
}

function closeUploadDialog() {
  uploadDialogVisible.value = false
}

async function onUploaded(payload) {
  uploadDialogVisible.value = false

  if (payload?.parentNodeId) {
    filters.value["resourceNode.parent"] = Number(payload.parentNodeId)
  }

  await onUpdateOptions()
}

async function refreshList() {
  await onUpdateOptions()
}
</script>
