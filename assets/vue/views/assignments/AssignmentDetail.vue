<template>
  <SectionHeader :title="t('Assignment')">
    <template #end>
      <StudentViewButton
        v-if="securityStore.isAuthenticated"
        @change="onStudentViewChange"
      />
    </template>
  </SectionHeader>

  <div>
    <div
      v-if="!assignment"
      class="p-4 text-center text-gray-600"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else
      class="space-y-6"
    >
      <div class="flex items-center flex-wrap gap-2">
        <!-- Back -->
        <BaseIcon
          v-if="!fromLearnpath"
          icon="back"
          size="big"
          @click="goBack"
        />

        <template v-if="forceStudentView && !isAfterEndDate">
          <div class="ml-auto flex gap-2">
            <BaseButton
              v-if="allowTextFlag && !allowFileFlag"
              icon="edit"
              :label="t('Write my submission')"
              type="primary"
              @click="goToSubmit({ text: true })"
            />
            <BaseButton
              v-else-if="allowFileFlag && !allowTextFlag"
              icon="upload"
              :label="t('Upload file')"
              type="primary"
              @click="goToSubmit({ file: true })"
            />
            <template v-else>
              <BaseButton
                v-if="allowTextFlag"
                icon="edit"
                :label="t('Write my submission')"
                type="primary"
                @click="goToSubmit({ text: true })"
              />
              <BaseButton
                v-if="allowFileFlag"
                icon="upload"
                :label="t('Upload file')"
                type="primary"
                @click="goToSubmit({ file: true })"
              />
            </template>
          </div>
        </template>

        <template v-else-if="isTeacherUI">
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
            :title="t('Export to PDF')"
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
            icon="zip-pack"
            :label="t('Download assignments package')"
            type="primary"
            @click="downloadAssignments"
          />
          <BaseButton
            icon="zip-unpack"
            :label="t('Upload corrections package')"
            type="success"
            :title="t('Each file name must match: YYYY-MM-DD_HH-MM_username_originalTitle.ext')"
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

      <div
        v-if="forceStudentView && isAfterEndDate"
        class="text-red-600 border border-red-300 p-4 rounded bg-red-50"
      >
        {{ t("You can no longer submit. The deadline has passed.") }}
      </div>

      <h2 class="text-2xl font-bold">{{ assignment.title }}</h2>

      <div class="bg-gray-10 border border-gray-25 rounded-lg shadow-sm">
        <div
          class="p-4 text-gray-800 prose max-w-none"
          v-html="assignment.description"
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
              :href="doc.document.downloadUrl"
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
          v-if="forceStudentView"
          :assignment-id="assignmentId"
          :is-after-deadline="isAfterEndDate"
          :flags="{ allowText: allowTextFlag, allowFile: allowFileFlag }"
        />
        <TeacherSubmissionList
          v-else
          :key="submissionListKey"
          :assignment-id="assignmentId"
          :flags="{ allowText: allowTextFlag, allowFile: allowFileFlag }"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import cStudentPublicationService from "../../services/cstudentpublication"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useNotification } from "../../composables/notification"

import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import StudentSubmissionList from "../../components/assignments/StudentSubmissionList.vue"
import TeacherSubmissionList from "../../components/assignments/TeacherSubmissionList.vue"

const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const notification = useNotification()

const assignmentId = parseInt(route.params.id, 10)
const fromLearnpath = route.query.origin === "learnpath"

const isTeacherUI = computed(
  () =>
    (securityStore.isCurrentTeacher || securityStore.isCourseAdmin || securityStore.isAdmin) &&
    !platformConfigStore.isStudentViewActive,
)

const forceStudentView = computed(() => !isTeacherUI.value || platformConfigStore.isStudentViewActive)

function onStudentViewChange() {}

const assignment = ref(null)
const addedDocuments = ref([])
const submissionListKey = ref(0)

function buildCidParams() {
  return {
    cid,
    ...(sid && { sid }),
    ...(gid && { gid }),
  }
}

function fromApiLocal(str) {
  if (!str) return null
  const s = String(str).includes("T") ? String(str) : String(str).replace(" ", "T")
  return new Date(s)
}

const expiresOnDate = computed(() => fromApiLocal(assignment.value?.assignment?.expiresOn))
const endsOnDate = computed(() => fromApiLocal(assignment.value?.assignment?.endsOn))
const isAfterEndDate = computed(() => (endsOnDate.value ? new Date() > endsOnDate.value : false))
const isAfterDeadline = isAfterEndDate

const allowTextFlag = computed(
  () => assignment.value?.allowTextAssignment === 0 || assignment.value?.allowTextAssignment === 1,
)

const allowFileFlag = computed(
  () => assignment.value?.allowTextAssignment === 0 || assignment.value?.allowTextAssignment === 2,
)

async function loadAddedDocuments() {
  try {
    const resp = await axios.get(`${ENTRYPOINT}c_student_publication_rel_documents`, {
      params: {
        ...buildCidParams(),
        publication: `/api/c_student_publications/${assignmentId}`,
      },
    })
    addedDocuments.value = resp.data["hydra:member"]
  } catch (e) {
    console.warn("[AssignmentDetail] Failed to load added documents", e)
  }
}

onMounted(async () => {
  assignment.value = await cStudentPublicationService.getAssignmentMetadata(assignmentId, cid, sid, gid)
  await loadAddedDocuments()
})

function goBack() {
  router.push({ name: "AssignmentsList", query: { cid, sid, gid } })
}

function goToSubmit(flags) {
  router.push({
    name: "AssignmentSubmit",
    params: { id: assignmentId, node: route.params.node },
    query: {
      cid,
      sid,
      gid,
      allowText: flags.text ? "1" : "0",
      allowFile: flags.file ? "1" : "0",
    },
  })
}

function uploadMyAssignment(flags) {
  router.push({
    name: "AssignmentSubmit",
    params: {
      id: assignmentId,
      node: route.params.node,
    },
    query: {
      cid,
      sid,
      gid,
      allowText: flags.text ? "1" : "0",
      allowFile: flags.file ? "1" : "0",
    },
  })
}

function addDocument() {
  if (!isTeacherUI.value) return
  router.push({ name: "AssignmentAddDocument", params: { id: assignmentId }, query: { cid, sid, gid } })
}

function addUsers() {
  if (!isTeacherUI.value) return
  router.push({ name: "AssignmentAddUser", params: { id: assignmentId }, query: { cid, sid, gid } })
}

function editAssignment() {
  if (!isTeacherUI.value) return
  router.push({
    name: "AssignmentsUpdate",
    params: { id: assignment.value["@id"] },
    query: { ...route.query, from: "AssignmentDetail", node: route.params.node },
  })
}

function showUnsubmittedUsers() {
  if (!isTeacherUI.value) return
  router.push({ name: "AssignmentMissing", params: { id: assignmentId }, query: { cid, sid, gid } })
}

async function exportPdf() {
  if (!isTeacherUI.value) return
  try {
    const data = await cStudentPublicationService.exportAssignmentPdf(assignmentId, cid, sid, gid)
    const url = window.URL.createObjectURL(new Blob([data], { type: "application/pdf" }))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `assignment_${assignmentId}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch {
    notification.showErrorNotification(t("Failed to export PDF"))
  }
}

async function downloadAssignments() {
  if (!isTeacherUI.value) return
  try {
    const blob = await cStudentPublicationService.downloadAssignments(assignmentId)
    const url = window.URL.createObjectURL(new Blob([blob]))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `assignments_${assignmentId}.zip`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch {
    notification.showErrorNotification(t("Failed to download package"))
  }
}

async function uploadCorrections() {
  if (!isTeacherUI.value) return
  const input = document.createElement("input")
  input.type = "file"
  input.accept = ".zip"

  input.addEventListener("change", async () => {
    const file = input.files?.[0]
    if (!file) return

    try {
      const res = await cStudentPublicationService.uploadCorrectionsPackage(assignmentId, file)
      notification.showSuccessNotification(
        t(`Corrections uploaded: ${res.uploaded ?? 0}. Skipped: ${res.skipped ?? 0}.`),
      )
      submissionListKey.value++
    } catch {
      notification.showErrorNotification(t("Failed to upload corrections"))
    }
  })

  input.click()
}

async function deleteAllCorrections() {
  if (!isTeacherUI.value) return
  if (!confirm(t("Are you sure you want to delete all corrections?"))) return

  try {
    await cStudentPublicationService.deleteAllCorrections(assignmentId, cid, sid)
    notification.showSuccessNotification(t("All corrections deleted"))

    assignment.value = await cStudentPublicationService.getAssignmentMetadata(assignmentId, cid, sid, gid)
    submissionListKey.value++
  } catch {
    notification.showErrorNotification(t("Failed to delete corrections"))
  }
}
</script>
