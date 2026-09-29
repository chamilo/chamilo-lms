<script setup>
import { ref, reactive, computed, onMounted } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import globalReportingService from "../../services/globalReportingService"
import accessUrlAdminService from "../../services/accessUrlAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const route = useRoute()

const userId = computed(() => Number(route.params.id || 0))

const isLoading = ref(true)
const errorMessage = ref("")
const report = reactive({
  items: [],
  meta: {},
})
const urlBlocks = ref([])

const user = computed(() => report.meta.user || {})

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

function coursesForBlock(block) {
  return block.rows
    .map((row) => {
      const item = report.items.find((i) => i.id === row.courseId && (i.sessionId || 0) === row.sessionId)

      return item ? { ...item, sessionTitle: row.sessionTitle, rowKey: `${row.courseId}-${row.sessionId}` } : null
    })
    .filter(Boolean)
}

function statsForBlock(block) {
  const courses = coursesForBlock(block)
  const count = courses.length
  const totalTimeSeconds = courses.reduce((sum, item) => sum + Number(item.timeSeconds || 0), 0)
  const avgProgress = count ? courses.reduce((sum, item) => sum + Number(item.learningPathProgress || 0), 0) / count : 0
  const avgScore = count ? courses.reduce((sum, item) => sum + Number(item.score || 0), 0) / count : 0

  return { count, totalTimeSeconds, avgProgress, avgScore }
}

function courseDetailRoute(course) {
  return {
    name: "GlobalReportingLearnerCourseDetail",
    params: {
      userId: userId.value,
      courseId: course.id,
    },
    query: {
      sid: course.sessionId || 0,
    },
  }
}

async function loadReport() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const response = await globalReportingService.getSection("learner-detail", { userId: userId.value })
    report.items = response.items || []
    report.meta = response.meta || {}

    const pairs = report.items.map((item) => ({ courseId: item.id, sessionId: item.sessionId || 0 }))
    const urlData = await accessUrlAdminService.getUserUrls(userId.value, pairs)
    urlBlocks.value = urlData.urls || []
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
    <SectionHeader :title="t('User details')">
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
      <div class="flex flex-wrap items-center gap-6">
        <BaseUserAvatar
          :image-url="user.pictureUrl"
          :alt="user.fullName"
          size="xlarge"
        />
        <div class="flex flex-col gap-1">
          <h2 class="text-xl font-semibold">
            {{ user.fullName }}
            <span
              v-if="user.username"
              class="font-normal text-gray-500"
              >({{ user.username }})</span
            >
          </h2>
          <p
            v-if="user.email"
            class="text-sm text-gray-500"
          >
            {{ user.email }}
          </p>
        </div>
        <dl class="ml-auto grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
          <div>
            <dt class="text-gray-500">{{ t("Status") }}</dt>
            <dd class="font-medium">{{ t(user.status || "User") }}</dd>
          </div>
          <div>
            <dt class="text-gray-500">{{ t("Official code") }}</dt>
            <dd class="font-medium">{{ user.officialCode || "-" }}</dd>
          </div>
          <div>
            <dt class="text-gray-500">{{ t("Phone") }}</dt>
            <dd class="font-medium">{{ user.phone || "-" }}</dd>
          </div>
          <div v-if="user.timezone">
            <dt class="text-gray-500">{{ t("Time zone") }}</dt>
            <dd class="font-medium">{{ user.timezone }}</dd>
          </div>
        </dl>
      </div>
    </div>

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

      <dl class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
          <dt class="text-sm text-gray-500">{{ t("Courses") }}</dt>
          <dd class="text-lg font-medium">{{ statsForBlock(block).count }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Average progress") }}</dt>
          <dd class="text-lg font-medium">{{ formatPercent(statsForBlock(block).avgProgress) }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Average score") }}</dt>
          <dd class="text-lg font-medium">{{ formatPercent(statsForBlock(block).avgScore) }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Total time") }}</dt>
          <dd class="text-lg font-medium">{{ formatDuration(statsForBlock(block).totalTimeSeconds) }}</dd>
        </div>
      </dl>

      <BaseTable
        :values="coursesForBlock(block)"
        :total-items="coursesForBlock(block).length"
        :is-loading="isLoading"
        :lazy="false"
        data-key="rowKey"
        :text-for-empty="t('No results found')"
      >
        <Column
          field="title"
          :header="t('Course')"
        />
        <Column :header="t('Session')">
          <template #body="{ data }">{{ data.sessionTitle || t("Direct") }}</template>
        </Column>
        <Column
          field="timeSeconds"
          :header="t('Time')"
        >
          <template #body="{ data }">{{ formatDuration(data.timeSeconds) }}</template>
        </Column>
        <Column
          field="learningPathProgress"
          :header="t('Progress')"
        >
          <template #body="{ data }">{{ formatPercent(data.learningPathProgress) }}</template>
        </Column>
        <Column
          field="score"
          :header="t('Score')"
        >
          <template #body="{ data }">{{ formatPercent(data.score) }}</template>
        </Column>
        <Column
          field="absences"
          :header="t('Absences')"
        >
          <template #body="{ data }">{{ data.absences || "-" }}</template>
        </Column>
        <Column
          field="gradebook"
          :header="t('Gradebooks')"
        >
          <template #body="{ data }">{{ data.gradebook || "-" }}</template>
        </Column>
        <Column :header="t('Details')">
          <template #body="{ data }">
            <BaseButton
              :label="t('Details')"
              icon="next"
              only-icon
              size="small"
              type="primary-alternative"
              :route="courseDetailRoute(data)"
            />
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>
</template>
