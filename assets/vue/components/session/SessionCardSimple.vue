<template>
  <div
      v-for="course in courses"
      no-body
      style="max-width: 540px;"
  >
    <CourseCard
        :session="session"
        :course="course"
        :session-id="session._id"
    />
  </div>
</template>

<script setup>
import CourseCard from '../course/CourseCard.vue'
import {ref} from "vue"
import isEmpty from 'lodash/isEmpty'

const props = defineProps({
  session: {
    type: Object,
    required: true,
  },
})

const courses = ref([])

let showAllCourses = false

if (!isEmpty(props.session.users) && !isEmpty(props.session.users.edges)) {
  props.session.users.edges.forEach(({node}) => {
    // User is Session::SESSION_ADMIN
    if (4 === node.relationType) {
      showAllCourses = true
    }
  })
}

if (showAllCourses) {
  courses.value = props.session.courses.edges.map(({node}) => {
    return node.course
  })
} else {
  if (
    !isEmpty(props.session.courses)
    && !isEmpty(props.session.courses.edges)
  ) {
    props.session.courses.edges.map(({node}) => {
      const courseExists = props.session.courses.edges.findIndex(courseItem => courseItem.node.course._id === node.course._id) >= 0

      if (courseExists) {
        courses.value.push(node.course);
      }
    })
  }
}
</script>
