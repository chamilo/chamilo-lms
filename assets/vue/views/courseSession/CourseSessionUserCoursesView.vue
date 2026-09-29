<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Session overview')"
          :route="buildOverviewRoute()"
          icon="back"
          type="primary-text"
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
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-500 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <form
      v-else
      class="space-y-6 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
      @submit.prevent="save"
    >
      <div>
        <h2 class="text-xl font-semibold text-gray-900">
          {{ user.fullname || user.username }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          {{ sessionTitle }}
        </p>
      </div>

      <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-sm text-orange-800">
        {{ t("Select the courses that this user must not be able to access in this session.") }}
      </div>

      <div
        v-if="courses.length === 0"
        class="text-gray-600"
      >
        {{ t("No courses found") }}
      </div>

      <div
        v-else
        class="space-y-3"
      >
        <label
          v-for="course in courses"
          :key="course.id"
          class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-20 p-3 hover:bg-gray-10"
        >
          <input
            v-model="avoidedCourseIds"
            class="h-4 w-4 rounded border-gray-30"
            name="avoided_course_ids[]"
            type="checkbox"
            :value="course.id"
          />
          <span class="font-medium text-gray-900">{{ course.title }}</span>
          <span class="text-sm text-gray-500">{{ course.code }}</span>
        </label>
      </div>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          :label="t('Cancel')"
          :route="buildOverviewRoute()"
          icon="close"
          type="plain"
        />
        <BaseButton
          :disabled="courses.length === 0"
          :is-loading="saving"
          :label="t('Save')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseSessionService from "../../services/courseSessionService"

const { t } = useI18n()
const route = useRoute()

const sessionId = Number(route.params.sessionId)
const userId = Number(route.params.userId)
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const sessionTitle = ref("")
const user = ref({})
const courses = ref([])
const avoidedCourseIds = ref([])

async function loadData() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseSessionService.getUserCourses(sessionId, userId)
    sessionTitle.value = response.sessionTitle || ""
    user.value = response.user || {}
    courses.value = response.courses || []
    avoidedCourseIds.value = courses.value.filter((course) => course.avoided).map((course) => course.id)
  } catch (error) {
    console.error("[CourseSession] Failed to load user course restrictions", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function buildOverviewRoute() {
  return { name: "CourseSessionOverview", params: { sessionId }, query: { ...route.query } }
}

async function save() {
  if (courses.value.length > 0 && avoidedCourseIds.value.length === courses.value.length) {
    errorMessage.value = t("A user cannot be blocked from every course in the session. Unsubscribe the user instead.")
    return
  }

  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseSessionService.updateUserCourses(sessionId, userId, avoidedCourseIds.value)
    successMessage.value = response.message ? t(response.message) : t("Update successful")
    await loadData()
  } catch (error) {
    console.error("[CourseSession] Failed to update user course restrictions", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(loadData)
</script>
