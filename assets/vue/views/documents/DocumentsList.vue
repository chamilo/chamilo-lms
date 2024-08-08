<template>
  <SectionHeader
    v-if="securityStore.isAuthenticated"
    :title="t('Documents')"
  >
    <BaseButton
      v-if="showNewCertificateButton"
      :label="t('Create certificate')"
      icon="file-add"
      only-icon
      type="black"
      @click="goToNewDocument"
    />
    <BaseButton
      v-if="showUploadCertificateButton"
      :label="t('Upload')"
      icon="file-upload"
      only-icon
      type="black"
      @click="goToUploadFile"
    />

    <BaseButton
      v-if="showBackButtonIfNotRootFolder"
      :label="t('Back')"
      icon="back"
      only-icon
      type="gray"
      @click="back"
    />
    <BaseButton
      v-if="showNewDocumentButton"
      :label="t('New document')"
      icon="file-add"
      only-icon
      type="success"
      @click="goToNewDocument"
    />
    <BaseButton
      v-if="showUploadButton"
      :label="t('Upload')"
      icon="file-upload"
      only-icon
      type="success"
      @click="goToUploadFile"
    />
    <BaseButton
      v-if="showNewFolderButton"
      :label="t('New folder')"
      icon="folder-plus"
      only-icon
      type="success"
      @click="openNew"
    />
    <BaseButton
      v-if="showNewDrawingButton"
      :label="t('New drawing')"
      icon="drawing"
      only-icon
      type="success"
    />
    <BaseButton
      v-if="showRecordAudioButton"
      :label="t('Record audio')"
      icon="record-add"
      only-icon
      type="success"
      @click="showRecordAudioDialog"
    />
    <BaseButton
      v-if="showNewCloudFileButton"
      :label="t('New cloud file')"
      icon="file-cloud-add"
      only-icon
      type="success"
    />
    <BaseButton
      v-if="showSlideshowButton"
      :disabled="!hasImageInDocumentEntries"
      :label="t('Slideshow')"
      icon="view-gallery"
      only-icon
      type="black"
      @click="showSlideShowWithFirstImage"
    />
    <BaseButton
      v-if="showUsageButton"
      :label="t('Usage')"
      icon="usage"
      only-icon
      type="black"
      @click="showUsageDialog"
    />
    <BaseButton
      v-if="showDownloadAllButton"
      :label="t('Download all')"
      icon="download"
      only-icon
      type="primary"
    />
  </SectionHeader>

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
        <div style="display: flex; align-items: center;">
          <DocumentEntry
            v-if="slotProps.data"
            :data="slotProps.data"
          />
          <BaseIcon
            v-if="isAllowedToEdit && isSessionDocument(slotProps.data)"
            icon="session-star"
            class="mr-8"
          />
        </div>
      </template>
    </Column>

    <Column
      :header="t('Size')"
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
      :header="t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row justify-end gap-2">
          <BaseButton
            icon="information"
            size="small"
            type="primary"
            @click="btnShowInformationOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data)"
            :icon="
              RESOURCE_LINK_PUBLISHED === slotProps.data.resourceLinkListFromEntity[0].visibility
                ? 'eye-on'
                : RESOURCE_LINK_DRAFT === slotProps.data.resourceLinkListFromEntity[0].visibility
                  ? 'eye-off'
                  : ''
            "
            size="small"
            type="secondary"
            @click="btnChangeVisibilityOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data)"
            icon="edit"
            size="small"
            type="secondary"
            @click="btnEditOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data)"
            icon="delete"
            size="small"
            type="danger"
            @click="confirmDeleteItem(slotProps.data)"
          />
          <BaseButton
            v-if="isCertificateMode && canEdit(slotProps.data)"
            :class="{ selected: slotProps.data.iid === defaultCertificateId }"
            :icon="slotProps.data.iid === defaultCertificateId ? 'certificate-selected' : 'certificate-not-selected'"
            size="small"
            type="slotProps.data.iid === defaultCertificateId ? 'success' : 'black'"
            @click="selectAsDefaultCertificate(slotProps.data)"
          />
          <BaseButton
            v-if="securityStore.isAuthenticated && isCurrentTeacher && isHtmlFile(slotProps.data) && canEdit(slotProps.data)"
            :icon="getTemplateIcon(slotProps.data.iid)"
            size="small"
            type="secondary"
            @click="openTemplateForm(slotProps.data.iid)"
          />
        </div>
      </template>
    </Column>
  </DataTable>

  <BaseToolbar
    v-if="securityStore.isAuthenticated && isCurrentTeacher"
    show-top-border
  >
    <BaseButton
      :label="t('Select all')"
      icon="select-all"
      type="primary"
      @click="selectAll"
    />
    <BaseButton
      :label="t('Unselect all')"
      icon="unselect-all"
      type="primary"
      @click="unselectAll"
    />
    <BaseButton
      :disabled="!selectedItems || !selectedItems.length"
      :label="t('Delete selected')"
      icon="delete"
      type="danger"
      @click="showDeleteMultipleDialog"
    />
  </BaseToolbar>

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
        name="title"
        required="true"
      />
      <label
        v-t="'Name'"
        for="title"
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
    :style="{ width: '28rem' }"
    :title="t('Space available')"
  >
    <p>This feature is in development, this is a mockup with placeholder data!</p>
    <BaseChart :data="usageData" />
  </BaseDialog>

  <BaseDialog
    v-model:is-visible="isRecordAudioDialogVisible"
    :style="{ width: '28rem' }"
    :title="t('Record audio')"
    header-icon="record-add"
  >
    <DocumentAudioRecorder
      :parent-resource-node-id="route.params.node"
      @document-saved="recordedAudioSaved"
      @document-not-saved="recordedAudioNotSaved"
    />
  </BaseDialog>
  <BaseDialogConfirmCancel
    v-model:is-visible="showTemplateFormModal"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('Add as a template')"
    @confirm-clicked="submitTemplateForm"
    @cancel-clicked="showTemplateFormModal = false"
  >
    <form @submit.prevent="submitTemplateForm">
      <div class="p-float-label">
        <InputText
          id="templateTitle"
          v-model.trim="templateFormData.title"
          class="form-control"
          required
        />
        <label
          v-t="'Name'"
          for="templateTitle"
        />
      </div>
      <small
        v-if="submitted && !templateFormData.title"
        v-t="'Title is required'"
        class="p-error"
      />
      <BaseFileUpload
        id="post-file"
        :label="t('File upload')"
        accept="image"
        model-value=""
        size="small"
        @file-selected="selectedFile = $event"
      />
    </form>
  </BaseDialogConfirmCancel>
</template>

<script setup>
import { useStore } from "vuex"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { isEmpty } from "lodash"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { computed, onMounted, ref, watch } from "vue"
import { useCidReq } from "../../composables/cidReq"
import { useDatatableList } from "../../composables/datatableList"
import { useFormatDate } from "../../composables/formatDate"
import axios from "axios"
import DocumentEntry from "../../components/documents/DocumentEntry.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import { useFileUtils } from "../../composables/fileUtils"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseChart from "../../components/basecomponents/BaseChart.vue"
import DocumentAudioRecorder from "../../components/documents/DocumentAudioRecorder.vue"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import prettyBytes from "pretty-bytes"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import { useDocumentActionButtons } from "../../composables/document/documentActionButtons"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"

const store = useStore()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()

const { t } = useI18n()
const { filters, options, onUpdateOptions, deleteItem } = useDatatableList("Documents")
const notification = useNotification()
const { cid, sid, gid } = useCidReq()
const { isImage, isHtml } = useFileUtils()

const { relativeDatetime } = useFormatDate()
const isAllowedToEdit = ref(false)

const {
  showNewDocumentButton,
  showUploadButton,
  showNewFolderButton,
  showNewDrawingButton,
  showRecordAudioButton,
  showNewCloudFileButton,
  showSlideshowButton,
  showUsageButton,
  showDownloadAllButton,
  showNewCertificateButton,
  showUploadCertificateButton,
} = useDocumentActionButtons()

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

const items = computed(() => store.getters["documents/getRecents"])
const isLoading = computed(() => store.getters["documents/isLoading"])

const totalItems = computed(() => store.getters["documents/getTotalItems"])

const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

const hasImageInDocumentEntries = computed(() => {
  return items.value.find((i) => isImage(i)) !== undefined
})

const isCertificateMode = computed(() => {
  return route.query.filetype === "certificate"
})

const defaultCertificateId = ref(null)

const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)

const canEdit = (item) => {
  const resourceLink = item.resourceLinkListFromEntity[0]
  const isSessionDocument = resourceLink.session && resourceLink.session['@id'] === `/api/sessions/${sid}`
  const isBaseCourse = !resourceLink.session
  return (
    (isSessionDocument && isAllowedToEdit.value) ||
    (isBaseCourse && !sid && isCurrentTeacher.value)
  )
}

const isSessionDocument = (item) => {
  const resourceLink = item.resourceLinkListFromEntity[0]
  return resourceLink.session && resourceLink.session['@id'] === `/api/sessions/${sid}`
}

const isHtmlFile = (fileData) => isHtml(fileData)

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
  filters.value.loadNode = 1

  // Set resource node.
  let nodeId = route.params.node

  if (isEmpty(nodeId)) {
    nodeId = route.query.node
  }

  await store.dispatch("resourcenode/findResourceNode", { id: `/api/resource_nodes/${nodeId}` })

  await loadDefaultCertificate()
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
  return resourceNode.value.resourceType.title !== "courses"
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

  if ("file" === item.filetype || "certificate" === item.filetype) {
    folderParams.getFile = true

    if (
      item.resourceNode.firstResourceFile &&
      item.resourceNode.firstResourceFile.mimeType &&
      "text/html" === item.resourceNode.firstResourceFile.mimeType
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

async function selectAsDefaultCertificate(certificate) {
  try {
    const response = await axios.patch(`/gradebook/set_default_certificate/${cid}/${certificate.iid}`)
    if (response.status === 200) {
      loadDefaultCertificate()
      onUpdateOptions(options.value)
      notification.showSuccessNotification(t("Certificate set as default successfully"))
    }
  } catch (error) {
    notification.showErrorNotification(t("Error setting certificate as default"))
  }
}

async function loadDefaultCertificate() {
  try {
    const response = await axios.get(`/gradebook/default_certificate/${cid}`)
    defaultCertificateId.value = response.data.certificateId
  } catch (error) {
    if (error.response && error.response.status === 404) {
      console.error("Default certificate not found.")
      defaultCertificateId.value = null
    } else {
      console.error("Error loading the certificate", error)
    }
  }
}

const showTemplateFormModal = ref(false)
const selectedFile = ref(null)
const templateFormData = ref({
  title: "",
  thumbnail: null,
})

const currentDocumentId = ref(null)

const isDocumentTemplate = async (documentId) => {
  try {
    const response = await axios.get(`/template/document-templates/${documentId}/is-template`)
    return response.data.isTemplate
  } catch (error) {
    console.error("Error verifying the template status:", error)
    return false
  }
}

const deleteDocumentTemplate = async (documentId) => {
  try {
    await axios.post(`/template/document-templates/${documentId}/delete`)
    onUpdateOptions(options.value)
    notification.showSuccessNotification(t("Template successfully deteled."))
  } catch (error) {
    console.error("Error deleting the template:", error)
    notification.showErrorNotification(t("Error deleting the template."))
  }
}

const getTemplateIcon = (documentId) => {
  const document = items.value.find((doc) => doc.iid === documentId)
  return document && document.template ? "template-selected" : "template-not-selected"
}

const openTemplateForm = async (documentId) => {
  const isTemplate = await isDocumentTemplate(documentId)

  if (isTemplate) {
    await deleteDocumentTemplate(documentId)
    onUpdateOptions(options.value)
  } else {
    currentDocumentId.value = documentId
    showTemplateFormModal.value = true
  }
}

const submitTemplateForm = async () => {
  submitted.value = true

  if (!templateFormData.value.title || !selectedFile.value) {
    notification.showErrorNotification(t("The title and thumbnail are required."))
    return
  }

  try {
    const formData = new FormData()
    formData.append("title", templateFormData.value.title)
    formData.append("thumbnail", selectedFile.value)
    formData.append("refDoc", currentDocumentId.value)
    formData.append("cid", cid)

    const response = await axios.post("/template/document-templates/create", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })

    if (response.status === 200 || response.status === 201) {
      notification.showSuccessNotification(t("Template created successfully."))
      templateFormData.value.title = ""
      selectedFile.value = null
      showTemplateFormModal.value = false
      onUpdateOptions(options.value)
    } else {
      notification.showErrorNotification(t("Error creating the template."))
    }
  } catch (error) {
    console.error("Error submitting the form:", error)
    notification.showErrorNotification(t("Error submitting the form."))
  }
}
</script>
