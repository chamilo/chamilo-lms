<template>
  <div
    v-if="courses.length"
    class="mb-6"
  >
    <SectionHeader
      :title="t('Sticky courses')"
      size="5"
    />
    <div
      v-if="courses.length"
      class="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 mt-2"
    >
      <CourseCardList :courses="courses" />
    </div>

    <BaseDivider />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import CourseCardList from "../../../components/course/CourseCardList.vue"
import SectionHeader from "../../../components/layout/SectionHeader.vue"
import BaseDivider from "../../../components/basecomponents/BaseDivider.vue"

import { useSecurityStore } from "../../../store/securityStore"

import { getBuyCoursesCourseServiceLabels, getStickyCourses } from "../../../services/courseService"

const { t } = useI18n()

const securityStore = useSecurityStore()

const emit = defineEmits({
  loaded: (count) => Number.isInteger(count) && count >= 0,
})

const courses = ref([])

function extractCourseId(course) {
  const rawId = course?.id ?? course?._id ?? course?.["@id"] ?? 0

  if (typeof rawId === "number" && Number.isFinite(rawId)) {
    return rawId
  }

  const match = String(rawId).match(/(\d+)(?!.*\d)/)

  return match ? Number(match[1]) : 0
}

async function addBuyCoursesServiceLabels(items) {
  const courseIds = items.map(extractCourseId).filter((courseId) => courseId > 0)

  if (courseIds.length === 0) {
    return items
  }

  try {
    const labels = await getBuyCoursesCourseServiceLabels(courseIds)

    return items.map((course) => {
      const courseId = extractCourseId(course)
      const serviceName = String(labels?.[courseId]?.serviceName ?? "").trim()

      return serviceName ? { ...course, buyCoursesServiceName: serviceName } : course
    })
  } catch (error) {
    console.warn("[StickyCourses] Failed to load BuyCourses service labels.", error)

    return items
  }
}

async function loadStickyCourses() {
  if (!securityStore.isAuthenticated) {
    courses.value = []
    emit("loaded", 0)

    return
  }

  try {
    const items = await getStickyCourses()
    const normalizedItems = Array.isArray(items) ? items : []
    courses.value = await addBuyCoursesServiceLabels(normalizedItems)
  } catch (error) {
    console.warn("[StickyCourses] Failed to fetch sticky courses.", error)
    courses.value = []
  } finally {
    emit("loaded", courses.value.length)
  }
}

loadStickyCourses()
</script>
