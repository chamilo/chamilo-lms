<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="calendar-plus" />
      {{ t("Add Attendance Calendar") }}
    </template>

    <AttendanceCalendarForm @back-pressed="goBack" />
  </LayoutFormGeneric>
</template>

<script setup>
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import AttendanceCalendarForm from "../../components/attendance/AttendanceCalendarForm.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "CalendarList",
    params: { node: route.params.node, id: route.params.id },
    query: {
      ...route.query,
    },
  })
}
</script>
