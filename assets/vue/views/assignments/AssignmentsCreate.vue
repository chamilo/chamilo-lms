<template>
  <div class="field space-y-2">
    <BaseIcon
      icon="back"
      size="big"
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
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import { useNotification } from "../../composables/notification"
import { useRouter } from "vue-router"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const router = useRouter()

const { showSuccessNotification, showErrorNotification } = useNotification()

const isFormLoading = ref(false)

function onSubmit(publicationStudent) {
  isFormLoading.value = true

  axios
    .post(`${ENTRYPOINT}c_student_publications`, publicationStudent)
    .then(({ data }) => {
      console.log("cstudentpublication", data)

      showSuccessNotification(t("Assignment created"))

      goBack()
    })
    .catch((error) => showErrorNotification(error))
    .finally(() => (isFormLoading.value = false))
}

function goBack() {
  router.push({
    name: "AssignmentsList",
    query: { cid, sid, gid },
  })
}
</script>
