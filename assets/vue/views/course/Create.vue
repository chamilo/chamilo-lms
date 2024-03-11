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
import { ref, computed } from 'vue'
import { useStore } from 'vuex'
import CourseForm from '../../components/course/Form.vue'
import Loading from '../../components/Loading.vue'
import { useRouter } from "vue-router"
import Message from 'primevue/message'
import courseService from "../../services/courseService"
import { useI18n } from "vue-i18n"

const store = useStore()
const item = ref({})
const router = useRouter()
const { t } = useI18n()

const isLoading = computed(() => store.getters['course/getField']('isLoading'))
const violations = computed(() => store.getters['course/getField']('violations'))
const courseData = ref({})

const submitCourse = async (formData) => {
  isLoading.value = true
  try {
    let tempResponse = await courseService.createCourse(formData)
    if (tempResponse.success) {
      const courseId = tempResponse.courseId
      const sessionId = 0
      await router.push(`/course/${courseId}/home?sid=${sessionId}`)
    } else {
      console.error(tempResponse.message)
    }
  } catch (error) {
    console.error(error)
    if (error.response && error.response.data) {
      violations.value = error.response.data
    } else {
      console.error('An unexpected error occurred.')
    }
  } finally {
    isLoading.value = false
  }
}
</script>
