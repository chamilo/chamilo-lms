<template>
  <div class="create-course-page m-10">
    <h1 class="page-title text-xl text-gray-90">{{ t("Add a new course") }}</h1>
    <hr class="mb-6" />

    <Message
      v-if="capabilityStatus === 'blocked'"
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

    <Message
      v-else-if="capabilityStatus === 'error'"
      severity="error"
    >
      <div class="space-y-2">
        <p class="font-medium">
          {{ t("Unable to verify whether you can create a new course right now.") }}
        </p>

        <p>
          {{ t("Please try again later or contact the administrator.") }}
        </p>
      </div>
    </Message>

    <CourseForm
      v-else-if="capabilityStatus === 'allowed'"
      ref="createForm"
      :errors="violations"
      :values="item"
      @submit="submitCourse"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
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
const capabilityStatus = ref("pending")
const createCapabilityMessage = ref("")

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

function withTimeout(promise, timeout = 2500) {
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
  capabilityStatus.value = "pending"
  createCapabilityMessage.value = ""

  try {
    const data = await withTimeout(courseService.getCreateCourseCapability(), 2500)

    if (true === data.canCreate) {
      capabilityStatus.value = "allowed"
      return
    }

    capabilityStatus.value = "blocked"
    createCapabilityMessage.value = data.message || t("You cannot create more courses right now.")
  } catch (error) {
    console.error("[course.create-capability] request failed", error)
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

    const errorMessage = error?.response?.data?.message || error?.message || t("An unexpected error occurred.")

    showErrorNotification(errorMessage)

    if (error?.response?.data?.violations) {
      violations.value = error.response.data.violations
    }
  }
}

onMounted(() => {
  loadCreateCapability()
})
</script>
