<template>
  <SectionHeader :title="t('Attendance')">
    <template #end>
      <StudentViewButton
        v-if="securityStore.isAuthenticated"
        @change="onStudentViewChange"
      />
    </template>
  </SectionHeader>

  <div>
    <BaseToolbar>
      <template #start>
        <BaseButton
          v-if="!readonly"
          icon="file-add"
          size="normal"
          type="black"
          :title="t('Add attendance')"
          @click="redirectToCreateAttendance"
        />
      </template>
    </BaseToolbar>
    <AttendanceTable
      :attendances="attendances"
      :loading="isLoading"
      :total-records="totalAttendances"
      :readonly="securityStore.isStudent || platformConfigStore.isStudentViewActive"
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
import { ref, onMounted, computed, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import attendanceService from "../../services/attendanceService"
import AttendanceTable from "../../components/attendance/AttendanceTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

const readonly = computed(() => securityStore.isStudent || platformConfigStore.isStudentViewActive)

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
      doneCalendars: item.doneCalendars,
      resourceNode: item.resourceNode,
    }))

    if (readonly.value) {
      attendanceList = attendanceList.filter((i) => (i.resourceLinkListFromEntity?.[0]?.visibility || 0) === 2)
    }

    attendances.value = attendanceList
    totalAttendances.value = data.total || 0
  } catch (error) {
    console.error("Error fetching attendances:", error)
  } finally {
    isLoading.value = false
  }
}
function onStudentViewChange() {
  fetchAttendances()
}

watch(
  () => platformConfigStore.isStudentViewActive,
  () => {
    fetchAttendances()
  },
)

onMounted(fetchAttendances)
</script>
