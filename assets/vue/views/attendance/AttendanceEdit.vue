<template>
  <section>
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Edit attendance')"
      icon="edit"
    />

    <div
      v-if="loading"
      aria-busy="true"
      class="mt-6 flex flex-col"
    >
      <Skeleton
        v-for="(field, index) in FORM_SKELETON"
        :key="index"
        :height="`${field.height}px`"
        :style="{ marginTop: `${field.marginTop}px` }"
      />
    </div>
    <AttendanceForm
      v-else-if="attendanceData"
      :initial-data="{ ...attendanceData }"
      @back-pressed="goBack"
    />
    <p
      v-else
      class="text-body-2 text-gray-50"
    >
      {{ t("No data available") }}
    </p>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue"
import AttendanceForm from "../../components/attendance/AttendanceForm.vue"
import attendanceService from "../../services/attendanceService"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import Skeleton from "primevue/skeleton"
import DOMPurify from "dompurify"
import { useNotification } from "../../composables/notification"

// AttendanceForm fields measured at 1440 px: title, description editor, default room, advanced
// settings and buttons, each with its distance from the previous one (labels and margins).
const FORM_SKELETON = [
  { height: 42, marginTop: 0 },
  { height: 320, marginTop: 44 },
  { height: 42, marginTop: 44 },
  { height: 24, marginTop: 56 },
  { height: 40, marginTop: 44 },
]

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { showErrorNotification } = useNotification()

const attendanceData = ref(null)
const loading = ref(true)

function extractResourceLanguage(resource) {
  return String(resource?.resourceNode?.language?.isocode || resource?.language || "").trim()
}

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
      qualifyGradebook: false,
      gradebookOption: null,
      gradebookTitle: fetchedData.attendanceQualifyTitle || "",
      gradeWeight: fetchedData.attendanceWeight || 0.0,
      requireUnique: !!fetchedData.requireUnique,
      language: extractResourceLanguage(fetchedData),
      room: typeof fetchedData.room === "string" ? fetchedData.room : fetchedData.room?.["@id"] || null,
    }
  } catch (error) {
    showErrorNotification(error)
    attendanceData.value = null
  } finally {
    loading.value = false
  }
}

onMounted(fetchAttendance)
</script>
