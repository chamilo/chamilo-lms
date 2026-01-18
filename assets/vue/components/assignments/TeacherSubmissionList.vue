<template>
  <div>
    <BaseTable
      :is-loading="loading"
      v-model:multi-sort-meta="sortFields"
      v-model:rows="loadParams.itemsPerPage"
      :total-items="totalRecords"
      :values="submissions"
      data-key="@id"
      lazy
      @page="onPage"
      @sort="onSort"
    >
      <Column
        field="user.fullName"
        :header="t('Full name')"
      >
        <template #body="{ data }">
          <span class="text-gray-900">
            {{ getUserDisplayName(data?.user) }}
          </span>
        </template>
      </Column>

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
              :class="actionBtnClass"
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
              icon="download"
              size="normal"
              only-icon
              :label="t('Download')"
              :class="actionBtnClass"
              @click="saveCorrection(data)"
              type="primary"
            />
            <BaseButton
              v-if="canUseAiTaskGrader"
              icon="robot"
              size="normal"
              only-icon
              :label="t('AI grade')"
              :class="actionBtnClass"
              @click="openCorrectAndRate(data)"
              type="black"
            />
            <BaseButton
              icon="reply-all"
              size="normal"
              only-icon
              :label="t('Correct and rate')"
              :class="actionBtnClass"
              @click="openCorrectAndRate(data)"
              type="success"
            />
            <BaseButton
              icon="edit"
              size="normal"
              only-icon
              :label="t('Edit')"
              :class="actionBtnClass"
              @click="editSubmission(data)"
              type=""
            />
            <BaseButton
              icon="folder-move"
              size="normal"
              only-icon
              :label="t('Move')"
              :class="actionBtnClass"
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
              :class="actionBtnClass"
              @click="viewSubmission(data)"
            />
            <BaseButton
              icon="delete"
              size="normal"
              only-icon
              :label="t('Delete')"
              :class="actionBtnClass"
              @click="deleteSubmission(data)"
              type="danger"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

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
import { nextTick, watch, reactive, ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"
import UppyModalUploader from "./UppyModalUploader.vue"
import resourceLinkService from "../../services/resourcelink"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import EditStudentSubmissionForm from "./EditStudentSubmissionForm.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"
import MoveSubmissionModal from "./MoveSubmissionModal.vue"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useSecurityStore } from "../../store/securityStore"

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
  itemsPerPage: null,
})

const showUploader = ref(false)
const selectedSubmission = ref(null)
const showEditDialog = ref(false)
const editingItem = ref(null)
const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)
const showMoveDialog = ref(false)
const actionBtnClass = "w-10 h-10 !p-2"
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const platform = usePlatformConfig()
const courseSettingsStore = useCourseSettings()
const securityStore = useSecurityStore()

async function loadCourseSettingsIfPossible() {
  const courseId = course.value?.id
  const sessionId = session.value?.id

  if (!courseId) return

  try {
    await courseSettingsStore.loadCourseSettings(courseId, sessionId)
  } catch (err) {
    console.error("[Assignments] loadCourseSettings FAILED:", err)
  }
}

onMounted(async () => {
  await loadCourseSettingsIfPossible()
})

watch(
  () => [course.value?.id, session.value?.id],
  async () => {
    await loadCourseSettingsIfPossible()
  },
)

const aiHelpersEnabled = computed(() => {
  const v = String(platform.getSetting("ai_helpers.enable_ai_helpers"))
  return v === "true"
})

const taskGraderEnabled = computed(() => {
  const v = courseSettingsStore?.getSetting?.("task_grader")
  return String(v) === "true"
})

const canUseAiTaskGrader = computed(() => {
  // Only teachers/admins and not in student view
  const canEdit = !!(securityStore.isTeacher || securityStore.isCourseAdmin || securityStore.isAdmin)
  const notStudentView = !platform.isStudentViewActive
  return !!(canEdit && notStudentView && aiHelpersEnabled.value && taskGraderEnabled.value)
})

watch(
  loadParams,
  () => {
    if (!loadParams.itemsPerPage) return
    loadData()
  },
  { deep: true, immediate: true },
)

function buildOrderFromSort() {
  // PrimeVue multi sort meta => API expects { field: "asc|desc" }
  const order = {}
  const meta = Array.isArray(sortFields.value) ? sortFields.value : []
  meta.forEach((s) => {
    if (!s?.field) return
    order[s.field] = s.order === 1 ? "asc" : "desc"
  })
  if (!Object.keys(order).length) {
    order.sentDate = "desc"
  }
  return order
}

async function loadData() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.getAssignmentDetailForTeacher({
      assignmentId: props.assignmentId,
      page: loadParams.page,
      itemsPerPage: loadParams.itemsPerPage,
      order: buildOrderFromSort(),
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
  loadParams.itemsPerPage = event.rows
}

function onSort(event) {
  if (event?.multiSortMeta) {
    sortFields.value = event.multiSortMeta
  }
}

/**
 * Robust full name resolver:
 * API usually returns user.fullName, but we keep fallbacks to avoid blank display.
 */
function getUserDisplayName(user) {
  if (!user) return "—"
  if (typeof user === "string") return user

  if (user.fullName) return user.fullName
  if (user.fullname) return user.fullname

  const first = user.firstname || user.firstName || ""
  const last = user.lastname || user.lastName || ""
  const combined = `${first} ${last}`.trim()
  if (combined) return combined

  if (user.username) return user.username

  return "—"
}

async function onCorrectionUploaded(file) {
  if (!file || !selectedSubmission.value) {
    showUploader.value = false
    selectedSubmission.value = null
    return
  }

  notification.showSuccessNotification(t("Correction uploaded successfully!"))

  const uploadedFileName = file?.name || "Correction uploaded"
  const idx = submissions.value.findIndex((it) => it.iid === selectedSubmission.value.iid)
  if (idx !== -1) {
    submissions.value[idx].correctionTitle = uploadedFileName
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
  const m = idString.match(/\/(\d+)$/)
  return m ? parseInt(m[1], 10) : 0
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
    if (item.resourceLinkListFromEntity?.length) {
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
    notification.showErrorNotification(t("Can not change visibility"))
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
  if (!confirmed) return

  try {
    await cStudentPublicationService.deleteAssignmentSubmission(item.iid)
    notification.showSuccessNotification(t("Submission deleted successfully!"))
    await loadData()
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

function openCorrectAndRate(item) {
  correctingItem.value = null
  nextTick(() => {
    correctingItem.value = item
    showCorrectAndRateDialog.value = true
  })
}

function openCommentDialog(item) {
  openCorrectAndRate(item)
}

function moveSubmission(item) {
  selectedSubmission.value = item
  showMoveDialog.value = true
}
</script>
