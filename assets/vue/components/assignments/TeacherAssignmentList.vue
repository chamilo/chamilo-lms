<template>
  <BaseTable
    v-model:selected-items="selected"
    :is-loading="loading"
    v-model:multi-sort-meta="sortFields"
    v-model:rows="loadParams.itemsPerPage"
    :total-items="totalRecords"
    :values="assignments"
    data-key="@id"
    lazy
    removable-sort
    @page="onPage"
    @sort="onSort"
  >
    <Column selection-mode="multiple" />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    >
      <template #body="slotProps">
        <RouterLink
          class="text-blue-600 hover:underline"
          :to="getAssignmentDetailLink(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </RouterLink>
      </template>
    </Column>

    <Column
      :header="t('Deadline')"
      :sortable="true"
      field="assignment.expiresOn"
    >
      <template #body="slotProps">
        <span v-if="slotProps.data.assignment?.expiresOn">
          {{ formatStored(slotProps.data.assignment.expiresOn) }}
        </span>
        <span
          v-else
          class="text-gray-400 italic"
          >No deadline</span
        >
      </template>
    </Column>

    <Column
      :header="t('End date')"
      :sortable="true"
      field="assignment.endsOn"
    >
      <template #body="slotProps">
        <span v-if="slotProps.data.assignment?.endsOn">
          {{ formatStored(slotProps.data.assignment.endsOn) }}
        </span>
        <span
          v-else
          class="text-gray-400 italic"
          >—</span
        >
      </template>
    </Column>

    <Column :header="t('Number submitted')">
      <template #body="slotProps">
        <BaseTag
          :label="`${slotProps.data.uniqueStudentAttemptsTotal || 0} / ${slotProps.data.studentSubscribedToWork || 0}`"
          type="success"
        />
      </template>
    </Column>

    <Column
      :header="t('Actions')"
      body-class="space-x-2"
    >
      <template #body="slotProps">
        <div v-if="canEdit(slotProps.data)">
          <BaseButton
            :icon="
              RESOURCE_LINK_PUBLISHED === slotProps.data.firstResourceLink?.visibility
                ? 'eye-on'
                : RESOURCE_LINK_DRAFT === slotProps.data.firstResourceLink?.visibility
                  ? 'eye-off'
                  : ''
            "
            :label="t('Visibility')"
            only-icon
            size="normal"
            type="black"
            @click="onClickVisibility(slotProps.data)"
          />
          <BaseButton
            :label="t('Upload corrections package')"
            icon="zip-unpack"
            only-icon
            size="normal"
            type="success"
            :title="`${t('Upload corrections package')} — ${t('Each file name must match: YYYY-MM-DD_HH-MM_username_originalTitle.ext')}`"
            @click="() => uploadCorrections(slotProps.data)"
          />
          <BaseButton
            :disabled="0 === (slotProps.data.uniqueStudentAttemptsTotal || 0)"
            :label="t('Download assignments package')"
            icon="zip-pack"
            only-icon
            size="normal"
            type="primary"
            :title="t('Download assignments package')"
            @click="() => downloadAssignments(slotProps.data)"
          />
          <BaseButton
            :label="t('Edit')"
            icon="edit"
            only-icon
            size="normal"
            type="black"
            @click="onClickEdit(slotProps.data)"
          />
        </div>
      </template>
    </Column>

    <template #footer>
      <BaseButton
        :disabled="0 === selected.length || loading"
        :label="t('Delete selected')"
        icon="delete"
        type="danger"
        @click="onClickMultipleDelete()"
      />
    </template>
  </BaseTable>
</template>

<script setup>
import Column from "primevue/column"
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useCidReq } from "../../composables/cidReq"
import { useFormatDate } from "../../composables/formatDate"
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useNotification } from "../../composables/notification"
import { useConfirm } from "primevue/useconfirm"
import resourceLinkService from "../../services/resourcelink"
import { useRoute, useRouter } from "vue-router"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useSecurityStore } from "../../store/securityStore"

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const assignments = ref([])
const selected = ref([])
const loading = ref(false)
const totalRecords = ref(0)

const { cid, sid, gid } = useCidReq()

const notification = useNotification()

const confirm = useConfirm()
const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)

const { abbreviatedDatetime } = useFormatDate()

const sortFields = ref([{ field: "sentDate", order: -1 }])
const loadParams = reactive({
  page: 1,
  itemsPerPage: null,
})

const isAllowedToEdit = ref(false)

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
})

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
    const response = await cStudentPublicationService.findAll({
      params: {
        ...loadParams,
        cid,
        sid,
        gid,
        "publicationParent.iid": false,
      },
    })
    const json = await response.json()

    assignments.value = json["hydra:member"]
    totalRecords.value = json["hydra:totalItems"]
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

const onPage = (event) => {
  loadParams.page = event.page + 1
  loadParams.itemsPerPage = event.rows
}

const onSort = (event) => {
  Object.keys(loadParams)
    .filter((key) => key.indexOf("order[") >= 0)
    .forEach((key) => delete loadParams[key])

  event.multiSortMeta.forEach((sortItem) => {
    loadParams[`order[${sortItem.field}]`] = -1 === sortItem.order ? "desc" : "asc"
  })
}

function onClickMultipleDelete() {
  confirm.require({
    header: t("Confirmation"),
    message: t("Are you sure you want to delete the selected items?"),
    accept: async () => {
      loading.value = true

      try {
        for (const assignment of selected.value) {
          await cStudentPublicationService.del(assignment)
        }
      } catch (e) {
        notification.showErrorNotification(e)

        loading.value = false

        return
      } finally {
        selected.value = []
      }

      loadData()

      notification.showSuccessNotification(t("Assignments deleted"))
    },
  })
}

function getAssignmentDetailLink(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)
  const nodeUrl = assignment.resourceNode?.["@id"]
  const nodeId = nodeUrl ? parseInt(nodeUrl.split("/").pop(), 10) : 0

  return {
    name: "AssignmentDetail",
    params: {
      node: nodeId,
      id: assignmentId,
    },
    query: route.query,
  }
}

async function onClickVisibility(assignment) {
  if (RESOURCE_LINK_PUBLISHED === assignment.firstResourceLink?.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_DRAFT
  } else if (RESOURCE_LINK_DRAFT === assignment.firstResourceLink?.visibility) {
    assignment.firstResourceLink.visibility = RESOURCE_LINK_PUBLISHED
  }

  try {
    await resourceLinkService.update(assignment.firstResourceLink)
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

function onClickEdit(assignment) {
  router.push({
    name: "AssignmentsUpdate",
    params: { id: assignment["@id"] },
    query: {
      ...route.query,
      from: "AssignmentsList",
    },
  })
}

async function downloadAssignments(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)
  try {
    const blob = await cStudentPublicationService.downloadAssignments(assignmentId)
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `assignments_${assignmentId}.zip`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    notification.showErrorNotification(t("Failed to download package"))
    console.error("[Assignments] Download error", error)
  }
}

async function uploadCorrections(assignment) {
  const assignmentId = parseInt(assignment["@id"].split("/").pop(), 10)

  const input = document.createElement("input")
  input.type = "file"
  input.accept = ".zip"

  input.addEventListener("change", async () => {
    const file = input.files[0]
    if (!file) return

    try {
      const result = await cStudentPublicationService.uploadCorrectionsPackage(assignmentId, file)
      const uploaded = result.uploaded ?? 0
      const skipped = result.skipped ?? 0
      notification.showSuccessNotification(t(`Corrections uploaded: ${uploaded}. Skipped: ${skipped}.`))
      await loadData()
    } catch (error) {
      console.error("[Assignments] Upload corrections error", error)
      notification.showErrorNotification(t("Failed to upload corrections"))
    }
  })

  input.click()
}

const getSessionId = (item) => {
  if (!item.firstResourceLink || !item.firstResourceLink.session) {
    return null
  }

  const sessionParts = item.firstResourceLink.session.split("/")
  return parseInt(sessionParts[sessionParts.length - 1])
}

const canEdit = (item) => {
  const sessionId = getSessionId(item)

  const isSessionDocument = sessionId && sessionId === sid
  const isBaseCourse = !sessionId

  return (isSessionDocument && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

function pad2(n) {
  return String(n).padStart(2, "0")
}

function getLocalePrefix(localeValue) {
  if (typeof localeValue !== "string" || !localeValue) return "en"
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
