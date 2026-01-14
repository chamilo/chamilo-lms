<template>
  <div
    v-if="isAuthenticated"
    class="q-card"
  >
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2">
        <Button
          class="btn btn--secondary"
          icon="pi pi-home"
          :label="$t('Root')"
          @click="resetToRoot"
        />
        <Button
          class="btn btn--primary"
          icon="fa fa-folder-plus"
          :label="t('New folder')"
          @click="openNewDialog"
        />
        <Button
          class="btn btn--primary"
          icon="fa fa-file-upload"
          :label="t('Upload')"
          @click="openUploadDialog"
        />
        <Button
          v-if="selectedFiles.length"
          class="btn btn--danger"
          icon="pi pi-trash"
          :label="t('Delete')"
          @click="confirmDeleteMultiple"
        />
        <Button
          v-if="previousFolders.length"
          class="btn btn--primary"
          icon="pi pi-arrow-left"
          :label="$t('Back')"
          @click="goBack"
        />
      </div>
    </div>
  </div>
  <BaseTable
    v-model:filters="filters"
    v-model:selected-items="selectedFiles"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :is-loading="isLoading"
    :total-items="totalFiles"
    :values="files"
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
        <div v-if="slotProps.data?.resourceNode?.firstResourceFile">
          <ResourceFileLink :resource="slotProps.data" />
          <v-icon
            v-if="slotProps.data.resourceLinkListFromEntity?.length"
            icon="mdi-link"
          />
        </div>
        <div v-else>
          <a
            v-if="slotProps.data"
            class="cursor-pointer"
            @click="handleClickFile(slotProps.data)"
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
            v-if="isFromEditor"
            class="p-button-sm p-button p-mr-2"
            :label="$t('Select')"
            @click="returnToEditor(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>

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
        required="true"
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
        :label="$t('Cancel')"
        @click="hideDialog"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
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
    <div class="confirmation-content">
      <i
        class="pi pi-exclamation-triangle p-mr-3"
        style="font-size: 2rem"
      ></i>
      <span>{{ $t("Are you sure you want to delete {0}?", [itemToDelete?.title]) }}</span>
    </div>
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        :label="$t('No')"
        @click="deleteDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
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
        :label="$t('No')"
        @click="deleteMultipleDialog = false"
      />
      <Button
        class="p-button-text"
        icon="pi pi-check"
        :label="$t('Yes')"
        @click="deleteMultipleItems"
      />
    </template>
  </Dialog>

  <Dialog
    v-model:visible="detailsDialogVisible"
    :header="selectedItem.title || $t('Item details')"
    :modal="true"
    :style="{ width: '50%' }"
  >
    <div v-if="Object.keys(selectedItem).length > 0">
      <p>
        <strong>{{ $t("Title") }}:</strong> {{ selectedItem.resourceNode.title }}
      </p>
      <p>
        <strong>{{ $t("Modified") }}:</strong> {{ relativeDatetime(selectedItem.resourceNode.updatedAt) }}
      </p>
      <p>
        <strong>{{ $t("Size") }}:</strong> {{ prettyBytes(selectedItem.resourceNode.firstResourceFile.size) }}
      </p>
      <p>
        <strong>{{ $t("URL") }}:</strong>
        <a
          :href="selectedItem.contentUrl"
          target="_blank"
          >{{ $t("Open file") }}</a
        >
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
    :header="$t('Upload')"
    :modal="true"
    :style="{ width: '680px', maxWidth: '95vw' }"
    class="p-fluid"
    @hide="refreshList"
  >
    <Upload
      :key="'upload-' + String(filters['resourceNode.parent']) + '-' + String(uploadDialogVisible)"
      embedded
      :parent-resource-node-id="filters['resourceNode.parent']"
      :filetype="'file'"
      @done="onUploaded"
      @cancel="closeUploadDialog"
    />
    <template #footer>
      <Button
        class="p-button-text"
        icon="pi pi-times"
        :label="$t('Cancel')"
        @click="closeUploadDialog"
      />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from "vue"
import { useFileManager } from "../../composables/useFileManager"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import prettyBytes from "pretty-bytes"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import Upload from "../filemanager/Upload.vue"

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()

const uploadDialogVisible = ref(false)
const openUploadDialog = () => {
  uploadDialogVisible.value = true
}
const closeUploadDialog = () => {
  uploadDialogVisible.value = false
}
const onUploaded = (payload) => {
  uploadDialogVisible.value = false
  onMountedCallback()
}

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
  uploadDocumentHandler,
  onMountedCallback,
  isAuthenticated,
  showHandler,
  editHandler,
  goBack,
  previousFolders,
  onUpdateOptions,
  resetToRoot,
} = useFileManager("personalfile", "/api/personal_files", "FileManagerUploadFile")

onMountedCallback()
const refreshList = async () => {
  await onUpdateOptions()
}
</script>
