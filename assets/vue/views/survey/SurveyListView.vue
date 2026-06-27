<template>
  <section class="space-y-6">
    <div class="space-y-3">
      <div class="flex flex-wrap items-center gap-2">
        <div
          class="survey-list-toolbar flex flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm"
        >
          <BaseButton
            v-if="canCreate"
            class="survey-list-toolbar__button"
            :label="t('Create survey')"
            :route="buildCreateRoute()"
            icon="plus"
            only-icon
            size="small"
            type="primary-text"
          />
          <BaseButton
            v-if="canCreate"
            class="survey-list-toolbar__button"
            :label="t('Create meeting poll')"
            :route="buildMeetingCreateRoute()"
            icon="calendar-plus"
            only-icon
            size="small"
            type="primary-text"
          />
          <span
            v-if="canCreate"
            class="mx-1 h-6 w-px bg-gray-20"
            aria-hidden="true"
          />
          <BaseButton
            class="survey-list-toolbar__button"
            :label="isSearchVisible ? t('Hide search') : t('Search')"
            :icon="isSearchVisible ? 'close' : 'search'"
            only-icon
            size="small"
            type="primary-text"
            @click="toggleSearchForm"
          />
        </div>
      </div>
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="activeSearch && !isSearchVisible"
      class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-20 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm"
    >
      <span>{{ t("Showing results for: {0}", [activeSearch]) }}</span>
      <BaseButton
        :label="t('Clear search')"
        icon="close"
        only-icon
        size="small"
        type="secondary-text"
        @click="clearSearch"
      />
    </div>

    <form
      v-if="isSearchVisible"
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      @submit.prevent="applySearch"
    >
      <div class="flex flex-col gap-3 md:flex-row md:items-start">
        <BaseInputText
          id="survey-search"
          v-model="searchTerm"
          class="flex-1"
          :help-text="t('Search by survey title, code or description.')"
          :label="t('Search surveys')"
          name="search"
        />
        <div class="flex flex-wrap gap-2 md:pt-1">
          <BaseButton
            :is-loading="isLoading"
            :label="t('Search')"
            icon="search"
            :is-submit="true"
            type="primary"
          />
          <BaseButton
            v-if="activeSearch"
            :label="t('Clear search')"
            icon="close"
            type="secondary"
            @click="clearSearch"
          />
          <BaseButton
            v-else
            :label="t('Cancel')"
            icon="close"
            type="secondary"
            @click="hideSearchForm"
          />
        </div>
      </div>
      <p
        v-if="activeSearch"
        class="mt-3 text-sm text-gray-600"
      >
        {{ t("Showing results for: {0}", [activeSearch]) }}
      </p>
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="selectedSurveyIds.length > 0"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white px-4 py-3 text-sm shadow-sm"
    >
      <span class="font-semibold text-gray-800">
        {{ t("{0} surveys selected", [selectedSurveyIds.length]) }}
      </span>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="t('Delete selected')"
          icon="delete"
          size="small"
          type="danger"
          @click="confirmBulkDelete"
        />
        <BaseButton
          :label="t('Clear selection')"
          icon="close"
          size="small"
          type="secondary"
          @click="clearSelection"
        />
      </div>
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No surveys found')"
      :total-items="surveys.length"
      :values="surveys"
      data-key="iid"
    >
      <Column
        v-if="canManage"
        class="w-12"
      >
        <template #header>
          <input
            aria-label="Select all surveys"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="areAllSelectableSurveysSelected"
            :disabled="selectableSurveys.length === 0"
            :indeterminate.prop="isSelectionIndeterminate"
            @change="toggleAllSurveySelection($event.target.checked)"
          />
        </template>
        <template #body="{ data }">
          <input
            v-if="data.canDelete"
            :aria-label="t('Select survey')"
            class="h-4 w-4 cursor-pointer rounded border-gray-30"
            type="checkbox"
            :checked="isSurveySelected(data.iid)"
            @change="toggleSurveySelection(data, $event.target.checked)"
          />
        </template>
      </Column>

      <Column
        :header="t('Title')"
        field="title"
        sortable
      >
        <template #body="{ data }">
          <div class="min-w-64">
            <div class="flex items-center gap-2">
              <span
                :class="chamiloIconToClass[data.surveyType === 3 ? 'calendar-plus' : 'multiple-marked']"
                class="ch-tool-icon"
                aria-hidden="true"
              />
              <RouterLink
                v-if="data.canAnswer"
                :to="buildAnswerRoute(data)"
                class="font-semibold text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
              >
                {{ displayText(data.title, t("Untitled")) }}
              </RouterLink>
              <span
                v-else
                class="font-semibold text-gray-90"
              >
                {{ displayText(data.title, t("Untitled")) }}
              </span>
            </div>
            <p
              v-if="displayText(data.subtitle)"
              class="mt-1 text-xs text-gray-500"
            >
              {{ displayText(data.subtitle) }}
            </p>
            <p
              v-if="data.unsupportedReason"
              class="mt-1 text-xs text-orange-700"
            >
              {{ t(data.unsupportedReason) }}
            </p>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
              <span
                v-if="!data.visible"
                class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
              >
                {{ t("Hidden") }}
              </span>
              <span
                v-if="data.anonymous"
                class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
              >
                {{ t("Anonymous") }}
              </span>
              <span
                v-if="data.mandatory"
                class="rounded-full bg-red-100 px-2 py-0.5 text-red-700"
              >
                {{ t("Mandatory") }}
              </span>
              <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700">
                {{ t(data.surveyTypeLabel || "Regular survey") }}
              </span>
              <span
                v-if="data.isUnsupportedPersonality"
                class="rounded-full bg-orange-100 px-2 py-0.5 text-orange-700"
              >
                {{ t("Unsupported") }}
              </span>
            </div>
          </div>
        </template>
      </Column>

      <Column
        :header="t('Code')"
        field="code"
        sortable
      >
        <template #body="{ data }">
          <span class="font-mono text-xs text-gray-700">{{ data.code || "-" }}</span>
        </template>
      </Column>

      <Column :header="t('Dates')">
        <template #body="{ data }">
          <div class="space-y-1 text-xs text-gray-600">
            <div>
              <span class="font-semibold text-gray-700">{{ t("Available from") }}:</span>
              {{ formatDate(data.availableFrom) }}
            </div>
            <div>
              <span class="font-semibold text-gray-700">{{ t("Until") }}:</span>
              {{ formatDate(data.availableUntil) }}
            </div>
          </div>
        </template>
      </Column>

      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            :class="['rounded-full px-2 py-1 text-xs font-semibold', availabilityBadgeClass(data.availabilityStatus)]"
          >
            {{ availabilityLabel(data.availabilityStatus) }}
          </span>
        </template>
      </Column>

      <Column
        v-if="canManage"
        :header="t('Questions')"
        field="questionCount"
        sortable
      >
        <template #body="{ data }">
          <span class="font-semibold text-gray-800">{{ data.questionCount ?? "-" }}</span>
        </template>
      </Column>

      <Column
        v-if="canManage"
        :header="t('Invitees')"
        field="invited"
        sortable
      >
        <template #body="{ data }">
          <div class="space-y-1 text-sm">
            <div>
              {{ t("Invited") }}: <span class="font-semibold">{{ data.invited || 0 }}</span>
            </div>
            <div>
              {{ t("Answered") }}: <span class="font-semibold">{{ data.answered || 0 }}</span>
            </div>
          </div>
        </template>
      </Column>

      <Column
        :header="t('Actions')"
        class="w-56"
      >
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              v-if="data.canAnswer"
              :label="t('Answer')"
              :route="buildAnswerRoute(data)"
              icon="reply"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canEdit && data.surveyType !== 3"
              :label="t('Edit questions')"
              :route="buildQuestionsRoute(data)"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canEdit && data.surveyType === 3"
              :label="t('Edit questions')"
              :route="buildMeetingEditRoute(data)"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canConfigure && data.surveyType !== 3"
              :label="t('Configure')"
              :route="buildConfigureRoute(data)"
              icon="settings"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canConfigure && data.surveyType === 3"
              :label="t('Configure')"
              :route="buildMeetingEditRoute(data)"
              icon="settings"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canPreview"
              :label="t('Preview')"
              :route="buildPreviewRoute(data)"
              icon="play-box-outline"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canInvite"
              :label="t('Publish')"
              :route="buildInviteRoute(data)"
              icon="send"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canReport"
              :label="t('Reporting')"
              :route="data.surveyType === 3 ? buildMeetingRoute(data) : buildReportingRoute(data)"
              icon="tracking"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.canCopy"
              :label="t('Copy survey')"
              :route="buildCopyRoute(data)"
              icon="copy"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.canDuplicate"
              :label="t('Duplicate survey')"
              icon="file-add"
              only-icon
              size="small"
              type="secondary-text"
              @click="confirmSurveyAction(data, 'duplicate')"
            />
            <BaseButton
              v-if="data.canMultiplicate"
              :label="t('Multiply questions')"
              icon="multiple-marked"
              only-icon
              size="small"
              type="secondary-text"
              @click="confirmSurveyAction(data, 'multiplicate')"
            />
            <BaseButton
              v-if="data.canMultiplicate"
              :label="t('Remove multiplied questions')"
              icon="clear-all"
              only-icon
              size="small"
              type="secondary-text"
              @click="confirmSurveyAction(data, 'remove-multiplicate')"
            />
            <BaseButton
              v-if="data.canSendToTutors"
              :label="t('Publish for group tutors')"
              icon="account-check"
              only-icon
              size="small"
              type="primary-text"
              @click="confirmSurveyAction(data, 'send-to-tutors')"
            />
            <BaseButton
              v-if="data.canEmpty"
              :label="t('Empty survey')"
              icon="clear-all"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmSurveyAction(data, 'empty')"
            />
            <BaseButton
              v-if="data.canDelete"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmSurveyAction(data, 'delete')"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { chamiloIconToClass } from "../../components/basecomponents/ChamiloIcons"
import { useConfirmation } from "../../composables/useConfirmation"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const surveys = ref([])
const settings = ref({})
const canManage = ref(false)
const canCreate = ref(false)
const isLoading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const selectedSurveyIds = ref([])
const searchTerm = ref(getSearchQuery())
const isSearchVisible = ref(Boolean(getSearchQuery()))
const activeSearch = computed(() => getSearchQuery())
const selectableSurveys = computed(() => surveys.value.filter((survey) => survey.canDelete))
const selectedSurveys = computed(() =>
  surveys.value.filter((survey) => selectedSurveyIds.value.includes(Number(survey.iid))),
)
const areAllSelectableSurveysSelected = computed(
  () =>
    selectableSurveys.value.length > 0 &&
    selectableSurveys.value.every((survey) => selectedSurveyIds.value.includes(Number(survey.iid))),
)
const isSelectionIndeterminate = computed(
  () => selectedSurveyIds.value.length > 0 && !areAllSelectableSurveysSelected.value,
)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
  }
}

function getSearchQuery() {
  return String(getQueryValue(route.query.search) || "").trim()
}

function getListParams() {
  const params = { ...getContextParams() }
  const search = getSearchQuery()

  if (search) {
    params.search = search
  }

  return params
}

function buildCreateRoute() {
  return {
    name: "SurveyCreate",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function buildMeetingCreateRoute() {
  return {
    name: "SurveyMeetingCreate",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function buildMeetingRoute(survey) {
  return {
    name: "SurveyMeeting",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: {
      ...getContextParams(),
      invitationCode: survey.invitationCode || undefined,
    },
  }
}

function buildMeetingEditRoute(survey) {
  return {
    name: "SurveyMeetingEdit",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function buildQuestionsRoute(survey) {
  return {
    name: "SurveyQuestions",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function buildReportingRoute(survey) {
  return {
    name: "SurveyReporting",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function buildInviteRoute(survey) {
  return {
    name: "SurveyInvitations",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function buildCopyRoute(survey) {
  return {
    name: "SurveyCopy",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function buildPreviewRoute(survey) {
  if (survey.surveyType === 3) {
    return {
      name: "SurveyMeeting",
      params: {
        node: route.params.node,
        surveyId: survey.iid,
      },
      query: {
        ...getContextParams(),
        preview: 1,
      },
    }
  }

  return {
    name: "SurveyPreview",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: {
      ...getContextParams(),
      preview: 1,
    },
  }
}

function buildAnswerRoute(survey) {
  if (survey.surveyType === 3) {
    return buildMeetingRoute(survey)
  }

  return {
    name: "SurveyAnswer",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: {
      ...getContextParams(),
      invitationCode: survey.invitationCode || undefined,
    },
  }
}

function buildConfigureRoute(survey) {
  if (survey.surveyType === 3) {
    return null
  }

  return {
    name: "SurveyEdit",
    params: {
      node: route.params.node,
      surveyId: survey.iid,
    },
    query: getContextParams(),
  }
}

function toggleSearchForm() {
  isSearchVisible.value = !isSearchVisible.value
}

function hideSearchForm() {
  searchTerm.value = getSearchQuery()
  isSearchVisible.value = false
}

async function applySearch() {
  const search = searchTerm.value.trim()
  const query = { ...route.query }

  if (search) {
    query.search = search
  } else {
    delete query.search
  }

  await router.push({
    name: route.name,
    params: route.params,
    query,
  })

  isSearchVisible.value = false
}

async function clearSearch() {
  searchTerm.value = ""
  await applySearch()
}

async function loadSurveys() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await surveyService.getSurveyList(getListParams())
    surveys.value = Array.isArray(response.items) ? response.items : []
    settings.value = response.settings || {}
    canManage.value = true === response.canManage
    canCreate.value = true === response.canCreate
    pruneSelection()
  } catch (error) {
    console.error("Error loading surveys", error)
    errorMessage.value = t("Could not load surveys")
  } finally {
    isLoading.value = false
  }
}

function isSurveySelected(surveyId) {
  return selectedSurveyIds.value.includes(Number(surveyId))
}

function toggleSurveySelection(survey, checked) {
  const surveyId = Number(survey.iid)
  if (!survey.canDelete || !surveyId) {
    return
  }

  if (checked && !selectedSurveyIds.value.includes(surveyId)) {
    selectedSurveyIds.value = [...selectedSurveyIds.value, surveyId]
    return
  }

  if (!checked) {
    selectedSurveyIds.value = selectedSurveyIds.value.filter((id) => id !== surveyId)
  }
}

function toggleAllSurveySelection(checked) {
  if (checked) {
    selectedSurveyIds.value = selectableSurveys.value.map((survey) => Number(survey.iid)).filter((id) => id > 0)
    return
  }

  clearSelection()
}

function clearSelection() {
  selectedSurveyIds.value = []
}

function pruneSelection() {
  const availableIds = new Set(selectableSurveys.value.map((survey) => Number(survey.iid)))
  selectedSurveyIds.value = selectedSurveyIds.value.filter((id) => availableIds.has(id))
}

function getBulkActionCsrfToken() {
  return selectedSurveys.value.find((survey) => survey.actionCsrfToken)?.actionCsrfToken || ""
}

function confirmBulkDelete() {
  if (selectedSurveyIds.value.length === 0) {
    return
  }

  requireConfirmation({
    title: t("Delete selected surveys"),
    message: t("The selected surveys and their questions will be deleted. This action cannot be undone."),
    accept: performBulkDelete,
  })
}

async function performBulkDelete() {
  errorMessage.value = ""
  successMessage.value = ""
  isLoading.value = true

  try {
    const response = await surveyService.runSurveyBulkDelete(
      getContextParams(),
      selectedSurveyIds.value,
      getBulkActionCsrfToken(),
    )

    successMessage.value = response.message ? t(response.message) : t("Updated")
    clearSelection()
    await loadSurveys()
  } catch (error) {
    console.error("Error running survey bulk delete", error)
    errorMessage.value =
      error?.response?.data?.detail ||
      error?.response?.data?.["hydra:description"] ||
      t("Could not delete selected surveys")
  } finally {
    isLoading.value = false
  }
}

function getSurveyActionLabel(action) {
  switch (action) {
    case "duplicate":
      return t("Duplicate survey")
    case "empty":
      return t("Empty survey")
    case "multiplicate":
      return t("Multiply questions")
    case "remove-multiplicate":
      return t("Remove multiplied questions")
    case "send-to-tutors":
      return t("Publish for group tutors")
    case "delete":
      return t("Delete survey")
    default:
      return t("Survey action")
  }
}

function getSurveyActionMessage(action, survey) {
  const title = displayText(survey.title, t("Untitled"))

  switch (action) {
    case "duplicate":
      return t("A copy of this survey will be created: {0}", [title])
    case "empty":
      return t("All answers and invitations will be deleted for this survey: {0}", [title])
    case "multiplicate":
      return t("Questions with class or student placeholders will be generated for course classes or groups: {0}", [
        title,
      ])
    case "remove-multiplicate":
      return t("Generated multiplied questions will be removed from this survey: {0}", [title])
    case "send-to-tutors":
      return t("This survey will be published for the linked group tutors: {0}", [title])
    case "delete":
      return t("This survey and its questions will be deleted: {0}", [title])
    default:
      return t("Please confirm your choice")
  }
}

function confirmSurveyAction(survey, action) {
  requireConfirmation({
    title: getSurveyActionLabel(action),
    message: getSurveyActionMessage(action, survey),
    accept: () => performSurveyAction(survey, action),
  })
}

async function performSurveyAction(survey, action) {
  errorMessage.value = ""
  successMessage.value = ""
  isLoading.value = true

  try {
    const response = await surveyService.runSurveyAction(getContextParams(), survey.iid, action, survey.actionCsrfToken)

    successMessage.value = response.message ? t(response.message) : t("Updated")
    await loadSurveys()
  } catch (error) {
    console.error("Error running survey action", error)
    errorMessage.value =
      error?.response?.data?.detail ||
      error?.response?.data?.["hydra:description"] ||
      t("Could not complete survey action")
  } finally {
    isLoading.value = false
  }
}

function formatDate(date) {
  if (!date) {
    return t("No date")
  }

  const parsedDate = new Date(date)
  if (Number.isNaN(parsedDate.getTime())) {
    return t("No date")
  }

  return parsedDate.toLocaleString()
}

function availabilityLabel(status) {
  switch (status) {
    case "not_started":
      return t("Not started")
    case "closed":
      return t("Closed")
    default:
      return t("Open")
  }
}

function availabilityBadgeClass(status) {
  switch (status) {
    case "not_started":
      return "bg-blue-100 text-blue-700"
    case "closed":
      return "bg-gray-100 text-gray-700"
    default:
      return "bg-green-100 text-green-700"
  }
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

onMounted(loadSurveys)

watch(
  () => route.query,
  () => {
    searchTerm.value = getSearchQuery()
    loadSurveys()
  },
)
</script>
<style scoped>
:deep(.survey-list-toolbar__button) {
  min-width: 2.25rem;
  width: 2.25rem;
  height: 2.25rem;
}

:deep(.survey-list-toolbar__button .p-button-icon) {
  font-size: 1.1rem;
}
</style>
