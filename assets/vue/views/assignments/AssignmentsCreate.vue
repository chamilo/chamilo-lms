<template>
  <div class="field space-y-2">
    <BaseButton
      :label="t('Back')"
      icon="back"
      only-icon
      size="small"
      type="black"
      @click="goBack"
    />
    <div class="field">
      <h3 v-text="t('Create assignment')" />
    </div>

    <AssignmentsForm
      :is-form-loading="isFormLoading"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import AssignmentsForm from "../../components/assignments/AssignmentsForm.vue"
import { useI18n } from "vue-i18n"
import { ref } from "vue"
import cStudentPublicationService from "../../services/cstudentpublication"
import lpService from "../../services/lpService"
import { getCourseContext } from "../../utils/courseContext"
import { useNotification } from "../../composables/notification"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const { cid, sid, gid } = getCourseContext()
const router = useRouter()
const route = useRoute()

const { showSuccessNotification, showErrorNotification } = useNotification()

const isFormLoading = ref(false)

function extractResourceId(resource) {
  const directId = Number(resource?.iid || resource?.id || 0)
  if (directId > 0) {
    return directId
  }

  const iri = String(resource?.["@id"] || "")
  const match = iri.match(/\/(\d+)\/?$/)

  return match ? Number(match[1]) : 0
}

function isLearningPathContext() {
  return "learnpath" === String(route.query.origin || "").toLowerCase() && Number(route.query.lp_id || 0) > 0
}

function buildLearningPathBuilderRoute() {
  const query = { ...route.query }
  delete query.action
  delete query.create
  delete query.content

  return {
    name: "LpBuilder",
    params: {
      node: Number(route.query.node || route.params.node || 0),
      lpId: Number(route.query.lp_id || 0),
    },
    query,
  }
}

async function addCreatedAssignmentToLearningPath(assignmentId) {
  const lpId = Number(route.query.lp_id || 0)
  const context = { cid, sid, gid }
  const builder = await lpService.getBuilder(lpId, context)

  await lpService.addBuilderResource(lpId, context, {
    resourceType: "student_publication",
    resourceId: assignmentId,
    parentId: Number(route.query.parent || 0) || null,
    exportAllowed: false,
    csrfToken: builder.csrfToken,
  })
}

async function onSubmit(publicationStudent) {
  isFormLoading.value = true

  try {
    const data = await cStudentPublicationService.createPublication(publicationStudent)
    showSuccessNotification(t("Assignment created"))

    if (isLearningPathContext()) {
      const assignmentId = extractResourceId(data)
      if (assignmentId <= 0) {
        throw new Error("Invalid assignment identifier.")
      }

      await addCreatedAssignmentToLearningPath(assignmentId)
      await router.push(buildLearningPathBuilderRoute())

      return
    }

    await goBack()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isFormLoading.value = false
  }
}

function goBack() {
  if (isLearningPathContext()) {
    return router.push(buildLearningPathBuilderRoute())
  }

  return router.push({
    name: "AssignmentsList",
    query: { cid, sid, gid },
  })
}
</script>
