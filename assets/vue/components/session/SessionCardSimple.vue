<script setup>
import CourseCard from "../course/CourseCard.vue"
import { useSessionCard } from "../../composables/my_course_list/myCourseListSessions"

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
})

const { courses, isEnabled } = useSessionCard(props.session)
</script>

<template>
  <div
    v-for="(course, index) in courses"
    :key="index"
    style="max-width: 540px"
  >
    <CourseCard
      :course="course"
      :disabled="!isEnabled"
      :session="session"
      :session-id="session.id"
    />
  </div>
</template>
