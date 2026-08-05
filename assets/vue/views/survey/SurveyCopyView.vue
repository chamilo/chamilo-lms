<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-90">{{ t("Copy survey") }}</h1>
        <p class="mt-1 text-sm text-gray-60">
          {{ t("Copy this survey to another course or session") }}
        </p>
      </div>

      <BaseButton
        :label="t('Back to surveys')"
        :route="buildListRoute()"
        icon="back"
        type="primary-text"
      />
    </div>

    <div class="border-b border-gray-20" />

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

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,24rem)]">
      <form
        class="space-y-5 rounded-xl border border-gray-25 bg-white p-5 shadow-sm"
        novalidate
        @submit.prevent="confirmCopySurvey"
      >
        <div class="space-y-1">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Target course") }}</h2>
          <p class="text-sm text-gray-60">
            {{ t("The survey will be copied without answers or invitations.") }}
          </p>
        </div>

        <BaseSearchSelect
          v-model="selectedTargetKey"
          :clearable="true"
          :empty-message="t('No target courses found')"
          :filter-fields="['label', 'sublabel']"
          :hint="t('Search by course title, course code or session name.')"
          :label="t('Select target course')"
          :loading="isLoading"
          :options="targetOptions"
          :placeholder="t('Select target course')"
          input-id="survey-copy-target"
          name="destination_course"
          @filter="handleTargetFilter"
        />

        <p class="text-xs text-gray-50">
          {{ t("Use Duplicate survey when you want a copy in the same course.") }}
        </p>

        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 pt-4">
          <BaseButton
            :label="t('Cancel')"
            :route="buildListRoute()"
            type="secondary"
          />
          <BaseButton
            :disabled="!selectedTarget"
            :is-loading="isSaving"
            :label="t('Copy survey')"
            icon="copy"
            type="success"
            @click="confirmCopySurvey"
          />
        </div>
      </form>

      <aside class="space-y-4 rounded-xl border border-gray-25 bg-white p-5 shadow-sm">
        <div>
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Survey") }}</h2>
          <div
            v-if="survey.iid"
            class="mt-3 space-y-2 text-sm"
          >
            <div class="flex items-start gap-2">
              <BaseIcon
                icon="multiple-marked"
                size="small"
              />
              <div>
                <div class="font-semibold text-gray-90">{{ displayText(survey.title, t("Untitled")) }}</div>
                <div class="font-mono text-xs text-gray-50">{{ survey.code || '-' }}</div>
              </div>
            </div>
          </div>
          <div
            v-else
            class="mt-3 text-sm text-gray-50"
          >
            {{ t("Loading") }}...
          </div>
        </div>

        <div class="rounded-lg bg-gray-10 p-3 text-xs text-gray-60">
          {{ t("Questions and options will be copied. Answers, invitations and reports will stay only in the original survey.") }}
        </div>

        <div
          v-if="selectedTarget"
          class="rounded-lg border border-gray-20 p-3 text-sm"
        >
          <div class="text-xs font-semibold uppercase text-gray-50">{{ t("Selected target") }}</div>
          <div class="mt-1 font-semibold text-gray-90">{{ selectedTarget.label }}</div>
          <div
            v-if="selectedTarget.sublabel"
            class="text-xs text-gray-50"
          >
            {{ selectedTarget.sublabel }}
          </div>
        </div>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseSearchSelect from "../../components/basecomponents/BaseSearchSelect.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const survey = ref({})
const targets = ref([])
const selectedTargetKey = ref("")
const csrfToken = ref("")
const searchQuery = ref("")
const errorMessage = ref("")
const successMessage = ref("")
const isLoading = ref(false)
const isSaving = ref(false)
let searchTimer = null

const surveyId = computed(() => Number(route.params.surveyId || 0))
const targetOptions = computed(() => targets.value.map((target) => ({ ...target })))
const selectedTarget = computed(() => targets.value.find((target) => String(target.id) === String(selectedTargetKey.value)) || null)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams(extra = {}) {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    ...extra,
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

async function loadCopyForm() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await surveyService.getSurveyCopy(
      getContextParams({ q: searchQuery.value.trim() }),
      surveyId.value,
    )

    survey.value = response.survey || {}
    targets.value = Array.isArray(response.targets) ? response.targets : []
    csrfToken.value = response.csrfToken || ""

    if (selectedTargetKey.value && !targets.value.some((target) => String(target.id) === String(selectedTargetKey.value))) {
      selectedTargetKey.value = ""
    }
  } catch (error) {
    console.error("Error loading survey copy form", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not load copy form")
  } finally {
    isLoading.value = false
  }
}

function handleTargetFilter(value) {
  searchQuery.value = String(value || "").trim()

  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    loadCopyForm()
  }, 350)
}

function confirmCopySurvey() {
  if (!selectedTarget.value || isSaving.value) {
    return
  }

  requireConfirmation({
    title: t("Copy survey"),
    message: t("The survey will be copied to: {0}", [selectedTarget.value.label]),
    accept: () => copySurvey(),
  })
}

async function copySurvey() {
  if (!selectedTarget.value) {
    return
  }

  isSaving.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await surveyService.copySurveyToTarget(
      {
        targetCourseId: selectedTarget.value.targetCourseId,
        targetSessionId: selectedTarget.value.targetSessionId || 0,
        csrfToken: csrfToken.value,
      },
      getContextParams(),
      surveyId.value,
    )

    successMessage.value = response.message ? t(response.message) : t("Survey copied.")
    selectedTargetKey.value = ""
    await loadCopyForm()
  } catch (error) {
    console.error("Error copying survey", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not copy survey")
  } finally {
    isSaving.value = false
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

onMounted(loadCopyForm)

onBeforeUnmount(() => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
})

watch(
  () => route.query,
  () => loadCopyForm(),
)

watch(
  () => route.params.surveyId,
  () => loadCopyForm(),
)
</script>
