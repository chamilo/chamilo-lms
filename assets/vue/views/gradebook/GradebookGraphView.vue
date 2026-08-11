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
        :route="flatRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Graphical view") }}</h1>
      <p
        v-if="categorySubtitle"
        class="mt-1 text-sm text-gray-600"
      >
        {{ categorySubtitle }}
      </p>
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="graph && !graph.enabled"
      class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm"
    >
      {{ t("To view graph score rule must be enabled") }}
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <article
        v-for="resource in graph?.resources || []"
        :key="resource.key"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <h2 class="mb-4 font-semibold text-gray-90">{{ resource.title }}</h2>
        <div class="space-y-3">
          <div v-for="bucket in resource.distribution" :key="bucket.label" class="grid grid-cols-[minmax(6rem,auto)_1fr_auto] items-center gap-3">
            <span class="truncate text-sm text-gray-700">{{ bucket.label }}</span>
            <div class="h-4 overflow-hidden rounded bg-gray-100">
              <div class="h-full rounded bg-primary" :style="{ width: `${bucket.widthPercent}%` }" />
            </div>
            <span class="min-w-8 text-right text-sm font-semibold text-gray-90">{{ bucket.count }}</span>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const graph = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(graph.value?.category?.title || "").trim()
})

const flatRoute = computed(() => ({ name: "GradebookFlatView", params: { node: route.params.node }, query: { ...route.query } }))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

async function loadGraph() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    graph.value = await gradebookService.getGraph({
      cid: getQueryValue(route.query.cid),
      sid: getQueryValue(route.query.sid),
      gid: getQueryValue(route.query.gid),
      node: route.params.node,
      categoryId: getQueryValue(route.query.categoryId),
    })
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadGraph)
watch(() => route.query, loadGraph, { deep: true })
</script>
