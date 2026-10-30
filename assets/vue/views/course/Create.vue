<template>
  <div class="create-course-page m-10">
    <h1 class="page-title text-xl text-gray-90">{{ t("Add a new course") }}</h1>
    <hr class="mb-6" />

    <Message
      v-if="isLoading"
      severity="info"
    >
      {{ t("Checking course creation options...") }}
    </Message>

    <Message
      v-else-if="showGlobalBlockedMessage"
      severity="warn"
    >
      <div class="space-y-2">
        <p class="font-medium">
          {{ t("You cannot create a new course right now.") }}
        </p>

        <p v-if="createCapabilityMessage">
          {{ createCapabilityMessage }}
        </p>
      </div>
    </Message>

    <section
      v-if="!isLoading && !isCreationRestrictedByPolicy && !hasBuyCoursesCourseTypeOptions && showStandardCourseSummary"
      class="mb-5 rounded-2xl border border-gray-25 bg-white p-4 shadow-sm"
      :aria-label="t('Standard course')"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-gray-90">
            {{ standardPlanOverview.label || t("Standard course") }}
          </h2>
          <p class="mt-1 text-sm text-gray-50">
            {{ standardPlanOverview.description || t("Use the standard platform limits.") }}
          </p>
        </div>
        <span
          v-if="standardPlanOverview.maxCourses > 0"
          class="rounded-xl bg-support-2 px-3 py-2 text-sm font-semibold text-gray-90 tabular-nums"
        >
          {{ standardPlanOverview.currentCourses }} / {{ standardPlanOverview.maxCourses }}
        </span>
      </div>

      <div
        v-if="standardPlanOverview.maxCourses > 0"
        class="mt-4 h-2 overflow-hidden rounded-full bg-gray-15"
        role="progressbar"
        :aria-valuemin="0"
        :aria-valuemax="standardPlanOverview.maxCourses"
        :aria-valuenow="Math.min(standardPlanOverview.currentCourses, standardPlanOverview.maxCourses)"
        :aria-label="t('Courses')"
      >
        <div
          class="h-full rounded-full bg-primary transition-all"
          :style="{ width: `${standardPlanPercentage}%` }"
        />
      </div>

      <div
        v-if="standardPlanOverview.hasDetailedLimits && (standardPlanOverview.hostingLimit > 0 || standardPlanOverview.documentQuotaMb > 0)"
        class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-50"
      >
        <span v-if="standardPlanOverview.hostingLimit > 0">
          {{ t("Hosting limit") }}: {{ formatStandardLimit(standardPlanOverview.hostingLimit, t("users")) }}
        </span>
        <span v-if="standardPlanOverview.documentQuotaMb > 0">
          {{ t("Document quota") }}: {{ formatStandardLimit(standardPlanOverview.documentQuotaMb, "MB") }}
        </span>
      </div>
      <p
        v-if="!standardPlanOverview.available && standardPlanOverview.maxCourses > 0"
        class="mt-3 text-sm font-medium text-danger"
      >
        {{ t("The standard course limit has been reached.") }}
      </p>
    </section>

    <Message
      v-if="!isLoading && showCourseForm && !standardPlanOverview && !hasBuyCoursesCourseTypeOptions && buyCoursesOptionsError"
      severity="warn"
      class="mb-4"
    >
      {{ t("Unable to load the course allowance. Course creation remains subject to server validation.") }}
    </Message>

    <CourseForm
      v-if="showCourseForm"
      ref="createForm"
      :buy-courses-options="buyCoursesOptions"
      :errors="violations"
      :values="item"
      @submit="submitCourse"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import Message from "primevue/message"

import CourseForm from "../../components/course/Form.vue"
import courseService from "../../services/courseService"
import { useNotification } from "../../composables/notification"

const router = useRouter()
const { t, locale } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()

function normalizeLocale(value) {
  return String(value || "")
    .trim()
    .replace("-", "_")
    .toLowerCase()
}

const item = ref({
  language: normalizeLocale(locale.value),
})

const violations = ref(null)
const isLoading = ref(true)
const capabilityStatus = ref("allowed")
const capabilityLimitSource = ref("unlimited")
const createCapabilityMessage = ref("")
const buyCoursesOptions = ref(null)
const buyCoursesOptionsError = ref(false)
const createCapabilityDetails = ref(null)

// Keep the standard allowance visible even when BuyCourses options are unavailable.
// The fallback only uses platform-based capability data; service limits are never
// presented as standard limits.
const standardPlanOverview = computed(() => {
  const standard = buyCoursesOptions.value?.standard
  if (standard && typeof standard === "object") {
    return {
      ...standard,
      currentCourses: Math.max(0, Number(standard.currentCourses) || 0),
      maxCourses: Math.max(0, Number(standard.maxCourses) || 0),
      hasDetailedLimits: true,
    }
  }

  const capability = createCapabilityDetails.value
  const limitSource = String(capability?.limitSource || "")
  if (!capability || ["role", "setting", "error", "admin"].includes(limitSource)) {
    return null
  }

  // Without BuyCourses details, a service allowance must not be labeled standard.
  const isStandardLimit = ["global", "unlimited"].includes(limitSource)
  return {
    label: isStandardLimit ? t("Standard course") : t("Course creation allowance"),
    description: isStandardLimit
      ? t("Use the standard platform limits.")
      : t("Your current course creation allowance."),
    currentCourses: Math.max(0, Number(capability.currentCount) || 0),
    maxCourses: Math.max(0, Number(capability.effectiveLimit) || 0),
    available: capability.canCreate === true,
    hasDetailedLimits: false,
  }
})

// Do not advertise an unlimited plan as a consumed quota. Keep meaningful
// per-course restrictions visible even if course creation is unlimited.
const showStandardCourseSummary = computed(() => {
  const standard = standardPlanOverview.value
  if (!standard) {
    return false
  }

  return standard.maxCourses > 0 || (
    standard.hasDetailedLimits &&
    (standard.hostingLimit > 0 || standard.documentQuotaMb > 0)
  )
})

const standardPlanPercentage = computed(() => {
  const standard = standardPlanOverview.value
  if (!standard || standard.maxCourses <= 0) {
    return 0
  }

  return Math.min(100, Math.max(0, (standard.currentCourses / standard.maxCourses) * 100))
})

function formatStandardLimit(value, unit) {
  const limit = Math.max(0, Number(value) || 0)
  return limit <= 0 ? t("Unlimited") : `${limit} ${unit}`
}


const hasBuyCoursesCourseTypeOptions = computed(() => {
  return !!buyCoursesOptions.value?.enabled && !!buyCoursesOptions.value?.hasServiceOptions
})

const isCreationRestrictedByPolicy = computed(() => {
  return capabilityStatus.value === "blocked" && ["role", "setting", "error"].includes(capabilityLimitSource.value)
})

const showGlobalBlockedMessage = computed(() => {
  return capabilityStatus.value === "blocked" && (isCreationRestrictedByPolicy.value || !hasBuyCoursesCourseTypeOptions.value)
})

const showCourseForm = computed(() => {
  if (isLoading.value) {
    return false
  }

  if (isCreationRestrictedByPolicy.value) {
    return false
  }

  if (hasBuyCoursesCourseTypeOptions.value) {
    return true
  }

  return capabilityStatus.value !== "blocked"
})

function sanitizeCoursePayload(data) {
  const payload = { ...data }

  const demoKeys = [
    "fillDemoContent",
    "includeSampleContent",
    "include_sample_content",
    "addExampleContent",
    "add_example_content",
    "exampleContent",
    "example_content",
  ]

  for (const key of demoKeys) {
    if (key in payload) {
      payload[key] = false
    }
  }

  return payload
}

function normalizeCapabilityResponse(response) {
  return response?.data || response || {}
}

async function loadCreateCapability() {
  createCapabilityMessage.value = ""
  createCapabilityDetails.value = null
  capabilityLimitSource.value = "unlimited"

  try {
    const response = await courseService.getCreateCourseCapability()
    const capability = normalizeCapabilityResponse(response)
    createCapabilityDetails.value = capability
    capabilityLimitSource.value = String(capability.limitSource || "unlimited")

    if (false === capability.canCreate) {
      capabilityStatus.value = "blocked"
      createCapabilityMessage.value = capability.message || t("You cannot create more courses right now.")

      return
    }

    capabilityStatus.value = "allowed"
  } catch (error) {
    console.error("[course.create-capability] request failed", error)

    // Do not block the form because this is only a pre-check.
    // The backend validates the real limit when submitting the course.
    capabilityStatus.value = "blocked"
    capabilityLimitSource.value = "error"
    createCapabilityMessage.value = t("Unable to verify whether you can create a new course right now.")
  }
}

async function loadBuyCoursesOptions() {
  buyCoursesOptions.value = null
  buyCoursesOptionsError.value = false

  try {
    const response = await courseService.getBuyCoursesCourseCreationOptions()
    const payload = response?.data ?? response
    if (payload?.success && payload?.enabled && payload?.standard) {
      buyCoursesOptions.value = payload
      return
    }
  } catch (error) {
    console.warn("[course.buycourses-options] Service request failed", error)
  }

  // Fallback for plugin pages when the API client cannot read the plain PHP response.
  // No store avoids showing stale limits after a platform settings change.
  try {
    const response = await fetch("/plugin/BuyCourses/src/course_creation_options.php", {
      credentials: "same-origin",
      cache: "no-store",
      headers: { Accept: "application/json" },
    })
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }
    const payload = await response.json()
    if (payload?.success && payload?.enabled && payload?.standard) {
      buyCoursesOptions.value = payload
      return
    }
    // Disabled plugin or missing standard options: use capability fallback, if valid.
  } catch (error) {
    console.warn("[course.buycourses-options] Direct request failed", error)
    buyCoursesOptionsError.value = true
  }
}

async function loadCreationContext() {
  isLoading.value = true

  await Promise.all([loadCreateCapability(), loadBuyCoursesOptions()])

  isLoading.value = false
}

async function submitCourse(formData) {
  violations.value = null

  try {
    const payload = sanitizeCoursePayload(formData)
    const response = await courseService.createCourse(payload)

    const courseId = response.courseId
    const sessionId = 0

    if (!courseId) {
      throw new Error(t("Course ID is missing. Unable to navigate to the course home page."))
    }

    showSuccessNotification(t("Course created successfully"))
    await router.push(`/course/${courseId}/home?sid=${sessionId}`)
  } catch (error) {
    console.error(error)

    const errorMessage = error?.response?.data?.message || error?.message || t("An unexpected error occurred.")

    showErrorNotification(errorMessage)

    if (error?.response?.data?.violations) {
      violations.value = error.response.data.violations
    }
  }
}

onMounted(() => {
  loadCreationContext()
})
</script>
