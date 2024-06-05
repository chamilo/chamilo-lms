<script setup>
import CourseCard from "../course/CourseCard.vue"
import { SESSION_VISIBILITY_LIST_ONLY } from "../../constants/entity/session"

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
})

const courses = props.session.courses
  ? props.session.courses.map((sesionRelCourse) => ({ ...sesionRelCourse.course, _id: sesionRelCourse.course.id }))
  : []

const enableAccess = props.session.accessVisibility !== SESSION_VISIBILITY_LIST_ONLY
</script>

<template>
  <div
    v-for="(course, index) in courses"
    :key="index"
    style="max-width: 540px"
  >
    <CourseCard
      :session="session"
      :course="course"
      :session-id="session.id"
      :disabled="!enableAccess"
    />
  </div>
</template>
