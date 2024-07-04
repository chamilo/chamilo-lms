<template>
  <div class="create-course-page m-10">

    <div class="message-container mb-4">
      <Message severity="info">
        {{ t('Once you click on "Create a course", a course is created with a section for Tests, Project based learning, Assessments, Courses, Dropbox, Agenda and much more. Logging in as teacher provides you with editing privileges for this course.') }}
      </Message>
    </div>

    <h1 class="page-title text-xl text-gray-90">{{ t('Add a new course') }}</h1>
    <hr />

    <CourseForm
      ref="createForm"
      :errors="violations"
      :values="item"
      @submit="submitCourse"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import CourseForm from '../../components/course/Form.vue'
import Loading from '../../components/Loading.vue'
import { useRouter } from "vue-router"
import Message from 'primevue/message'
import courseService from "../../services/courseService"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"

const item = ref({})
const router = useRouter()
const { t } = useI18n()

const isLoading = ref(false)
const violations = ref(null)
const { showSuccessNotification, showErrorNotification } = useNotification()

const submitCourse = async (formData) => {
  isLoading.value = true
  violations.value = null
  try {
    const response = await courseService.createCourse(formData)
    const courseId = response.courseId
    const sessionId = 0
    showSuccessNotification(t('Course created successfully.'))
    await router.push(`/course/${courseId}/home?sid=${sessionId}`)
  } catch (error) {
    console.error(error)

    const errorMessage = error.response && error.response.data && error.response.data.message
      ? error.response.data.message
      : t('An unexpected error occurred.')
    showErrorNotification(errorMessage)

    if (error.response && error.response.data && error.response.data.violations) {
      violations.value = error.response.data.violations
    }
  } finally {
    isLoading.value = false
  }
}
</script>
