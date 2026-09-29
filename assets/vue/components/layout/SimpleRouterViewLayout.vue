<template>
  <CourseIntroduction
    v-if="tool"
    :key="tool"
    compact
    :tool="tool"
  />

  <router-view />
</template>

<script setup>
import { computed } from "vue"
import { useRoute } from "vue-router"
import CourseIntroduction from "../course/CourseIntroduction.vue"

const route = useRoute()

const tool = computed(() => {
  const matchedWithTool = [...route.matched].reverse().find((item) => item.meta?.tool)

  return matchedWithTool?.meta.tool || null
})
</script>
