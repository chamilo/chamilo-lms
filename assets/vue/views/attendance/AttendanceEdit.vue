<template>
  <LayoutFormGeneric>
    <template #header>
      <BaseIcon icon="edit" />
      {{ t("Edit attendance") }}
    </template>
    <div v-if="loading"></div>
    <div v-else-if="attendanceData">
      <AttendanceForm
        :initial-data="{ ...attendanceData }"
        @back-pressed="goBack"
      />
    </div>
    <div v-else>
      <p>{{ t("No data available") }}</p>
    </div>
  </LayoutFormGeneric>
</template>

<script setup>
import { ref, onMounted } from "vue"
import AttendanceForm from "../../components/attendance/AttendanceForm.vue"
import attendanceService from "../../services/attendanceService"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import LayoutFormGeneric from "../../components/layout/LayoutFormGeneric.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import DOMPurify from "dompurify"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()

const attendanceData = ref(null)
const loading = ref(true)

const goBack = (query = {}) => {
  router.push({
    name: "AttendanceList",
    params: { node: String(route.params.node) },
    query: { ...route.query, ...query },
  })
}

const fetchAttendance = async () => {
  const attendanceId = route.params.id

  if (!attendanceId) {
    goBack()
    return
  }

  try {
    loading.value = true
    const fetchedData = await attendanceService.getAttendance(attendanceId)
    attendanceData.value = {
      id: fetchedData.iid,
      title: fetchedData.title,
      description: DOMPurify.sanitize(fetchedData.description),
      qualifyGradebook: !!fetchedData.attendanceQualifyTitle,
      gradebookOption: fetchedData.gradebookOption || null,
      gradebookTitle: fetchedData.attendanceQualifyTitle || "",
      gradeWeight: fetchedData.attendanceWeight || 0.0,
      requireUnique: !!fetchedData.requireUnique,
    }
  } catch (error) {
    console.error("Error fetching attendance:", error)
    attendanceData.value = null
  } finally {
    loading.value = false
  }
}

onMounted(fetchAttendance)
</script>
