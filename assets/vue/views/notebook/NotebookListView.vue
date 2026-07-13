<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          v-if="canWrite"
          icon="plus"
          :label="t('Add')"
          only-icon
          size="large"
          type="success"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="addRoute"
        />
      </template>

      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <div class="w-56 max-w-full">
            <BaseSelect
              id="notebook_sort"
              v-model="sortField"
              :label="t('Sort by')"
              name="sort"
              :options="sortOptions"
              @change="changeSortField"
            />
          </div>

          <BaseButton
            :icon="sortDirection === 'ASC' ? 'arrow-up' : 'arrow-down'"
            :label="sortDirectionLabel"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            @click="toggleSortDirection"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-else-if="notes.length === 0"
      class="rounded-xl border border-gray-20 bg-white px-6 py-10 text-center shadow-sm"
    >
      <BaseIcon
        class="mb-3 text-gray-500"
        icon="information"
        size="big"
      />
      <p class="text-sm italic text-gray-500">
        {{ t("No data available") }}
      </p>
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <BaseCard
        v-for="note in notes"
        :id="`notebook_${note.iid}`"
        :key="note.iid"
        :data-id="note.iid"
        data-type="notebook"
      >
        <template #title>
          <div class="flex min-w-0 items-start gap-2">
            <div class="min-w-0 flex-1 break-words text-lg font-semibold text-gray-90">
              {{ note.title }}
            </div>
            <BaseIcon
              v-if="note.sessionId"
              :tooltip="t('Session')"
              icon="sessions"
              size="small"
            />
            <BaseButton
              v-if="canWrite && note.canEdit"
              icon="pencil"
              :label="t('Edit')"
              only-icon
              size="small"
              type="secondary-text"
              :route="getEditRoute(note)"
            />
            <BaseButton
              v-if="canWrite && note.canDelete"
              icon="delete"
              :is-loading="deletingId === note.iid"
              :label="t('Delete')"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(note)"
            />
          </div>
        </template>

        <div
          v-if="note.content"
          class="break-words"
          v-html="note.content"
        ></div>

        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-gray-20 pt-3 text-xs text-gray-500">
          <span class="inline-flex items-center gap-1.5">
            <BaseIcon
              icon="agenda-event"
              size="small"
            />
            {{ t("Created at") }}: {{ formatDate(note.creationDate) }}
          </span>
          <span
            v-if="note.updateDate"
            class="inline-flex items-center gap-1.5"
          >
            <BaseIcon
              icon="edit"
              size="small"
            />
            {{ t("Updated at") }}: {{ formatDate(note.updateDate) }}
          </span>
        </div>
      </BaseCard>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import notebookService from "../../services/notebookService"

const { t } = useI18n()
const toast = useToast()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const notes = ref([])
const isLoading = ref(false)
const errorMessage = ref("")
const canWrite = ref(false)
const csrfToken = ref("")
const deletingId = ref(null)
const sortField = ref(normalizeSort(getQueryValue(route.query.sort)))
const sortDirection = ref(normalizeDirection(getQueryValue(route.query.direction)))

const addRoute = computed(() => ({
  name: "NotebookAdd",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const sortOptions = computed(() => [
  { label: t("Created at"), value: "creation_date" },
  { label: t("Updated at"), value: "update_date" },
  { label: t("Title"), value: "title" },
])

const selectedSortLabel = computed(
  () => sortOptions.value.find((option) => option.value === sortField.value)?.label || t("Sort"),
)

const sortDirectionLabel = computed(
  () => `${t("Sort by")}: ${selectedSortLabel.value} ${sortDirection.value === "ASC" ? "↑" : "↓"}`,
)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function normalizeSort(value) {
  return ["creation_date", "update_date", "title"].includes(String(value)) ? String(value) : "creation_date"
}

function normalizeDirection(value) {
  return String(value).toUpperCase() === "DESC" ? "DESC" : "ASC"
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    params.isStudentView = getQueryValue(route.query.isStudentView)
  }

  return params
}

function getListParams() {
  return {
    ...getContextParams(),
    sort: sortField.value,
    direction: sortDirection.value,
  }
}

function getEditRoute(note) {
  return {
    name: "NotebookEdit",
    params: {
      node: route.params.node,
      id: note.iid,
    },
    query: getContextParams(),
  }
}

function updateSortRoute(field, direction) {
  router.replace({
    name: "NotebookList",
    params: { node: route.params.node },
    query: {
      ...getContextParams(),
      sort: normalizeSort(field),
      direction: normalizeDirection(direction),
    },
  })
}

function changeSortField() {
  updateSortRoute(sortField.value, sortDirection.value)
}

function toggleSortDirection() {
  updateSortRoute(sortField.value, sortDirection.value === "ASC" ? "DESC" : "ASC")
}

function confirmDelete(note) {
  requireConfirmation({
    message: `${t("Are you sure you want to delete")} "${note.title}"?`,
    accept: () => deleteNote(note),
  })
}

function showToast(severity, summaryKey, detail, life = 3500) {
  toast.add({
    severity,
    summary: t(summaryKey),
    detail,
    life,
  })
}

function showSuccessMessage(messageKey) {
  showToast("success", "Success", t(messageKey))
}

async function deleteNote(note) {
  if (deletingId.value !== null) {
    return
  }

  deletingId.value = note.iid

  try {
    await notebookService.remove(note.iid, { csrfToken: csrfToken.value }, getContextParams())
    notes.value = notes.value.filter((item) => item.iid !== note.iid)
    showSuccessMessage("Deleted")
  } catch (error) {
    console.error("Error deleting notebook entry", error)
    const message =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
    showToast("error", "Error", message, 5000)
  } finally {
    deletingId.value = null
  }
}

function formatDate(value) {
  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? String(value || "")
    : new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(date)
}

function clearResultQuery() {
  const query = { ...route.query }

  delete query.result
  delete query.legacyAction

  router.replace({
    name: "NotebookList",
    params: { node: route.params.node },
    query,
  })
}

function loadResultMessage() {
  const result = String(getQueryValue(route.query.result) || "")
  const legacyAction = String(getQueryValue(route.query.legacyAction) || "")
  const labels = {
    created: "Created",
    updated: "Updated",
    deleted: "Deleted",
  }

  if (labels[result]) {
    showSuccessMessage(labels[result])
  }

  if (legacyAction === "deletenote") {
    showToast("warn", "Warning", t("NotAllowed"), 5000)
  }

  if (result || legacyAction) {
    clearResultQuery()
  }
}

async function loadNotes() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await notebookService.getList(getListParams())
    notes.value = Array.isArray(response.items) ? response.items : []
    canWrite.value = Boolean(response.canWrite)
    csrfToken.value = response.csrfToken || ""
    sortField.value = normalizeSort(response.sort)
    sortDirection.value = normalizeDirection(response.direction)
  } catch (error) {
    console.error("Error loading notebook entries", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadNotes()
  loadResultMessage()
})

watch(
  () => [
    route.query.cid,
    route.query.sid,
    route.query.gid,
    route.query.isStudentView,
    route.query.sort,
    route.query.direction,
  ],
  () => {
    sortField.value = normalizeSort(getQueryValue(route.query.sort))
    sortDirection.value = normalizeDirection(getQueryValue(route.query.direction))
    loadNotes()
  },
)
</script>
