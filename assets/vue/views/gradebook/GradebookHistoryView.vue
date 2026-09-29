<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back')"
        :route="gradebookRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Assessment history") }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ history?.itemTitle || "-" }}</p>
    </div>

    <BaseTable
      :is-loading="isLoading"
      :text-for-empty="t('No results found')"
      :total-items="history?.rows?.length || 0"
      :values="history?.rows || []"
      data-key="id"
    >
      <Column :header="t('Assessment name')" field="title" />
      <Column :header="t('Assessment description')" field="description" />
      <Column :header="t('Previous weight of resource')" field="weight" />
      <Column :header="t('Assessment visibility')">
        <template #body="{ data }">
          {{ data.visible ? t("Assessments visible") : t("Assessments invisible") }}
        </template>
      </Column>
      <Column :header="t('Category')" field="type" />
      <Column :header="t('Date')">
        <template #body="{ data }">{{ formatDate(data.createdAt) }}</template>
      </Column>
      <Column :header="t('Who changed it')">
        <template #body="{ data }">{{ data.user?.fullName || data.user?.username || "-" }}</template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const history = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

const gradebookRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: { ...route.query },
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

async function loadHistory() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    history.value = await gradebookService.getHistory({
      cid: getQueryValue(route.query.cid),
      sid: getQueryValue(route.query.sid),
      gid: getQueryValue(route.query.gid),
      node: route.params.node,
      categoryId: getQueryValue(route.query.categoryId),
      kind: route.params.kind,
      itemId: route.params.itemId,
    })
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
  } finally {
    isLoading.value = false
  }
}

function formatDate(value) {
  if (!value) return "-"
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(date)
}

onMounted(loadHistory)
watch(() => [route.params.kind, route.params.itemId, route.query], loadHistory, { deep: true })
</script>
