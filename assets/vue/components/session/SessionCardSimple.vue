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

function extractIdFromIri(iri) {
  if (!iri) return undefined
  const match = iri.match(/\/(\d+)$/)
  return match ? parseInt(match[1], 10) : undefined
}

function normalizeCourse(course) {
  return {
    ...course,
    id: course.id || course._id || extractIdFromIri(course["@id"]),
  }
}
</script>

<template>
  <div class="grid grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-4">
    <div
      v-for="(course, index) in courses"
      :key="normalizeCourse(course).id || index"
      class="w-full"
      style="max-width: 540px"
    >
      <CourseCard
        :course="normalizeCourse(course)"
        :disabled="!isEnabled"
        :session="session"
        :session-id="session.id"
      />
    </div>
  </div>
</template>
