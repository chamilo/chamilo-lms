<template>
  <StickyCourses />

  <hr />

  <div
    v-if="isLoading && courses.length === 0"
    class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
  >
    <Skeleton height="16rem" />
    <Skeleton
      class="hidden md:block"
      height="16rem"
    />
    <Skeleton
      class="hidden lg:block"
      height="16rem"
    />
    <Skeleton
      class="hidden xl:block"
      height="16rem"
    />
  </div>

  <div
    v-if="courses.length > 0"
    class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
  >
    <CourseCardList :courses="courses" />
    <div ref="lastCourseRef"></div>
  </div>
  <EmptyState
    v-else-if="!isLoading && 0 === courses.length"
    :detail="t('Go to Explore to find a topic of interest, or wait for someone to subscribe you')"
    :summary="t('You don\'t have any course yet.')"
    icon="courses"
  />
</template>

<script setup>
import { nextTick, onMounted, ref, watch } from "vue"
import { useQuery } from "@vue/apollo-composable"
import { useI18n } from "vue-i18n"
import { GET_COURSE_REL_USER } from "../../../graphql/queries/CourseRelUser.js"
import Skeleton from "primevue/skeleton"
import StickyCourses from "../../../views/user/courses/StickyCourses.vue"
import CourseCardList from "../../../components/course/CourseCardList.vue"
import EmptyState from "../../../components/EmptyState"
import { useSecurityStore } from "../../../store/securityStore"

const securityStore = useSecurityStore()
const { t } = useI18n()

const courses = ref([])
const isLoading = ref(false)
const endCursor = ref(null)
const hasMore = ref(true)
const lastCourseRef = ref(null)

const { result, fetchMore } = useQuery(GET_COURSE_REL_USER, {
  user: securityStore.user["@id"],
  first: 30,
  after: null,
})

watch(result, (newResult) => {
  if (newResult?.courseRelUsers) {
    const newCourses = newResult.courseRelUsers.edges.map(({ node }) => node.course)

    const filteredCourses = newCourses.filter(
      (newCourse) => !courses.value.some((existingCourse) => existingCourse._id === newCourse._id),
    )

    courses.value.push(...filteredCourses)
    endCursor.value = newResult.courseRelUsers.pageInfo.endCursor
    hasMore.value = newResult.courseRelUsers.pageInfo.hasNextPage

    nextTick(() => {
      if (lastCourseRef.value) {
        observer.observe(lastCourseRef.value)
      }
    })
  }
  isLoading.value = false
})

const loadMoreCourses = () => {
  if (!hasMore.value || isLoading.value) return
  isLoading.value = true

  fetchMore({
    variables: {
      user: securityStore.user["@id"],
      first: 10,
      after: endCursor.value,
    },
    updateQuery: (previousResult, { fetchMoreResult }) => {
      if (!fetchMoreResult) return previousResult

      const newCourses = fetchMoreResult.courseRelUsers.edges.map(({ node }) => node.course)
      const filteredCourses = newCourses.filter(
        (newCourse) => !courses.value.some((existingCourse) => existingCourse._id === newCourse._id),
      )
      courses.value.push(...filteredCourses)
      endCursor.value = fetchMoreResult.courseRelUsers.pageInfo.endCursor
      hasMore.value = fetchMoreResult.courseRelUsers.pageInfo.hasNextPage

      return {
        ...previousResult,
        courseRelUsers: {
          ...fetchMoreResult.courseRelUsers,
          edges: [...previousResult.courseRelUsers.edges, ...fetchMoreResult.courseRelUsers.edges],
        },
      }
    },
  }).finally(() => {
    isLoading.value = false
  })
}

let observer = new IntersectionObserver(
  (entries) => {
    if (entries[0].isIntersecting) {
      loadMoreCourses()
    }
  },
  {
    rootMargin: "300px",
  },
)

onMounted(() => {
  courses.value = []
  endCursor.value = null
  hasMore.value = true
  isLoading.value = false

  if (observer) observer.disconnect()
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        loadMoreCourses()
      }
    },
    {
      rootMargin: "300px",
    },
  )

  loadMoreCourses()
})
</script>
