<template>
  <div>
    <BaseTable
      :sort-field="sortField"
      :sort-order="sortOrder"
      v-model:rows="loadParams.itemsPerPage"
      :is-loading="loading"
      :total-items="totalRecords"
      :values="students"
      data-key="userId"
      lazy
      @page="onPage"
      @sort="onSort"
    >
      <Column
        field="user.fullName"
        :header="t('Full name')"
        sortable
      >
        <template #body="{ data }"> {{ data.firstname }} {{ data.lastname }} </template>
      </Column>

      <Column
        field="submissionCount"
        :header="t('Number submitted')"
        sortable
      >
        <template #body="{ data }">
          <span :data-testid="`assignment-submission-count-${data.userId}`">
            {{ data.submissionCount }}
          </span>
        </template>
      </Column>

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <BaseButton
            :label="t('View')"
            icon="list"
            only-icon
            size="small"
            type="primary-text"
            :data-testid="`assignment-submissions-open-${data.userId}`"
            @click="selectStudent(data)"
          />
        </template>
      </Column>
    </BaseTable>

    <section
      v-if="selectedStudent"
      class="mt-6 rounded-lg border border-gray-25 p-4"
      data-testid="assignment-student-submission-details"
    >
      <div class="mb-4 flex items-center justify-between gap-3">
        <h3 class="text-body-1 font-semibold">
          {{ t("Submissions") }} — {{ selectedStudent.firstname }} {{ selectedStudent.lastname }}
        </h3>
        <BaseButton
          :label="t('Close')"
          icon="close"
          only-icon
          size="small"
          type="black"
          @click="selectedStudent = null"
        />
      </div>
      <TeacherSubmissionList
        :key="`${assignmentId}-${selectedStudent.userId}`"
        :assignment-id="assignmentId"
        :user-id="selectedStudent.userId"
        @submissions-changed="loadData"
      />
    </section>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Column from "primevue/column"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import TeacherSubmissionList from "./TeacherSubmissionList.vue"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import { getCourseContext } from "../../utils/courseContext"
import {
  getAssignmentSortStorageKey,
  loadAssignmentSort,
  saveAssignmentSort,
} from "../../utils/assignmentSubmissionSort"

const props = defineProps({
  assignmentId: { type: Number, required: true },
})

const { t } = useI18n()
const notification = useNotification()
const loading = ref(false)
const students = ref([])
const totalRecords = ref(0)
const selectedStudent = ref(null)
const loadParams = reactive({ page: 1, itemsPerPage: null })
const securityStore = useSecurityStore()
const sortableFields = ["user.fullName", "submissionCount"]
const sortStorageKey = computed(() =>
  getAssignmentSortStorageKey("students", securityStore.user?.id, getCourseContext()),
)
const sortField = ref("user.fullName")
const sortOrder = ref(1)

watch(
  sortStorageKey,
  (key) => {
    if (!key) return
    const saved = loadAssignmentSort(key, sortableFields, "user.fullName", 1)
    sortField.value = saved.field
    sortOrder.value = saved.order
    if (loadParams.itemsPerPage) loadData()
  },
  { immediate: true },
)

watch(
  loadParams,
  () => {
    if (!loadParams.itemsPerPage) return
    loadData()
  },
  { deep: true, immediate: true },
)

async function loadData() {
  loading.value = true
  try {
    const response = await cStudentPublicationService.getAssignmentSubmissionGroupsForTeacher({
      assignmentId: props.assignmentId,
      page: loadParams.page,
      itemsPerPage: loadParams.itemsPerPage,
      order: { [sortField.value]: sortOrder.value === 1 ? "asc" : "desc" },
    })
    students.value = response["hydra:member"]
    totalRecords.value = response["hydra:totalItems"]
    if (selectedStudent.value) {
      selectedStudent.value = students.value.find((student) => student.userId === selectedStudent.value.userId) ?? null
    }
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function onPage(event) {
  loadParams.page = event.page + 1
  loadParams.itemsPerPage = event.rows
  selectedStudent.value = null
}

function onSort(event) {
  if (!sortableFields.includes(event.sortField)) return

  sortField.value = event.sortField
  sortOrder.value = event.sortOrder === -1 ? -1 : 1
  saveAssignmentSort(sortStorageKey.value, sortField.value, sortOrder.value)

  const isFirstPage = loadParams.page === 1
  loadParams.page = 1
  selectedStudent.value = null
  if (isFirstPage && loadParams.itemsPerPage) loadData()
}

function selectStudent(student) {
  selectedStudent.value = selectedStudent.value?.userId === student.userId ? null : student
}
</script>
