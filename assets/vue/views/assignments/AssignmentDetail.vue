<template>
  <div class="space-y-6">
    <div class="flex items-center flex-wrap gap-2">
      <BaseIcon
        icon="back"
        size="big"
        @click="goBack"
      />

      <template v-if="!isEditor">
        <div class="ml-auto">
          <BaseButton
            icon="upload"
            :label="t('Upload my assignment')"
            type="primary"
            @click="uploadMyAssignment"
          />
        </div>
      </template>

      <template v-else>
        <BaseIcon
          icon="file-add"
          size="big"
          :title="t('Add document')"
          @click="addDocument"
        />
        <BaseIcon
          icon="user-add"
          size="big"
          :title="t('Add users')"
          @click="addUsers"
        />
        <BaseIcon
          icon="file-pdf"
          size="big"
          :title="t('Export PDF')"
          @click="exportPdf"
        />
        <BaseIcon
          icon="edit"
          size="big"
          :title="t('Edit assignment')"
          @click="editAssignment"
        />
        <BaseIcon
          icon="list"
          size="big"
          :title="t('Users without submission')"
          @click="showUnsubmittedUsers"
        />
        <BaseButton
          icon="download"
          :label="t('Download assignments package')"
          type="primary"
          @click="downloadAssignments"
        />
        <BaseButton
          icon="upload"
          :label="t('Upload corrections package')"
          type="success"
          @click="uploadCorrections"
        />
        <BaseButton
          icon="delete"
          :label="t('Delete all corrections')"
          type="danger"
          @click="deleteAllCorrections"
        />
      </template>
    </div>

    <h2 class="text-2xl font-bold">{{ assignment?.title }}</h2>

    <div class="bg-gray-10 border border-gray-25 rounded-lg shadow-sm">
      <div
        class="p-4 text-gray-800 prose max-w-none"
        v-html="assignment?.description"
      />
    </div>
    <div
      v-if="addedDocuments.length"
      class="bg-gray-10 border-t border-gray-25 p-4 mt-0"
    >
      <h3 class="font-bold text-gray-90 mb-2">{{ t("Documents") }}</h3>
      <ul class="space-y-2">
        <li
          v-for="doc in addedDocuments"
          :key="doc.document.iid"
        >
          <a
            :href="`${doc.document.downloadUrl}`"
            class="text-primary hover:underline"
            target="_blank"
          >
            {{ doc.document.title }}
          </a>
        </li>
      </ul>
    </div>
    <div>
      <StudentSubmissionList
        v-if="!isEditor"
        :assignment-id="assignmentId"
      />
      <TeacherSubmissionList
        v-else
        :assignment-id="assignmentId"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import cStudentPublicationService from "../../services/cstudentpublication"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import StudentSubmissionList from "../../components/assignments/StudentSubmissionList.vue"
import TeacherSubmissionList from "../../components/assignments/TeacherSubmissionList.vue"
import { ENTRYPOINT } from "../../config/entrypoint"
import axios from "axios"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const route = useRoute()
const router = useRouter()
const assignmentId = parseInt(route.params.id)

const assignment = ref(null)

const securityStore = useSecurityStore()
const isEditor = securityStore.isCourseAdmin || securityStore.isTeacher
const addedDocuments = ref([])
const notification = useNotification()

async function loadAddedDocuments() {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publication_rel_documents`, {
      params: { "publication.id": assignmentId },
    })
    addedDocuments.value = response.data["hydra:member"]
  } catch (e) {
    console.error("Error loading added documents", e)
  }
}

onMounted(async () => {
  try {
    const response = await cStudentPublicationService.getAssignmentMetadata(assignmentId)
    assignment.value = response
    await loadAddedDocuments()
  } catch (error) {
    console.error("Error loading assignment metadata", error)
  }
})

function goBack() {
  router.push({
    name: "AssignmentsList",
    query: { cid, sid, gid },
  })
}

function uploadMyAssignment() {
  router.push({
    name: "AssignmentSubmit",
    params: { id: assignmentId },
    query: { cid, sid, gid },
  })
}

function editAssignment() {
  router.push({
    name: "AssignmentsUpdate",
    params: { id: assignment.value["@id"] },
    query: {
      ...route.query,
      from: "AssignmentDetail",
      node: route.params.node,
    },
  })
}

async function exportPdf() {
  try {
    const response = await axios.get(`/assignments/${assignmentId}/export/pdf`, {
      params: { cid, ...(sid && { sid }), ...(gid && { gid }) },
      responseType: "blob",
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `assignment_${assignmentId}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    notification.showErrorNotification(t("Failed to export PDF"))
    console.error("PDF export error", error)
  }
}

function showUnsubmittedUsers() {
  router.push({
    name: "AssignmentMissing",
    params: { id: assignmentId },
    query: { cid, sid, gid },
  })
}

function addDocument() {
  router.push({
    name: "AssignmentAddDocument",
    params: { id: assignmentId },
    query: route.query,
  })
}

function addUsers() {
  router.push({
    name: "AssignmentAddUser",
    params: { id: assignmentId },
    query: route.query,
  })
}

function downloadAssignments() {
  console.log("Download assignments clicked")
}

function uploadCorrections() {
  console.log("Upload corrections clicked")
}

function deleteAllCorrections() {
  console.log("Delete all corrections clicked")
}
</script>
