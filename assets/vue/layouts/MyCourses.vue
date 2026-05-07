<template>
  <SectionHeader :title="t('My courses')">
    <BaseButton
      v-if="showPendingAssignmentsLink"
      :label="t('Pending assignments')"
      icon="assignment"
      to-url="/main/work/pending.php"
      type="primary"
    />
    <BaseButton
      v-if="showPendingExerciseAttemptsLink"
      :label="t('Pending tests')"
      icon="quiz"
      to-url="/main/exercise/pending.php"
      type="primary"
    />
  </SectionHeader>

  <router-view />
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import SectionHeader from "../components/layout/SectionHeader.vue"
import BaseButton from "../components/basecomponents/BaseButton.vue"
import { usePlatformConfig } from "../store/platformConfig"
import { useSecurityStore } from "../store/securityStore"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()

function isSettingEnabled(name) {
  const value = platformConfigStore.getSetting(name)

  return value === true || value === "true" || value === 1 || value === "1"
}

const canReviewCourseItems = computed(() => {
  return Boolean(
    securityStore.isAdmin ||
    securityStore.isTeacher ||
    securityStore.isCurrentTeacher ||
    securityStore.isCurrentCourseTeacher,
  )
})

const showPendingAssignmentsSetting = computed(() => {
  return isSettingEnabled("work.my_courses_show_pending_work")
})

const showPendingAssignmentsLink = computed(() => {
  return showPendingAssignmentsSetting.value && canReviewCourseItems.value
})

const showPendingExerciseAttemptsSetting = computed(() => {
  return isSettingEnabled("exercise.my_courses_show_pending_exercise_attempts")
})

const showPendingExerciseAttemptsLink = computed(() => {
  return showPendingExerciseAttemptsSetting.value && canReviewCourseItems.value
})
</script>
