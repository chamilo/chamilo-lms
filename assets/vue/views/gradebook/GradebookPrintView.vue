<template>
  <section class="space-y-4">
    <div class="print:hidden flex items-center gap-2">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="primary-text"
        @click="goBack"
      />
      <BaseButton
        :disabled="isLoading || !payload"
        :label="t('Print')"
        icon="file-text"
        type="primary"
        @click="printPage"
      />
    </div>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
      role="status"
    >
      {{ t("Loading") }}...
    </div>

    <template v-else-if="scope === 'flat' && payload">
      <div>
        <h1 class="text-xl font-semibold text-gray-90">{{ t("List view") }}</h1>
        <p
          v-if="categorySubtitle"
          class="text-sm text-gray-600"
        >
          {{ categorySubtitle }}
        </p>
      </div>

      <BaseTable
        :text-for-empty="t('No results found')"
        :total-items="flatRows.length"
        :values="flatRows"
        data-key="id"
      >
        <Column
          :header="t('First name')"
          field="firstName"
        />
        <Column
          :header="t('Last name')"
          field="lastName"
        />
        <Column
          :header="t('Username')"
          field="username"
        />
        <Column
          :header="t('Official code')"
          field="officialCode"
        />
        <Column
          v-for="field in payload.extraFieldColumns || []"
          :key="`extra-${field.variable}`"
          :header="field.label"
        >
          <template #body="{ data }">
            {{ data.extraFields?.[field.variable] || "-" }}
          </template>
        </Column>
        <Column
          v-for="column in payload.columns || []"
          :key="column.key"
          :header="column.title"
        >
          <template #body="{ data }">
            {{ formatScore(data.scores?.[column.key], payload.settings) }}
          </template>
        </Column>
        <Column :header="t('Total')">
          <template #body="{ data }">
            {{ formatTotal(data.total, payload.settings) }}
          </template>
        </Column>
        <Column
          v-if="payload.settings?.customScoreStandalone"
          :header="t('Ranking')"
          field="customScore"
        />
      </BaseTable>
    </template>

    <template v-else-if="scope === 'evaluation' && payload">
      <div>
        <h1 class="text-xl font-semibold text-gray-90">{{ payload.evaluation?.title || t("Assessment") }}</h1>
        <p class="text-sm text-gray-600">
          {{ t("Maximum score") }}: {{ formatNumber(payload.evaluation?.maxScore, payload.settings) }}
        </p>
      </div>

      <BaseTable
        :text-for-empty="t('No results found')"
        :total-items="evaluationRows.length"
        :values="evaluationRows"
        data-key="userId"
      >
        <Column
          :header="t('Official code')"
          field="officialCode"
        />
        <Column
          :header="t('Username')"
          field="username"
        />
        <Column
          :header="t('First name')"
          field="firstname"
        />
        <Column
          :header="t('Last name')"
          field="lastname"
        />
        <Column :header="t('Score')">
          <template #body="{ data }">
            {{ Number(data.resultId || 0) > 0 ? formatNumber(data.score, payload.settings) : "-" }}
          </template>
        </Column>
        <Column :header="t('Date')">
          <template #body="{ data }">
            {{ formatDate(data.createdAt) }}
          </template>
        </Column>
      </BaseTable>
    </template>

    <template v-else-if="scope === 'learner' && payload">
      <div class="space-y-1">
        <h1 class="text-xl font-semibold text-gray-90">{{ t("Results and feedback") }}</h1>
        <p class="font-semibold text-gray-90">{{ payload.learner?.fullName || t("Learner") }}</p>
        <p class="text-sm text-gray-600">
          {{ payload.learner?.username || "" }}
          <span v-if="payload.learner?.officialCode"> · {{ payload.learner.officialCode }}</span>
        </p>
        <p class="text-sm text-gray-700">
          {{ t("Result") }}: {{ formatTotal(payload.total, payload.settings) }}
        </p>
        <p
          v-if="payload.comment"
          class="whitespace-pre-line text-sm text-gray-700"
        >
          {{ t("Comment") }}: {{ payload.comment }}
        </p>
      </div>

      <BaseTable
        :text-for-empty="t('No data available')"
        :total-items="learnerRows.length"
        :values="learnerRows"
        data-key="rowKey"
      >
        <Column
          :header="t('Assessment')"
          field="title"
        />
        <Column
          :header="t('Category')"
          field="categoryTitle"
        />
        <Column :header="t('Average rating')">
          <template #body="{ data }">
            {{ formatScorePair(data.averageScore, data.averageMaxScore, null, payload.settings) }}
          </template>
        </Column>
        <Column :header="t('Result')">
          <template #body="{ data }">
            {{ formatScorePair(data.score, data.maxScore, data.percentage, payload.settings) }}
          </template>
        </Column>
        <Column
          v-if="payload.settings?.customScoreDisplay"
          :header="t('Skills ranking')"
          field="ranking"
        />
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const payload = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

const scope = computed(() => String(route.params.scope || ""))
const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(payload.value?.category?.title || "").trim()
})
const flatRows = computed(() =>
  (payload.value?.rows || []).map((row) => ({
    ...row,
    id: row.user?.id,
    firstName: row.user?.firstName || "",
    lastName: row.user?.lastName || "",
    username: row.user?.username || "",
    officialCode: row.user?.officialCode || "",
  })),
)
const evaluationRows = computed(() => payload.value?.results || [])
const learnerRows = computed(() =>
  (payload.value?.rows || []).map((row) => ({
    ...row,
    rowKey: `${row.kind}-${row.id}`,
  })),
)

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    if (scope.value === "flat") {
      payload.value = await gradebookService.getReport({
        ...getContextParams(),
        all: true,
        search: getQueryValue(route.query.search) || "",
        sortBy: getQueryValue(route.query.sortBy) || "fullName",
        sortDirection: getQueryValue(route.query.sortDirection) || "asc",
      })
    } else if (scope.value === "evaluation") {
      payload.value = await gradebookService.getEvaluationResults({
        ...getContextParams(),
        evaluationId: Number(route.params.id || 0),
      })
    } else if (scope.value === "learner") {
      const params = getContextParams()
      const userId = Number(route.params.id || 0)
      if (userId > 0) {
        params.userId = userId
      }
      payload.value = await gradebookService.getLearnerReport(params)
    } else {
      throw new Error(t("No data available"))
    }
  } catch (error) {
    console.error("Failed to load Gradebook print view", error)
    payload.value = null
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function numberDecimals(settings) {
  return Math.max(0, Math.min(6, Number(settings?.numberDecimals ?? 2)))
}

function formatNumber(value, settings) {
  if (value === null || value === undefined || value === "") {
    return "-"
  }
  const number = Number(value)

  return Number.isFinite(number) ? number.toFixed(numberDecimals(settings)) : String(value)
}

function formatScore(result, settings) {
  if (!result || result.hasResult !== true) {
    return "-"
  }

  if (typeof result.display === "string" && result.display !== "") {
    return result.display
  }

  return formatScorePair(result.score, result.maxScore, result.percentage, settings)
}

function formatScorePair(score, maxScore, percentage, settings) {
  if ((score === null || score === undefined) && (percentage === null || percentage === undefined)) {
    return "-"
  }

  if (score === null || score === undefined) {
    return `${formatNumber(percentage, settings)}%`
  }

  let value = formatNumber(score, settings)
  if (maxScore !== null && maxScore !== undefined) {
    value += ` / ${formatNumber(maxScore, settings)}`
  }
  if (percentage !== null && percentage !== undefined) {
    value += ` (${formatNumber(percentage, settings)}%)`
  }

  return value
}

function formatTotal(result, settings) {
  if (!result || result.hasResult !== true) {
    return "-"
  }
  if (typeof result.display === "string" && result.display !== "") {
    return result.display
  }

  return formatScorePair(result.score, result.maxScore, result.percentage, settings)
}

function formatDate(value) {
  if (!value) {
    return ""
  }
  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? "" : date.toLocaleString()
}

function goBack() {
  router.back()
}

function printPage() {
  window.print()
}

onMounted(loadData)
watch(
  () => [route.params.scope, route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.categoryId],
  loadData,
)
</script>
