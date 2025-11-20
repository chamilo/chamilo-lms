<template>
  <div class="space-y-6">
    <CMAlert type="error" :text="t('Danger zone: deleting an access URL is permanent.')" />
    <section class="p-0">
      <h3 class="mb-2 text-sm font-semibold text-rose-900">{{ t("Confirm deletion") }}</h3>
      <p class="mb-3 text-sm text-rose-800">
        {{ t("Type the full URL to confirm. The URL and its relations will be permanently removed.") }}
      </p>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-rose-900">{{ t("URL") }}</label>
          <input
            v-model="confirmText"
            class="w-full rounded border border-rose-300 p-2 text-sm"
            :placeholder="urlText || 'https://example.com'"
          />
          <p v-if="confirmText && !canDelete" class="mt-1 text-xs text-rose-700">
            {{ t("The value must match exactly:") }} <strong>{{ urlText }}</strong>
          </p>
        </div>
        <div class="flex items-end">
          <button
            class="btn-danger inline-flex items-center gap-3 px-4 py-2 rounded-lg shadow-sm disabled:opacity-50"
            :disabled="loading || !canDelete"
            @click="submit"
            style="background-color: #df3b3b; color: #ffffff; border: none;"
          >
            <i class="mdi mdi-delete-alert"></i>
            <span class="text-sm font-medium">{{ t("Delete URL") }}</span>
          </button>
        </div>
      </div>
    </section>
    <CMAlert v-if="error" type="error" :text="error" />
    <CMAlert v-if="notice" type="success" :text="notice" />
    <CMLoader v-if="loading" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import svc from "../../services/accessUrl"




const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const urlId = Number(route.params.id || 0)
const urlText = ref(route.query.url || "")
const confirmText = ref("")
const loading = ref(false)
const error = ref("")
const notice = ref("")
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

async function submit() {
  if (!confirm(t("This action cannot be undone. Continue?"))) return
  error.value = ""; notice.value = ""
  try {
    loading.value = true
    const secToken = (window.SEC_TOKEN || route.query.sec_token || "")
    const res = await svc.deleteAccessUrl(urlId, confirmText.value, secToken)
    notice.value = res.message || t("URL deleted successfully.")
    if (res.redirectUrl) {
      window.location.href = res.redirectUrl
    } else {
      // by default, go back to the list
      router.push({ name: "AccessUrlsList" })
    }
  } catch (e) {
    error.value = e?.message || (e?.response?.data?.error) || t("Failed to delete URL.")
  } finally {
    loading.value = false
  }
}
</script>
