<template>
  <div>
    <BaseToolbar>
      <BaseButton
        :label="t('Add Attendance')"
        icon="plus"
        type="black"
        @click="redirectToCreateAttendance"
      />
    </BaseToolbar>

    <AttendanceTable
      :attendances="attendances"
      :loading="isLoading"
      :total-records="totalAttendances"
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
  </div>
</template>
<script setup>
import { ref, onMounted, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import attendanceService from "../../services/attendanceService"
import AttendanceTable from "../../components/attendance/AttendanceTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const securityStore = useSecurityStore()
const isStudent = computed(() => securityStore.isStudent)
const attendances = ref([])
const isDeleteDialogVisible = ref(false)
const attendanceToDelete = ref({ id: null, title: "" })
const totalAttendances = ref(0)
const isLoading = ref(false)
const { sid, cid, gid } = useCidReq()
const parentResourceNodeId = ref(Number(route.params.node))

const redirectToCreateAttendance = () => {
  router.push({
    name: "CreateAttendance",
    query: { cid, sid, gid },
  })
}

const redirectToEditAttendance = (attendance) => {
  router.push({
    name: "EditAttendance",
    params: { id: attendance.id },
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
    console.error("Error deleting attendance:", error)
  } finally {
    isDeleteDialogVisible.value = false
  }
}

const toggleResourceLinkVisibility = async (attendance) => {
  try {
    await attendanceService.toggleVisibility(attendance.id)
    await fetchAttendances()
  } catch (error) {
    console.error("Error toggling visibility:", error)
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
    }))

    if (isStudent.value) {
      attendanceList = attendanceList.filter((item) => {
        const visibility = item.resourceLinkListFromEntity?.[0]?.visibility || 0
        return visibility === 2
      })
    }

    attendances.value = attendanceList
    totalAttendances.value = data.total || 0
  } catch (error) {
    console.error("Error fetching attendances:", error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchAttendances)
</script>
