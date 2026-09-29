<script setup>
import { ref, reactive, computed, onMounted } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import accessUrlAdminService from "../../services/accessUrlAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const route = useRoute()

const courseId = computed(() => Number(route.params.id || 0))

const isLoading = ref(true)
const errorMessage = ref("")
const course = reactive({ id: 0, title: "", code: "" })
const metrics = reactive({
  learners: 0,
  totalTimeSeconds: 0,
  avgProgress: 0,
  avgScore: 0,
  sessionsCount: 0,
  urlsCount: 0,
})
const urlBlocks = ref([])

function formatDuration(value) {
  const seconds = Math.max(0, Number(value || 0))
  return [Math.floor(seconds / 3600), Math.floor((seconds % 3600) / 60), Math.floor(seconds % 60)]
    .map((part) => String(part).padStart(2, "0"))
    .join(":")
}

function formatPercent(value) {
  return `${Number(value || 0)
    .toFixed(2)
    .replace(/\.00$/u, "")}%`
}

function rowsForBlock(block) {
  const rows = []
  if (block.direct) {
    rows.push({
      key: "direct",
      session: t("Direct"),
      startDate: "",
      endDate: "",
      learners: block.direct.learners,
      totalTimeSeconds: block.direct.totalTimeSeconds,
      avgProgress: block.direct.avgProgress,
      avgScore: block.direct.avgScore,
    })
  }
  block.sessions.forEach((session) => {
    rows.push({
      key: `session-${session.id}`,
      session: session.title,
      startDate: session.displayStartDate || "",
      endDate: session.displayEndDate || "",
      learners: session.learners,
      totalTimeSeconds: session.totalTimeSeconds,
      avgProgress: session.avgProgress,
      avgScore: session.avgScore,
    })
  })

  return rows
}

async function loadReport() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const response = await accessUrlAdminService.getCourseDetail(courseId.value)
    Object.assign(course, response.course || {})
    Object.assign(metrics, response.metrics || {})
    urlBlocks.value = response.urls || []
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadReport)
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Course details')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminMultiUrlList' }"
      />
    </SectionHeader>

    <div
      v-if="errorMessage"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <h2 class="mb-4 text-xl font-semibold">
        {{ course.title }} <span class="text-gray-500">({{ course.code }})</span>
      </h2>
      <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div>
          <dt class="text-sm text-gray-500">{{ t("Learners") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : metrics.learners }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Total time") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : formatDuration(metrics.totalTimeSeconds) }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Average progress") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : formatPercent(metrics.avgProgress) }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Average score") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : formatPercent(metrics.avgScore) }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Sessions") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : metrics.sessionsCount }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("URLs") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : metrics.urlsCount }}</dd>
        </div>
      </dl>
    </div>

    <p class="text-sm text-gray-500">
      {{
        t(
          "Direct enrollment belongs to the course as a whole, not to a specific URL, so the same 'Direct' figures are repeated below for every URL this course is linked to. Sessions are the intended way to share a course across URLs with metrics that actually differ per URL.",
        )
      }}
    </p>

    <div
      v-for="block in urlBlocks"
      :key="block.id"
      class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <h2 class="mb-4 text-lg font-semibold">
        <a
          :href="block.url"
          target="_blank"
          rel="noopener noreferrer"
          class="text-blue-600 hover:underline"
        >
          {{ block.url }}
        </a>
      </h2>

      <BaseTable
        :values="rowsForBlock(block)"
        :total-items="rowsForBlock(block).length"
        :is-loading="isLoading"
        :lazy="false"
        data-key="key"
        :text-for-empty="t('No results found')"
      >
        <Column
          field="session"
          :header="t('Session')"
        />
        <Column
          field="startDate"
          :header="t('Start date')"
        >
          <template #body="{ data }">{{ data.startDate || "-" }}</template>
        </Column>
        <Column
          field="endDate"
          :header="t('End date')"
        >
          <template #body="{ data }">{{ data.endDate || "-" }}</template>
        </Column>
        <Column
          field="learners"
          :header="t('Learners')"
        />
        <Column
          field="totalTimeSeconds"
          :header="t('Total time')"
        >
          <template #body="{ data }">{{ formatDuration(data.totalTimeSeconds) }}</template>
        </Column>
        <Column
          field="avgProgress"
          :header="t('Average progress')"
        >
          <template #body="{ data }">{{ formatPercent(data.avgProgress) }}</template>
        </Column>
        <Column
          field="avgScore"
          :header="t('Average score')"
        >
          <template #body="{ data }">{{ formatPercent(data.avgScore) }}</template>
        </Column>
      </BaseTable>
    </div>
  </div>
</template>
