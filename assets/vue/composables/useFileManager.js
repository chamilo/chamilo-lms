import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex"
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../store/securityStore"
import { RESOURCE_LINK_PUBLISHED } from "../constants/entity/resourcelink"
import { useCidReqStore } from "../store/cidReq"
import documentsService from "../services/documents"
import baseService from "../services/baseService"

export function useFileManager(entity, apiEndpoint, uploadRoute, isCourseDocument = false) {
  const route = useRoute()
  const router = useRouter()
  const store = useStore()
  const securityStore = useSecurityStore()
  const { isAuthenticated, user } = storeToRefs(securityStore)
  // Always resolve course context so personal mode can reject a course root parent.
  const cidReqStore = useCidReqStore()
  const { course } = storeToRefs(cidReqStore)

  const files = ref([])
  const totalFiles = ref(0)
  const isLoading = ref(false)
  const selectedFiles = ref([])
  const dialog = ref(false)
  const deleteDialog = ref(false)
  const deleteMultipleDialog = ref(false)
  const detailsDialogVisible = ref(false)
  const selectedItem = ref({})
  const itemToDelete = ref(null)
  const item = ref({})
  const submitted = ref(false)
  const filters = ref({ shared: 0, loadNode: 1, itemsPerPage: 10, page: 1, sortBy: "", sortDesc: false })
  const viewMode = ref("thumbnails")
  const contextMenuVisible = ref(false)
  const contextMenuPosition = ref({ x: 0, y: 0 })
  const contextMenuFile = ref(null)
  const previousFolders = ref([])
  const currentFolderTitle = ref("Root")

  // Namespace browser storage so My files and Documents tabs do not share parent ids.
  // Previously both modes used `pf_parent`, so opening Documents (course node) then
  // switching to My files listed personal_files under the course root → empty list.
  const storagePrefix = isCourseDocument ? "cd_" : "pf_"
  const SS_KEY_PARENT = `${storagePrefix}parent`
  const SS_KEY_PREVIOUS_FOLDERS = `${storagePrefix}previousFolders`
  const SS_KEY_CURRENT_FOLDER_TITLE = `${storagePrefix}currentFolderTitle`
  const LS_KEY_PREVIOUS_FOLDERS = `${storagePrefix}previousFolders`
  const LS_KEY_CURRENT_FOLDER_TITLE = `${storagePrefix}currentFolderTitle`
  const LS_KEY_IS_UPLOADED = `${storagePrefix}isUploaded`
  const LS_KEY_UPLOAD_PARENT = `${storagePrefix}uploadParentNodeId`

  // ---- Picker type filter (files/images/media) ----
  const filterType = computed(() => {
    const raw = String(route.query.type || "files").toLowerCase()
    return ["files", "images", "media"].includes(raw) ? raw : "files"
  })

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
    // Always keep folders visible for navigation
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

  // Backwards-compatible: keep `files`, add `visibleFiles`
  const visibleFiles = computed(() => {
    const type = filterType.value
    return (files.value || []).filter((f) => matchesFilter(f, type))
  })

  function getRootParentId() {
    if (isCourseDocument) {
      return Number(course.value?.resourceNode?.id || 0)
    }

    return Number(user.value?.resourceNode?.id || 0)
  }

  /**
   * Reject parents that clearly belong to the other tab's tree.
   * Personal files must never list under a course resource node (and vice versa).
   */
  function isValidParentForMode(parentId) {
    const id = Number(parentId || 0)
    if (id <= 0) {
      return false
    }

    const courseRootId = Number(course.value?.resourceNode?.id || 0)
    const userRootId = Number(user.value?.resourceNode?.id || 0)

    if (isCourseDocument) {
      if (userRootId > 0 && id === userRootId) {
        return false
      }
      return true
    }

    if (courseRootId > 0 && id === courseRootId) {
      return false
    }

    return true
  }

  const setParentInSession = (id) => {
    try {
      sessionStorage.setItem(SS_KEY_PARENT, String(Number(id || 0)))
    } catch {
      // Ignore browser storage errors.
    }
  }
  const getParentFromSession = () => {
    try {
      return Number(sessionStorage.getItem(SS_KEY_PARENT) || 0)
    } catch {
      return 0
    }
  }
  const clearParentInSession = () => {
    try {
      sessionStorage.removeItem(SS_KEY_PARENT)
    } catch {
      // Ignore browser storage errors.
    }
  }

  const persistNavigationInSession = () => {
    try {
      sessionStorage.setItem(SS_KEY_PREVIOUS_FOLDERS, JSON.stringify(previousFolders.value))
      sessionStorage.setItem(SS_KEY_CURRENT_FOLDER_TITLE, currentFolderTitle.value)
    } catch {
      // Ignore browser storage errors.
    }
  }

  const restoreNavigationFromSession = () => {
    try {
      const storedFolders = sessionStorage.getItem(SS_KEY_PREVIOUS_FOLDERS)
      const storedTitle = sessionStorage.getItem(SS_KEY_CURRENT_FOLDER_TITLE)

      if (storedFolders) {
        const parsedFolders = JSON.parse(storedFolders)
        previousFolders.value = Array.isArray(parsedFolders) ? parsedFolders : []
      }

      if (storedTitle) {
        currentFolderTitle.value = storedTitle
      }
    } catch {
      previousFolders.value = []
      currentFolderTitle.value = "Root"
    }
  }

  const clearNavigationInSession = () => {
    try {
      sessionStorage.removeItem(SS_KEY_PREVIOUS_FOLDERS)
      sessionStorage.removeItem(SS_KEY_CURRENT_FOLDER_TITLE)
    } catch {
      // Ignore browser storage errors.
    }
  }

  const flattenFilters = (filtersObj) => {
    return Object.keys(filtersObj).reduce((acc, key) => {
      acc[key] = filtersObj[key]
      return acc
    }, {})
  }

  const onUpdateOptions = async () => {
    if (!filters.value) {
      filters.value = { shared: 0, loadNode: 1 }
    }

    const flattenedFilters = flattenFilters({
      ...filters.value,
      cid: route.query.cid || "",
      sid: route.query.sid || "",
      gid: route.query.gid || "",
      type: route.query.type || "",
    })

    const params = {
      ...flattenedFilters,
      page: filters.value.page || 1,
      itemsPerPage: filters.value.itemsPerPage || 10,
      [`order[${filters.value.sortBy}]`]: filters.value.sortDesc ? "desc" : "asc",
    }

    isLoading.value = true

    try {
      const { items, totalItems } = await baseService.getCollection(apiEndpoint, params)
      if (items) {
        files.value = items
        totalFiles.value = totalItems
      } else {
        console.error("[FILEMANAGER] Unexpected API response format", items)
      }
    } catch (error) {
      console.error("[FILEMANAGER] Error fetching files:", error)
    } finally {
      isLoading.value = false
    }
  }

  const handleClickFile = (data) => {
    if (data.resourceNode.firstResourceFile) {
      returnToEditor(data)
    } else {
      previousFolders.value.push({
        id: filters.value["resourceNode.parent"],
        title: currentFolderTitle.value,
      })
      filters.value["resourceNode.parent"] = data.resourceNode.id
      currentFolderTitle.value = data.resourceNode.title
      setParentInSession(filters.value["resourceNode.parent"])
      persistNavigationInSession()
      onUpdateOptions()
    }
  }

  const goBack = () => {
    if (previousFolders.value.length > 0) {
      const previousFolder = previousFolders.value.pop()
      filters.value["resourceNode.parent"] = previousFolder.id
      currentFolderTitle.value = previousFolder.title
    } else {
      filters.value["resourceNode.parent"] = getRootParentId()
      currentFolderTitle.value = "Root"
    }
    setParentInSession(filters.value["resourceNode.parent"])
    persistNavigationInSession()
    onUpdateOptions()
  }

  const resetToRoot = () => {
    clearParentInSession()
    clearNavigationInSession()
    previousFolders.value = []
    currentFolderTitle.value = "Root"
    filters.value["resourceNode.parent"] = getRootParentId()
    onUpdateOptions()
  }

  function toAbsoluteUrl(url) {
    const raw = String(url || "").trim()
    if (!raw) return ""
    try {
      return new URL(raw, window.location.origin).href
    } catch {
      return raw
    }
  }

  const returnToEditor = (data) => {
    const url = toAbsoluteUrl(data?.contentUrl)
    if (!url) {
      console.error("[FILEMANAGER] Missing contentUrl for selected item", data)
      return
    }

    // TinyMCE preferred channel (openUrl onMessage)
    const tinymcePayload = { mceAction: "fileSelected", content: { url } }

    try {
      if (parent?.tinymce?.activeEditor?.windowManager?.sendMessage) {
        parent.tinymce.activeEditor.windowManager.sendMessage(tinymcePayload)
      }
    } catch (e) {
      console.warn("[FILEMANAGER] Failed to send TinyMCE windowManager message", e)
    }

    // postMessage fallback (both formats)
    try {
      window.parent.postMessage(tinymcePayload, window.location.origin)
    } catch {
      // Ignore cross-window access errors.
    }
    try {
      window.parent.postMessage({ url }, "*")
    } catch {
      // Ignore cross-window access errors.
    }

    // Close TinyMCE dialog if present
    try {
      if (parent?.tinymce?.activeEditor?.windowManager) {
        parent.tinymce.activeEditor.windowManager.close()
      }
    } catch {
      // Ignore cross-window access errors.
    }

    // CKEditor legacy support
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
      console.warn("[FILEMANAGER] CKEditor callback failed", e)
    }
  }

  const toggleViewMode = () => {
    viewMode.value = viewMode.value === "list" ? "thumbnails" : "list"
    onUpdateOptions()
  }

  const viewModeIcon = computed(() => (viewMode.value === "list" ? "mdi mdi-view-grid" : "mdi mdi-view-list"))

  const isImage = (file) => {
    const fileExtensions = ["jpeg", "jpg", "png", "gif", "svg", "webp"]
    const extension = String(file?.resourceNode?.title || "")
      .split(".")
      .pop()
      .toLowerCase()
    return fileExtensions.includes(extension)
  }

  const getFileUrl = (file) => file.contentUrl

  const getIcon = (file) => {
    if (!file.resourceNode.firstResourceFile) return "mdi-folder"
    const fileTypeIcons = {
      pdf: "mdi-file-pdf-box",
      doc: "mdi-file-word-box",
      docx: "mdi-file-word-box",
      xls: "mdi-file-excel-box",
      xlsx: "mdi-file-excel-box",
      zip: "mdi-zip-box",
      jpeg: "mdi-file-image-box",
      jpg: "mdi-file-image-box",
      png: "mdi-file-image-box",
      gif: "mdi-file-image-box",
      svg: "mdi-file-image-box",
      webp: "mdi-file-image-box",
      default: "mdi-file",
    }
    const extension = String(file?.resourceNode?.title || "")
      .split(".")
      .pop()
      .toLowerCase()
    return fileTypeIcons[extension] || fileTypeIcons.default
  }

  const showContextMenu = (event, file) => {
    event.preventDefault()
    contextMenuFile.value = file
    contextMenuPosition.value = { x: event.clientX, y: event.clientY }
    contextMenuVisible.value = true
  }

  const openNewDialog = () => {
    item.value = {}
    submitted.value = false
    dialog.value = true
  }

  const hideDialog = () => {
    dialog.value = false
    submitted.value = false
  }

  const saveItem = async () => {
    submitted.value = true
    if (item.value.title?.trim()) {
      if (!item.value.id) {
        item.value.filetype = "folder"
        item.value.parentResourceNodeId = filters.value["resourceNode.parent"]
        // Course context derived server-side from the gated session course.
        item.value.resourceLinkList = JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }])

        try {
          await store.dispatch(`${entity}/createWithFormData`, item.value)
          await onUpdateOptions()
        } catch (error) {
          console.error("[FILEMANAGER] Error creating folder:", error)
        }
      }
      dialog.value = false
      item.value = {}
      submitted.value = false
    }
  }

  const confirmDeleteItem = (it) => {
    itemToDelete.value = { ...it }
    deleteDialog.value = true
  }

  const confirmDeleteMultiple = () => {
    deleteMultipleDialog.value = true
  }

  const deleteMultipleItems = async () => {
    const ids = selectedFiles.value.map((file) => file.id)
    try {
      await store.dispatch(`${entity}/delMultiple`, ids)
      deleteMultipleDialog.value = false
      selectedFiles.value = []
      onUpdateOptions()
    } catch (error) {
      console.error("[FILEMANAGER] Error deleting multiple items:", error)
    }
  }

  const deleteItemButton = async () => {
    if (isCourseDocument) {
      if (itemToDelete.value && itemToDelete.value.iid) {
        try {
          await documentsService.deleteDocument(itemToDelete.value.iid)
          deleteDialog.value = false
          itemToDelete.value = { resourceNode: {} }
          await onUpdateOptions()
        } catch (error) {
          console.error("[FILEMANAGER] Error deleting document:", error)
        }
      } else {
        console.error("[FILEMANAGER] Document to delete is missing or invalid", itemToDelete.value)
      }
    } else {
      if (itemToDelete.value && itemToDelete.value.id) {
        try {
          await store.dispatch(`${entity}/del`, itemToDelete.value)
          deleteDialog.value = false
          itemToDelete.value = null
          onUpdateOptions()
        } catch (error) {
          console.error("[FILEMANAGER] Error deleting item", error)
        }
      }
    }
  }

  const onFilesPage = (event) => {
    filters.value.itemsPerPage = event.rows || 10
    filters.value.page = event.page + 1
    filters.value.sortBy = event.sortField
    filters.value.sortDesc = event.sortOrder === -1
    onUpdateOptions()
  }

  const sortingFilesChanged = (event) => {
    filters.value.sortBy = event.sortField || ""
    filters.value.sortDesc = event.sortOrder === -1
    onUpdateOptions()
  }

  const closeDetailsDialog = () => {
    detailsDialogVisible.value = false
  }

  const uploadDocumentHandler = async () => {
    localStorage.setItem(LS_KEY_PREVIOUS_FOLDERS, JSON.stringify(previousFolders.value))
    localStorage.setItem(LS_KEY_CURRENT_FOLDER_TITLE, currentFolderTitle.value)
    localStorage.setItem(LS_KEY_IS_UPLOADED, "true")
    localStorage.setItem(LS_KEY_UPLOAD_PARENT, String(filters.value["resourceNode.parent"] || 0))
    setParentInSession(filters.value["resourceNode.parent"])
    persistNavigationInSession()

    await router.push({
      name: uploadRoute,
      query: {
        ...route.query, // keep picker/type
        parentResourceNodeId: filters.value["resourceNode.parent"],
        parent: filters.value["resourceNode.parent"],
        returnTo: route.name,
      },
    })
  }

  const onMountedCallback = () => {
    onMounted(() => {
      const rootParentId = getRootParentId()
      const routeNodeId = Number(route.params?.node || 0)
      const queryParentId = Number(route.query?.parentResourceNodeId || route.query?.parent || 0)

      // Only treat route parent as relevant when it belongs to this tab's tree.
      // TinyMCE often opens FileManagerList with the course node in :node while the
      // default tab is My files — that must not become resourceNode.parent for personal_files.
      const hasUsableRouteParent = isValidParentForMode(routeNodeId) || isValidParentForMode(queryParentId)

      if (!hasUsableRouteParent) {
        clearParentInSession()
        clearNavigationInSession()
        previousFolders.value = []
        currentFolderTitle.value = "Root"
      }

      const savedPreviousFolders = localStorage.getItem(LS_KEY_PREVIOUS_FOLDERS)
      const savedCurrentFolderTitle = localStorage.getItem(LS_KEY_CURRENT_FOLDER_TITLE)
      const isUploaded = localStorage.getItem(LS_KEY_IS_UPLOADED)
      const uploadParentNodeId = Number(localStorage.getItem(LS_KEY_UPLOAD_PARENT) || 0)
      const sessionParentId = getParentFromSession()

      let resolvedParent = rootParentId

      if (isUploaded === "true" && isValidParentForMode(uploadParentNodeId)) {
        resolvedParent = uploadParentNodeId
        localStorage.removeItem(LS_KEY_IS_UPLOADED)
        localStorage.removeItem(LS_KEY_UPLOAD_PARENT)
      } else if (isValidParentForMode(sessionParentId)) {
        resolvedParent = sessionParentId
      } else if (isValidParentForMode(queryParentId)) {
        resolvedParent = queryParentId
      } else if (isValidParentForMode(routeNodeId)) {
        resolvedParent = routeNodeId
      }

      if (!isValidParentForMode(resolvedParent)) {
        resolvedParent = rootParentId
      }

      filters.value["resourceNode.parent"] = resolvedParent
      setParentInSession(resolvedParent)

      let restoredNavigation = false

      if (savedPreviousFolders) {
        try {
          const parsedFolders = JSON.parse(savedPreviousFolders)
          previousFolders.value = Array.isArray(parsedFolders) ? parsedFolders : []
        } catch {
          previousFolders.value = []
        }
        localStorage.removeItem(LS_KEY_PREVIOUS_FOLDERS)
        restoredNavigation = true
      }
      if (savedCurrentFolderTitle) {
        currentFolderTitle.value = savedCurrentFolderTitle
        localStorage.removeItem(LS_KEY_CURRENT_FOLDER_TITLE)
        restoredNavigation = true
      }

      if (!restoredNavigation && resolvedParent === sessionParentId && resolvedParent !== rootParentId) {
        restoreNavigationFromSession()
      }

      if (resolvedParent === rootParentId) {
        previousFolders.value = []
        currentFolderTitle.value = "Root"
        clearNavigationInSession()
      } else {
        persistNavigationInSession()
      }

      onUpdateOptions()
    })
  }

  // If picker type changes (images/media/files), refresh list
  watch(
    () => filterType.value,
    () => {
      filters.value.page = 1
      onUpdateOptions()
    },
  )

  const selectFile = (file) => {
    returnToEditor(file)
    contextMenuVisible.value = false
  }

  const showHandler = (it) => {
    selectedItem.value = it
    detailsDialogVisible.value = true
  }

  const editHandler = (it) => {
    item.value = { ...it }
    dialog.value = true
  }

  const totalPages = computed(() => {
    return Math.ceil(totalFiles.value / filters.value.itemsPerPage)
  })

  const nextPage = () => {
    if (filters.value.page < totalPages.value) {
      filters.value.page++
      onUpdateOptions()
    }
  }

  const previousPage = () => {
    if (filters.value.page > 1) {
      filters.value.page--
      onUpdateOptions()
    }
  }

  return {
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
    resetToRoot,
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
    totalPages,
  }
}
