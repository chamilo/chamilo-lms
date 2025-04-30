<template>
  <DataTable
    :value="assignments"
    :loading="loading"
    :paginator="true"
    :rows="10"
    data-key="id"
    striped-rows
  >
    <Column :header="t('Type')">
      <template #body>
        <BaseIcon
          icon="file-text"
          size="small"
        />
      </template>
    </Column>

    <Column :header="t('Title')">
      <template #body="slotProps">
        <a
          class="text-blue-600 hover:underline cursor-pointer flex items-center gap-1"
          @click="goToAssignmentDetail(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </a>
      </template>
    </Column>

    <Column :header="t('Deadline')">
      <template #body="slotProps">
        {{ abbreviatedDatetime(slotProps.data.assignment?.expiresOn) || "-" }}
      </template>
    </Column>

    <Column :header="t('Feedback')">
      <template #body="slotProps">
        <div
          v-if="slotProps.data.feedbackCount > 0"
          class="flex items-center gap-2"
        >
          <span class="bg-primary text-white text-tiny font-semibold px-2 py-0.5 rounded">
            {{ slotProps.data.feedbackCount }} {{ t("Feedback") }}
          </span>
          <BaseIcon
            icon="comment"
            size="small"
            class="cursor-pointer hover:text-primary"
            @click="openCommentDialog(slotProps.data)"
          />
        </div>
        <span v-else>-</span>
      </template>
    </Column>

    <Column :header="t('Last upload')">
      <template #body="slotProps">
        {{ abbreviatedDatetime(slotProps.data.sentDate) || "-" }}
      </template>
    </Column>
  </DataTable>

  <CorrectAndRateModal
    v-model="showCorrectAndRateDialog"
    :item="correctingItem"
    @commentSent="loadAssignments"
    @update:modelValue="handleDialogVisibility"
  />
</template>

<script setup>
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import { ref, onMounted, nextTick } from "vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const router = useRouter()
const { cid, sid } = useCidReq()

const assignments = ref([])
const loading = ref(false)

const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)

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

onMounted(async () => {
  loading.value = true
  try {
    const response = await cStudentPublicationService.findStudentAssignments()
    assignments.value = response["hydra:member"].map((item) => ({
      ...item,
      id: item.iid,
    }))
  } catch (e) {
    console.error("Error loading student assignments", e)
  } finally {
    loading.value = false
  }
})

function goToAssignmentDetail(assignment) {
  if (!assignment?.id) return
  router.push({
    name: "AssignmentDetail",
    params: { id: assignment.id },
    query: { cid, sid },
  })
}
</script>
