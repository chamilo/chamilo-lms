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
  <div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="(course, index) in courses"
        :key="normalizeCourse(course).id || index"
      >
        <CourseCard
          :course="normalizeCourse(course)"
          :disabled="!isEnabled"
          :session="session"
          :session-id="session.id"
        />
      </div>
    </div>
  </div>
</template>
