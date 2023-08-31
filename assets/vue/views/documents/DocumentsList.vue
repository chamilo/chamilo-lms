<template>
  <ButtonToolbar v-if="securityStore.isAuthenticated && isCurrentTeacher">
    <BaseButton
      v-if="showBackButtonIfNotRootFolder"
      :label="t('Back')"
      icon="back"
      type="black"
      @click="back"
    />
    <BaseButton
      :label="t('New document')"
      icon="file-add"
      type="black"
      @click="goToNewDocument"
    />
    <BaseButton
      :disabled="true"
      :label="t('New drawing')"
      icon="drawing"
      type="black"
    />
    <BaseButton
      :label="t('Record audio')"
      icon="record-add"
      type="black"
      @click="showRecordAudioDialog"
    />
    <BaseButton
      :label="t('Upload')"
      icon="file-upload"
      type="black"
      @click="goToUploadFile"
    />
    <BaseButton
      :label="t('New folder')"
      icon="folder-plus"
      type="black"
      @click="openNew"
    />
    <BaseButton
      :disabled="true"
      :label="t('New cloud file')"
      icon="file-cloud-add"
      type="black"
    />
    <BaseButton
      :disabled="!hasImageInDocumentEntries"
      :label="t('Slideshow')"
      icon="view-gallery"
      type="black"
      @click="showSlideShowWithFirstImage"
    />
    <BaseButton
      :label="t('Usage')"
      icon="usage"
      type="black"
      @click="showUsageDialog"
    />
    <BaseButton
      :disabled="true"
      :label="t('Download all')"
      icon="download"
      type="black"
    />
  </ButtonToolbar>

  <DataTable
    v-model:filters="filters"
    v-model:selection="selectedItems"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :lazy="true"
    :loading="isLoading"
    :paginator="true"
    :rows="options.itemsPerPage"
    :rows-per-page-options="[5, 10, 20, 50]"
    :total-records="totalItems"
    :value="items"
    class="mb-5"
    current-page-report-template="Showing {first} to {last} of {totalRecords}"
    data-key="iid"
    filter-display="menu"
    paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
    responsive-layout="scroll"
    striped-rows
    @page="onPage($event)"
    @sort="sortingChanged($event)"
  >
    <Column
      v-if="isCurrentTeacher"
      :exportable="false"
      selection-mode="multiple"
    />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <DocumentEntry
          v-if="slotProps.data"
          :data="slotProps.data"
        />
      </template>
    </Column>

    <Column
      :header="t('Size')"
      :sortable="true"
      field="resourceNode.resourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode.resourceFile
            ? $filters.prettyBytes(slotProps.data.resourceNode.resourceFile.size)
            : ""
        }}
      </template>
    </Column>

    <Column
      :header="t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ useRelativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row justify-end gap-2">
          <BaseButton
            icon="information"
            size="small"
            type="black"
            @click="btnShowInformationOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="securityStore.isAuthenticated && isCurrentTeacher"
            :icon="
              RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility
                ? 'eye-on'
                : RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility
                ? 'eye-off'
                : ''
            "
            size="small"
            type="black"
            @click="btnChangeVisibilityOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="securityStore.isAuthenticated && isCurrentTeacher"
            icon="edit"
            size="small"
            type="black"
            @click="btnEditOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="securityStore.isAuthenticated && isCurrentTeacher"
            icon="delete"
            size="small"
            type="danger"
            @click="confirmDeleteItem(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <ButtonToolbar
    v-if="securityStore.isAuthenticated && isCurrentTeacher"
    show-top-border
  >
    <BaseButton
      :label="t('Select all')"
      icon="select-all"
      type="black"
      @click="selectAll"
    />
    <BaseButton
      :label="t('Unselect all')"
      icon="unselect-all"
      type="black"
      @click="unselectAll"
    />
    <BaseButton
      :disabled="!selectedItems || !selectedItems.length"
      :label="t('Delete selected')"
      icon="delete"
      type="danger"
      @click="showDeleteMultipleDialog"
    />
  </ButtonToolbar>

  <BaseDialogConfirmCancel
    v-model:is-visible="isNewFolderDialogVisible"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('New folder')"
    @confirm-clicked="createNewFolder"
    @cancel-clicked="hideNewFolderDialog"
  >
    <div class="p-float-label">
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{ 'p-invalid': submitted && !item.title }"
        autocomplete="off"
        autofocus
        name="name"
        required="true"
      />
      <label
        v-t="'Name'"
        for="name"
      />
    </div>
    <small
      v-if="submitted && !item.title"
      v-t="'Title is required'"
      class="p-error"
    />
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteItemDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteSingleItem"
    @cancel-clicked="isDeleteItemDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      <span v-if="item"
        >{{ t("Are you sure you want to delete") }} <b>{{ item.title }}</b
        >?</span
      >
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteMultipleDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteMultipleItems"
    @cancel-clicked="isDeleteMultipleDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      <span v-if="item">{{ t("Are you sure you want to delete the selected items?") }}</span>
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialog
    v-model:is-visible="isFileUsageDialogVisible"
    :title="t('Space available')"
  >
    <p>This feature is in development, this is a mockup with placeholder data!</p>
    <BaseChart :data="usageData" />
  </BaseDialog>

  <BaseDialog
    v-model:is-visible="isRecordAudioDialogVisible"
    :title="t('Record audio')"
    header-icon="record-add"
  >
    <DocumentAudioRecorder
      :parent-resource-node-id="route.params.node"
      @document-saved="recordedAudioSaved"
      @document-not-saved="recordedAudioNotSaved"
    />
  </BaseDialog>
</template>

<script setup>
import { useStore } from "vuex"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import { isEmpty } from "lodash"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { computed, onMounted, ref, watch } from "vue"
import { useCidReq } from "../../composables/cidReq"
import { useDatatableList } from "../../composables/datatableList"
import { useRelativeDatetime } from "../../composables/formatDate"
import axios from "axios"
import DocumentEntry from "../../components/documents/DocumentEntry.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import { useFileUtils } from "../../composables/fileUtils"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseChart from "../../components/basecomponents/BaseChart.vue"
import DocumentAudioRecorder from "../../components/documents/DocumentAudioRecorder.vue"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import { useCidReqStore } from "../../store/cidReq"

const store = useStore()
const cidReqStore = useCidReqStore()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()

const { t } = useI18n()
const { filters, options, onUpdateOptions, deleteItem } = useDatatableList("Documents")
const notification = useNotification()
const { cid, sid, gid } = useCidReq()
const { isImage } = useFileUtils()

cidReqStore.setCourseAndSessionById(cid, sid)

const item = ref({})
const usageData = ref({})

const isNewFolderDialogVisible = ref(false)
const isDeleteItemDialogVisible = ref(false)
const isDeleteMultipleDialogVisible = ref(false)
const isFileUsageDialogVisible = ref(false)
const isRecordAudioDialogVisible = ref(false)

const submitted = ref(false)

filters.value.loadNode = 1

const selectedItems = ref([])

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])

const items = computed(() => store.getters["documents/getRecents"])
const isLoading = computed(() => store.getters["documents/isLoading"])

const totalItems = computed(() => store.getters["documents/getTotalItems"])

const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

const hasImageInDocumentEntries = computed(() => {
  return items.value.find((i) => isImage(i)) !== undefined
})

onMounted(() => {
  filters.value.loadNode = 1

  // Set resource node.
  let nodeId = route.params.node

  if (isEmpty(nodeId)) {
    nodeId = route.query.node
  }

  store.dispatch("resourcenode/findResourceNode", { id: `/api/resource_nodes/${nodeId}` })

  onUpdateOptions(options.value)
})

watch(
  () => route.params,
  () => {
    const nodeId = route.params.node

    const finderParams = { id: `/api/resource_nodes/${nodeId}`, cid, sid, gid }

    store.dispatch("resourcenode/findResourceNode", finderParams)

    if ("DocumentsList" === route.name) {
      onUpdateOptions(options.value)
    }
  },
)

const showBackButtonIfNotRootFolder = computed(() => {
  if (!resourceNode.value) {
    return false
  }
  return resourceNode.value.resourceType.name !== "courses"
})

function back() {
  if (!resourceNode.value) {
    return
  }
  let parent = resourceNode.value.parent
  if (parent) {
    let queryParams = { cid, sid, gid }
    router.push({ name: "DocumentsList", params: { node: parent.id }, query: queryParams })
  }
}

function openNew() {
  item.value = {}
  submitted.value = false
  isNewFolderDialogVisible.value = true
}

function hideNewFolderDialog() {
  isNewFolderDialogVisible.value = false
  submitted.value = false
}

function createNewFolder() {
  submitted.value = true

  if (item.value.title?.trim()) {
    if (!item.value.id) {
      item.value.filetype = "folder"
      item.value.parentResourceNodeId = route.params.node
      item.value.resourceLinkList = JSON.stringify([
        {
          gid,
          sid,
          cid,
          visibility: RESOURCE_LINK_PUBLISHED, // visible by default
        },
      ])

      store.dispatch("documents/createWithFormData", item.value).then(() => {
        notification.showSuccessNotification(t("Saved"))
        onUpdateOptions(options.value)
      })
    }
    isNewFolderDialogVisible.value = false
    item.value = {}
  }
}

function selectAll() {
  selectedItems.value = items.value
}

function showDeleteMultipleDialog() {
  isDeleteMultipleDialogVisible.value = true
}

function confirmDeleteItem(itemToDelete) {
  item.value = itemToDelete
  isDeleteItemDialogVisible.value = true
}

async function deleteMultipleItems() {
  await store.dispatch("documents/delMultiple", selectedItems.value)
  isDeleteMultipleDialogVisible.value = false
  notification.showSuccessNotification(t("Deleted"))
  unselectAll()
  onUpdateOptions(options.value)
}

function unselectAll() {
  selectedItems.value = []
}

function deleteSingleItem() {
  deleteItem(item)

  item.value = {}

  isDeleteItemDialogVisible.value = false
}

function onPage(event) {
  options.value = {
    itemsPerPage: event.rows,
    page: event.page + 1,
    sortBy: event.sortField,
    sortDesc: event.sortOrder === -1,
  }

  onUpdateOptions(options.value)
}

function sortingChanged(event) {
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  onUpdateOptions(options.value)
}

function goToNewDocument() {
  router.push({
    name: "DocumentsCreateFile",
    query: route.query,
  })
}

function goToUploadFile() {
  router.push({
    name: "DocumentsUploadFile",
    query: route.query,
  })
}

function btnShowInformationOnClick(item) {
  const folderParams = route.query

  if (item) {
    folderParams.id = item["@id"]
  }

  router.push({
    name: "DocumentsShow",
    params: folderParams,
    query: folderParams,
  })
}

function btnChangeVisibilityOnClick(item) {
  const folderParams = route.query

  folderParams.id = item["@id"]

  axios.put(item["@id"] + "/toggle_visibility").then((response) => {
    item.resourceLinkListFromEntity = response.data.resourceLinkListFromEntity
  })
}

function btnEditOnClick(item) {
  const folderParams = route.query

  folderParams.id = item["@id"]

  if ("folder" === item.filetype || isEmpty(item.filetype)) {
    router.push({
      name: "DocumentsUpdate",
      params: { id: item["@id"] },
      query: folderParams,
    })

    return
  }

  if ("file" === item.filetype) {
    folderParams.getFile = true

    if (
      item.resourceNode.resourceFile &&
      item.resourceNode.resourceFile.mimeType &&
      "text/html" === item.resourceNode.resourceFile.mimeType
    ) {
      //folderParams.getFile = true;
    }

    router.push({
      name: "DocumentsUpdateFile",
      params: { id: item["@id"] },
      query: folderParams,
    })
  }
}

function showSlideShowWithFirstImage() {
  let item = items.value.find((i) => isImage(i))
  if (item === undefined) {
    return
  }
  // Right now Vue prime datatable does not offer a method to click on a row in a table
  // https://primevue.org/datatable/#api.datatable.methods
  // so we click on the dom element that has the href on the item
  document.querySelector(`a[href='${item.contentUrl}']`).click()
  // start slideshow trusting the button to play is present
  document.querySelector('button[class="fancybox-button fancybox-button--play"]').click()
}

function showUsageDialog() {
  // TODO retrieve usage data from server
  usageData.value = {
    datasets: [
      {
        data: [83, 14, 5],
        backgroundColor: [
          "rgba(255, 99, 132, 0.7)",
          "rgba(54, 162, 235, 0.7)",
          "rgba(255, 206, 86, 0.7)",
          "rgba(75, 192, 192, 0.7)",
          "rgba(153, 102, 255, 0.7)",
          "rgba(255, 159, 64, 0.7)",
        ],
      },
    ],
    labels: ["Course", "Teacher", "Available space"],
  }
  isFileUsageDialogVisible.value = true
}

function showRecordAudioDialog() {
  isRecordAudioDialogVisible.value = true
}

function recordedAudioSaved() {
  notification.showSuccessNotification(t("Saved"))
  isRecordAudioDialogVisible.value = false
  onUpdateOptions(options.value)
}

function recordedAudioNotSaved(error) {
  notification.showErrorNotification(t("Document not saved"))
  console.error(error)
}
</script>