<template>
  <div class="filemanager-container">
    <div v-if="isAuthenticated" class="q-card">
      <div class="p-4 flex flex-row gap-1 mb-2">
        <div class="flex flex-row gap-2">
          <Button class="btn btn--primary" icon="fa fa-folder-plus" label="New folder" @click="openNewPersonalFile" />
          <Button class="btn btn--primary" icon="fa fa-file-upload" label="Upload" @click="uploadDocumentHandler" />
          <Button v-if="selectedPersonalFiles.length" class="btn btn--danger" icon="pi pi-trash" label="Delete" @click="confirmDeleteMultiplePersonalFiles" />
          <Button class="btn btn--primary" :icon="viewModeIcon" @click="toggleViewMode" />
        </div>
      </div>
    </div>

    <div v-if="viewMode === 'list'">
      <DataTable
        v-model:filters="personalFileFilters"
        v-model:selection="selectedPersonalFiles"
        :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
        :lazy="true"
        :loading="isPersonalFilesLoading"
        :paginator="true"
        :rows="10"
        :rows-per-page-options="[5, 10, 20, 50]"
        :total-records="totalPersonalFiles"
        :value="personalFiles"
        class="p-datatable-sm"
        current-page-report-template="Showing {first} to {last} of {totalRecords}"
        data-key="iid"
        filter-display="menu"
        paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsive-layout="scroll"
        @page="onPersonalFilesPage"
        @sort="sortingPersonalFilesChanged"
      >
        <Column :header="$t('Title')" :sortable="true" field="resourceNode.title">
          <template #body="slotProps">
            <div>
              <span :class="['mdi', getIcon(slotProps.data)]" class="mdi-icon" />
              {{ slotProps.data.resourceNode.title }}
            </div>
          </template>
        </Column>

        <Column :header="$t('Size')" :sortable="true" field="resourceNode.firstResourceFile.size">
          <template #body="slotProps">
            {{ slotProps.data.resourceNode.firstResourceFile ? prettyBytes(slotProps.data.resourceNode.firstResourceFile.size) : "" }}
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
              <Button v-if="isAuthenticated" class="btn btn--danger" icon="pi pi-trash" @click="confirmDeleteItemPersonalFile(slotProps.data)" />
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
      </DataTable>
    </div>

    <div v-else>
      <div class="thumbnails">
        <div v-for="file in personalFiles" :key="file.iid" class="thumbnail-item" @dblclick="handleDoubleClick(file)" @contextmenu.prevent="showContextMenu($event, file)">
          <div class="thumbnail-icon">
            <template v-if="isImage(file)">
              <img :src="getFileUrl(file)" :alt="file.resourceNode.title" :title="file.resourceNode.title" class="thumbnail-image" />
            </template>
            <template v-else>
              <span :class="['mdi', getIcon(file)]" class="mdi-icon"></span>
            </template>
          </div>
          <div class="thumbnail-title">{{ file.resourceNode.title }}</div>
        </div>
      </div>
      <BaseContextMenu :visible="contextMenuVisible" :position="contextMenuPosition" @close="contextMenuVisible = false">
        <ul>
          <li @click="selectFile(contextMenuFile)">
            <span class="mdi mdi-file-check-outline"></span>
            Select
          </li>
          <li @click="confirmDeleteItemPersonalFile(contextMenuFile)">
            <span class="mdi mdi-delete-outline"></span>
            Delete
          </li>
        </ul>
      </BaseContextMenu>
    </div>

    <Dialog v-model:visible="personalFileDialog" :header="$t('New folder')" :modal="true" :style="{ width: '450px' }" class="p-fluid">
      <div class="p-field">
        <label for="title">{{ $t('Name') }}</label>
        <InputText id="title" v-model.trim="personalFileItem.title" :class="{ 'p-invalid': personalFileSubmitted && !personalFileItem.title }" autocomplete="off" autofocus required />
        <small v-if="personalFileSubmitted && !personalFileItem.title" class="p-error">{{ $t('Title is required') }}</small>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="Cancel" @click="hidePersonalFileDialog" />
        <Button class="p-button-text" icon="pi pi-check" label="Save" @click="savePersonalFileItem" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deletePersonalFileDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
        <span>Are you sure you want to delete <b>{{ personalFileToDelete?.title }}</b>?</span>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="No" @click="deletePersonalFileDialog = false" />
        <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deletePersonalFileItemButton" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteMultiplePersonalFileDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
        <span>{{ $t('Are you sure you want to delete the selected items?') }}</span>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteMultiplePersonalFileDialog = false" />
        <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteMultiplePersonalFilesItems" />
      </template>
    </Dialog>

    <Dialog v-model:visible="detailsPersonalFileDialogVisible" :header="selectedPersonalFileItem.title || 'Item Details'" :modal="true" :style="{ width: '50%' }">
      <div v-if="Object.keys(selectedPersonalFileItem).length > 0">
        <p><strong>Title:</strong> {{ selectedPersonalFileItem.title }}</p>
        <p><strong>Modified:</strong> {{ relativeDatetime(selectedPersonalFileItem.resourceNode.updatedAt) }}</p>
        <p><strong>Size:</strong> {{ prettyBytes(selectedPersonalFileItem.resourceNode.firstResourceFile.size) }}</p>
        <p><strong>URL:</strong> <a :href="selectedPersonalFileItem.contentUrl" target="_blank">Open File</a></p>
      </div>
      <template #footer>
        <Button class="p-button-text" label="Close" @click="closePersonalFileDetailsDialog" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import { storeToRefs } from 'pinia'
import { useSecurityStore } from '../../store/securityStore'
import prettyBytes from 'pretty-bytes'
import { useI18n } from 'vue-i18n'
import { useFormatDate } from '../../composables/formatDate'
import BaseContextMenu from '../basecomponents/BaseContextMenu.vue'

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const route = useRoute()
const router = useRouter()
const store = useStore()
const securityStore = useSecurityStore()
const { isAuthenticated, user } = storeToRefs(securityStore)

const personalFiles = computed(() => store.getters['personalfile/list'])
const totalPersonalFiles = computed(() => store.getters['personalfile/totalItems'])
const isPersonalFilesLoading = computed(() => store.getters['personalfile/isLoading'])
const selectedPersonalFiles = ref([])

const personalFileDialog = ref(false)
const deletePersonalFileDialog = ref(false)
const deleteMultiplePersonalFileDialog = ref(false)
const detailsPersonalFileDialogVisible = ref(false)
const selectedPersonalFileItem = ref({})
const personalFileToDelete = ref(null)
const personalFileFilters = ref({ shared: 0, loadNode: 1 })
const personalFileSubmitted = ref(false)
const personalFileItem = ref({})
const personalFileOptions = ref({ itemsPerPage: 10, page: 1, sortBy: '', sortDesc: false })
const viewMode = ref('thumbnails') // Default to thumbnails

const contextMenuVisible = ref(false)
const contextMenuPosition = ref({ x: 0, y: 0 })
const contextMenuFile = ref(null)

const flattenFilters = (filters) => {
  return Object.keys(filters).reduce((acc, key) => {
    acc[key] = filters[key]
    return acc
  }, {})
}

const onUpdatePersonalFileOptions = async () => {
  const filters = flattenFilters({
    ...personalFileFilters.value,
    cid: route.query.cid || '',
    sid: route.query.sid || '',
    gid: route.query.gid || '',
    type: route.query.type || ''
  })

  await store.dispatch('personalfile/fetchAll', {
    page: personalFileOptions.value.page,
    rows: personalFileOptions.value.itemsPerPage,
    sortBy: personalFileOptions.value.sortBy,
    sortDesc: personalFileOptions.value.sortDesc,
    ...filters
  })
}

const uploadDocumentHandler = () => {
  const uploadRoute = 'FileManagerUploadFile'
  router.push({ name: uploadRoute, query: route.query, params: { node: route.params.node } })
}

const openNewPersonalFile = () => {
  personalFileItem.value = {}
  personalFileSubmitted.value = false
  personalFileDialog.value = true
}

const hidePersonalFileDialog = () => {
  personalFileDialog.value = false
  personalFileSubmitted.value = false
}

const savePersonalFileItem = async () => {
  personalFileSubmitted.value = true
  if (personalFileItem.value.title.trim()) {
    if (personalFileItem.value.id) {
      // Update logic here
    } else {
      let resourceNodeId = user.value.resourceNode.id
      if (route.params.node) {
        resourceNodeId = route.params.node
      }
      personalFileItem.value.filetype = 'folder'
      personalFileItem.value.parentResourceNodeId = resourceNodeId
      personalFileItem.value.resourceLinkList = JSON.stringify([{
        gid: 0,
        sid: 0,
        cid: 0,
        visibility: 'RESOURCE_LINK_PUBLISHED'
      }])
      await store.dispatch('personalfile/createWithFormData', personalFileItem.value)
    }
    personalFileDialog.value = false
    personalFileItem.value = {}
    onUpdatePersonalFileOptions()
  }
}

const confirmDeleteItemPersonalFile = (item) => {
  personalFileToDelete.value = { ...item }
  deletePersonalFileDialog.value = true
}

const confirmDeleteMultiplePersonalFiles = () => {
  deleteMultiplePersonalFileDialog.value = true
}

const deleteMultiplePersonalFilesItems = async () => {
  await store.dispatch('personalfile/delMultiple', selectedPersonalFiles.value)
  deleteMultiplePersonalFileDialog.value = false
  selectedPersonalFiles.value = []
  onUpdatePersonalFileOptions()
}

const deletePersonalFileItemButton = async () => {
  if (personalFileToDelete.value && personalFileToDelete.value.id) {
    try {
      await store.dispatch('personalfile/del', personalFileToDelete.value)
      deletePersonalFileDialog.value = false
      personalFileToDelete.value = null
      onUpdatePersonalFileOptions()
    } catch (error) {
      console.error('An error occurred while deleting the item', error)
    }
  }
}

const onPersonalFilesPage = (event) => {
  personalFileOptions.value.itemsPerPage = event.rows
  personalFileOptions.value.page = event.page + 1
  personalFileOptions.value.sortBy = event.sortField
  personalFileOptions.value.sortDesc = event.sortOrder === -1

  onUpdatePersonalFileOptions()
}

const sortingPersonalFilesChanged = (event) => {
  personalFileOptions.value.sortBy = event.sortField
  personalFileOptions.value.sortDesc = event.sortOrder === -1

  onUpdatePersonalFileOptions()
}

const handleClickPersonalFile = (data) => {
  if (data.resourceNode.firstResourceFile) {
    returnToEditor(data)
  } else {
    const resourceId = data.resourceNode.id
    personalFileFilters.value['resourceNode.parent'] = resourceId

    router.push({
      name: 'FileManagerList',
      params: { node: resourceId },
      query: route.query,
    })
  }
}

const closePersonalFileDetailsDialog = () => {
  detailsPersonalFileDialogVisible.value = false
}

const returnToEditor = (data) => {
  const url = data.contentUrl

  window.parent.postMessage(
    {
      url: url,
    },
    '*'
  )

  if (parent.tinymce) {
    parent.tinymce.activeEditor.windowManager.close()
  }

  function getUrlParam (paramName) {
    const reParam = new RegExp('(?:[\\?&]|&amp;)' + paramName + '=([^&]+)', 'i')
    const match = window.location.search.match(reParam)
    return match && match.length > 1 ? match[1] : ''
  }

  const funcNum = getUrlParam('CKEditorFuncNum')
  if (window.opener.CKEDITOR) {
    window.opener.CKEDITOR.tools.callFunction(funcNum, url)
    window.close()
  }
}

const refreshPersonalFilesList = async () => {
  await onUpdatePersonalFileOptions()
}

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'list' ? 'thumbnails' : 'list'
}

const viewModeIcon = computed(() => viewMode.value === 'list' ? 'pi pi-th-large' : 'pi pi-list')

const isImage = (file) => {
  const fileExtensions = ['jpeg', 'jpg', 'png', 'gif']
  const extension = file.resourceNode.title.split('.').pop().toLowerCase()
  return fileExtensions.includes(extension)
}

const getFileUrl = (file) => {
  return file.contentUrl
}

const getIcon = (file) => {
  if (!file.resourceNode.firstResourceFile) {
    return 'mdi-folder' // Assume it's a folder if no firstResourceFile is present
  }
  const fileTypeIcons = {
    'pdf': 'mdi-file-pdf-box', // Cambié a un ícono existente
    'doc': 'mdi-file-word-box',
    'docx': 'mdi-file-word-box',
    'xls': 'mdi-file-excel-box',
    'xlsx': 'mdi-file-excel-box',
    'zip': 'mdi-zip-box',
    'jpeg': 'mdi-file-image-box',
    'jpg': 'mdi-file-image-box',
    'png': 'mdi-file-image-box',
    'gif': 'mdi-file-image-box',
    'default': 'mdi-file'
  }
  const extension = file.resourceNode.title.split('.').pop().toLowerCase()
  return fileTypeIcons[extension] || fileTypeIcons['default']
}

const handleDoubleClick = (file) => {
  selectFile(file)
}

const openContextMenu = (event) => {
  contextMenuVisible.value = false
}

const showContextMenu = (event, file) => {
  event.preventDefault()
  contextMenuFile.value = file
  contextMenuPosition.value = { x: event.clientX, y: event.clientY }
  contextMenuVisible.value = true
}

const selectFile = (file) => {
  returnToEditor(file)
  contextMenuVisible.value = false
}

onMounted(() => {
  onUpdatePersonalFileOptions()
})
</script>
