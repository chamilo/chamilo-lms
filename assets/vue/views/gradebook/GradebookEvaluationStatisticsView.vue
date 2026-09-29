<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="data">
      <div class="flex w-fit items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          size="normal"
          type="primary-text"
        />
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex items-center gap-2">
          <BaseIcon
            icon="graph"
            size="normal"
          />
          <h1 class="text-xl font-semibold text-gray-90">
            {{ t("Statistics") }}
          </h1>
        </div>
        <p class="mt-2 text-sm text-gray-600">
          {{ data.evaluation?.title || t("Assessment") }}
        </p>
      </div>

      <div
        v-if="!data.customEnabled || data.rows.length === 0"
        class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      >
        {{ t("Skills ranking") }}: {{ t("Disabled") }}
      </div>

      <BaseTable
        v-else
        :is-loading="isLoading"
        :text-for-empty="t('No data available')"
        :total-items="data.rows.length"
        :values="data.rows"
        data-key="label"
      >
        <Column
          :header="t('Skills ranking')"
          field="label"
        />
        <Column :header="t('Progress')">
          <template #body="{ data: row }">
            <progress
              class="h-3 w-full min-w-48"
              :value="Math.max(0, Math.min(100, Number(row.barPercent || 0)))"
              max="100"
            />
          </template>
        </Column>
        <Column
          :header="t('Users')"
          field="count"
        />
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const data = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

const backRoute = computed(() => ({
  name: "GradebookEvaluationResults",
  params: {
    node: route.params.node,
    evaluationId: route.params.evaluationId,
  },
  query: { ...route.query },
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    evaluationId: Number(route.params.evaluationId || 0),
  }
}

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    data.value = await gradebookService.getEvaluationStatistics(getContextParams())
  } catch (error) {
    console.error("Failed to load Gradebook evaluation statistics:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadData)
</script>
