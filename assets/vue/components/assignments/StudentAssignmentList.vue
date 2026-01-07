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

    <Column
      v-if="showMetaColumns"
      :header="t('Feedback')"
    >
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

    <Column
      v-if="showMetaColumns"
      :header="t('Last upload')"
    >
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
import { ref, onMounted, nextTick, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useRoute, useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import CorrectAndRateModal from "./CorrectAndRateModal.vue"

const { t, locale } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const router = useRouter()
const route = useRoute()
const { cid, sid, gid } = useCidReq()

const assignments = ref([])
const loading = ref(false)

const showCorrectAndRateDialog = ref(false)
const correctingItem = ref(null)
const showMetaColumns = computed(() => false)

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

async function loadAssignments() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.findStudentAssignments()
    assignments.value = response["hydra:member"].map((item) => ({
      ...item,
      id: item.iid,
    }))
  } catch (e) {
    console.error("[Assignments] Error loading student assignments", e)
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadAssignments()
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

function pad2(n) {
  return String(n).padStart(2, "0")
}

function getLocalePrefix(localeValue) {
  if (typeof localeValue !== "string" || !localeValue) return "en"
  // Accept both "es_ES" and "es-ES"
  return localeValue.replace("-", "_").split("_")[0]
}

/**
 * Format stored datetime values the same way the DatePicker shows them:
 * - API returns ISO strings with timezone (e.g. +00:00)
 * - Editing form uses new Date(iso) => browser timezone
 * - List must do the same conversion
 */
function formatStored(val) {
  if (!val) return "—"

  const s = String(val)

  // Prefer Date parsing to respect timezone and match the editing form behavior
  const d = new Date(s)
  if (!Number.isNaN(d.getTime())) {
    const day = pad2(d.getDate())
    const month = pad2(d.getMonth() + 1)
    const year = d.getFullYear()
    const hour = pad2(d.getHours())
    const min = pad2(d.getMinutes())

    const prefix = getLocalePrefix(locale.value)
    // Keep the same behavior as BaseCalendar: en => mm/dd, others => dd/mm
    if (prefix === "en") return `${month}/${day}/${year} ${hour}:${min}`
    return `${day}/${month}/${year} ${hour}:${min}`
  }

  // Fallback to legacy regex formatting (kept to avoid regressions)
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/)
  if (m) return `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}`

  console.warn("[Assignments] Failed to parse date, falling back to abbreviated", { val: s })
  return abbreviatedDatetime(s)
}
</script>
