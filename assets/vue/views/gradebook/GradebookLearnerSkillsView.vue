<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="infoMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton :label="t('Back')" :route="learnerRoute" icon="back" only-icon size="normal" type="primary-text" />
      <BaseButton
        v-if="skills?.skills?.some((skill) => skill.acquired)"
        :label="t('Export badges')"
        :route="badgesRoute"
        icon="gradebook"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <h1 class="text-xl font-semibold text-gray-90">{{ t("Skills and users list") }}</h1>
      <p class="mt-1 text-sm text-gray-600">{{ skills?.learner?.fullName || "-" }}</p>
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading...") }}
    </div>

    <div v-else-if="!skills?.skills?.length" class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm">
      {{ t("No results found") }}
    </div>

    <div v-else class="space-y-4">
      <article v-for="skill in skills.skills" :key="skill.id" class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="font-semibold text-gray-90">{{ skill.title }}</h2>
            <p v-if="skill.description" class="mt-1 text-sm text-gray-600">{{ skill.description }}</p>
          </div>
          <BaseButton
            :disabled="isSaving"
            :label="skill.acquired ? t('Acquired') : t('Validate')"
            :icon="skill.acquired ? 'check' : 'plus'"
            size="small"
            :type="skill.acquired ? 'secondary' : 'success'"
            @click="toggleSkill(skill)"
          />
        </div>

        <div class="mt-4 divide-y divide-gray-20 border-t border-gray-20">
          <div v-for="item in skill.items" :key="item.id" class="flex items-center justify-between gap-4 py-3 text-sm">
            <RouterLink v-if="item.url && item.spa" :to="item.url" class="font-medium text-primary hover:underline">{{ item.title }}</RouterLink>
            <a v-else-if="item.url" :href="item.url" class="font-medium text-primary hover:underline">{{ item.title }}</a>
            <span v-else class="font-medium text-gray-90">{{ item.title }}</span>
            <span :class="item.validated ? 'text-success' : 'text-gray-500'">
              {{ item.validated ? t("Validated") : t("Not validated") }}
            </span>
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
const skills = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")

const learnerRoute = computed(() => ({
  name: "GradebookLearnerReport",
  params: { node: route.params.node, userId: route.params.userId },
  query: { ...route.query },
}))
const badgesRoute = computed(() => ({
  name: "GradebookBadges",
  params: { node: route.params.node, userId: route.params.userId },
  query: { ...route.query },
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function contextParams() {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    categoryId: getQueryValue(route.query.categoryId),
    userId: route.params.userId,
  }
}

async function loadSkills() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    skills.value = await gradebookService.getLearnerSkills(contextParams())
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
  } finally {
    isLoading.value = false
  }
}

async function toggleSkill(skill) {
  isSaving.value = true
  errorMessage.value = ""
  infoMessage.value = ""
  try {
    await gradebookService.toggleLearnerSkill(
      { userId: Number(route.params.userId), skillId: Number(skill.id), submittedCsrfToken: skills.value?.csrfToken || "" },
      contextParams(),
    )
    infoMessage.value = t("Saved")
    await loadSkills()
  } catch (error) {
    errorMessage.value = error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("No data available")
  } finally {
    isSaving.value = false
  }
}

onMounted(loadSkills)
watch(() => [route.params.userId, route.query], loadSkills, { deep: true })
</script>
