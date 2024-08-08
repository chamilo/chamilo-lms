<template>
  <div class="field">
    <h3 v-t="'Edit assignment'" />
  </div>

  <AssignmentsForm
    :default-assignment="assignment"
    :is-form-loading="isFormLoading"
    @submit="onSubmit"
  />
</template>

<script setup>
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import AssignmentsForm from "../../components/assignments/AssignmentsForm.vue"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"

const route = useRoute()
const router = useRouter()

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
  console.log("update", publicationStudent)

  isFormLoading.value = true

  cStudentPublicationService
    .update(publicationStudent)
    .then(() => {
      notification.showSuccessNotification(t("Assignment updated"))

      router.push({ name: "AssignmentsList", query: route.query })
    })
    .catch((e) => notification.showErrorNotification(e))
    .finally(() => (isFormLoading.value = false))
}
</script>
