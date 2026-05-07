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

    <CourseForm
      v-if="capabilityStatus !== 'blocked'"
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
const capabilityStatus = ref("allowed")
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

function normalizeCapabilityResponse(response) {
  return response?.data || response || {}
}

async function loadCreateCapability() {
  createCapabilityMessage.value = ""

  try {
    const response = await courseService.getCreateCourseCapability()
    const capability = normalizeCapabilityResponse(response)

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
    capabilityStatus.value = "allowed"
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
