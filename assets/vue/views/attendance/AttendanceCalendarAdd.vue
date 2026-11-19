<template>
  <section class="space-y-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Add attendance date')"
      icon="calendar-plus"
    />

    <p class="text-body-2 text-gray-50">
      <span v-if="attendanceTitle">
        {{ t("You are adding calendar events for attendance") }}:
        <strong class="text-gray-90">{{ attendanceTitle }}</strong
        >.
      </span>
      {{
        t(
          "The attendance calendar allows you to register attendance lists (one per real session the students need to attend). Add new attendance lists here.",
        )
      }}
    </p>

    <AttendanceCalendarForm @back-pressed="goBack" />
  </section>
</template>

<script setup>
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import AttendanceCalendarForm from "../../components/attendance/AttendanceCalendarForm.vue"
import attendanceService from "../../services/attendanceService"
import { onMounted, ref } from "vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const attendanceTitle = ref("")
const { showErrorNotification } = useNotification()

onMounted(async () => {
  try {
    const attendance = await attendanceService.getAttendance(route.params.id)
    attendanceTitle.value = attendance.title
  } catch (error) {
    showErrorNotification(error)
  }
})

const goBack = () => {
  router.push({
    name: "AttendanceCalendarList",
    params: { node: route.params.node, id: route.params.id },
    query: {
      ...route.query,
    },
  })
}
</script>
