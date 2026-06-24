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
          <BaseInputText
            id="delete-course-code"
            v-model="confirmText"
            :label="t('Course code')"
            name="course_code"
            :placeholder="courseCode || 'ABC101'"
          />
          <p
            v-if="confirmText && !canDelete"
            class="mt-1 text-xs text-rose-700"
          >
            {{ t("The code must match '{0}' exactly.", [courseCode]) }}
          </p>

          <!-- Extra option: delete orphan documents too -->
          <div class="mt-3 text-xs text-rose-900">
            <BaseCheckbox
              id="delete-docs"
              v-model="deleteDocs"
              name="delete_docs"
              :label="t('Also delete documents that are only used in this course (if any).')"
            />
            <p class="mt-1 text-[11px] text-rose-700">
              {{
                t(
                  "If unchecked, those files will remain available to the platform administrator through the 'File information' tool.",
                )
              }}
            </p>
          </div>
        </div>

        <div class="flex items-end">
          <BaseButton
            :label="t('Delete course')"
            icon="delete-forever"
            type="danger"
            :disabled="loading || !canDelete"
            @click="submit"
          />
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
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"

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
