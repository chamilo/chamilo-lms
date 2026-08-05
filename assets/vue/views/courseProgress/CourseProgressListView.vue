<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Course progress')" />

    <BaseToolbar
      v-if="canManageCurrentView"
      class="mb-4 border-b border-gray-25 bg-white"
    >
      <template #start>
        <BaseButton
          icon="plus"
          :label="t('New thematic section')"
          only-icon
          size="large"
          type="success-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="addThematicRoute"
        />
        <BaseButton
          icon="import"
          :label="t('Import course progress')"
          only-icon
          size="large"
          type="success-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="importRoute"
        />
        <BaseButton
          icon="file-delimited-outline"
          :is-loading="isExporting"
          :label="t('Export course progress')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="exportCourseProgress"
        />
        <BaseButton
          icon="file-pdf"
          :is-loading="isExportingPdf"
          :label="t('Export to PDF')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="exportCourseProgressPdf"
        />
        <BaseButton
          v-if="selectableThematicIds.length > 0"
          :icon="allSelectableThematicsSelected ? 'unselect-all' : 'select-all'"
          :label="allSelectableThematicsSelected ? t('Unselect all') : t('Select all')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="toggleSelectAll"
        />
        <BaseButton
          v-if="selectedThematicIds.length > 0"
          icon="delete"
          :is-loading="isBulkDeleting"
          :label="t('Delete')"
          only-icon
          size="large"
          type="danger-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="confirmBulkDelete"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
      aria-live="polite"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="actionErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
      aria-live="assertive"
    >
      {{ actionErrorMessage }}
    </div>

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

    <template v-else>
      <BaseCard v-if="thematics.length > 0">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-semibold text-gray-90">
              {{ t("Course progress") }}
            </p>
            <p class="mt-1 text-sm text-gray-50">
              {{ t("Progress") }}
            </p>
          </div>

          <div class="flex min-w-52 items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-25">
              <div
                class="h-full rounded-full bg-primary"
                :style="{ width: `${normalizedTotalAverage}%` }"
              ></div>
            </div>
            <span class="min-w-12 text-right text-sm font-semibold text-gray-90"> {{ normalizedTotalAverage }}% </span>
          </div>
        </div>
      </BaseCard>

      <div
        v-if="thematics.length === 0"
        class="rounded-xl border border-gray-20 bg-white px-6 py-10 text-center shadow-sm"
      >
        <BaseIcon
          class="mb-3 text-gray-500"
          icon="information"
          size="big"
        />
        <p class="text-sm italic text-gray-500">
          {{ t("There is no thematic section") }}
        </p>
      </div>

      <div
        v-else
        class="space-y-6"
      >
        <BaseCard
          v-for="(thematic, index) in thematics"
          :key="thematic.iid"
          :data-id="thematic.iid"
          data-type="course_progress"
        >
          <template #title>
            <div class="flex min-w-0 items-start justify-between gap-3">
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <input
                  v-if="canManageCurrentView && thematic.canDelete"
                  v-model="selectedThematicIds"
                  class="mt-1 h-4 w-4 shrink-0 rounded border-gray-30 text-primary focus:ring-primary"
                  :aria-label="t('Select')"
                  :name="`thematic_ids_${thematic.iid}`"
                  type="checkbox"
                  :value="thematic.iid"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-semibold uppercase tracking-wide text-gray-50">
                    {{ t("Thematic") }} {{ formatThematicNumber(index) }}
                  </p>
                  <div
                    class="mt-1 break-words text-xl font-bold text-gray-90"
                    v-html="thematic.title"
                  ></div>
                </div>
              </div>

              <div class="flex shrink-0 items-center gap-1">
                <BaseIcon
                  v-if="thematic.isInheritedFromCourse"
                  :tooltip="t('Course')"
                  icon="courses"
                  size="small"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canCopy"
                  icon="copy"
                  :is-loading="isThematicActionLoading(thematic, 'copy')"
                  :label="t('Copy')"
                  only-icon
                  size="small"
                  type="success-text"
                  @click="copyThematic(thematic)"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canMove"
                  :disabled="!thematic.canMoveUp || thematicActionId !== null"
                  icon="arrow-up"
                  :is-loading="isThematicActionLoading(thematic, 'up')"
                  :label="t('Move up')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="moveThematic(thematic, 'up')"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canMove"
                  :disabled="!thematic.canMoveDown || thematicActionId !== null"
                  icon="arrow-down"
                  :is-loading="isThematicActionLoading(thematic, 'down')"
                  :label="t('Move down')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="moveThematic(thematic, 'down')"
                />
                <BaseButton
                  v-if="canManageCurrentView"
                  icon="file-pdf"
                  :is-loading="exportingThematicPdfId === thematic.iid"
                  :label="t('Export to PDF')"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="exportThematicPdf(thematic)"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canEdit"
                  icon="list"
                  :label="t('Thematic plan')"
                  only-icon
                  size="small"
                  type="primary-text"
                  :route="getPlanRoute(thematic)"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canEdit"
                  icon="agenda-plan"
                  :label="t('Thematic advance')"
                  only-icon
                  size="small"
                  type="primary-text"
                  :route="getAdvanceRoute(thematic)"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canEdit"
                  icon="pencil"
                  :label="t('Edit')"
                  only-icon
                  size="small"
                  type="secondary-text"
                  :route="getEditRoute(thematic)"
                />
                <BaseButton
                  v-if="canManageCurrentView && thematic.canDelete"
                  icon="delete"
                  :is-loading="deletingId === thematic.iid"
                  :label="t('Delete')"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDelete(thematic)"
                />
              </div>
            </div>
          </template>

          <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="min-w-0">
              <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-50">
                {{ t("Thematic") }}
              </h2>
              <div
                v-if="thematic.content"
                class="prose max-w-none break-words text-gray-90"
                v-html="thematic.content"
              ></div>
            </section>

            <section class="min-w-0">
              <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-50">
                {{ t("Thematic plan") }}
              </h2>

              <div
                v-if="thematic.plans.length === 0"
                class="rounded-lg border border-gray-25 bg-support-2 p-4 text-sm text-gray-90"
              >
                {{ t("There is no thematic plan for now") }}
              </div>

              <div
                v-else
                class="space-y-4"
              >
                <article
                  v-for="plan in thematic.plans"
                  :key="plan.iid"
                  class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm"
                >
                  <div
                    class="mb-2 break-words text-base font-semibold text-gray-90"
                    v-html="plan.title"
                  ></div>
                  <div
                    v-if="plan.description"
                    class="prose max-w-none break-words text-gray-90"
                    v-html="plan.description"
                  ></div>
                </article>
              </div>
            </section>

            <section class="min-w-0">
              <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-50">
                {{ t("Thematic advance") }}
              </h2>

              <div
                v-if="thematic.advances.length === 0"
                class="rounded-lg border border-gray-25 bg-support-2 p-4 text-sm text-gray-90"
              >
                {{ t("There is no thematic advance") }}
              </div>

              <ol
                v-else
                class="space-y-3"
              >
                <li
                  v-for="advance in thematic.advances"
                  :key="advance.iid"
                  class="rounded-lg border border-gray-25 p-4 shadow-sm"
                  :class="advance.doneAdvance ? 'bg-support-2' : 'bg-white'"
                >
                  <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-50">
                      <strong>{{ advance.formattedStartDate }}</strong>
                    </p>

                    <div
                      v-if="advance.doneAdvance"
                      class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700"
                    >
                      <BaseIcon
                        icon="check"
                        size="small"
                      />
                      <span>{{ t("Done") }}</span>
                    </div>
                  </div>

                  <div
                    v-if="advance.content"
                    class="prose mt-2 max-w-none break-words text-gray-90"
                    v-html="advance.content"
                  ></div>

                  <div
                    v-if="canManageCurrentView && thematic.canEdit"
                    class="mt-3 flex justify-end"
                  >
                    <label
                      class="inline-flex cursor-pointer items-center gap-2 rounded-md px-2 py-1 text-sm text-gray-90"
                      :class="advance.doneAdvance ? 'bg-support-2' : 'bg-white'"
                    >
                      <input
                        v-model="selectedCompletionAdvanceId"
                        class="h-4 w-4 rounded-full border-gray-25 text-primary focus:ring-primary"
                        :disabled="completingAdvanceId !== null"
                        name="done_thematic"
                        type="radio"
                        :value="advance.iid"
                        @change="updateCompletion(advance)"
                      />
                      <span>{{ t("Done") }}</span>
                    </label>
                  </div>
                </li>
              </ol>
            </section>
          </div>
        </BaseCard>
      </div>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseProgressService from "../../services/courseProgressService"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const platformConfigStore = usePlatformConfig()

const thematics = ref([])
const totalAverage = ref(0)
const isLoading = ref(false)
const errorMessage = ref("")
const actionErrorMessage = ref("")
const successMessage = ref("")
const canManage = ref(false)
const csrfToken = ref("")
const completionCsrfToken = ref("")
const deletingId = ref(null)
const completingAdvanceId = ref(null)
const selectedCompletionAdvanceId = ref(null)
const lastConfirmedCompletionAdvanceId = ref(null)
const selectedThematicIds = ref([])
const thematicActionId = ref(null)
const thematicActionName = ref("")
const isBulkDeleting = ref(false)
const isExporting = ref(false)
const isExportingPdf = ref(false)
const exportingThematicPdfId = ref(null)

const canManageCurrentView = computed(() => canManage.value && !platformConfigStore.isStudentViewActive)

const selectableThematicIds = computed(() =>
  canManageCurrentView.value
    ? thematics.value.filter((thematic) => thematic.canDelete).map((thematic) => Number(thematic.iid))
    : [],
)

const allSelectableThematicsSelected = computed(
  () =>
    selectableThematicIds.value.length > 0 &&
    selectableThematicIds.value.every((thematicId) => selectedThematicIds.value.includes(thematicId)),
)

const normalizedTotalAverage = computed(() => {
  const value = Number(totalAverage.value)

  if (!Number.isFinite(value)) {
    return 0
  }

  return Math.min(100, Math.max(0, Math.round(value)))
})

const addThematicRoute = computed(() => ({
  name: "CourseProgressThematicAdd",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const importRoute = computed(() => ({
  name: "CourseProgressImport",
  params: { node: route.params.node },
  query: getContextParams(),
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
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

function getEditRoute(thematic) {
  return {
    name: "CourseProgressThematicEdit",
    params: {
      node: route.params.node,
      id: thematic.iid,
    },
    query: getContextParams(),
  }
}

function getPlanRoute(thematic) {
  return {
    name: "CourseProgressThematicPlan",
    params: {
      node: route.params.node,
      thematicId: thematic.iid,
    },
    query: getContextParams(),
  }
}

function getAdvanceRoute(thematic) {
  return {
    name: "CourseProgressThematicAdvanceList",
    params: {
      node: route.params.node,
      thematicId: thematic.iid,
    },
    query: getContextParams(),
  }
}

function formatThematicNumber(index) {
  return String(index + 1).padStart(3, "0")
}

function getPlainTitle(thematic) {
  return String(thematic.title || t("Thematic"))
    .replace(/<[^>]*>/g, "")
    .trim()
}

function isThematicActionLoading(thematic, actionName) {
  return thematicActionId.value === thematic.iid && thematicActionName.value === actionName
}

function toggleSelectAll() {
  selectedThematicIds.value = allSelectableThematicsSelected.value ? [] : [...selectableThematicIds.value]
}

function confirmBulkDelete() {
  if (selectedThematicIds.value.length === 0) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete"),
    accept: deleteSelectedThematics,
  })
}

async function copyThematic(thematic) {
  if (thematicActionId.value !== null) {
    return
  }

  thematicActionId.value = thematic.iid
  thematicActionName.value = "copy"
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseProgressService.copyThematic(thematic.iid, csrfToken.value, getContextParams())
    await loadCourseProgress()
    successMessage.value = t("Update successful")
  } catch (error) {
    console.error("Error copying thematic", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    thematicActionId.value = null
    thematicActionName.value = ""
  }
}

async function moveThematic(thematic, direction) {
  if (thematicActionId.value !== null) {
    return
  }

  thematicActionId.value = thematic.iid
  thematicActionName.value = direction
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseProgressService.moveThematic(thematic.iid, direction, csrfToken.value, getContextParams())
    await loadCourseProgress()
    successMessage.value = t("Update successful")
  } catch (error) {
    console.error("Error moving thematic", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    thematicActionId.value = null
    thematicActionName.value = ""
  }
}

async function deleteSelectedThematics() {
  if (isBulkDeleting.value || selectedThematicIds.value.length === 0) {
    return
  }

  isBulkDeleting.value = true
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseProgressService.removeThematics(selectedThematicIds.value, csrfToken.value, getContextParams())
    selectedThematicIds.value = []
    await loadCourseProgress()
    successMessage.value = t("Deleted")
  } catch (error) {
    console.error("Error deleting selected thematics", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isBulkDeleting.value = false
  }
}

function confirmDelete(thematic) {
  const title = getPlainTitle(thematic)

  requireConfirmation({
    message: `${t("Are you sure you want to delete")} "${title}"?`,
    accept: () => deleteThematic(thematic),
  })
}

async function deleteThematic(thematic) {
  if (deletingId.value !== null) {
    return
  }

  deletingId.value = thematic.iid
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseProgressService.removeThematic(thematic.iid, { csrfToken: csrfToken.value }, getContextParams())

    await loadCourseProgress()
    successMessage.value = t("Deleted")
  } catch (error) {
    console.error("Error deleting thematic", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    deletingId.value = null
  }
}

function applyCompletionResponse(response) {
  const doneAdvanceIds = new Set(
    Array.isArray(response.doneAdvanceIds) ? response.doneAdvanceIds.map((id) => Number(id)) : [],
  )

  thematics.value = thematics.value.map((thematic) => ({
    ...thematic,
    advances: thematic.advances.map((advance) => ({
      ...advance,
      doneAdvance: doneAdvanceIds.has(Number(advance.iid)),
    })),
  }))
  totalAverage.value = Number(response.totalAverage || 0)
  selectedCompletionAdvanceId.value = Number(response.advanceId || 0) || null
  lastConfirmedCompletionAdvanceId.value = selectedCompletionAdvanceId.value
}

async function updateCompletion(advance) {
  if (completingAdvanceId.value !== null) {
    return
  }

  const previousAdvanceId = lastConfirmedCompletionAdvanceId.value
  completingAdvanceId.value = advance.iid
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseProgressService.updateCompletion(
      advance.iid,
      completionCsrfToken.value,
      getContextParams(),
    )

    applyCompletionResponse(response)
    successMessage.value = t("Update successful")
  } catch (error) {
    console.error("Error updating thematic progress", error)
    selectedCompletionAdvanceId.value = previousAdvanceId
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    completingAdvanceId.value = null
  }
}

async function consumeSavedMessage() {
  const wasSaved = String(getQueryValue(route.query.saved) || "") === "1"
  const wasImported = String(getQueryValue(route.query.imported) || "") === "1"

  if (!wasSaved && !wasImported) {
    return
  }

  successMessage.value = wasImported ? t("Import course progress") : t("Update successful")
  const query = { ...route.query }
  delete query.saved
  delete query.imported

  await router.replace({
    name: route.name,
    params: route.params,
    query,
  })
}

function getDownloadFilename(contentDisposition, fallback) {
  if (typeof contentDisposition !== "string" || contentDisposition === "") {
    return fallback
  }

  const match = contentDisposition.match(/filename="?([^";]+)"?/i)

  return match?.[1] || fallback
}

async function exportCourseProgress() {
  if (isExporting.value) {
    return
  }

  isExporting.value = true
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseProgressService.exportCsv(getContextParams())
    const filename = getDownloadFilename(response.headers?.["content-disposition"], "course_progress.csv")
    const url = URL.createObjectURL(response.data)
    const link = document.createElement("a")

    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error("Error exporting course progress", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isExporting.value = false
  }
}

function downloadBlobResponse(response, fallbackFilename) {
  const filename = getDownloadFilename(response.headers?.["content-disposition"], fallbackFilename)
  const url = URL.createObjectURL(response.data)
  const link = document.createElement("a")

  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

async function exportCourseProgressPdf() {
  if (isExportingPdf.value) {
    return
  }

  isExportingPdf.value = true
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseProgressService.exportPdf(getContextParams())
    downloadBlobResponse(response, "thematic.pdf")
  } catch (error) {
    console.error("Error exporting course progress PDF", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isExportingPdf.value = false
  }
}

async function exportThematicPdf(thematic) {
  if (exportingThematicPdfId.value !== null) {
    return
  }

  exportingThematicPdfId.value = thematic.iid
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseProgressService.exportThematicPdf(thematic.iid, getContextParams())
    downloadBlobResponse(response, `thematic-${thematic.iid}.pdf`)
  } catch (error) {
    console.error("Error exporting thematic PDF", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    exportingThematicPdfId.value = null
  }
}

async function loadCourseProgress() {
  isLoading.value = true
  errorMessage.value = ""
  actionErrorMessage.value = ""

  try {
    const response = await courseProgressService.getList(getContextParams())

    thematics.value = Array.isArray(response.items) ? response.items : []
    const selectableIds = new Set(
      thematics.value.filter((thematic) => thematic.canDelete).map((thematic) => Number(thematic.iid)),
    )
    selectedThematicIds.value = selectedThematicIds.value.filter((thematicId) => selectableIds.has(thematicId))
    totalAverage.value = Number(response.totalAverage || 0)
    canManage.value = Boolean(response.canManage)
    csrfToken.value = response.csrfToken || ""
    completionCsrfToken.value = response.completionCsrfToken || ""
    selectedCompletionAdvanceId.value = Number(response.lastDoneAdvanceId || 0) || null
    lastConfirmedCompletionAdvanceId.value = selectedCompletionAdvanceId.value
  } catch (error) {
    console.error("Error loading course progress", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await loadCourseProgress()
  await consumeSavedMessage()
})

watch(
  () => platformConfigStore.isStudentViewActive,
  async () => {
    selectedThematicIds.value = []
    successMessage.value = ""
    actionErrorMessage.value = ""
    await loadCourseProgress()
  },
)

watch(() => [route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView], loadCourseProgress)
</script>
