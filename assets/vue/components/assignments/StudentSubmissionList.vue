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
      <Column :header="t('Type')">
        <template #body="{}">
          <div class="flex justify-center">
            <i class="pi pi-file" />
          </div>
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
                class="hover:underline"
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

            <span
              v-else
              class="text-gray-400"
            >
              —
            </span>
          </div>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          <template v-if="hasReceivedGrade(data) && data.publicationParent?.qualification">
            <span
              :class="{
                'bg-success/10 text-success font-semibold text-sm px-2 py-1 rounded':
                  Number(data.qualification) > Number(data.publicationParent.qualification) / 2,
                'bg-danger/10 text-danger font-semibold text-sm px-2 py-1 rounded':
                  Number(data.qualification) <= Number(data.publicationParent.qualification) / 2,
              }"
            >
              {{ Number(data.qualification).toFixed(1) }} /
              {{ Number(data.publicationParent.qualification).toFixed(1) }}
            </span>
          </template>
          <template v-else>
            <span class="text-gray-50">{{ t("Not graded yet") }}</span>
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

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex justify-center gap-2">
            <BaseButton
              v-if="flags.allowFile"
              icon="download"
              only-icon
              size="normal"
              :class="actionBtnClass"
              :label="t('Download')"
              @click="downloadSubmission(data)"
              type="primary"
            />
            <BaseButton
              v-if="flags.allowText"
              icon="reply-all"
              only-icon
              size="normal"
              :class="actionBtnClass"
              :label="t('Comment')"
              @click="correctAndRate(data)"
              type="success"
            />
            <BaseButton
              v-if="canDeleteSubmission(data)"
              icon="delete"
              only-icon
              size="normal"
              :class="actionBtnClass"
              :label="t('Delete')"
              @click="deleteSubmission(data)"
              type="danger"
            />
            <span
              v-if="!flags.allowFile && !flags.allowText"
              class="text-gray-400"
              >—</span
            >
          </div>
        </template>
      </Column>
    </BaseTable>

    <CorrectAndRateModal
      v-model="showCorrectAndRateDialog"
      :item="correctingItem"
      :flags="flags"
      @commentSent="loadData"
      @update:modelValue="handleDialogVisibility"
    />
  </div>
</template>

<script setup>
import { nextTick, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"

const props = defineProps({
  assignmentId: { type: Number, required: true },
  flags: {
    type: Object,
    default: () => ({ allowText: true, allowFile: true }),
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

const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)

/**
 * Bigger icon-only buttons for better readability and click area.
 */
const actionBtnClass = "w-10 h-10 !p-2"

/**
 * Some APIs send qualification = 0.0 by default even if not graded yet.
 * Prefer grading metadata (qualificator/qualification date) when available.
 */
function getQualificatorId(row) {
  const v = row?.qualificatorId ?? row?.qualificator_id ?? row?.qualifiedById ?? row?.qualified_by_id ?? 0

  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

function getQualificationDate(row) {
  return (
    row?.qualificationDate ??
    row?.qualification_date ??
    row?.qualifiedAt ??
    row?.qualified_at ??
    row?.dateOfQualification ??
    row?.date_of_qualification ??
    null
  )
}

function hasReceivedGrade(row) {
  // Best signals: who graded it and/or when it was graded
  if (getQualificatorId(row) > 0) return true
  if (getQualificationDate(row)) return true

  // Fallback: if API does not provide metadata, infer carefully
  const q = row?.qualification
  if (q === null || q === undefined || q === "") return false

  const qNum = Number(q)
  if (!Number.isFinite(qNum)) return false

  // Any non-zero score implies grading happened
  if (qNum !== 0) return true

  // Score is 0: only treat as graded if there is clear teacher feedback metadata
  return (row?.comments?.length ?? 0) > 0 || !!row?.correctionTitle
}

function canDeleteSubmission(row) {
  return !hasReceivedGrade(row)
}

watch(
  loadParams,
  () => {
    if (!loadParams.itemsPerPage) return
    loadData()
  },
  { deep: true, immediate: true },
)

async function loadData() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.getAssignmentDetail({
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

async function deleteSubmission(item) {
  if (!item?.iid) {
    notification.showErrorNotification(t("Invalid submission"))
    return
  }

  const confirmed = window.confirm(t("Are you sure you want to delete this submission?"))
  if (!confirmed) return

  try {
    await cStudentPublicationService.deleteAssignmentSubmission(item.iid)
    notification.showSuccessNotification(t("Submission deleted successfully!"))
    await loadData()
  } catch (e) {
    console.warn("[Assignments][StudentSubmissionList] Failed to delete submission", e)
    notification.showErrorNotification(e)
  }
}

function onPage(event) {
  loadParams.page = event.page + 1
  loadParams.itemsPerPage = event.rows
}

function onSort(event) {
  Object.keys(loadParams)
    .filter((k) => k.startsWith("order["))
    .forEach((k) => delete loadParams[k])

  event.multiSortMeta.forEach((s) => {
    loadParams[`order[${s.field}]`] = s.order === 1 ? "asc" : "desc"
  })
}

function downloadSubmission(item) {
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

function handleDialogVisibility(newVal) {
  showCorrectAndRateDialog.value = newVal
  if (!newVal) {
    correctingItem.value = null
  }
}
</script>
