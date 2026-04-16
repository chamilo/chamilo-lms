<template>
  <div class="create-course-page m-10">
    <div class="message-container mb-4">
      <Message severity="info">
        <div class="space-y-2 text-blue-900">
          <p class="font-medium">
            {{
              t(
                'Once you click on "Create a course", a course is created with a section for Tests, Project based learning, Assessments, Courses, Dropbox, Agenda and much more. Logging in as teacher provides you with editing privileges for this course.',
              )
            }}
          </p>
        </div>
      </Message>
    </div>

    <h1 class="page-title text-xl text-gray-90">{{ t("Add a new course") }}</h1>
    <hr class="mb-6" />

    <div
      v-if="capabilityStatus === 'checking'"
      class="space-y-4"
    >
      <Message severity="info">
        <div class="space-y-2 text-blue-900">
          <p class="font-semibold">
            {{ t("Checking whether you can create a new course.") }}
          </p>
        </div>
      </Message>
    </div>

    <div
      v-else-if="capabilityStatus === 'blocked'"
      class="space-y-4"
    >
      <Message severity="warn">
        <div class="space-y-2 text-amber-900">
          <p class="font-semibold">
            {{ t("You cannot create more courses right now.") }}
          </p>

          <p class="text-sm leading-6">
            {{ createCapabilityMessage }}
          </p>

          <p
            v-if="effectiveLimit > 0"
            class="text-sm leading-6"
          >
            {{ t("Current courses") }}: <strong>{{ currentCount }}</strong>
            —
            {{ t("Allowed limit") }}: <strong>{{ effectiveLimit }}</strong>
          </p>

          <p
            v-if="limitSourceLabel"
            class="text-sm leading-6"
          >
            {{ t("Limit source") }}: <strong>{{ limitSourceLabel }}</strong>
          </p>
        </div>
      </Message>
    </div>

    <div
      v-else-if="capabilityStatus === 'error'"
      class="space-y-4"
    >
      <Message severity="error">
        <div class="space-y-2 text-red-900">
          <p class="font-semibold">
            {{
              t(
                "Unable to verify whether you can create a new course right now. Please try again later or contact the administrator.",
              )
            }}
          </p>
        </div>
      </Message>
    </div>

    <CourseForm
      v-else
      ref="createForm"
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

const capabilityStatus = ref("checking")
const createCapabilityMessage = ref("")
const currentCount = ref(0)
const effectiveLimit = ref(0)
const limitSource = ref("unlimited")

const limitSourceLabel = computed(() => {
  if (limitSource.value === "service") {
    return t("Active service")
  }

  if (limitSource.value === "global") {
    return t("Platform setting")
  }

  return ""
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

function withTimeout(promise, timeout = 8000) {
  return Promise.race([
    promise,
    new Promise((_, reject) => {
      window.setTimeout(() => {
        reject(new Error("create-capability-timeout"))
      }, timeout)
    }),
  ])
}

async function loadCreateCapability() {
  capabilityStatus.value = "checking"
  createCapabilityMessage.value = ""
  currentCount.value = 0
  effectiveLimit.value = 0
  limitSource.value = "unlimited"

  try {
    const data = await withTimeout(
      courseService.getCreateCourseCapability({
        timeout: 8000,
      }),
      8500,
    )

    createCapabilityMessage.value = data.message || t("You cannot create more courses right now.")
    currentCount.value = Number(data.currentCount || 0)
    effectiveLimit.value = Number(data.effectiveLimit || 0)
    limitSource.value = data.limitSource || "unlimited"

    capabilityStatus.value = true === data.canCreate ? "allowed" : "blocked"
  } catch (error) {
    console.error("Failed to load create course capability.", error)
    capabilityStatus.value = "error"
  }
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

    showSuccessNotification(t("Course created successfully."))
    await router.push(`/course/${courseId}/home?sid=${sessionId}`)
  } catch (error) {
    console.error(error)

    const errorMessage =
      error.message ||
      (error.response && error.response.data && error.response.data.message
        ? error.response.data.message
        : t("An unexpected error occurred."))

    showErrorNotification(errorMessage)

    if (error.response && error.response.data && error.response.data.violations) {
      violations.value = error.response.data.violations
    }

    await loadCreateCapability()
  }
}

onMounted(async () => {
  await loadCreateCapability()
})
</script>
