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
      v-if="infoMessage"
      class="rounded-xl border border-info/30 bg-support-1 p-4 text-sm text-support-4"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="data">
      <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          size="normal"
          type="primary-text"
        />
        <BaseButton
          v-if="data.canManage"
          :disabled="isSaving || data.locked || data.category?.hasGradeModel || rows.length === 0"
          :is-loading="isSaving"
          :label="t('Distribution')"
          icon="refresh"
          only-icon
          size="normal"
          type="secondary-text"
          @click="autoDistribute"
        />
        <BaseButton
          v-if="data.canManage"
          :disabled="isSaving || data.locked || data.category?.hasGradeModel"
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          only-icon
          size="normal"
          type="success"
          @click="saveWeights"
        />
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
          <div>
            <div class="flex items-center gap-2">
              <BaseIcon
                icon="settings"
                size="normal"
              />
              <h1 class="text-xl font-semibold text-gray-90">
                {{ t("Weight in Report") }}
              </h1>
            </div>
            <p
              v-if="categorySubtitle"
              class="mt-2 text-sm text-gray-600"
            >
              {{ categorySubtitle }}
            </p>
          </div>

          <div class="flex flex-wrap gap-2 text-xs">
            <span class="rounded-full bg-gray-100 px-2 py-1 text-gray-700">
              {{ t("Total") }}: {{ formatNumber(data.expectedTotal) }}
            </span>
            <span
              class="rounded-full px-2 py-1"
              :class="weightTotalClass"
            >
              {{ t("Current") }}: {{ formatNumber(currentTotal) }}
            </span>
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("Total") }}: {{ formatNumber(currentTotal) }} / {{ formatNumber(data.expectedTotal) }}
      </div>

      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('No data available')"
        :total-items="rows.length"
        :values="rows"
        data-key="key"
      >
        <Column :header="t('Type')">
          <template #body="{ data: row }">
            {{ t(row.typeLabel) }}
          </template>
        </Column>
        <Column
          :header="t('Assessment')"
          field="title"
        />
        <Column :header="t('Weight')">
          <template #body="{ data: row }">
            <div class="max-w-40">
              <BaseInputNumber
                :id="`gradebook-weight-${row.kind}-${row.id}`"
                v-model="row.weight"
                :disabled="!data.canManage || data.locked || row.locked || data.category?.hasGradeModel"
                :min="0"
                :name="`gradebook_weight_${row.kind}_${row.id}`"
              />
            </div>
          </template>
        </Column>
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
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const data = ref(null)
const rows = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")

const backRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: { ...route.query },
}))

const currentTotal = computed(() => rows.value.reduce((total, row) => total + Number(row.weight || 0), 0))
const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(data.value?.category?.title || "").trim()
})

const weightTotalClass = computed(() =>
  Math.abs(currentTotal.value - Number(data.value?.expectedTotal || 0)) < 0.001
    ? "bg-green-100 text-green-700"
    : "bg-red-100 text-red-700",
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
    data.value = await gradebookService.getWeights(getContextParams())
    rows.value = (data.value?.items || []).map((item) => ({
      ...item,
      key: `${item.kind}-${item.id}`,
      weight: Number(item.weight || 0),
    }))
  } catch (error) {
    console.error("Failed to load Gradebook weights:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function runAction(action, weights = []) {
  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.runWeightAction(
      {
        action,
        categoryId: Number(data.value?.category?.id || 0),
        weights,
        submittedCsrfToken: data.value?.csrfToken || "",
      },
      getContextParams(),
    )
    infoMessage.value = t("Update successful")
    await loadData()
  } catch (error) {
    console.error("Failed to update Gradebook weights:", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

async function autoDistribute() {
  await runAction("auto_distribute")
}

async function saveWeights() {
  await runAction(
    "save",
    rows.value.map((row) => ({
      kind: row.kind,
      id: Number(row.id),
      weight: Number(row.weight || 0),
    })),
  )
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "0"
  }

  return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/0+$/, "").replace(/\.$/, "")
}

onMounted(loadData)
</script>
