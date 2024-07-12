<template>
  <div class="filemanager-container">
    <div v-if="isAuthenticated" class="q-card">
      <div class="p-4 flex flex-row gap-1 mb-2">
        <div class="flex flex-row gap-2">
          <Button class="btn btn--primary" icon="fa fa-folder-plus" label="New folder" @click="openNewDocument" />
          <Button class="btn btn--primary" icon="fa fa-file-upload" label="Upload" @click="uploadDocumentHandler" />
          <Button v-if="selectedDocuments.length" class="btn btn--danger" icon="pi pi-trash" label="Delete" @click="confirmDeleteMultipleDocuments" />
          <Button class="btn btn--primary" :icon="viewModeIcon" @click="toggleViewMode" />
          <Button v-if="previousFolders.length" class="btn btn--primary" icon="pi pi-arrow-left" label="Back" @click="goBack" />
        </div>
      </div>
      <div class="breadcrumbs">
        <span v-for="(folder, index) in previousFolders" :key="index">
          <a @click="navigateToFolder(folder)">{{ folder.title }}</a> /
        </span>
        <span>{{ currentFolderTitle }}</span>
      </div>
    </div>

    <div v-if="viewMode === 'list'">
      <DataTable
        v-model:filters="documentFilters"
        v-model:selection="selectedDocuments"
        :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
        :lazy="true"
        :loading="isDocumentsLoading"
        :paginator="true"
        :rows="10"
        :rows-per-page-options="[5, 10, 20, 50]"
        :total-records="totalDocuments"
        :value="documents"
        class="p-datatable-sm"
        current-page-report-template="Showing {first} to {last} of {totalRecords}"
        data-key="iid"
        filter-display="menu"
        paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        responsive-layout="scroll"
        @page="onDocumentsPage"
        @sort="sortingDocumentsChanged"
      >
        <Column :header="$t('Title')" :sortable="true" field="resourceNode.title">
          <template #body="slotProps">
            <div>
              <span :class="['mdi', getIcon(slotProps.data)]" class="mdi-icon" @click="handleClickDocument(slotProps.data)" />
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
              <Button v-if="isAuthenticated" class="btn btn--danger" icon="pi pi-trash" @click="confirmDeleteItemDocument(slotProps.data)" />
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
        <div v-for="doc in documents" :key="doc.iid" class="thumbnail-item" @dblclick="handleDoubleClick(doc)" @contextmenu.prevent="showContextMenu($event, doc)">
          <div class="thumbnail-icon" @click="handleClickDocument(doc)">
            <template v-if="isImage(doc)">
              <img :src="getFileUrl(doc)" :alt="doc.resourceNode.title" :title="doc.resourceNode.title" class="thumbnail-image" />
            </template>
            <template v-else>
              <span :class="['mdi', getIcon(doc)]" class="mdi-icon"></span>
            </template>
          </div>
          <div class="thumbnail-title">{{ doc.resourceNode.title }}</div>
        </div>
      </div>
      <BaseContextMenu :visible="contextMenuVisible" :position="contextMenuPosition" @close="contextMenuVisible = false">
        <ul>
          <li @click="selectFile(contextMenuFile)">
            <span class="mdi mdi-file-check-outline"></span>
            Select
          </li>
          <li @click="confirmDeleteItemDocument(contextMenuFile)">
            <span class="mdi mdi-delete-outline"></span>
            Delete
          </li>
        </ul>
      </BaseContextMenu>
    </div>

    <Dialog v-model:visible="documentDialog" :header="$t('New folder')" :modal="true" :style="{ width: '450px' }" class="p-fluid">
      <div class="p-field">
        <label for="title">{{ $t('Name') }}</label>
        <InputText id="title" v-model.trim="documentItem.title" :class="{ 'p-invalid': documentSubmitted && !documentItem.title }" autocomplete="off" autofocus required />
        <small v-if="documentSubmitted && !documentItem.title" class="p-error">{{ $t('Title is required') }}</small>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="Cancel" @click="hideDocumentDialog" />
        <Button class="p-button-text" icon="pi pi-check" label="Save" @click="saveDocumentItem" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteDocumentDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
        <span>Are you sure you want to delete <b>{{ documentToDelete?.title }}</b>?</span>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteDocumentDialog = false" />
        <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteDocumentItemButton" />
      </template>
    </Dialog>

    <Dialog v-model:visible="deleteMultipleDocumentDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
        <span>{{ $t('Are you sure you want to delete the selected items?') }}</span>
      </div>
      <template #footer>
        <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteMultipleDocumentDialog = false" />
        <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteMultipleDocumentsItems" />
      </template>
    </Dialog>

    <Dialog v-model:visible="detailsDocumentDialogVisible" :header="selectedDocumentItem.title || 'Item Details'" :modal="true" :style="{ width: '50%' }">
      <div v-if="Object.keys(selectedDocumentItem).length > 0">
        <p><strong>Title:</strong> {{ selectedDocumentItem.title }}</p>
        <p><strong>Modified:</strong> {{ relativeDatetime(selectedDocumentItem.resourceNode.updatedAt) }}</p>
        <p><strong>Size:</strong> {{ prettyBytes(selectedDocumentItem.resourceNode.firstResourceFile.size) }}</p>
        <p><strong>URL:</strong> <a :href="selectedDocumentItem.contentUrl" target="_blank">Open File</a></p>
      </div>
      <template #footer>
        <Button class="p-button-text" label="Close" @click="closeDocumentDetailsDialog" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useStore } from "vuex";
import { storeToRefs } from "pinia";
import { useSecurityStore } from "../../store/securityStore";
import prettyBytes from "pretty-bytes";
import { useI18n } from "vue-i18n";
import { useFormatDate } from "../../composables/formatDate";
import { useCidReqStore } from "../../store/cidReq";
import BaseContextMenu from "../basecomponents/BaseContextMenu.vue";
import axios from "axios";

const { t } = useI18n();
const { relativeDatetime } = useFormatDate();
const route = useRoute();
const router = useRouter();
const store = useStore();
const securityStore = useSecurityStore();
const { isAuthenticated, user } = storeToRefs(securityStore);
const cidReqStore = useCidReqStore();
const { course } = storeToRefs(cidReqStore);

const documents = ref([]);
const totalDocuments = ref(0);
const isDocumentsLoading = ref(false);
const selectedDocuments = ref([]);
const documentDialog = ref(false);
const deleteDocumentDialog = ref(false);
const deleteMultipleDocumentDialog = ref(false);
const detailsDocumentDialogVisible = ref(false);
const selectedDocumentItem = ref({ resourceNode: {} });
const documentToDelete = ref({ resourceNode: {} });
const documentFilters = ref({
  shared: 0,
  loadNode: 1,
  'resourceNode.parent': course.value.resourceNode.id,
  cid: route.query.cid || '',
  sid: route.query.sid || '',
  gid: route.query.gid || '',
  type: route.query.type || ''
});
const documentSubmitted = ref(false);
const documentItem = ref({});
const documentOptions = ref({ itemsPerPage: 10, page: 1, sortBy: '', sortDesc: false });
const viewMode = ref('thumbnails'); // Default to thumbnails

const contextMenuVisible = ref(false);
const contextMenuPosition = ref({ x: 0, y: 0 });
const contextMenuFile = ref(null);
const previousFolders = ref([]);
const currentFolderTitle = ref('Root');

const flattenFilters = (filters) => {
  return Object.keys(filters).reduce((acc, key) => {
    acc[key] = filters[key];
    return acc;
  }, {});
};

const onUpdateDocumentOptions = async () => {
  const filters = flattenFilters({
    ...documentFilters.value,
    cid: route.query.cid || '',
    sid: route.query.sid || '',
    gid: route.query.gid || '',
    type: route.query.type || '',
  });

  isDocumentsLoading.value = true;

  try {
    const response = await fetch(`/api/documents?page=${documentOptions.value.page}&rows=${documentOptions.value.itemsPerPage}&sortBy=${documentOptions.value.sortBy}&sortDesc=${documentOptions.value.sortDesc}&shared=${filters.shared}&loadNode=${filters.loadNode}&resourceNode.parent=${filters['resourceNode.parent']}&cid=${filters.cid}&sid=${filters.sid}&gid=${filters.gid}&type=${filters.type}`);
    const data = await response.json();

    if (data['hydra:member']) {
      documents.value = data['hydra:member'];
      totalDocuments.value = data['hydra:totalItems'];
      console.log('Documents updated:', documents.value);
    } else {
      console.error('Error: Data format is not correct', data);
    }
  } catch (error) {
    console.error('Error fetching documents:', error);
  } finally {
    isDocumentsLoading.value = false;
  }
};

watch(documents, (newVal) => {
  console.log('Documents updated:', newVal);
});

const handleClickDocument = (data) => {
  if (data.resourceNode.firstResourceFile) {
    returnToEditor(data);
  } else {
    previousFolders.value.push({ id: documentFilters.value["resourceNode.parent"], title: currentFolderTitle.value });
    documentFilters.value["resourceNode.parent"] = data.resourceNode.id;
    currentFolderTitle.value = data.resourceNode.title;
    onUpdateDocumentOptions();
  }
};

const goBack = () => {
  if (previousFolders.value.length > 0) {
    const previousFolder = previousFolders.value.pop();
    documentFilters.value["resourceNode.parent"] = previousFolder.id;
    currentFolderTitle.value = previousFolder.title;
    onUpdateDocumentOptions();
  }
};

const navigateToFolder = (folder) => {
  documentFilters.value["resourceNode.parent"] = folder.id;
  currentFolderTitle.value = folder.title;
  onUpdateDocumentOptions();
};

const closeDocumentDetailsDialog = () => {
  detailsDocumentDialogVisible.value = false;
};

// Common Functions
const returnToEditor = (data) => {
  const url = data.contentUrl;

  // Tiny mce.
  window.parent.postMessage(
    {
      url: url,
    },
    "*"
  );

  if (parent.tinymce) {
    parent.tinymce.activeEditor.windowManager.close();
  }

  // Ckeditor
  function getUrlParam(paramName) {
    const reParam = new RegExp("(?:[\\?&]|&amp;)" + paramName + "=([^&]+)", "i");
    const match = window.location.search.match(reParam);
    return match && match.length > 1 ? match[1] : "";
  }

  const funcNum = getUrlParam("CKEditorFuncNum");
  if (window.opener.CKEDITOR) {
    window.opener.CKEDITOR.tools.callFunction(funcNum, url);
    window.close();
  }
};

const refreshDocumentsList = async () => {
  await onUpdateDocumentOptions();
};

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'list' ? 'thumbnails' : 'list';
};

const viewModeIcon = computed(() => viewMode.value === 'list' ? 'pi pi-th-large' : 'pi pi-list');

const isImage = (doc) => {
  return doc.resourceNode.firstResourceFile && ['jpeg', 'jpg', 'png', 'gif'].includes(doc.resourceNode.firstResourceFile.mimeType.split('/').pop());
};

const getFileUrl = (doc) => {
  return doc.contentUrl;
};

const getIcon = (doc) => {
  if (!doc.resourceNode.firstResourceFile) {
    return 'mdi-folder';
  }
  const fileTypeIcons = {
    'application/pdf': 'pi pi-file-pdf',
    'application/msword': 'pi pi-file-word',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'pi pi-file-word',
    'application/vnd.ms-excel': 'pi pi-file-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'pi pi-file-excel',
    'application/zip': 'pi pi-file-zip',
    'image/jpeg': 'pi pi-file-image',
    'image/png': 'pi pi-file-image',
    'image/gif': 'pi pi-file-image',
    'default': 'pi pi-file'
  };
  return fileTypeIcons[doc.resourceNode.firstResourceFile.mimeType] || fileTypeIcons['default'];
};

const handleDoubleClick = (doc) => {
  selectFile(doc);
};

const openContextMenu = (event) => {
  contextMenuVisible.value = false;
};

const showContextMenu = (event, doc) => {
  event.preventDefault();
  contextMenuFile.value = doc;
  contextMenuPosition.value = { x: event.clientX, y: event.clientY };
  contextMenuVisible.value = true;
};

const selectFile = (file) => {
  returnToEditor(file);
  contextMenuVisible.value = false;
};

const uploadDocumentHandler = () => {
  const uploadRoute = 'CourseDocumentsUploadFile';
  router.push({
    name: uploadRoute,
    query: {
      ...route.query,
      parent: documentFilters.value['resourceNode.parent'],
    },
  });
};

const openNewDocument = () => {
  documentItem.value = {};
  documentSubmitted.value = false;
  documentDialog.value = true;
};

const hideDocumentDialog = () => {
  documentDialog.value = false;
  documentSubmitted.value = false;
};

const saveDocumentItem = async () => {
  documentSubmitted.value = true;
  if (documentItem.value.title.trim()) {
    if (documentItem.value.id) {
      // Update logic here
    } else {
      const resourceNodeId = documentFilters.value["resourceNode.parent"];
      documentItem.value.filetype = "folder";
      documentItem.value.parentResourceNodeId = resourceNodeId;
      documentItem.value.resourceLinkList = JSON.stringify([{
        gid: 0,
        sid: 0,
        cid: 0,
        visibility: 'RESOURCE_LINK_PUBLISHED'
      }]);
      await store.dispatch('documents/createWithFormData', documentItem.value);
    }
    documentDialog.value = false;
    documentItem.value = {};
    await onUpdateDocumentOptions();
  }
};

const confirmDeleteItemDocument = (item) => {
  if (item && item.iid) {
    documentToDelete.value = { ...item };
    deleteDocumentDialog.value = true;
  } else {
    console.error("Document ID is missing or invalid", item);
  }
};

const deleteDocumentItemButton = async () => {
  if (documentToDelete.value && documentToDelete.value.iid) {
    try {
      await axios.delete(`/api/documents/${documentToDelete.value.iid}`);
      deleteDocumentDialog.value = false;
      documentToDelete.value = { resourceNode: {} };
      await onUpdateDocumentOptions();
    } catch (error) {
      console.error('Error deleting document:', error);
    }
  } else {
    console.error('Document to delete is missing or invalid', documentToDelete.value);
  }
};

const deleteMultipleDocumentsItems = async () => {
  const documentIds = selectedDocuments.value.map(doc => doc.iid).filter(id => id);
  if (documentIds.length > 0) {
    try {
      await Promise.all(documentIds.map(id => axios.delete(`/api/documents/${id}`)));
      deleteMultipleDocumentDialog.value = false;
      selectedDocuments.value = [];
      await onUpdateDocumentOptions();
    } catch (error) {
      console.error('Error deleting multiple documents:', error);
    }
  } else {
    console.error('No valid document IDs found for deletion', selectedDocuments.value);
  }
};

const confirmDeleteMultipleDocuments = () => {
  if (selectedDocuments.value.length > 0) {
    deleteMultipleDocumentDialog.value = true;
  } else {
    console.error("No documents selected for deletion", selectedDocuments.value);
  }
};

const onDocumentsPage = (event) => {
  documentOptions.value.itemsPerPage = event.rows;
  documentOptions.value.page = event.page + 1;
  documentOptions.value.sortBy = event.sortField;
  documentOptions.value.sortDesc = event.sortOrder === -1;

  onUpdateDocumentOptions();
};

const sortingDocumentsChanged = (event) => {
  documentOptions.value.sortBy = event.sortField;
  documentOptions.value.sortDesc = event.sortOrder === -1;

  onUpdateDocumentOptions();
};

onMounted(() => {
  documentFilters.value["resourceNode.parent"] = course.value.resourceNode.id;

  onUpdateDocumentOptions();
});
</script>
