<template>
  <div class="filemanager-container">
    <div v-if="isAuthenticated" class="q-card">
      <div class="p-4 flex flex-row gap-1 mb-2">
        <div class="flex flex-row gap-2">
          <Button class="btn btn--primary" icon="fa fa-folder-plus" label="New folder" @click="openNewPersonalFile" />
          <Button class="btn btn--primary" icon="fa fa-file-upload" label="Upload" @click="uploadDocumentHandler" />
          <Button v-if="selectedPersonalFiles.length" class="btn btn--danger" icon="pi pi-trash" label="Delete" @click="confirmDeleteMultiplePersonalFiles" />
          <Button class="btn btn--primary" :icon="viewModeIcon" @click="toggleViewMode" />
          <Button v-if="previousFolders.length" class="btn btn--primary" icon="pi pi-arrow-left" label="Back" @click="goBack" />
        </div>
      </div>
      <div class="breadcrumbs">
        <span v-for="(folder, index) in previousFolders" :key="index">
          <span>{{ folder.title }}</span> /
        </span>
        <span>{{ currentFolderTitle }}</span>
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
        paginator-template="CurrentPageReport FirstPageReportLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsive-layout="scroll"
        @page="onPersonalFilesPage"
        @sort="sortingPersonalFilesChanged"
      >
        <Column :header="$t('Title')" :sortable="true" field="resourceNode.title">

          <template #body="slotProps">
            <div>
              <span
                v-if="!slotProps.data.resourceNode.firstResourceFile" @click="handleClickPersonalFile(slotProps.data)">
                {{ slotProps.data.resourceNode.title }} folder
              </span>
              <span v-else>
                {{ slotProps.data.resourceNode.title }}
              </span>
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
        <div v-for="file in personalFiles" :key="file.iid" class="thumbnail-item" @click="handleClickPersonalFile(file)" @contextmenu.prevent="showContextMenu($event, file)">
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
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from 'vue-router'
import { useStore } from 'vuex'
import { storeToRefs } from 'pinia'
import { useSecurityStore } from '../../store/securityStore'
import prettyBytes from 'pretty-bytes'
import { useI18n } from 'vue-i18n'
import { useFormatDate } from '../../composables/formatDate'
import BaseContextMenu from '../basecomponents/BaseContextMenu.vue'
import { useCidReq } from "../../composables/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import isEmpty from "lodash/isEmpty"

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
const viewMode = ref('thumbnails')

const contextMenuVisible = ref(false)
const contextMenuPosition = ref({ x: 0, y: 0 })
const contextMenuFile = ref(null)
const { cid, sid, gid } = useCidReq()
const previousFolders = ref([]);
const currentFolderTitle = ref('Root');

const flattenFilters = (filters) => {
  return Object.keys(filters).reduce((acc, key) => {
    acc[key] = filters[key]
    return acc
  }, {})
}

const onUpdatePersonalFileOptions = async (parentResourceNodeId = null) => {
  if (!isEmpty(route.query.filetype) && route.query.filetype === "certificate") {
    personalFileFilters.value.filetype = "certificate";
  } else {
    personalFileFilters.value.filetype = ["file", "folder"];
  }

  let filters = flattenFilters({
    ...personalFileFilters.value,
    cid: route.query.cid || '',
    sid: route.query.sid || '',
    gid: route.query.gid || '',
    type: route.query.type || '',
  });

  if (parentResourceNodeId) {
    filters["resourceNode.parent"] = parentResourceNodeId;
  } else if (filters.loadNode === 1 && route.params.node) {
    filters["resourceNode.parent"] = route.params.node;
  } else {
    filters["resourceNode.parent"] = user.value.resourceNode.id;
  }

  console.log('parentResourceNodeId :::: ', parentResourceNodeId);
  console.log('filters["resourceNode.parent"] :::: ', filters["resourceNode.parent"]);
  console.log('route.params.node :::: ', route.params.node);

  isPersonalFilesLoading.value = true;

  try {
    const response = await fetch(`/api/personal_files?page=${personalFileOptions.value.page}&rows=${personalFileOptions.value.itemsPerPage}&sortBy=${personalFileOptions.value.sortBy}&sortDesc=${personalFileOptions.value.sortDesc}&shared=${filters.shared}&loadNode=${filters.loadNode}&resourceNode.parent=${filters['resourceNode.parent']}&cid=${filters.cid}&sid=${filters.sid}&gid=${filters.gid}&type=${filters.type}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      },
    });

    const data = await response.json();

    if (data['hydra:member']) {
      personalFiles.value = data['hydra:member'];
      totalPersonalFiles.value = data['hydra:totalItems'];
    } else {
      console.error('Error: Data format is not correct', data);
    }
  } catch (error) {
    console.error('Error fetching personal files:', error);
  } finally {
    isPersonalFilesLoading.value = false;
  }
};

const uploadDocumentHandler = async () => {
  localStorage.setItem('previousFolders', JSON.stringify(previousFolders.value));
  localStorage.setItem('currentFolderTitle', currentFolderTitle.value);

  const uploadRoute = 'FileManagerUploadFile';
  await router.push({
    name: uploadRoute,
    query: {
      ...route.query,
      parentResourceNodeId: personalFileFilters.value['resourceNode.parent'],
      parent: personalFileFilters.value['resourceNode.parent'],
      returnTo: route.name
    },
  });
};

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
    if (!personalFileItem.value.id) {

      let resourceNodeId = user.value.resourceNode.id
      if (route.params.node) {
        resourceNodeId = route.params.node
      }
      personalFileItem.value.filetype = 'folder'
      personalFileItem.value.parentResourceNodeId = resourceNodeId
      personalFileItem.value.resourceLinkList = JSON.stringify([{
        gid,
        sid,
        cid,
        visibility: RESOURCE_LINK_PUBLISHED
      }])

      try {
        await store.dispatch('personalfile/createWithFormData', personalFileItem.value);
        await onUpdatePersonalFileOptions();
      } catch (error) {
        console.error('Error creating folder:', error);
      }
    }
    personalFileDialog.value = false
    personalFileItem.value = {}
    personalFileSubmitted.value = false
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
    returnToEditor(data);
  } else {
    previousFolders.value.push({
      id: personalFileFilters.value["resourceNode.parent"],
      title: currentFolderTitle.value
    });
    personalFileFilters.value["resourceNode.parent"] = data.resourceNode.id;
    currentFolderTitle.value = data.resourceNode.title;
    onUpdatePersonalFileOptions(data.resourceNode.id);
  }
};

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

  function getUrlParam(paramName) {
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
    return 'mdi-folder'
  }
  const fileTypeIcons = {
    'pdf': 'mdi-file-pdf-box',
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

const showContextMenu = (event, file) => {
  event.preventDefault()
  contextMenuFile.value = file
  contextMenuPosition.value = { x: event.clientX, y: event.clientY }
  contextMenuVisible.value = true
}

const goBack = () => {
  if (previousFolders.value.length > 0) {
    const previousFolder = previousFolders.value.pop();
    personalFileFilters.value["resourceNode.parent"] = previousFolder.id;
    currentFolderTitle.value = previousFolder.title;
    onUpdatePersonalFileOptions();
  } else {
    personalFileFilters.value["resourceNode.parent"] = user.value.resourceNode.id;
    currentFolderTitle.value = 'Root';
    onUpdatePersonalFileOptions();
  }
};

const selectFile = (file) => {
  returnToEditor(file)
  contextMenuVisible.value = false
}

onMounted(() => {
  if (route.query.parentResourceNodeId) {
    personalFileFilters.value["resourceNode.parent"] = Number(route.query.parentResourceNodeId);
  } else {
    personalFileFilters.value["resourceNode.parent"] = user.value.resourceNode.id;
  }

  const savedPreviousFolders = localStorage.getItem('previousFolders');
  const savedCurrentFolderTitle = localStorage.getItem('currentFolderTitle');

  console.log('savedPreviousFolders ::: ', savedPreviousFolders)

  if (savedPreviousFolders) {
    previousFolders.value = JSON.parse(savedPreviousFolders);
    localStorage.removeItem('previousFolders');
  }
  if (savedCurrentFolderTitle) {
    currentFolderTitle.value = savedCurrentFolderTitle;
    localStorage.removeItem('currentFolderTitle');
  }

  onUpdatePersonalFileOptions();

  window.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'upload-personal-complete') {
      personalFileFilters.value["resourceNode.parent"] = event.data.parentResourceNodeId;
      onUpdatePersonalFileOptions();
    }
  });
});

</script>
