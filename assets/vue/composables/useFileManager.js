import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStore } from 'vuex';
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import { useSecurityStore } from '../store/securityStore';
import { useCidReq } from './cidReq';
import { RESOURCE_LINK_PUBLISHED } from "../constants/entity/resourcelink"
import { useCidReqStore } from "../store/cidReq"
import axios from "axios"

export function useFileManager(entity, apiEndpoint, uploadRoute, isCourseDocument = false) {
  const route = useRoute();
  const router = useRouter();
  const store = useStore();
  const { t } = useI18n();
  const securityStore = useSecurityStore();
  const { isAuthenticated, user } = storeToRefs(securityStore);
  const cidReqStore = isCourseDocument ? useCidReqStore() : null;
  const { course } = cidReqStore ? storeToRefs(cidReqStore) : { course: null };

  const files = ref([]);
  const totalFiles = ref(0);
  const isLoading = ref(false);
  const selectedFiles = ref([]);
  const dialog = ref(false);
  const deleteDialog = ref(false);
  const deleteMultipleDialog = ref(false);
  const detailsDialogVisible = ref(false);
  const selectedItem = ref({});
  const itemToDelete = ref(null);
  const item = ref({});
  const submitted = ref(false);
  const filters = ref({ shared: 0, loadNode: 1, itemsPerPage: 10, page: 1, sortBy: '', sortDesc: false });
  const viewMode = ref('thumbnails');
  const contextMenuVisible = ref(false);
  const contextMenuPosition = ref({ x: 0, y: 0 });
  const contextMenuFile = ref(null);
  const previousFolders = ref([]);
  const currentFolderTitle = ref('Root');
  const { cid, sid, gid } = useCidReq();

  const flattenFilters = (filters) => {
    return Object.keys(filters).reduce((acc, key) => {
      acc[key] = filters[key];
      return acc;
    }, {});
  };

  const onUpdateOptions = async () => {

    if (!filters.value) {
      filters.value = { shared: 0, loadNode: 1 };
    }

    const flattenedFilters = flattenFilters({
      ...filters.value,
      cid: route.query.cid || '',
      sid: route.query.sid || '',
      gid: route.query.gid || '',
      type: route.query.type || '',
    });

    const params = {
      ...flattenedFilters,
      page: filters.value.page || 1,
      itemsPerPage: filters.value.itemsPerPage || 10,
      [`order[${filters.value.sortBy}]`]: filters.value.sortDesc ? 'desc' : 'asc',
    };

    isLoading.value = true;

    try {
      const response = await fetch(`${apiEndpoint}?${new URLSearchParams(params).toString()}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        },
      });

      const data = await response.json();
      if (data['hydra:member']) {
        files.value = data['hydra:member'];
        totalFiles.value = data['hydra:totalItems'];
      } else {
        console.error('Error: Data format is not correct', data);
      }
    } catch (error) {
      console.error('Error fetching files:', error);
    } finally {
      isLoading.value = false;
    }
  };

  const handleClickFile = (data) => {
    if (data.resourceNode.firstResourceFile) {
      returnToEditor(data);
    } else {
      previousFolders.value.push({
        id: filters.value["resourceNode.parent"],
        title: currentFolderTitle.value
      });
      filters.value["resourceNode.parent"] = data.resourceNode.id;
      currentFolderTitle.value = data.resourceNode.title;
      onUpdateOptions();
    }
  };

  const goBack = () => {
    if (previousFolders.value.length > 0) {
      const previousFolder = previousFolders.value.pop();
      filters.value["resourceNode.parent"] = previousFolder.id;
      currentFolderTitle.value = previousFolder.title;
      onUpdateOptions();
    } else {
      filters.value["resourceNode.parent"] = isCourseDocument ? course.value.resourceNode.id : user.value.resourceNode.id;
      currentFolderTitle.value = 'Root';
      onUpdateOptions();
    }
  };

  const returnToEditor = (data) => {
    const url = data.contentUrl;
    window.parent.postMessage({ url: url }, '*');
    if (parent.tinymce) {
      parent.tinymce.activeEditor.windowManager.close();
    }
    function getUrlParam(paramName) {
      const reParam = new RegExp('(?:[\\?&]|&amp;)' + paramName + '=([^&]+)', 'i');
      const match = window.location.search.match(reParam);
      return (match && match.length > 1) ? match[1] : '';
    }
    const funcNum = getUrlParam('CKEditorFuncNum');
    if (window.opener.CKEDITOR) {
      window.opener.CKEDITOR.tools.callFunction(funcNum, url);
      window.close();
    }
  };

  const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'list' ? 'thumbnails' : 'list';
    onUpdateOptions();
  };

  const viewModeIcon = computed(() => viewMode.value === 'list' ? 'pi pi-th-large' : 'pi pi-list');

  const isImage = (file) => {
    const fileExtensions = ['jpeg', 'jpg', 'png', 'gif'];
    const extension = file.resourceNode.title.split('.').pop().toLowerCase();
    return fileExtensions.includes(extension);
  };

  const getFileUrl = (file) => {
    return file.contentUrl;
  };

  const getIcon = (file) => {
    if (!file.resourceNode.firstResourceFile) {
      return 'mdi-folder';
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
      'default': 'mdi-file',
    };
    const extension = file.resourceNode.title.split('.').pop().toLowerCase();
    return fileTypeIcons[extension] || fileTypeIcons['default'];
  };

  const showContextMenu = (event, file) => {
    event.preventDefault();
    contextMenuFile.value = file;
    contextMenuPosition.value = { x: event.clientX, y: event.clientY };
    contextMenuVisible.value = true;
  };

  const openNewDialog = () => {
    item.value = {};
    submitted.value = false;
    dialog.value = true;
  };

  const hideDialog = () => {
    dialog.value = false;
    submitted.value = false;
  };

  const saveItem = async () => {
    submitted.value = true;
    if (item.value.title.trim()) {
      if (!item.value.id) {
        item.value.filetype = 'folder';
        item.value.parentResourceNodeId = filters.value["resourceNode.parent"];
        item.value.resourceLinkList = JSON.stringify([{ gid, sid, cid, visibility: RESOURCE_LINK_PUBLISHED }]);

        try {
          await store.dispatch(`${entity}/createWithFormData`, item.value);
          await onUpdateOptions();
        } catch (error) {
          console.error('Error creating folder:', error);
        }
      }
      dialog.value = false;
      item.value = {};
      submitted.value = false;
    }
  };

  const confirmDeleteItem = (item) => {
    itemToDelete.value = { ...item };
    deleteDialog.value = true;
  };

  const confirmDeleteMultiple = () => {
    deleteMultipleDialog.value = true;
  };

  const deleteMultipleItems = async () => {
    const ids = selectedFiles.value.map(file => file.id);
    try {
      await store.dispatch(`${entity}/delMultiple`, ids);
      deleteMultipleDialog.value = false;
      selectedFiles.value = [];
      onUpdateOptions();
    } catch (error) {
      console.error('Error deleting multiple items:', error);
    }
  };

  const deleteItemButton = async () => {
    if (isCourseDocument) {
      if (itemToDelete.value && itemToDelete.value.iid) {
        try {
          await axios.delete(`/api/documents/${itemToDelete.value.iid}`);
          deleteDialog.value = false;
          itemToDelete.value = { resourceNode: {} };
          await onUpdateOptions();
        } catch (error) {
          console.error('Error deleting document:', error);
        }
      } else {
        console.error('Document to delete is missing or invalid', itemToDelete.value);
      }
    } else {
      if (itemToDelete.value && itemToDelete.value.id) {
        try {
          await store.dispatch(`${entity}/del`, itemToDelete.value);
          deleteDialog.value = false;
          itemToDelete.value = null;
          onUpdateOptions();
        } catch (error) {
          console.error('An error occurred while deleting the item', error);
        }
      }
    }
  };

  const onFilesPage = (event) => {
    filters.value.itemsPerPage = event.rows || 10;
    filters.value.page = event.page + 1;
    filters.value.sortBy = event.sortField;
    filters.value.sortDesc = event.sortOrder === -1;
    onUpdateOptions();
  };

  const sortingFilesChanged = (event) => {
    filters.value.sortBy = event.sortField || '';
    filters.value.sortDesc = event.sortOrder === -1;
    onUpdateOptions();
  };

  const closeDetailsDialog = () => {
    detailsDialogVisible.value = false;
  };

  const uploadDocumentHandler = async () => {
    localStorage.setItem('previousFolders', JSON.stringify(previousFolders.value));
    localStorage.setItem('currentFolderTitle', currentFolderTitle.value);
    localStorage.setItem('isUploaded', 'true');
    localStorage.setItem('uploadParentNodeId', filters.value['resourceNode.parent']);

    await router.push({
      name: uploadRoute,
      query: {
        ...route.query,
        parentResourceNodeId: filters.value['resourceNode.parent'],
        parent: filters.value['resourceNode.parent'],
        returnTo: route.name
      },
    });
  };

  const onMountedCallback = () => {
    onMounted(() => {
      const savedPreviousFolders = localStorage.getItem('previousFolders');
      const savedCurrentFolderTitle = localStorage.getItem('currentFolderTitle');
      const isUploaded = localStorage.getItem('isUploaded');
      const uploadParentNodeId = localStorage.getItem('uploadParentNodeId');

      if (isUploaded === 'true' && uploadParentNodeId) {
        filters.value["resourceNode.parent"] = Number(uploadParentNodeId);
        localStorage.removeItem('isUploaded');
        localStorage.removeItem('uploadParentNodeId');
      } else if (!filters.value["resourceNode.parent"] || filters.value["resourceNode.parent"] === 0) {
        filters.value["resourceNode.parent"] = isCourseDocument ? course.value.resourceNode.id : user.value.resourceNode.id;
      }

      if (savedPreviousFolders) {
        previousFolders.value = JSON.parse(savedPreviousFolders);
        localStorage.removeItem('previousFolders');
      }
      if (savedCurrentFolderTitle) {
        currentFolderTitle.value = savedCurrentFolderTitle;
        localStorage.removeItem('currentFolderTitle');
      }

      onUpdateOptions();
    });
  };

  const selectFile = (file) => {
    returnToEditor(file);
    contextMenuVisible.value = false;
  };

  const showHandler = (item) => {
    selectedItem.value = item;
    detailsDialogVisible.value = true;
  };

  const editHandler = (item) => {
    item.value = { ...item };
    dialog.value = true;
  };

  const totalPages = computed(() => {
    return Math.ceil(totalFiles.value / filters.value.itemsPerPage);
  });

  const nextPage = () => {
    if (filters.value.page < totalPages.value) {
      filters.value.page++;
      onUpdateOptions();
    }
  };

  const previousPage = () => {
    if (filters.value.page > 1) {
      filters.value.page--;
      onUpdateOptions();
    }
  };

  return {
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
    flattenFilters,
    onUpdateOptions,
    handleClickFile,
    goBack,
    returnToEditor,
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
    selectFile,
    showHandler,
    editHandler,
    nextPage,
    previousPage,
    totalPages
  };
}
