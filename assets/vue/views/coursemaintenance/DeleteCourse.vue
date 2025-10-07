<template>
  <div class="space-y-6">
    <CMAlert type="error" :text="t('Danger zone: deleting a course is permanent.')" />

    <section class="rounded-lg border border-rose-200 bg-rose-50 p-4">
      <h3 class="mb-2 text-sm font-semibold text-rose-900">{{ t("Confirm deletion") }}</h3>
      <p class="mb-3 text-sm text-rose-800">
        {{ t("Type the course code to confirm. All data will be permanently removed.") }}
      </p>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-rose-900">{{ t("Course code") }}</label>
          <input
            v-model="confirmText"
            class="w-full rounded border border-rose-300 p-2 text-sm"
            :placeholder="courseCode || 'ABC101'"
          />
          <p v-if="confirmText && !canDelete" class="mt-1 text-xs text-rose-700">
            {{ t("The code must match exactly:") }} <strong>{{ courseCode }}</strong>
          </p>
        </div>
        <div class="flex items-end">
          <button class="btn-danger" :disabled="loading || !canDelete" @click="submit">
            <i class="mdi mdi-delete-alert"></i> {{ t("Delete course") }}
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
import { ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import svc from "../../services/courseMaintenance"

const { t } = useI18n()
const route = useRoute()
const node = ref(Number(route.params.node || 0))

const confirmText = ref("")
const loading = ref(false)
const error = ref("")
const notice = ref("")

// Read current course from Pinia (header ya lo muestra)
const cidReq = useCidReqStore()
const { course } = storeToRefs(cidReq)
const courseCode = computed(() => String(course?.value?.code || ""))

// Optional strict guard: require exact match
const canDelete = computed(() => !!confirmText.value && confirmText.value === courseCode.value)

async function submit() {
  if (!confirm(t("This action cannot be undone. Continue?"))) return
  error.value = ""; notice.value = ""
  try {
    loading.value = true
    const res = await svc.deleteCourse(node.value, confirmText.value)
    notice.value = res.message || t("Course deleted successfully.")
    if (res.redirectUrl) window.location.href = res.redirectUrl
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to delete course.")
  } finally {
    loading.value = false
  }
}
</script>
