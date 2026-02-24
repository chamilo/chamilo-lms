<template>
  <div class="field space-y-2">
    <BaseIcon
      icon="back"
      size="big"
      @click="goBack"
    />

    <h3 v-text="t('Edit assignment')" />

    <AssignmentsForm
      :default-assignment="assignment"
      :is-form-loading="isFormLoading"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import AssignmentsForm from "../../components/assignments/AssignmentsForm.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useNotification } from "../../composables/notification"
import { useCidReq } from "../../composables/cidReq"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notification = useNotification()
const { cid, sid, gid } = useCidReq()

const assignment = ref(null)
const isFormLoading = ref(true)

function normalizeRouteId(raw) {
  if (raw === null || raw === undefined) return ""
  return typeof raw === "string" ? decodeURIComponent(raw) : String(raw)
}

function extractNumericId(idOrIri) {
  const s = normalizeRouteId(idOrIri)
  const last = s.split("/").filter(Boolean).pop()
  const n = Number(last)
  return Number.isFinite(n) ? n : null
}

const assignmentIdRaw = route.params.id
const assignmentId = extractNumericId(assignmentIdRaw)

function buildCidParams() {
  return {
    cid,
    ...(sid ? { sid } : {}),
    ...(gid ? { gid } : {}),
  }
}

onMounted(async () => {
  try {
    assignment.value = await cStudentPublicationService.getAssignmentMetadata(assignmentId, cid, sid, gid)
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isFormLoading.value = false
  }
})

async function onSubmit(publicationStudent) {
  isFormLoading.value = true

  try {
    await cStudentPublicationService.updatePublication(assignmentId, publicationStudent, buildCidParams())

    notification.showSuccessNotification(t("Assignment updated"))
    goBack()
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isFormLoading.value = false
  }
}

function goBack() {
  if (route.query.from === "AssignmentDetail") {
    router.push({
      name: "AssignmentDetail",
      params: {
        id: assignmentId,
        node: route.query.node,
      },
      query: buildCidParams(),
    })
    return
  }

  router.push({
    name: "AssignmentsList",
    query: buildCidParams(),
  })
}
</script>
