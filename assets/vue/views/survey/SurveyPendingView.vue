<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-90">
          {{ t("Pending surveys") }}
        </h1>
      </div>

      <BaseButton
        :is-loading="isLoading"
        :label="t('Refresh')"
        icon="refresh"
        type="primary"
        @click="loadPendingSurveys"
      />
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No surveys found')"
      :total-items="pendingSurveys.length"
      :values="pendingSurveys"
      data-key="iid"
    >
      <Column
        :header="t('Survey')"
        field="title"
        sortable
      >
        <template #body="{ data }">
          <div class="min-w-64">
            <div class="flex items-center gap-2">
              <BaseIcon
                :icon="data.surveyType === 3 ? 'calendar-plus' : 'multiple-marked'"
                size="small"
              />
              <span class="font-semibold text-gray-90">
                {{ displayText(data.title, t("Untitled")) }}
              </span>
            </div>
            <p
              v-if="displayText(data.subtitle)"
              class="mt-1 text-xs text-gray-500"
            >
              {{ displayText(data.subtitle) }}
            </p>
            <span class="mt-2 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">
              {{ t(data.surveyTypeLabel || "Regular survey") }}
            </span>
          </div>
        </template>
      </Column>

      <Column :header="t('Course')">
        <template #body="{ data }">
          <div class="text-sm text-gray-700">
            <span class="font-semibold">{{ displayText(data.course?.title, "-") }}</span>
            <p
              v-if="data.course?.code"
              class="text-xs text-gray-500"
            >
              {{ data.course.code }}
            </p>
          </div>
        </template>
      </Column>

      <Column :header="t('Session')">
        <template #body="{ data }">
          <span class="text-sm text-gray-700">
            {{ displayText(data.session?.title, "-") }}
          </span>
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

      <Column class="w-20">
        <template #body="{ data }">
          <BaseButton
            :label="t('Answer survey')"
            :route="buildAnswerRoute(data)"
            icon="check"
            only-icon
            size="small"
            type="primary-text"
          />
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import surveyService from "../../services/surveyService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"

const { t } = useI18n()

const pendingSurveys = ref([])
const isLoading = ref(false)
const errorMessage = ref("")

function buildAnswerRoute(item) {
  return {
    name: item.routeName || (item.surveyType === 3 ? "SurveyMeeting" : "SurveyAnswer"),
    params: {
      node: item.nodeId,
      surveyId: item.surveyId,
    },
    query: cleanQuery({
      cid: item.course?.id,
      sid: item.session?.id,
      gid: item.group?.id,
      invitationCode: item.invitationCode,
    }),
  }
}

function cleanQuery(query) {
  const clean = {}

  Object.entries(query).forEach(([key, value]) => {
    if (value !== undefined && value !== null && String(value) !== "") {
      clean[key] = value
    }
  })

  return clean
}

function displayText(value, fallback = "") {
  if (!value) {
    return fallback
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value).replace(/<[^>]*>/g, " ")

  return textarea.value.replace(/\s+/g, " ").trim() || fallback
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

async function loadPendingSurveys() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await surveyService.getPendingSurveys()
    pendingSurveys.value = Array.isArray(data.items) ? data.items : []
  } catch (error) {
    console.error("Error loading pending surveys", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not load survey")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadPendingSurveys)
</script>
