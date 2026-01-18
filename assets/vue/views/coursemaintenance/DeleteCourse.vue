<template>
  <div class="space-y-6">
    <CMAlert
      type="error"
      :text="t('Danger zone: deleting a course is permanent.')"
    />

    <section class="rounded-lg border border-rose-200 bg-rose-50 p-4">
      <h3 class="mb-2 text-sm font-semibold text-rose-900">
        {{ t("Confirm deletion") }}
      </h3>
      <p class="mb-3 text-sm text-rose-800">
        {{ t("Type the course code to confirm. All data will be permanently removed.") }}
      </p>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-rose-900">
            {{ t("Course code") }}
          </label>
          <input
            v-model="confirmText"
            class="w-full rounded border border-rose-300 p-2 text-sm"
            :placeholder="courseCode || 'ABC101'"
          />
          <p
            v-if="confirmText && !canDelete"
            class="mt-1 text-xs text-rose-700"
          >
            {{ t("The code must match '{0}' exactly.", [courseCode]) }}
          </p>

          <!-- Extra option: delete orphan documents too -->
          <div class="mt-3 flex items-start gap-2 text-xs text-rose-900">
            <input
              id="delete-docs"
              v-model="deleteDocs"
              type="checkbox"
              class="mt-[2px] h-4 w-4 rounded border-rose-300"
            />
            <label
              for="delete-docs"
              class="select-none"
            >
              {{ t("Also delete documents that are only used in this course (if any).") }}
              <span class="block text-[11px] text-rose-700">
                {{
                  t(
                    "If unchecked, those files will remain available to the platform administrator through the 'File information' tool.",
                  )
                }}
              </span>
            </label>
          </div>
        </div>

        <div class="flex items-end">
          <button
            class="btn-danger"
            :disabled="loading || !canDelete"
            @click="submit"
          >
            <i class="mdi mdi-delete-alert"></i>
            {{ t("Delete course") }}
          </button>
        </div>
      </div>
    </section>

    <CMAlert
      v-if="error"
      type="error"
      :text="error"
    />
    <CMAlert
      v-if="notice"
      type="success"
      :text="notice"
    />
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
const deleteDocs = ref(false)
const loading = ref(false)
const error = ref("")
const notice = ref("")

const cidReq = useCidReqStore()
const { course } = storeToRefs(cidReq)
const courseCode = computed(() => String(course?.value?.code || ""))

// Optional strict guard: require exact match
const canDelete = computed(() => !!confirmText.value && confirmText.value === courseCode.value)

async function submit() {
  if (!confirm(t("This action cannot be undone. Continue?"))) return

  error.value = ""
  notice.value = ""

  try {
    loading.value = true

    const payload = {
      confirm: confirmText.value,
      delete_docs: deleteDocs.value ? 1 : 0,
    }

    const res = await svc.deleteCourse(node.value, payload)
    notice.value = res.message || t("Course deleted successfully.")
    if (res.redirectUrl) {
      window.location.href = res.redirectUrl
    }
  } catch (e) {
    error.value = e?.response?.data?.error || t("Failed to delete course.")
  } finally {
    loading.value = false
  }
}
</script>
