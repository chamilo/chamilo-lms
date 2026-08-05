<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-90">{{ t("Survey reporting") }}</h1>
        <p
          v-if="report.survey?.title"
          class="mt-1 text-sm text-gray-600"
        >
          {{ report.survey.title }}
        </p>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-1">
        <BaseButton
          :label="t('Back to surveys')"
          :route="buildListRoute()"
          icon="back"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="report.canExport"
          :label="t('Export CSV')"
          :to-url="csvExportUrl"
          icon="file-delimited-outline"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="report.canExport"
          :label="t('Export compact CSV')"
          :to-url="compactCsvExportUrl"
          icon="zip-pack"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="report.canExport"
          :label="t('Export Excel')"
          :to-url="xlsxExportUrl"
          icon="file-excel"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="report.canExport"
          :label="t('Export by class')"
          :to-url="byClassXlsxExportUrl"
          icon="join-group"
          only-icon
          size="small"
          type="primary-text"
        />
        <BaseButton
          v-if="report.canExport"
          :label="t('Export package')"
          :to-url="zipExportUrl"
          icon="download"
          only-icon
          size="small"
          type="primary-text"
        />
      </div>
    </div>

    <div class="border-b border-gray-20" />

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div class="grid gap-4 md:grid-cols-4">
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Invited") }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-90">{{ report.counts?.invited || 0 }}</p>
      </div>
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Answered") }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-90">{{ report.counts?.answered || 0 }}</p>
      </div>
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Pending") }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-90">{{ report.counts?.pending || 0 }}</p>
      </div>
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Questions") }}</p>
        <p class="mt-2 text-3xl font-bold text-gray-90">{{ report.counts?.questions || 0 }}</p>
      </div>
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div class="flex flex-wrap gap-2">
        <BaseButton
          v-for="item in reportTypes"
          :key="item.key"
          :label="t(item.label)"
          :type="activeReport === item.key ? 'primary' : 'plain'"
          @click="activeReport = item.key"
        />
      </div>
      <p class="mt-3 text-sm text-gray-500">
        {{ activeReportDescription }}
      </p>
    </div>

    <section
      v-if="activeReport === 'overview'"
      class="space-y-4"
    >
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-90">{{ t("Results summary") }}</h2>
        <p class="mt-2 text-sm text-gray-600">
          {{ t("This view shows the migrated reporting data for this survey.") }}
        </p>
      </div>
      <QuestionReportList :items="report.questionReports || []" />
    </section>

    <section
      v-if="activeReport === 'question'"
      class="space-y-4"
    >
      <QuestionReportList :items="report.questionReports || []" />
    </section>

    <section
      v-if="activeReport === 'user'"
      class="space-y-4"
    >
      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <label
          class="mb-2 block text-sm font-semibold text-gray-700"
          for="survey-report-user"
        >
          {{ t("Select user who filled the survey") }}
        </label>
        <select
          id="survey-report-user"
          v-model="selectedUser"
          class="w-full rounded border border-gray-300 px-3 py-2 text-sm md:w-96"
          name="survey_report_user"
          @change="reloadForUser"
        >
          <option value="">{{ t("Select user") }}</option>
          <option
            v-for="user in report.users || []"
            :key="user.key"
            :value="user.key"
          >
            {{ user.label }}
          </option>
        </select>
      </div>

      <BaseTable
        :text-for-empty="t('No answers found')"
        :total-items="report.userAnswers?.length || 0"
        :values="report.userAnswers || []"
        data-key="questionId"
      >
        <Column
          :header="t('Question')"
          field="question"
        />
        <Column :header="t('Answer')">
          <template #body="{ data }">
            <span>{{ data.answer || '-' }}</span>
          </template>
        </Column>
      </BaseTable>
    </section>

    <section
      v-if="activeReport === 'complete'"
      class="space-y-4"
    >
      <BaseTable
        :text-for-empty="t('No answers found')"
        :total-items="report.completeRows?.length || 0"
        :values="report.completeRows || []"
        data-key="userKey"
      >
        <Column
          :header="t('User')"
          field="userLabel"
        />
        <Column :header="t('Answers')">
          <template #body="{ data }">
            <div class="space-y-1 text-sm">
              <div
                v-for="question in report.questionReports || []"
                :key="question.id"
              >
                <span class="font-semibold">{{ question.title }}:</span>
                <span>{{ data.answers?.[question.id] || '-' }}</span>
              </div>
            </div>
          </template>
        </Column>
      </BaseTable>
    </section>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const report = ref({})
const activeReport = ref("overview")
const selectedUser = ref(route.query.user ? String(route.query.user) : "")
const isLoading = ref(false)
const errorMessage = ref("")

const reportTypes = computed(() => report.value.reportTypes || [])
const activeReportDescription = computed(() => {
  const item = reportTypes.value.find((entry) => entry.key === activeReport.value)

  return item ? t(item.description) : ""
})
const csvExportUrl = computed(() => surveyService.buildSurveyReportingCsvUrl(getContextParams(), route.params.surveyId))
const compactCsvExportUrl = computed(() => surveyService.buildSurveyReportingCompactCsvUrl(getContextParams(), route.params.surveyId))
const xlsxExportUrl = computed(() => surveyService.buildSurveyReportingXlsxUrl(getContextParams(), route.params.surveyId))
const byClassXlsxExportUrl = computed(() => surveyService.buildSurveyReportingByClassXlsxUrl(getContextParams(), route.params.surveyId))
const zipExportUrl = computed(() => surveyService.buildSurveyReportingZipUrl(getContextParams(), route.params.surveyId))

const QuestionReportList = defineComponent({
  name: "QuestionReportList",
  props: {
    items: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    return () =>
      h(
        "div",
        { class: "space-y-4" },
        props.items.map((question) =>
          h("article", { class: "rounded-xl border border-gray-20 bg-white p-4 shadow-sm" }, [
            h("div", { class: "mb-3 flex items-start gap-3" }, [
              h(BaseIcon, { icon: "chart-bar", size: "small" }),
              h("div", null, [
                h("h3", { class: "text-lg font-semibold text-gray-90" }, question.title),
                question.comment ? h("p", { class: "text-sm text-gray-500" }, question.comment) : null,
                h("p", { class: "text-xs text-gray-500" }, `${question.typeLabel} · ${question.totalAnswers} ${t("answers")}`),
              ]),
            ]),
            renderQuestionBody(question),
          ]),
        ),
      )
  },
})

function renderQuestionBody(question) {
  if (Array.isArray(question.scoreRows) && question.scoreRows.length > 0) {
    return h(
      "div",
      { class: "space-y-3" },
      question.scoreRows.map((row) =>
        h("div", { class: "rounded-lg border border-gray-20 p-3" }, [
          h("div", { class: "mb-2 font-semibold" }, row.optionLabel),
          h(
            "div",
            { class: "grid gap-2 md:grid-cols-5" },
            row.distribution.map((item) =>
              h("div", { class: "rounded bg-gray-15 p-2 text-sm" }, `${t("Score")} ${item.score}: ${item.count}`),
            ),
          ),
        ]),
      ),
    )
  }

  if (Array.isArray(question.textAnswers) && question.textAnswers.length > 0) {
    return h(
      "ul",
      { class: "space-y-2" },
      question.textAnswers.map((answer) => h("li", { class: "rounded bg-gray-15 p-3 text-sm" }, answer.text)),
    )
  }

  if (Array.isArray(question.options) && question.options.length > 0) {
    return h(
      "div",
      { class: "space-y-2" },
      question.options.map((option) =>
        h("div", { class: "rounded-lg border border-gray-20 p-3" }, [
          h("div", { class: "flex items-center justify-between gap-4" }, [
            h("span", { class: "font-medium" }, option.label),
            h("span", { class: "text-sm text-gray-600" }, `${option.count} · ${option.percentage}%`),
          ]),
          h("div", { class: "mt-2 h-2 rounded bg-gray-15" }, [
            h("div", {
              class: "h-2 rounded bg-primary",
              style: { width: `${Math.min(100, Math.max(0, option.percentage))}%` },
            }),
          ]),
        ]),
      ),
    )
  }

  return h("p", { class: "text-sm text-gray-500" }, t("No answers found"))
}

function getContextParams() {
  return {
    cid: route.query.cid,
    sid: route.query.sid,
    gid: route.query.gid,
    user: selectedUser.value || undefined,
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: {
      cid: route.query.cid,
      sid: route.query.sid,
      gid: route.query.gid,
    },
  }
}

async function loadReporting() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    report.value = await surveyService.getSurveyReporting(getContextParams(), route.params.surveyId)
  } catch (error) {
    console.error("Error loading survey reporting", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not load survey reporting")
  } finally {
    isLoading.value = false
  }
}

function reloadForUser() {
  router.replace({
    name: "SurveyReporting",
    params: route.params,
    query: {
      ...route.query,
      user: selectedUser.value || undefined,
    },
  })
  loadReporting()
}

onMounted(loadReporting)

watch(
  () => route.query,
  () => {
    selectedUser.value = route.query.user ? String(route.query.user) : ""
    loadReporting()
  },
)
</script>
