<template>
  <div>
    <DataTable
      v-model:rows="loadParams.itemsPerPage"
      :total-records="totalRecords"
      :value="submissions"
      :loading="loading"
      lazy
      paginator
      data-key="@id"
      striped-rows
      :rows-per-page-options="[10, 20, 50]"
      :multi-sort-meta="sortFields"
      current-page-report-template="Showing {first} to {last} of {totalRecords}"
      paginator-template="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        field="user.fullname"
        :header="t('Full name')"
      />

      <Column
        field="title"
        :header="t('Title')"
      />

      <Column :header="t('Feedback')">
        <template #body="{ data }">
          <div class="flex justify-center items-center gap-2">
            <span
              v-if="data.correctionTitle"
              class="text-green-600"
            >
              <a
                v-if="data.correctionDownloadUrl"
                :href="data.correctionDownloadUrl"
                target="_blank"
                download
                class="text-green-50 hover:underline"
              >
                <i class="pi pi-check-circle"></i>
              </a>
              <i
                v-else
                class="pi pi-check-circle"
              ></i>
            </span>
            <span
              v-if="data.comments && data.comments.length > 0"
              class="flex items-center gap-1 text-gray-600 text-sm cursor-pointer hover:underline"
              @click="openCommentDialog(data)"
            >
              <i class="pi pi-comment"></i> {{ data.comments.length }}
            </span>
          </div>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          <template v-if="data.qualification !== null && data.publicationParent?.qualification">
            <span
              :class="{
                'bg-success/10 text-success font-semibold text-sm px-2 py-1 rounded':
                  data.qualification > data.publicationParent.qualification / 2,
                'bg-danger/10 text-danger font-semibold text-sm px-2 py-1 rounded':
                  data.qualification <= data.publicationParent.qualification / 2,
              }"
            >
              {{ data.qualification.toFixed(1) }} / {{ data.publicationParent.qualification.toFixed(1) }}
            </span>
          </template>
          <template v-else>
            <span class="text-gray-50">
              {{ t("Not graded yet") }}
            </span>
          </template>
        </template>
      </Column>

      <Column
        field="sentDate"
        :header="t('Date')"
      >
        <template #body="{ data }">
          {{ abbreviatedDatetime(data.sentDate) }}
        </template>
      </Column>

      <Column :header="t('Upload correction')">
        <template #body="{ data }">
          <div class="flex flex-col items-center gap-1">
            <BaseButton
              icon="file-upload"
              size="normal"
              only-icon
              :label="t('Upload correction')"
              type="success"
              @click="openUploader(data)"
            />
            <div
              v-if="data.correctionTitle"
              class="text-xs text-green-600 mt-1 truncate max-w-[150px]"
              :title="data.correctionTitle"
            >
              {{ data.correctionTitle }}
            </div>
          </div>
        </template>
      </Column>

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex justify-center gap-2">
            <BaseButton
              icon="save"
              size="normal"
              only-icon
              :label="t('Download')"
              @click="saveCorrection(data)"
              type="primary"
            />
            <BaseButton
              icon="reply-all"
              size="normal"
              only-icon
              :label="t('Correct & Rate')"
              @click="correctAndRate(data)"
              type="success"
            />
            <BaseButton
              icon="edit"
              size="normal"
              only-icon
              :label="t('Edit')"
              @click="editSubmission(data)"
              type=""
            />
            <BaseButton
              icon="folder-move"
              size="normal"
              only-icon
              :label="t('Move')"
              @click="moveSubmission(data)"
              type="info"
            />
            <BaseButton
              :icon="
                RESOURCE_LINK_PUBLISHED === data.firstResourceLink?.visibility
                  ? 'eye-on'
                  : RESOURCE_LINK_DRAFT === data.firstResourceLink?.visibility
                    ? 'eye-off'
                    : ''
              "
              :label="t('Visibility')"
              only-icon
              size="normal"
              type="black"
              @click="viewSubmission(data)"
            />
            <BaseButton
              icon="delete"
              size="normal"
              only-icon
              :label="t('Delete')"
              @click="deleteSubmission(data)"
              type="danger"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <UppyModalUploader
      :parentResourceNodeId="getResourceNodeId(selectedSubmission?.resourceNode)"
      :submissionId="selectedSubmission?.iid || 0"
      :visible="showUploader"
      @close="closeUploader"
      @uploaded="onCorrectionUploaded"
    />
  </div>

  <EditStudentSubmissionForm
    v-model="showEditDialog"
    :item="editingItem"
    @updated="loadData"
  />

  <CorrectAndRateModal
    v-model="showCorrectAndRateDialog"
    :item="correctingItem"
    @commentSent="loadData"
  />

  <MoveSubmissionModal
    v-model="showMoveDialog"
    :submission="selectedSubmission"
    :currentAssignmentId="props.assignmentId"
    @moved="loadData"
  />
</template>

<script setup>
import { nextTick, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"
import UppyModalUploader from "./UppyModalUploader.vue"
import resourceLinkService from "../../services/resourcelink"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import EditStudentSubmissionForm from "./EditStudentSubmissionForm.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"
import MoveSubmissionModal from "./MoveSubmissionModal.vue"

const props = defineProps({
  assignmentId: {
    type: Number,
    required: true,
  },
})

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const notification = useNotification()

const loading = ref(false)
const submissions = ref([])
const totalRecords = ref(0)
const sortFields = ref([{ field: "sentDate", order: -1 }])
const loadParams = reactive({
  page: 1,
  itemsPerPage: 10,
})

const showUploader = ref(false)
const selectedSubmission = ref(null)
const showEditDialog = ref(false)
const editingItem = ref(null)
const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)
const showMoveDialog = ref(false)

watch(loadParams, loadData)
onMounted(loadData)

async function loadData() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.getAssignmentDetailForTeacher({
      assignmentId: props.assignmentId,
      page: loadParams.page,
      itemsPerPage: loadParams.itemsPerPage,
      order: { sentDate: "desc" },
    })

    submissions.value = response["hydra:member"]
    totalRecords.value = response["hydra:totalItems"]
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  loadParams.page = event.page + 1
}

function onSort(event) {
  Object.keys(loadParams)
    .filter((key) => key.startsWith("order["))
    .forEach((key) => delete loadParams[key])

  event.multiSortMeta.forEach((sortItem) => {
    loadParams[`order[${sortItem.field}]`] = sortItem.order === 1 ? "asc" : "desc"
  })
}

async function onCorrectionUploaded(file, response) {
  if (!file || !selectedSubmission.value) {
    showUploader.value = false
    selectedSubmission.value = null
    return
  }

  notification.showSuccessNotification(t("Correction uploaded successfully!"))

  const uploadedFileName = file?.name || "Correction uploaded"

  const foundIndex = submissions.value.findIndex((item) => item.iid === selectedSubmission.value.iid)

  if (foundIndex !== -1) {
    submissions.value[foundIndex].correctionTitle = uploadedFileName
  }

  showUploader.value = false
  selectedSubmission.value = null
}

function getResourceNodeId(resourceNode) {
  if (!resourceNode) return 0

  if (typeof resourceNode === "object" && "id" in resourceNode) {
    return parseInt(resourceNode.id, 10)
  }

  const idString = typeof resourceNode === "string" ? resourceNode : resourceNode["@id"]
  if (!idString || typeof idString !== "string") return 0

  const match = idString.match(/\/(\d+)$/)
  return match ? parseInt(match[1], 10) : 0
}

function openUploader(submission) {
  selectedSubmission.value = submission
  showUploader.value = true
}

function closeUploader() {
  showUploader.value = false
}

async function viewSubmission(item) {
  if (!item || !item.firstResourceLink) {
    notification.showErrorNotification(t("Invalid submission"))
    return
  }

  const resourceLink = item.firstResourceLink

  if (!resourceLink["@id"]) {
    if (item.resourceLinkListFromEntity && item.resourceLinkListFromEntity.length > 0) {
      const firstLink = item.resourceLinkListFromEntity[0]
      if (firstLink.id) {
        resourceLink["@id"] = `/api/resource_links/${firstLink.id}`
      } else {
        notification.showErrorNotification(t("Invalid resource link ID"))
        return
      }
    } else {
      notification.showErrorNotification(t("No resource link entity found"))
      return
    }
  }

  if (RESOURCE_LINK_PUBLISHED === resourceLink.visibility) {
    resourceLink.visibility = RESOURCE_LINK_DRAFT
  } else if (RESOURCE_LINK_DRAFT === resourceLink.visibility) {
    resourceLink.visibility = RESOURCE_LINK_PUBLISHED
  } else {
    notification.showErrorNotification(t("Cannot change visibility"))
    return
  }

  try {
    await resourceLinkService.update(resourceLink)
    notification.showSuccessNotification(t("Visibility updated successfully!"))
    await loadData()
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

function saveCorrection(item) {
  if (item?.downloadUrl) {
    const link = document.createElement("a")
    link.href = item.downloadUrl
    link.download = ""
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else {
    notification.showErrorNotification(t("No download available"))
  }
}

function editSubmission(item) {
  editingItem.value = item
  showEditDialog.value = true
}

async function deleteSubmission(item) {
  const confirmed = window.confirm(t("Are you sure you want to delete this submission?"))

  if (!confirmed) {
    return
  }

  try {
    await cStudentPublicationService.deleteAssignmentSubmission(item.iid)
    notification.showSuccessNotification(t("Submission deleted successfully!"))
    await loadData()
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

function correctAndRate(item) {
  correctingItem.value = null
  nextTick(() => {
    correctingItem.value = item
    showCorrectAndRateDialog.value = true
  })
}

function openCommentDialog(item) {
  correctingItem.value = null
  nextTick(() => {
    correctingItem.value = item
    showCorrectAndRateDialog.value = true
  })
}

function moveSubmission(item) {
  selectedSubmission.value = item
  showMoveDialog.value = true
}
</script>
