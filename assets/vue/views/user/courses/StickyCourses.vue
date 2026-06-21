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

import { getStickyCourses } from "../../../services/courseService"

const { t } = useI18n()

const securityStore = useSecurityStore()

const emit = defineEmits({
  loaded: (count) => Number.isInteger(count) && count >= 0,
})

const courses = ref([])

async function loadStickyCourses() {
  if (!securityStore.isAuthenticated) {
    courses.value = []
    emit("loaded", 0)

    return
  }

  try {
    const items = await getStickyCourses()
    courses.value = Array.isArray(items) ? items : []
  } catch (error) {
    console.warn("[StickyCourses] Failed to fetch sticky courses.", error)
    courses.value = []
  } finally {
    emit("loaded", courses.value.length)
  }
}

loadStickyCourses()
</script>
