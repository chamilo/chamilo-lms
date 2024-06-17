<template>
  <div v-if="isAuthenticated" class="q-card">
    <div class="p-4 flex flex-row gap-1 mb-2">
      <div class="flex flex-row gap-2">
        <Button class="btn btn--primary" icon="fa fa-folder-plus" label="New folder" @click="openNew" />
        <Button class="btn btn--primary" icon="fa fa-file-upload" label="Upload" @click="uploadDocumentHandler" />
        <Button v-if="selectedItems.length" class="btn btn--danger" icon="pi pi-trash" label="Delete" @click="confirmDeleteMultiple" />
      </div>
    </div>
  </div>

  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedItems"
    :globalFilterFields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="10"
    :rowsPerPageOptions="[5, 10, 20, 50]"
    :totalRecords="totalItems"
    :value="items"
    class="p-datatable-sm"
    currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
    dataKey="iid"
    filterDisplay="menu"
    paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsiveLayout="scroll"
    @page="onPage"
    @sort="sortingChanged"
  >
    <Column :header="$t('Title')" :sortable="true" field="resourceNode.title">
      <template #body="slotProps">
        <div v-if="slotProps.data?.resourceNode?.resourceFile">
          <ResourceFileLink :resource="slotProps.data" />
          <v-icon v-if="slotProps.data.resourceLinkListFromEntity?.length" icon="mdi-link" />
        </div>
        <div v-else>
          <a v-if="slotProps.data" class="cursor-pointer" @click="handleClick(slotProps.data)">
            <v-icon icon="mdi-folder" />
            {{ slotProps.data.resourceNode.title }}
          </a>
        </div>
      </template>
    </Column>

    <Column :header="$t('Size')" :sortable="true" field="resourceNode.resourceFile.size">
      <template #body="slotProps">
        {{ slotProps.data.resourceNode.resourceFile ? prettyBytes(slotProps.data.resourceNode.resourceFile.size) : "" }}
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
        <div v-if="slotProps.data.resourceNode.resourceFile" class="flex flex-row gap-2">
          <Button class="p-button-sm p-button p-mr-2" label="Select" @click="returnToEditor(slotProps.data)" />
        </div>
      </template>
    </Column>
  </DataTable>

  <Dialog v-model:visible="itemDialog" :header="$t('New folder')" :modal="true" :style="{ width: '450px' }" class="p-fluid">
    <div class="p-field">
      <label for="title">{{ $t("Name") }}</label>
      <InputText id="title" v-model.trim="item.title" :class="{ 'p-invalid': submitted && !item.title }" autocomplete="off" autofocus required />
      <small v-if="submitted && !item.title" class="p-error">{{ $t('Title is required') }}</small>
    </div>
    <template #footer>
      <Button class="p-button-text" icon="pi pi-times" label="Cancel" @click="hideDialog" />
      <Button class="p-button-text" icon="pi pi-check" label="Save" @click="saveItem" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteItemDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
      <span>Are you sure you want to delete <b>{{ itemToDelete?.title }}</b>?</span>
    </div>
    <template #footer>
      <Button class="p-button-text" icon="pi pi-times" label="No" @click="deleteItemDialog = false" />
      <Button class="p-button-text" icon="pi pi-check" label="Yes" @click="deleteItemButton" />
    </template>
  </Dialog>

  <Dialog v-model:visible="deleteMultipleDialog" :modal="true" :style="{ width: '450px' }" header="Confirm">
    <div class="confirmation-content">
      <i class="pi pi-exclamation-triangle p-mr-3" style="font-size: 2rem"></i>
      <span>{{ $t('Are you sure you want to delete the selected items?') }}</span>
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
      <p><strong>Size:</strong> {{ prettyBytes(selectedItem.resourceNode.resourceFile.size) }}</p>
      <p><strong>URL:</strong> <a :href="selectedItem.contentUrl" target="_blank">Open File</a></p>
    </div>
    <template #footer>
      <Button class="p-button-text" label="Close" @click="closeDetailsDialog" />
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFormatDate } from '../../composables/formatDate'
import prettyBytes from 'pretty-bytes'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import ResourceFileLink from '../../components/documents/ResourceFileLink.vue'
import { useSecurityStore } from '../../store/securityStore'
import { storeToRefs } from 'pinia'
import isEmpty from "lodash/isEmpty"
import isString from "lodash/isString"
import isBoolean from "lodash/isBoolean"
import toInteger from "lodash/toInteger"
import axios from 'axios'

const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const store = useStore()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const { isAuthenticated, user } = storeToRefs(securityStore)

const items = computed(() => store.getters['personalfile/list'])
const totalItems = computed(() => store.getters['personalfile/totalItems'])
const isLoading = computed(() => store.getters['personalfile/isLoading'])

const itemDialog = ref(false)
const deleteItemDialog = ref(false)
const deleteMultipleDialog = ref(false)
const detailsDialogVisible = ref(false)
const selectedItem = ref({})
const itemToDelete = ref(null)
const selectedItems = ref([])
const filters = ref({ shared: 0, loadNode: 1 })
const submitted = ref(false)
const item = ref({})
const options = ref({ itemsPerPage: 10, page: 1, sortBy: '', sortDesc: false })
const pagination = ref({
  sortBy: "resourceNode.title",
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 10,
})
const nextPage = ref(null)
const expandedFilter = ref(false)

const columnsQua = ref([
  { align: "left", name: "resourceNode.title", label: t("Title"), field: "resourceNode.title", sortable: true },
  {
    align: "left",
    name: "resourceNode.updatedAt",
    label: t("Modified"),
    field: "resourceNode.updatedAt",
    sortable: true
  },
  { name: "resourceNode.resourceFile.size", label: t("Size"), field: "resourceNode.resourceFile.size", sortable: true },
  { name: "action", label: t("Actions"), field: "action", sortable: false },
])

const columns = ref([
  { label: t("Title"), field: "title", name: "title", sortable: true },
  { label: t("Modified"), field: "resourceNode.updatedAt", name: "updatedAt", sortable: true },
  { label: t("Size"), field: "resourceNode.resourceFile.size", name: "size", sortable: true },
  { label: t("Actions"), name: "action", sortable: false },
])

const pageOptions = ref([10, 20, 50, t("All")])
const isBusy = ref(false)

const openNew = () => {
  item.value = {}
  submitted.value = false
  itemDialog.value = true
}

const hideDialog = () => {
  itemDialog.value = false
  submitted.value = false
}

const saveItem = async () => {
  submitted.value = true
  if (item.value.title.trim()) {
    if (item.value.id) {
      // Update logic here
    } else {
      let resourceNodeId = user.value.resourceNode.id
      if (route.params.node) {
        resourceNodeId = route.params.node
      }
      item.value.filetype = "folder"
      item.value.parentResourceNodeId = resourceNodeId
      item.value.resourceLinkList = JSON.stringify([{ gid: 0, sid: 0, cid: 0, visibility: 'RESOURCE_LINK_PUBLISHED' }])
      await store.dispatch('personalfile/createWithFormData', item.value)
      showMessage("Saved")
    }
    itemDialog.value = false
    item.value = {}
    onUpdateOptions()
  }
}

const confirmDeleteItem = (item) => {
  itemToDelete.value = { ...item }
  deleteItemDialog.value = true
}

const confirmDeleteMultiple = () => {
  deleteMultipleDialog.value = true
}

const deleteMultipleItems = async () => {
  await store.dispatch('personalfile/delMultiple', selectedItems.value)
  deleteMultipleDialog.value = false
  selectedItems.value = []
  onUpdateOptions()
}

const deleteItemButton = async () => {
  if (itemToDelete.value && itemToDelete.value.id) {
    try {
      await store.dispatch('personalfile/del', itemToDelete.value)
      showMessage("Item deleted successfully", "success")
      deleteItemDialog.value = false
      itemToDelete.value = null
      onUpdateOptions()
    } catch (error) {
      showMessage("An error occurred while deleting the item", "error")
    }
  }
}

const showMessage = (message, type = "info") => {
  // Show toast message
}

const onPage = (event) => {
  options.value.itemsPerPage = event.rows
  options.value.page = event.page + 1
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions()
}

const sortingChanged = (event) => {
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions()
}

const onUpdateOptions = async () => {
  await store.dispatch('personalfile/fetchAll', {
    page: options.value.page,
    rows: options.value.itemsPerPage,
    sortBy: options.value.sortBy,
    sortDesc: options.value.sortDesc
  })
}

const handleClick = (data) => {
  if (data.resourceNode.resourceFile) {
    returnToEditor(data)
  } else {
    const resourceId = data.resourceNode.id
    filters.value["resourceNode.parent"] = resourceId

    router.push({
      name: "FileManagerList",
      params: { node: resourceId },
      query: route.query,
    })
    //onUpdateOptions()
  }
}

const closeDetailsDialog = () => {
  detailsDialogVisible.value = false
}

const uploadDocumentHandler = () => {
  router.push({ name: "FileManagerUploadFile", query: route.query })
}

const returnToEditor = (data) => {
  const url = data.contentUrl

  // Tiny mce.
  window.parent.postMessage(
    {
      url: url,
    },
    "*"
  )

  if (parent.tinymce) {
    parent.tinymce.activeEditor.windowManager.close()
  }

  // Ckeditor
  function getUrlParam(paramName) {
    const reParam = new RegExp("(?:[\\?&]|&amp;)" + paramName + "=([^&]+)", "i")
    const match = window.location.search.match(reParam)
    return match && match.length > 1 ? match[1] : ""
  }

  const funcNum = getUrlParam("CKEditorFuncNum")
  if (window.opener.CKEDITOR) {
    window.opener.CKEDITOR.tools.callFunction(funcNum, url)
    window.close()
  }
}

const resetList = ref(true)

watch(route, () => {
  resetList.value = true
  const nodeId = route.params.node
  if (!isEmpty(nodeId)) {
    const cid = toInteger(route.query.cid)
    const sid = toInteger(route.query.sid)
    const gid = toInteger(route.query.gid)
    const id = "/api/resource_nodes/" + nodeId
    const params = { id, cid, sid, gid }
    findResourceNode(params)
  }
  onUpdateOptions()
})

const findResourceNode = async (params) => {
  await store.dispatch('resourcenode/findResourceNode', params)
}

const deletedItem = ref(null)
const deletedResource = ref(null)
const error = ref(null)

watch([deletedItem, deletedResource, error, items], () => {
  if (deletedItem.value) {
    showMessage(`${deletedItem.value["@id"]} deleted.`)
  }

  if (deletedResource.value) {
    const message = t("{resource} created", { resource: deletedResource.value.resourceNode.title })
    showMessage(message)
    onUpdateOptions()
  }

  if (error.value) {
    showMessage(error.value)
  }

  options.value.totalItems = totalItems.value
})

onMounted(() => {
  onUpdateOptions()
})
</script>
