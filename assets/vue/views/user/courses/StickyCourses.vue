<template>
  <div v-if="courses.length" class="mb-6">
    <h2 class="mb-2"> {{ $t('Sticky courses')}}</h2>
    <div
      v-if="courses.length"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2"
    >
      <CourseCardList
        :courses="courses"
      />
    </div>
  </div>
</template>

<script setup>
import CourseCardList from '../../../components/course/CourseCardList.vue'
import {computed} from "vue"
import {useStore} from 'vuex'
import {GET_STICKY_COURSES} from "../../../graphql/queries/Course"
import {useSession} from "../sessions/session"

const store = useStore()

let user = computed(() => store.getters['security/getUser'])
const {sessions: result} = useSession(user, null, null, GET_STICKY_COURSES)

const courses = computed(() => {
  if (result.value === null) {
    return []
  }
  return result.value
})
</script>
