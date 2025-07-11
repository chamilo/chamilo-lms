<template>
  <div class="field space-y-2">
    <BaseIcon
      icon="back"
      size="big"
      @click="goBack"
    />

    <h3 v-t="'Edit assignment'" />

    <AssignmentsForm
      :default-assignment="assignment"
      :is-form-loading="isFormLoading"
      @submit="onSubmit"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import AssignmentsForm from "../../components/assignments/AssignmentsForm.vue"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useCidReq } from "../../composables/cidReq"

const route = useRoute()
const router = useRouter()
const { cid, sid, gid } = useCidReq()

const { t } = useI18n()

const notification = useNotification()

const assignment = ref(null)
const isFormLoading = ref(true)

const assignmentId = route.params.id

cStudentPublicationService
  .find(assignmentId)
  .then((response) => response.json())
  .then((json) => (assignment.value = json))
  .finally(() => (isFormLoading.value = false))

function onSubmit(publicationStudent) {
  isFormLoading.value = true

  cStudentPublicationService
    .update(publicationStudent)
    .then(() => {
      notification.showSuccessNotification(t("Assignment updated"))
      goBack()
    })
    .catch((e) => notification.showErrorNotification(e))
    .finally(() => (isFormLoading.value = false))
}

function goBack() {
  if (route.query.from === "AssignmentDetail") {
    router.push({
      name: "AssignmentDetail",
      params: {
        id: parseInt(assignmentId.split("/").pop(), 10),
        node: route.query.node,
      },
      query: { cid, sid, gid },
    })
  } else {
    router.push({
      name: "AssignmentsList",
      query: { cid, sid, gid },
    })
  }
}
</script>
