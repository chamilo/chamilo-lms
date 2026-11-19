<template>
  <section>
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Add attendance')"
      icon="plus"
    />

    <AttendanceForm @back-pressed="goBack" />
  </section>
</template>

<script setup>
import AttendanceForm from "../../components/attendance/AttendanceForm.vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = (query = {}) => {
  router.push({
    name: "AttendanceList",
    params: { node: String(route.params.node) },
    query: {
      ...route.query,
      ...query,
    },
  })
}
</script>
