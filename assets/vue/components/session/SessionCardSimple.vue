<script setup>
import CourseCard from "../course/CourseCard.vue"
import { useSecurityStore } from "../../store/securityStore"

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
})

const securityStore = useSecurityStore()

const courses = props.session.courses
  ? props.session.courses.map((sesionRelCourse) => ({ ...sesionRelCourse.course, _id: sesionRelCourse.course.id }))
  : []

const isGeneralCoach = props.session.generalCoachesSubscriptions
  ? props.session.generalCoachesSubscriptions.findIndex((sRcRU) => sRcRU.user["@id"] === securityStore.user["@id"]) >= 0
  : false

const isCourseCoach = props.session.courseCoachesSubscriptions
  ? props.session.courseCoachesSubscriptions.findIndex((sRcRU) => sRcRU.user["@id"] === securityStore.user["@id"]) >= 0
  : false

const enableAccess =
  (isGeneralCoach || isCourseCoach) && props.session.activeForCoach ? true : props.session.activeForStudent
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
