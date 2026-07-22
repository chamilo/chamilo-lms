<template>
  <div>
    <div class="mb-4">
      <Button
        icon="mdi mdi-arrow-left"
        :label="t('Back to list')"
        class="p-button-secondary"
        @click="router.push({ name: 'PageList', query: $route.query })"
      />
    </div>

    <PageForm
      v-model="item"
      @submit="updateItem"
    />
    <Loading :visible="isLoading || isSaving" />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import Loading from "../../components/Loading.vue"
import PageForm from "../../components/page/Form.vue"
import { useDatatableUpdate } from "../../composables/datatableUpdate"
import pageService from "../../services/pageService"
import { normalize } from "../../utils/hydra"

const { item, isLoading, retrieve } = useDatatableUpdate("Page")
const router = useRouter()
const { t } = useI18n()
const toast = useToast()

const isSaving = ref(false)

retrieve()

/**
 * Persists the edited page through the dedicated pageService (HTTP PATCH),
 * instead of the generic CRUD store, and notifies the user of the result.
 * The payload is flattened with normalize() so nested relations (e.g. creator)
 * are sent as IRIs, which API Platform requires on write operations.
 * @param {Object} payload - The page item emitted by PageForm (includes @id).
 * @returns {Promise<void>}
 */
async function updateItem(payload) {
  isSaving.value = true

  try {
    await pageService.updatePage(payload["@id"], normalize(payload))

    toast.add({
      severity: "success",
      detail: t("{0} updated", [payload["@id"]]),
      life: 3500,
    })
  } catch (error) {
    toast.add({
      severity: "error",
      detail: error.message,
      life: 5000,
    })
  } finally {
    isSaving.value = false
  }
}
</script>
