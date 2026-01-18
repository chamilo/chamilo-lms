<script setup>
import CourseCard from "../course/CourseCard.vue"
import { useSessionCard } from "../../composables/my_course_list/myCourseListSessions"

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
  // "flat"  => card mode (flatten into parent grid)
  // "list"  => list mode (grid inside each session block)
  layout: {
    type: String,
    required: false,
    default: "list",
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

function courseKey(course, index) {
  const id = normalizeCourse(course).id
  return `${props.session.id}-${id ?? index}`
}
</script>

<template>
  <!-- FLAT: children become direct items of the parent grid -->
  <div
    v-if="layout === 'flat'"
    class="contents"
  >
    <div
      v-for="(course, index) in courses"
      :key="courseKey(course, index)"
      class="w-full h-full min-w-0"
    >
      <CourseCard
        :course="normalizeCourse(course)"
        :disabled="!isEnabled"
        :session="session"
        :session-id="session.id"
      />
    </div>
  </div>

  <!-- LIST: grid inside a session block -->
  <div
    v-else
    class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 items-stretch justify-items-start"
  >
    <div
      v-for="(course, index) in courses"
      :key="courseKey(course, index)"
      class="w-full h-full min-w-0"
    >
      <CourseCard
        :course="normalizeCourse(course)"
        :disabled="!isEnabled"
        :session="session"
        :session-id="session.id"
        :show-session-display-date="false"
      />
    </div>
  </div>
</template>
