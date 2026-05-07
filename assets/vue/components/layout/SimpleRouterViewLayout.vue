<template>
  <CourseIntroduction
    v-if="tool"
    :key="tool"
    :is-allowed-to-edit="isAllowedToEdit"
    compact
    :tool="tool"
  />

  <router-view />
</template>

<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import CourseIntroduction from "../course/CourseIntroduction.vue"
import { useSecurityStore } from "../../store/securityStore"

const route = useRoute()
const securityStore = useSecurityStore()

const tool = computed(() => {
  const matchedWithTool = [...route.matched].reverse().find((item) => item.meta?.tool)

  return matchedWithTool?.meta.tool || null
})

const isAllowedToEdit = computed(() => {
  return Boolean(
    securityStore.isAdmin ||
    securityStore.isTeacher ||
    securityStore.isCurrentCourseTeacher ||
    securityStore.isCurrentTeacher,
  )
})
</script>
