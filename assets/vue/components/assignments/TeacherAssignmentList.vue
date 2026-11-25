<template>
  <BaseTable
    v-model:selected-items="selected"
    :is-loading="loading"
    v-model:multi-sort-meta="sortFields"
    v-model:rows="loadParams.itemsPerPage"
    :total-items="totalRecords"
    :values="visibleAssignments"
    :first="(loadParams.page - 1) * (loadParams.itemsPerPage || 0)"
    sort-mode="multiple"
    data-key="@id"
    lazy
    removable-sort
    @page="onPage"
    @sort="onSort"
  >
    <Column
      v-if="showBaseAssignments"
      header-style="width: 3rem"
    >
      <template #header>
        <BaseCheckbox
          id="assignment-select-page"
          name="assignment-select-page"
          label=""
          :aria-label="t('Select all')"
          :model-value="allPageSelected"
          :indeterminate="somePageSelected && !allPageSelected"
          :disabled="loading || duplicating || !selectableAssignments.length"
          @update:model-value="selectPage"
        />
      </template>
      <template #body="{ data }">
        <BaseCheckbox
          v-if="canEdit(data)"
          :id="`assignment-select-${data['@id'].split('/').pop()}`"
          :name="`assignment-select-${data['@id'].split('/').pop()}`"
          label=""
          :aria-label="`${t('Select')}: ${data.title}`"
          :model-value="isSelected(data)"
          :disabled="loading || duplicating"
          @update:model-value="(checked) => selectAssignment(data, checked)"
        />
      </template>
    </Column>
    <Column
      v-else
      selection-mode="multiple"
    />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    >
      <template #body="slotProps">
        <div
          v-if="slotProps.data.isBaseCourseAssignment"
          class="text-gray-500"
        >
          <div>{{ slotProps.data.title }}</div>
          <span class="text-xs">{{ t("Original assignment") }}</span>
        </div>
        <BaseAppLink
          v-else
          class="table-link"
          :to="getAssignmentDetailLink(slotProps.data)"
        >
          {{ slotProps.data.title }}
        </BaseAppLink>
      </template>
    </Column>

    <Column
      :header="t('Deadline')"
      :sortable="true"
      field="assignment.expiresOn"
    >
      <template #body="slotProps">
        <span
          v-if="slotProps.data.isBaseCourseAssignment"
          class="text-gray-400"
          >—</span
        >
        <span v-else-if="slotProps.data.assignment?.expiresOn">
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
        <span
          v-if="slotProps.data.isBaseCourseAssignment"
          class="text-gray-400"
          >—</span
        >
        <BaseTag
          v-else
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
        <template v-if="slotProps.data.isBaseCourseAssignment">
          <BaseButton
            v-if="slotProps.data.canDuplicate && isAllowedToEdit"
            :disabled="duplicating || loading"
            :label="t('Duplicate')"
            icon="copy"
            only-icon
            size="small"
            type="tertiary-text"
            @click="onClickDuplicate(slotProps.data)"
          />
          <span v-else>{{ t("Not available") }}</span>
        </template>
        <div v-else-if="canEdit(slotProps.data)">
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
            size="small"
            type="tertiary-text"
            @click="onClickVisibility(slotProps.data)"
          />
          <BaseButton
            :label="t('Upload corrections package')"
            icon="zip-unpack"
            only-icon
            size="small"
            type="success-text"
            :title="`${t('Upload corrections package')} — ${t('Each file name must match: YYYY-MM-DD_HH-MM_username_originalTitle.ext')}`"
            @click="() => uploadCorrections(slotProps.data)"
          />
          <BaseButton
            :disabled="0 === (slotProps.data.uniqueStudentAttemptsTotal || 0)"
            :label="t('Download assignments package')"
            icon="zip-pack"
            only-icon
            size="small"
            type="primary-text"
            :title="t('Download assignments package')"
            @click="() => downloadAssignments(slotProps.data)"
          />
          <BaseButton
            :label="t('Edit')"
            icon="edit"
            only-icon
            size="small"
            type="tertiary-text"
            @click="onClickEdit(slotProps.data)"
          />
        </div>
      </template>
    </Column>

    <template #footer>
      <BaseButton
        :disabled="0 === selected.length || loading || duplicating"
        :label="t('Delete selected')"
        icon="delete"
        size="small"
        type="danger-text"
        @click="onClickMultipleDelete()"
      />
    </template>
  </BaseTable>
</template>

<script setup>
import Column from "primevue/column"
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import cStudentPublicationService from "../../services/cstudentpublication"
import { getCourseContext } from "../../utils/courseContext"
import { useFormatDate } from "../../composables/formatDate"
import BaseTag from "../basecomponents/BaseTag.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import { mergeAssignmentPage } from "../../utils/assignmentListPage"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import resourceLinkService from "../../services/resourcelink"
import { useRoute, useRouter } from "vue-router"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useSecurityStore } from "../../store/securityStore"

defineOptions({ inheritAttrs: false })

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()

const assignments = ref([])
const selected = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const duplicating = ref(false)
let requestId = 0
let knownSessionTotal = null

const { cid, sid, gid } = getCourseContext()

const notification = useNotification()

const { requireConfirmation } = useConfirmation()
const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)
const showBaseAssignments = computed(() => sid > 0 && gid === 0 && isCurrentTeacher.value)
const visibleAssignments = computed(() =>
  showBaseAssignments.value ? assignments.value : assignments.value.filter((item) => !item.isBaseCourseAssignment),
)

const { abbreviatedDatetime } = useFormatDate()

const sortFields = ref([{ field: "sentDate", order: -1 }])
const loadParams = reactive({
  page: 1,
  itemsPerPage: null,
})

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })

watch(
  [loadParams, showBaseAssignments],
  () => {
    if (!loadParams.itemsPerPage) return
    loadData()
  },
  { deep: true, immediate: true },
)

function onClickDuplicate(assignment) {
  if (
    !showBaseAssignments.value ||
    !assignment.isBaseCourseAssignment ||
    !assignment.canDuplicate ||
    !isAllowedToEdit.value
  ) {
    return
  }

  requireConfirmation({
    message: t("Are you sure to copy"),
    accept: async () => {
      duplicating.value = true
      try {
        await cStudentPublicationService.duplicateToSession(assignment.id)
        notification.showSuccessNotification(t("Copy completed"))
        selected.value = []
        await loadData()
      } catch (error) {
        notification.showErrorNotification(error)
      } finally {
        duplicating.value = false
      }
    },
  })
}

async function fetchAssignmentPage(params, page) {
  const response = await cStudentPublicationService.findAll({
    params: {
      ...params,
      page,
      cid,
      sid,
      gid,
      filetype: "folder",
    },
  })
  return response.json()
}

async function loadData() {
  if (!loadParams.itemsPerPage) return

  const currentRequest = ++requestId
  const params = { ...loadParams }
  const includeBase = showBaseAssignments.value
  const offset = (params.page - 1) * params.itemsPerPage
  loading.value = true

  try {
    // Do not request a session page beyond its known last page just to display originals.
    const countOnly = includeBase && params.page > 1 && knownSessionTotal !== null && offset >= knownSessionTotal
    const [initialPage, baseResult] = await Promise.all([
      fetchAssignmentPage(params, countOnly ? 1 : params.page),
      includeBase
        ? cStudentPublicationService.findBaseCourseAssignments().then(
            (response) => ({ items: response.items ?? [] }),
            (error) => ({ items: [], error }),
          )
        : Promise.resolve({ items: [] }),
    ])

    let json = initialPage
    if (countOnly && offset < Number(initialPage["hydra:totalItems"])) {
      json = await fetchAssignmentPage(params, params.page)
    }
    if (currentRequest !== requestId) return

    const sessionTotal = Number(json["hydra:totalItems"])
    knownSessionTotal = sessionTotal
    const sessionItems = countOnly && offset >= sessionTotal ? [] : json["hydra:member"]
    if (baseResult.error) notification.showErrorNotification(baseResult.error)

    const result = mergeAssignmentPage({
      sessionItems,
      sessionTotal,
      baseItems: baseResult.items,
      page: params.page,
      pageSize: params.itemsPerPage,
      titleOrder: params["order[title]"],
    })
    const lastPage = Math.max(1, Math.ceil(result.total / params.itemsPerPage))
    if (params.page > lastPage) {
      selected.value = []
      loadParams.page = lastPage
      return
    }

    assignments.value = result.items
    totalRecords.value = result.total
    if (includeBase) selected.value = []
  } catch (error) {
    if (currentRequest === requestId) notification.showErrorNotification(error)
  } finally {
    if (currentRequest === requestId) loading.value = false
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

  loadParams.page = 1
  for (const sortItem of event.multiSortMeta ?? []) {
    loadParams[`order[${sortItem.field}]`] = -1 === sortItem.order ? "desc" : "asc"
  }
}

function onClickMultipleDelete() {
  requireConfirmation({
    message: t("Are you sure you want to delete the selected items?"),
    accept: async () => {
      loading.value = true

      try {
        for (const assignment of selected.value) {
          if (!assignment.isBaseCourseAssignment && (!showBaseAssignments.value || canEdit(assignment))) {
            await cStudentPublicationService.del(assignment)
          }
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
  if (item.isBaseCourseAssignment) return false

  const sessionId = getSessionId(item)

  const isSessionDocument = sessionId && sessionId === sid
  const isBaseCourse = !sessionId

  return (isSessionDocument && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

const selectableAssignments = computed(() => visibleAssignments.value.filter(canEdit))
const allPageSelected = computed(
  () => selectableAssignments.value.length > 0 && selectableAssignments.value.every(isSelected),
)
const somePageSelected = computed(() => selectableAssignments.value.some(isSelected))

function isSelected(item) {
  return selected.value.some((assignment) => assignment["@id"] === item["@id"])
}

function selectAssignment(item, checked) {
  if (!canEdit(item)) return

  selected.value = selected.value.filter((assignment) => assignment["@id"] !== item["@id"])
  if (checked) selected.value.push(item)
}

function selectPage(checked) {
  selected.value = checked ? [...selectableAssignments.value] : []
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
