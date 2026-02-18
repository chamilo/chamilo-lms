<template>
  <CourseCard
    v-for="course in courses"
    :key="course.__key || course.id || course['@id']"
    :course="course"
    :session="course.session || null"
    :session-id="Number(course.sessionId || 0)"
  />
</template>

<script setup>
import CourseCard from "./CourseCard.vue"

defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})
</script>
