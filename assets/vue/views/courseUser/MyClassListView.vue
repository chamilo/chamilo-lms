<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <h1 class="text-2xl font-semibold">{{ t("My classes") }}</h1>
      </template>
      <template #end>
        <BaseButton
          v-if="canAddClasses"
          :label="t('Add classes')"
          icon="plus"
          only-icon
          size="normal"
          :to-url="addClassesUrl"
          :tooltip="t('Add classes')"
          type="success"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else-if="classes.length === 0"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-600 shadow-sm"
    >
      {{ t("No results found") }}
    </div>

    <div
      v-else
      class="space-y-4"
    >
      <article
        v-for="usergroup in classes"
        :key="usergroup.id"
        class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-xl font-semibold text-gray-900">{{ usergroup.title }}</h2>
          <span
            class="rounded-full px-2 py-1 text-xs"
            :class="usergroup.groupType === SOCIAL_GROUP ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
          >
            {{ t(usergroup.groupTypeLabel) }}
          </span>
        </div>
        <p class="mt-3 whitespace-pre-line text-gray-700">{{ usergroup.description || "-" }}</p>
      </article>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseClassService from "../../services/courseClassService"

const SOCIAL_GROUP = 1

const { t } = useI18n()
const route = useRoute()
const classes = ref([])
const loading = ref(false)
const errorMessage = ref("")
const canAddClasses = ref(false)
const addClassesUrl = ref("")

async function loadClasses() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseClassService.getMyClasses({ id: route.query.id })
    classes.value = response.items || []
    canAddClasses.value = Boolean(response.canAddClasses)
    addClassesUrl.value = response.addClassesUrl || ""
  } catch (error) {
    console.error("[MyClasses] Failed to load classes", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

onMounted(loadClasses)
</script>
