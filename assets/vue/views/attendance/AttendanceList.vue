<template>
  <section class="space-y-6">
    <SectionHeader
      :title="t('Attendance')"
      icon="attendance"
    >
      <BaseButton
        v-if="!readonly"
        :label="t('Add attendance')"
        icon="plus"
        name="add-attendance"
        type="success"
        @click="redirectToCreateAttendance"
      />
    </SectionHeader>

    <AttendanceTable
      :attendances="attendances"
      :loading="isLoading"
      :total-records="totalAttendances"
      :readonly="readonly"
      @edit="redirectToEditAttendance"
      @view="toggleResourceLinkVisibility"
      @delete="confirmDeleteAttendance"
      @pageChange="fetchAttendances"
    />

    <BaseDialogDelete
      v-model:is-visible="isDeleteDialogVisible"
      :item-to-delete="attendanceToDelete ? attendanceToDelete.title : ''"
      @confirm-clicked="deleteAttendance"
      @cancel-clicked="isDeleteDialogVisible = false"
    />
  </section>
</template>
<script setup>
import { ref, onMounted, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import attendanceService from "../../services/attendanceService"
import AttendanceTable from "../../components/attendance/AttendanceTable.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useI18n } from "vue-i18n"
import { getCourseContext } from "../../utils/courseContext"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const { showErrorNotification } = useNotification()

// Editable only for users who can manage the current course (course/session
// teacher or admin), matching CAttendance's write security. The student view
// forces read-only even for them.
const readonly = computed(() => !securityStore.isCourseAdmin || platformConfigStore.isStudentViewActive)

const attendances = ref([])
const isDeleteDialogVisible = ref(false)
const attendanceToDelete = ref({ id: null, title: "" })
const totalAttendances = ref(0)
const isLoading = ref(false)
const { sid, cid, gid } = getCourseContext()
const parentResourceNodeId = ref(Number(route.params.node))

const redirectToCreateAttendance = () => {
  router.push({
    name: "CreateAttendance",
    params: { node: String(route.params.node) },
    query: { cid, sid, gid },
  })
}

const redirectToEditAttendance = (attendance) => {
  router.push({
    name: "AttendanceEditAttendance",
    params: {
      node: String(route.params.node),
      id: attendance.id,
    },
    query: { cid, sid, gid },
  })
}

const confirmDeleteAttendance = (attendance) => {
  attendanceToDelete.value = { id: attendance.id, title: attendance.title }
  isDeleteDialogVisible.value = true
}

const deleteAttendance = async () => {
  try {
    await attendanceService.softDelete(attendanceToDelete.value.id)
    await fetchAttendances()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isDeleteDialogVisible.value = false
  }
}

const toggleResourceLinkVisibility = async (attendance) => {
  try {
    await attendanceService.toggleVisibility(attendance.id)
    await fetchAttendances()
  } catch (error) {
    showErrorNotification(error)
  }
}

const fetchAttendances = async ({ page = 1, rows = 10 } = {}) => {
  try {
    isLoading.value = true
    const params = {
      "resourceNode.parent": parentResourceNodeId.value,
      active: 1,
      cid,
      sid,
      gid,
      page,
      rows,
    }
    const data = await attendanceService.getAttendances(params)

    let attendanceList = data["hydra:member"].map((item) => ({
      id: item.iid,
      title: item.title,
      description: item.description,
      resourceLinkListFromEntity: item.resourceLinkListFromEntity,
      attendanceQualifyTitle: item.attendanceQualifyTitle,
      attendanceWeight: item.attendanceWeight,
      doneCalendars: item.doneCalendars,
      resourceNode: item.resourceNode,
    }))

    if (readonly.value) {
      attendanceList = attendanceList.filter((i) => (i.resourceLinkListFromEntity?.[0]?.visibility || 0) === 2)
    }

    attendances.value = attendanceList
    totalAttendances.value = data.total || 0
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

useStudentViewRefresh(fetchAttendances)

onMounted(fetchAttendances)
</script>
