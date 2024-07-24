<template>
  <div v-if="isAuthenticated" class="q-card">
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2">
        <Button class="btn btn--primary" icon="fa fa-folder-plus" label="New folder" @click="openNewDialog" />
        <Button class="btn btn--primary" icon="fa fa-file-upload" label="Upload" @click="uploadDocumentHandler" />
        <Button v-if="selectedFiles.length" class="btn btn--danger" icon="pi pi-trash" label="Delete" @click="confirmDeleteMultiple" />
        <Button v-if="previousFolders.length" class="btn btn--primary" icon="pi pi-arrow-left" label="Back" @click="goBack" />
      </div>
    </div>
  </div>
  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedFiles"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="10"
    :rows-per-page-options="[5, 10, 20, 50]"
    :total-records="totalFiles"
    :value="files"
    class="p-datatable-sm"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    data-key="iid"
    filter-display="menu"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsive-layout="scroll"
    @page="onFilesPage"
    @sort="sortingFilesChanged"
  >
    <Column :header="$t('Title')" :sortable="true" field="resourceNode.title">
      <template #body="slotProps">
        <div v-if="slotProps.data && slotProps.data.resourceNode && slotProps.data.resourceNode.firstResourceFile">
          <ResourceFileLink :resource="slotProps.data" />
          <v-icon v-if="slotProps.data.resourceLinkListFromEntity && slotProps.data.resourceLinkListFromEntity.length > 0" icon="mdi-link" />
        </div>
        <div v-else>
          <a v-if="slotProps.data" class="cursor-pointer" @click="handleClickFile(slotProps.data)">
            <v-icon icon="mdi-folder" />
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>
      </template>
    </Column>

    <Column :header="$t('Size')" :sortable="true" field="resourceNode.firstResourceFile.size">
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.firstResourceFile
            ? prettyBytes(slotProps.data.resourceNode.firstResourceFile.size)
            : ""
        }}
      </template>
    </Column>

    <Column :header="$t('Modified')" :sortable="true" field="resourceNode.updatedAt">
      <template #body="slotProps">
        {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button v-if="isAuthenticated" class="btn btn--danger" icon="pi pi-trash" @click="confirmDeleteItem(slotProps.data)" />
        </div>
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row gap-2">
          <Button v-if="isFromEditor" class="p-button-sm p-button p-mr-2" label="Select" @click="returnToEditor(slotProps.data)" />
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog v-model:visible="dialog" :header="$t('New folder')" :modal="true" :style="{ width: '450px' }" class="p-fluid">
    <div class="p-field">
      <label for="title">{{ $t("Name") }}</label>
      <InputText id="title" v-model.trim="item.title" :class="{ 'p-invalid': submitted && !item.title }" autocomplete="off" autofocus required="true" />
      <small v-if="submitted && !item.title" class="p-error">{{ $t('Title is required') }}</small>
    </div>

    <template #footer>
      <Button class="p-button-text" icon="pi pi-times" label="Cancel" @click="hideDialog" />
      <Button class="p-button-text" icon="pi pi-check" label="Save" @click="saveItem" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
      <span>Are you sure you want to delete <b>{{ itemToDelete?.title }}</b>?</span>
    </div>
    <template #footer>
      <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteDialog = false" />
      <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteItemButton" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteMultipleDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem" />
      <span v-if="item">{{ $t("Are you sure you want to delete the selected items?") }}</span>
    </div>
    <template #footer>
      <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteMultipleDialog = false" />
      <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteMultipleItems" />
    </template>
  </Dialog>

  <Dialog v-model:visible="detailsDialogVisible" :header="selectedItem.title || 'Item Details'" :modal="true" :style="{ width: '50%' }">
    <div v-if="Object.keys(selectedItem).length > 0">
      <p><strong>Title:</strong> {{ selectedItem.resourceNode.title }}</p>
      <p><strong>Modified:</strong> {{ relativeDatetime(selectedItem.resourceNode.updatedAt) }}</p>
      <p><strong>Size:</strong> {{ prettyBytes(selectedItem.resourceNode.firstResourceFile.size) }}</p>
      <p><strong>URL:</strong> <a :href="selectedItem.contentUrl" target="_blank">Open File</a></p>
    </div>
    <template #footer>
      <Button class="p-button-text" label="Close" @click="closeDetailsDialog" />
    </template>
  </Dialog>
</template>

<script setup>
import { useFileManager } from '../../composables/useFileManager';
import { useI18n } from 'vue-i18n';
import { useFormatDate } from '../../composables/formatDate'
import prettyBytes from "pretty-bytes"
import ResourceFileLink from "../../components/documents/ResourceFileLink.vue"

const { t } = useI18n();
const { relativeDatetime } = useFormatDate();

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
  previousFolders
} = useFileManager('personalfile', '/api/personal_files', 'FileManagerUploadFile');

onMountedCallback();
</script>
