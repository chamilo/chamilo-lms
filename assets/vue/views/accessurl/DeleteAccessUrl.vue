<template>
  <div class="space-y-6">
    <SectionHeader :title="t('Confirm deletion')" />
    <Message
      severity="warn"
      class="inline-block max-w-max"
    >
      {{ t("Danger zone: deleting an access URL is permanent.") }}
    </Message>
    <section>
      <p class="mb-3">
        {{ t("The value must match exactly: {0}", [urlText]) }}
      </p>

      <BaseInputText
        id="confirm-text"
        v-model="confirmText"
        :label="t('URL')"
        :placeholder="urlText || 'https://example.com'"
        class="w-full"
        :help-text="t('Type the full URL to confirm. The URL and its relations will be permanently removed.')"
      />

      <BaseButton
        :disabled="loading || !canDelete"
        :is-loading="loading"
        :label="t('Delete')"
        icon="delete"
        type="danger"
        @click="confirmDialogVisible = true"
      />
    </section>

    <BaseDialogConfirmCancel
      v-model:isVisible="confirmDialogVisible"
      :cancel-label="t('Cancel')"
      :confirm-label="t('Delete')"
      :title="t('Confirm deletion')"
      @confirm-clicked="confirmSubmit"
      @cancel-clicked="confirmDialogVisible = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { findById, deleteById } from "../../services/accessurlService"
import Message from "primevue/message"

import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()

const notification = useNotification()

const urlId = Number(route.params.id || 0)
const urlText = ref(route.query.url || "")
const confirmText = ref("")
const loading = ref(false)
const notice = ref("")
const confirmDialogVisible = ref(false)

const canDelete = computed(() => !!confirmText.value && confirmText.value === urlText.value)

onMounted(async () => {
  if (!urlText.value && urlId) {
    try {
      const data = await findById(urlId)
      urlText.value = data.url || ""
    } catch (e) {
      notification.showErrorNotification(e)
    }
  }
})

async function confirmSubmit() {
  confirmDialogVisible.value = false
  notice.value = ""
  try {
    loading.value = true
    const secToken = window.SEC_TOKEN || route.query.sec_token || ""
    const res = await deleteById(urlId, secToken)

    notification.showSuccessNotification(t("URL deleted."))

    window.location.href = res.redirectUrl
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    loading.value = false
  }
}
</script>
