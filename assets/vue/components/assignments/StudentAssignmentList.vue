<template>
  <BaseTable
    :is-loading="loading"
    :values="assignments"
    :total-items="assignments.length"
    data-key="id"
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
        {{ formatStored(slotProps.data.assignment?.expiresOn) }}
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
  </BaseTable>

  <CorrectAndRateModal
    v-model="showCorrectAndRateDialog"
    :item="correctingItem"
    @commentSent="loadAssignments"
    @update:modelValue="handleDialogVisibility"
  />
</template>

<script setup>
import Column from "primevue/column"
import { ref, onMounted, nextTick } from "vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useRoute, useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const router = useRouter()
const route = useRoute()
const { cid, sid, gid } = useCidReq()

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

function getNodeIdFromAssignment(item) {
  const rn = item?.resourceNode
  if (rn && typeof rn === "object" && "id" in rn) {
    return Number(rn.id)
  }

  if (typeof rn === "string") {
    const m = rn.match(/(\d+)$/)
    if (m) return Number(m[1])
  }

  if (item?.resourceNodeId) {
    return Number(item.resourceNodeId)
  }

  if (route.params.node) {
    return Number(route.params.node)
  }

  return 0
}

function goToAssignmentDetail(assignment) {
  if (!assignment?.id) return
  const nodeId = getNodeIdFromAssignment(assignment)

  router.push({
    name: "AssignmentDetail",
    params: { id: assignment.id, node: nodeId },
    query: { cid, ...(sid && { sid }), ...(gid && { gid }) },
  })
}

function formatStored(val) {
  if (!val) return "—"
  const s = String(val)
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
  if (m) return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}`
  return abbreviatedDatetime(s)
}
</script>
