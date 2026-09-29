<template>
  <Toolbar :handle-submit="onSendForm" />
  <ToolIntroForm
    v-if="item"
    ref="updateForm"
    :errors="violations"
    :values="item"
  />
  <Loading :visible="isLoading" />
</template>

<script setup>
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import ToolIntroForm from "../../components/ctoolintro/Form.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import cToolIntroService from "../../services/cToolIntroService"
import { useNotification } from "../../composables/notification"

const route = useRoute()
const router = useRouter()
const notification = useNotification()

const updateForm = ref(null)
const item = ref({})
const isLoading = ref(false)
const violations = ref(null)

// Only the intro text is editable; the IRI of the resource to update travels
// as a query param (the route has no :id segment).
const iri = decodeURIComponent(route.query.id)
item.value["@id"] = iri

// Load the current intro text.
cToolIntroService
  .findByIri(iri)
  .then((toolIntroInfo) => {
    item.value["introText"] = toolIntroInfo.introText
  })
  .catch(notification.showErrorNotification)

/**
 * Validates the form and updates the tool introduction, navigating back on success.
 */
async function onSendForm() {
  const form = updateForm.value

  form.v$.$touch()

  if (form.v$.$invalid) {
    return
  }

  isLoading.value = true

  try {
    await cToolIntroService.update(form.v$.item.$model)
    router.go(-1)
  } catch (error) {
    violations.value = error.response?.data?.violations ?? null
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}
</script>
