<template>
  <div class="space-y-6">
    <Message severity="warn" class="inline-block max-w-max">Danger zone: deleting an access URL is permanent.</Message>
    <section class="p-0">
      <h3 class="mb-2 text-sm font-semibold text-rose-900">{{ t("Confirm deletion") }}</h3>
      <p class="mb-3 text-sm text-rose-800">
        {{ t("Type the full URL to confirm. The URL and its relations will be permanently removed.") }}
      </p>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3 items-center">
        <div class="md:col-span-2">
          <label class="mb-1 block text-xs font-medium text-rose-900">{{ t("URL") }}</label>
          <BaseInputText
            v-model="confirmText"
            class="w-full"
            :placeholder="urlText || 'https://example.com'"
          />
          <p v-if="confirmText && !canDelete" class="mt-1 text-xs text-rose-700">
            {{ t("The value must match exactly:") }} <strong>{{ urlText }}</strong>
          </p>
        </div>
        <div class="flex items-center md:justify-end md:col-span-1 md:pr-8">
          <div class="hidden md:block">
            <BaseButton
              :label="t('Delete URL')"
              icon="delete"
              type="danger"
              :disabled="loading || !canDelete"
              :isLoading="loading"
              @click="openConfirmDialog"
            />
          </div>
        </div>
      </div>
    </section>
    <BaseDialogConfirmCancel
      v-model:isVisible="confirmDialogVisible"
      :title="t('Confirm deletion')"
      :confirmLabel="t('Delete')"
      :cancelLabel="t('Cancel')"
      @confirmClicked="confirmSubmit"
    />
  </div>
</template>

<script setup >
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import svc from "../../services/accessurlService"
import Message from "primevue/message"

import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const urlId = Number(route.params.id || 0)
const urlText = ref(route.query.url || "")
const confirmText = ref("")
const loading = ref(false)
const error = ref("")
const notice = ref("")
const confirmDialogVisible = ref(false)

const canDelete = computed(() => !!confirmText.value && confirmText.value === urlText.value)

onMounted(async () => {
  if (!urlText.value && urlId) {
    try {
      const data = await svc.getUrl(urlId)
      urlText.value = data.url || ""
    } catch (e) {
      error.value = t("Unable to load URL data.")
    }
  }
})

function openConfirmDialog() {
  confirmDialogVisible.value = true
}

async function confirmSubmit() {
  confirmDialogVisible.value = false
  error.value = ""
  notice.value = ""
  try {
    loading.value = true
    const secToken = (window.SEC_TOKEN || route.query.sec_token || "")
    const res = await svc.deleteAccessUrl(urlId, confirmText.value, secToken)
    notice.value = res.message || t("URL deleted successfully.")
    if (res.redirectUrl) {
      window.location.href = res.redirectUrl
    } else {
      router.push({ name: "AccessUrlsList" })
    }
  } catch (e) {
    error.value = e?.message || (e?.response?.data?.error) || t("Failed to delete URL.")
  } finally {
    loading.value = false
  }
}
</script>
