<template>
  <section class="space-y-4 rounded-xl border border-gray-25 bg-white p-6 shadow-sm">
    <div>
      <h3 class="text-xl font-semibold text-gray-90">{{ labels.title }}</h3>
    </div>

    <BaseCheckbox
      id="grading-electronic-preview"
      v-model="previewOnly"
      :label="labels.previewHelp"
      name="preview_only"
    />

    <div class="flex flex-wrap items-start gap-4">
      <BaseCalendar
        id="grading-electronic-range"
        v-model="dateRange"
        :label="t('Date range')"
        type="range"
      />
      <BaseInputText
        id="grading-electronic-course"
        v-model="externalCourseId"
        :label="labels.courseId"
        name="course"
      />
    </div>

    <div class="flex flex-wrap gap-2">
      <BaseButton
        v-if="previewOnly"
        :disabled="!canSubmit"
        :is-loading="isRunning"
        :label="t('Preview')"
        icon="eye-on"
        type="primary"
        @click="generate('preview')"
      />
      <template v-else>
        <BaseButton
          :disabled="!canSubmit"
          :is-loading="isRunning"
          :label="t('Generate TXT')"
          icon="file-text"
          type="success"
          @click="generate('txt')"
        />
        <BaseButton
          :disabled="!canSubmit"
          :is-loading="isRunning"
          :label="t('Generate XLSX')"
          icon="file-excel"
          type="success"
          @click="generate('xlsx')"
        />
      </template>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="preview"
      class="space-y-4"
    >
      <p class="text-sm text-gray-90">
        {{ t("Users") }}: {{ preview.processedCount }} — PASS: {{ preview.passedCount }} — SKIP:
        {{ preview.processedCount - preview.passedCount }}
      </p>
      <BaseTable
        :text-for-empty="t('No data available')"
        :total-items="preview.rows.length"
        :values="preview.rows"
        data-key="user_id"
      >
        <Column
          :header="t('User ID')"
          field="user_id"
        />
        <Column
          :header="t('Username')"
          field="username"
        />
        <Column
          :header="t('External ID')"
          field="external_id"
        />
        <Column
          :header="t('Score')"
          field="score"
        />
        <Column
          :header="t('Status')"
          field="status"
        />
      </BaseTable>
      <details>
        <summary class="cursor-pointer font-semibold">TXT</summary>
        <pre class="mt-2 max-h-96 overflow-auto rounded-lg bg-gray-10 p-3 text-body-2">{{ preview.txt }}</pre>
      </details>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const labels = ref({ title: "", courseId: "", previewHelp: t("Preview") })
const previewOnly = ref(true)
const dateRange = ref([new Date(), new Date()])
const externalCourseId = ref("")
const isRunning = ref(false)
const errorMessage = ref("")
const preview = ref(null)

const canSubmit = computed(
  () => !isRunning.value && dateRange.value?.[0] && dateRange.value?.[1] && externalCourseId.value,
)

// A preview of one format no longer matches once the form changes
watch([previewOnly, dateRange, externalCourseId], () => {
  preview.value = null
})

function contextParams() {
  return { cid: route.query.cid, sid: route.query.sid || 0, gid: route.query.gid || 0 }
}

// The server reads calendar days, so keep the local date rather than its UTC shift
function toDay(value) {
  const date = new Date(value)
  const pad = (n) => String(n).padStart(2, "0")

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

async function generate(format) {
  isRunning.value = true
  errorMessage.value = ""
  preview.value = null

  try {
    const result = await gradebookService.generateGradingElectronic(
      {
        format,
        rangeStart: toDay(dateRange.value[0]),
        rangeEnd: toDay(dateRange.value[1]),
        course: externalCourseId.value,
      },
      contextParams(),
    )

    if ("preview" === format) {
      preview.value = result
    }
  } catch (error) {
    errorMessage.value = error?.message || t("An error occurred")
  } finally {
    isRunning.value = false
  }
}

onMounted(async () => {
  try {
    const defaults = await gradebookService.getGradingElectronicDefaults(contextParams())
    labels.value = defaults.labels
    externalCourseId.value = defaults.course
  } catch {
    errorMessage.value = t("An error occurred")
  }
})
</script>
