<template>
  <div class="flex flex-col gap-6 pb-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-gray-90">{{ t("Course lifecycle") }}</h1>
        <p class="mt-2 text-sm text-gray-60">
          {{ t("Review the courses linked to expired BuyCourses subscriptions before taking action.") }}
        </p>
      </div>
      <a
        class="text-primary underline"
        href="/plugin/BuyCourses/src/list_service.php"
      >
        {{ t("Back to services") }}
      </a>
    </div>

    <div
      v-if="errorMessage"
      class="rounded border border-red-200 bg-red-50 p-4 text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="rounded border border-green-200 bg-green-50 p-4 text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

    <section class="rounded-lg border border-gray-25 bg-white p-5">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Lifecycle simulation") }}</h2>
          <p class="mt-1 text-sm text-gray-60">
            {{ t("Simulation shows planned actions without changing course or subscription data.") }}
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :label="t('Refresh')"
            icon="refresh"
            type="secondary"
            :disabled="isBusy"
            :is-loading="isLoading"
            @click="loadPreview"
          />
          <BaseButton
            :label="t('Run simulation')"
            icon="search"
            type="primary"
            :disabled="isBusy"
            :is-loading="isSimulating"
            @click="runSimulation"
          />
        </div>
      </div>
      <div
        v-if="output"
        class="mt-4"
      >
        <h3 class="mb-2 font-medium">{{ t("Command output") }}</h3>
        <pre class="max-h-80 overflow-auto whitespace-pre-wrap break-words rounded bg-gray-90 p-4 text-xs text-white">{{
          output
        }}</pre>
      </div>
    </section>

    <section class="rounded-lg border border-gray-25 bg-white p-5">
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Tracked hidden subscription courses") }}</h2>
      <p class="mb-4 mt-1 text-sm text-gray-60">
        {{ t("Only courses marked for deletion are eligible; other courses remain untouched.") }}
      </p>
      <div class="mb-4 flex flex-wrap gap-3 text-sm">
        <span class="rounded bg-green-100 px-3 py-2 text-green-700">{{ t("Eligible") }}: {{ counts.delete }}</span>
        <span class="rounded bg-blue-100 px-3 py-2 text-blue-700">{{ t("Pending") }}: {{ counts.pending }}</span>
        <span class="rounded bg-red-100 px-3 py-2 text-red-700">{{ t("Blocked") }}: {{ counts.blocked }}</span>
        <span class="rounded bg-gray-100 px-3 py-2 text-gray-700">{{ t("Disabled") }}: {{ counts.disabled }}</span>
      </div>
      <BaseTable
        :values="courses"
        :is-loading="isLoading"
        :text-for-empty="t('No hidden subscription courses to review.')"
        data-key="subscription_course_id"
      >
        <Column :header="t('Course')">
          <template #body="{ data }">
            <div class="font-medium">{{ data.course_title }}</div>
            <div class="text-sm text-gray-60">{{ data.course_code }} (ID: {{ data.course_id }})</div>
          </template>
        </Column>
        <Column
          field="service_sale_id"
          :header="t('Sale ID')"
        />
        <Column
          field="hidden_at"
          :header="t('Hidden since (UTC)')"
        />
        <Column :header="t('Action')">
          <template #body="{ data }">
            <span
              class="rounded px-2 py-1 text-sm font-semibold"
              :class="statusClass(data.decision.action)"
            >
              {{ statusLabel(data.decision.action) }}
            </span>
          </template>
        </Column>
        <Column :header="t('Due (UTC)')">
          <template #body="{ data }">
            {{ data.decision.due_at || "—" }}
          </template>
        </Column>
        <Column :header="t('Reason')">
          <template #body="{ data }">
            {{ data.decision.reason }}
          </template>
        </Column>
      </BaseTable>
    </section>

    <section class="rounded-lg border border-red-200 bg-white p-5">
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Manual execution") }}</h2>
      <p class="mb-4 mt-2 text-sm text-gray-60">
        {{ t("Permanent course deletion cannot be undone. Only one eligible course can be deleted per execution.") }}
      </p>
      <p
        v-if="counts.delete === 0"
        class="mb-4 text-sm text-gray-60"
      >
        {{ t("There are currently no eligible courses to delete.") }}
      </p>
      <div class="flex max-w-lg flex-col gap-3">
        <BaseInputText
          id="buycourses-lifecycle-course-id"
          v-model="selectedCourseId"
          name="courseId"
          :label="t('Course') + ' ID'"
          autocomplete="off"
          :disabled="isBusy || counts.delete === 0"
        />
        <BaseInputText
          id="buycourses-lifecycle-confirmation"
          v-model="confirmation"
          name="confirmation"
          :label="t('Type DELETE EXPIRED COURSES to proceed')"
          autocomplete="off"
          :disabled="isBusy || counts.delete === 0"
        />
        <div>
          <BaseButton
            :label="t('Delete eligible course')"
            icon="delete"
            type="danger"
            :disabled="isBusy || !targetCourse || confirmation !== requiredConfirmation"
            :is-loading="isExecuting"
            @click="confirmExecution"
          />
        </div>
      </div>
      <p class="mt-4 text-sm text-gray-60">
        {{ t("Scheduled deletion requires the explicit --delete-expired command option.") }}
      </p>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const requiredConfirmation = "DELETE EXPIRED COURSES"
const courses = ref([])
const counts = ref({ delete: 0, pending: 0, blocked: 0, disabled: 0 })
const confirmation = ref("")
const selectedCourseId = ref("")
const targetCourse = computed(() =>
  courses.value.find(
    (row) => row.decision?.action === "delete" && String(row.course_id) === selectedCourseId.value.trim(),
  ),
)
const output = ref("")
const errorMessage = ref("")
const successMessage = ref("")
const isLoading = ref(false)
const isSimulating = ref(false)
const isExecuting = ref(false)
const isBusy = computed(() => isLoading.value || isSimulating.value || isExecuting.value)

function statusClass(action) {
  return (
    {
      delete: "bg-green-100 text-green-700",
      pending: "bg-blue-100 text-blue-700",
      blocked: "bg-red-100 text-red-700",
      disabled: "bg-gray-100 text-gray-700",
    }[action] || "bg-gray-100 text-gray-700"
  )
}

function statusLabel(action) {
  return (
    {
      delete: t("Eligible"),
      pending: t("Pending"),
      blocked: t("Blocked"),
      disabled: t("Disabled"),
    }[action] || action
  )
}

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    credentials: "same-origin",
    ...options,
    headers: {
      Accept: "application/json",
      ...(options.body ? { "Content-Type": "application/json" } : {}),
    },
  })
  const data = await response.json().catch(() => ({}))
  if (!response.ok) {
    const error = new Error(data.error || t("Unable to process the request."))
    error.commandOutput = data.output || ""
    throw error
  }
  return data
}

function applyPreview(data) {
  courses.value = Array.isArray(data.courses) ? data.courses : []
  counts.value = data.counts || { delete: 0, pending: 0, blocked: 0, disabled: 0 }
}

async function loadPreview() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    applyPreview(await requestJson("/admin/buycourses/course-lifecycle-data"))
  } catch (error) {
    errorMessage.value = error.message || t("Unable to load course lifecycle data.")
  } finally {
    isLoading.value = false
  }
}

async function runSimulation() {
  isSimulating.value = true
  errorMessage.value = ""
  successMessage.value = ""
  output.value = ""
  try {
    const data = await requestJson("/admin/buycourses/course-lifecycle-run", {
      method: "POST",
      body: JSON.stringify({ action: "simulate" }),
    })
    output.value = data.output || ""
    applyPreview(data.preview)
    successMessage.value = t("Simulation completed without changing data.")
  } catch (error) {
    errorMessage.value = error.message || t("Unable to run the simulation.")
    output.value = error.commandOutput || ""
  } finally {
    isSimulating.value = false
  }
}

function confirmExecution() {
  if (isBusy.value || !targetCourse.value || confirmation.value !== requiredConfirmation) return
  const courseId = Number(targetCourse.value.course_id)
  const courseCode = targetCourse.value.course_code
  requireConfirmation({
    title: t("Confirmation"),
    message: [
      t("This permanently deletes an eligible course. Do you want to continue?"),
      `${courseCode} (ID: ${courseId})`,
    ].join(" "),
    accept: () => executeDeletion(courseId),
  })
}

async function executeDeletion(courseId) {
  if (isBusy.value || Number(targetCourse.value?.course_id) !== courseId || confirmation.value !== requiredConfirmation)
    return
  isExecuting.value = true
  errorMessage.value = ""
  successMessage.value = ""
  output.value = ""
  try {
    const data = await requestJson("/admin/buycourses/course-lifecycle-run", {
      method: "POST",
      body: JSON.stringify({ action: "execute", confirmation: confirmation.value, courseId }),
    })
    output.value = data.output || ""
    applyPreview(data.preview)
    confirmation.value = ""
    selectedCourseId.value = ""
    successMessage.value = t("Lifecycle execution completed. Review the command output.")
  } catch (error) {
    const message = error.message || t("Unable to execute the lifecycle.")
    output.value = error.commandOutput || ""
    await loadPreview()
    errorMessage.value = message
  } finally {
    isExecuting.value = false
  }
}

onMounted(loadPreview)
</script>
