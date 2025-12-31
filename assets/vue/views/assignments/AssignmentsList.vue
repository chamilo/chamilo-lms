<template>
  <SectionHeader :title="t('Assignments')">
    <template #end>
      <StudentViewButton
        v-if="securityStore.isAuthenticated"
        @change="onStudentViewChange"
      />
    </template>
  </SectionHeader>

  <BaseToolbar>
    <template #start>
      <BaseButton
        v-if="isTeacherUI"
        icon="folder-plus"
        size="normal"
        @click="goToNewAssignment"
        type="black"
      />
      <BaseButton
        v-if="isTeacherUI"
        icon="account"
        size="normal"
        @click="openProgressDialog"
        type="black"
      />
    </template>
  </BaseToolbar>

  <component
    :is="componentToShow"
    :isAllowedToEdit="isAllowedToEdit"
    @select-assignment="onSelectAssignment"
  />

  <BaseDialog
    v-model:visible="isDialogVisible"
    :title="t('Student progress')"
    size="large"
    is-visible
  >
    <div
      v-if="loadingProgress"
      class="text-center p-6"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="studentProgress.length === 0"
      class="text-center p-6"
    >
      {{ t("No data available") }}
    </div>

    <div
      v-else
      class="overflow-x-auto p-4"
    >
      <table class="min-w-full text-left">
        <thead>
          <tr>
            <th class="px-6 py-3 text-gray-700 font-bold">{{ t("Learners") }}</th>
            <th class="px-6 py-3 text-gray-700 font-bold text-center">{{ t("Assignments") }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="student in studentProgress"
            :key="student.id"
            class="border-b last:border-b-0"
          >
            <td class="px-6 py-3">{{ student.firstname }} {{ student.lastname }}</td>
            <td class="px-6 py-3 text-center">{{ student.submissions }} / {{ student.totalAssignments }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </BaseDialog>
</template>

<script setup>
import TeacherAssignmentList from "../../components/assignments/TeacherAssignmentList.vue"
import StudentAssignmentList from "../../components/assignments/StudentAssignmentList.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { onMounted, ref, computed } from "vue"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import cstudentpublicationService from "../../services/cstudentpublication"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

const isAllowedToEdit = ref(false)
onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
})

const isTeacherUI = computed(
  () =>
    (securityStore.isCurrentTeacher || securityStore.isCourseAdmin || securityStore.isAdmin) &&
    !platformConfigStore.isStudentViewActive,
)

const componentToShow = computed(() => (isTeacherUI.value ? TeacherAssignmentList : StudentAssignmentList))

const selectedAssignmentId = ref(null)
const isDialogVisible = ref(false)
const loadingProgress = ref(false)
const studentProgress = ref([])

function onStudentViewChange() {
  // Intentionally empty: child components read from the reactive store
}

function goToNewAssignment() {
  router.push({
    name: "AssignmentsCreate",
    query: route.query,
  })
}

function onSelectAssignment(assignmentId) {
  selectedAssignmentId.value = assignmentId
}

async function openProgressDialog() {
  if (!isTeacherUI.value) return

  try {
    isDialogVisible.value = true
    loadingProgress.value = true

    const result = await cstudentpublicationService.getStudentProgress(route.query)
    studentProgress.value = Array.isArray(result["hydra:member"]) ? result["hydra:member"] : []
  } catch (error) {
    console.warn("[Assignments] Failed to load student progress", error)
    studentProgress.value = []
  } finally {
    loadingProgress.value = false
  }
}
</script>
