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
      <BaseButton :label="t('Back')" :route="backRoute" icon="back" only-icon size="normal" type="primary-text" />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Badges") }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ badges?.learner?.fullName || "-" }}</p>
    </div>

    <div v-if="isLoading" class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm">
      {{ t("Loading...") }}
    </div>

    <div v-else class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
      <p class="mb-4 text-sm text-gray-600">
        {{ badges?.assertions?.length ? `${badges.assertions.length} ${t("Badges")}` : t("No results found") }}
      </p>
      <BaseButton
        v-if="badges?.assertions?.length"
        :disabled="isIssuing"
        :is-loading="isIssuing"
        :label="t('Export badges')"
        icon="export"
        type="primary"
        @click="issueBadges"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const badges = ref(null)
const isLoading = ref(false)
const isIssuing = ref(false)
const errorMessage = ref("")

const backRoute = computed(() => {
  const userId = Number(route.params.userId || 0)
  if (userId > 0) {
    return { name: "GradebookLearnerReport", params: { node: route.params.node, userId }, query: { ...route.query } }
  }
  return { name: "GradebookList", params: { node: route.params.node }, query: { ...route.query } }
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

async function loadBadges() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    badges.value = await gradebookService.getBadges({
      cid: getQueryValue(route.query.cid),
      sid: getQueryValue(route.query.sid),
      gid: getQueryValue(route.query.gid),
      node: route.params.node,
      userId: Number(route.params.userId || 0) || undefined,
    })
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
  } finally {
    isLoading.value = false
  }
}

function loadExternalScript(url) {
  return new Promise((resolve, reject) => {
    const existing = Array.from(document.scripts).find((script) => script.dataset.gradebookOpenbadges === url)
    if (existing) {
      if (window.OpenBadges) resolve()
      else existing.addEventListener("load", resolve, { once: true })
      return
    }
    const script = document.createElement("script")
    script.src = url
    script.async = true
    script.dataset.gradebookOpenbadges = url
    script.addEventListener("load", resolve, { once: true })
    script.addEventListener("error", reject, { once: true })
    document.head.appendChild(script)
  })
}

async function issueBadges() {
  if (!badges.value?.assertions?.length || !badges.value?.backpackScriptUrl) {
    errorMessage.value = t("No data available")
    return
  }
  isIssuing.value = true
  errorMessage.value = ""
  try {
    await loadExternalScript(badges.value.backpackScriptUrl)
    if (!window.OpenBadges || typeof window.OpenBadges.issue_no_modal !== "function") {
      throw new Error("OpenBadges issuer is unavailable")
    }
    window.OpenBadges.issue_no_modal(badges.value.assertions)
  } catch (error) {
    errorMessage.value = error?.message || t("No data available")
  } finally {
    isIssuing.value = false
  }
}

onMounted(loadBadges)
</script>
