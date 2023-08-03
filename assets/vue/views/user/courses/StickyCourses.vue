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
import { computed, ref, watchEffect } from "vue"
import {useStore} from 'vuex'
import {GET_STICKY_COURSES} from "../../../graphql/queries/Course"
import { useQuery } from "@vue/apollo-composable"

const store = useStore()

let isAuthenticated = computed(() => store.getters['security/isAuthenticated'])

const queryResponse = ref({})

if (isAuthenticated.value) {
  const { result } = useQuery(GET_STICKY_COURSES)

  watchEffect(() => {
    queryResponse.value = result.value
  })
}

const courses = computed(() => queryResponse.value?.courses?.edges?.map(({node}) => node) ?? [])
</script>
