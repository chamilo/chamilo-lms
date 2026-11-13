<template>
  <section
    v-if="configuration.canManage"
    class="no-print rounded-xl border border-gray-25 bg-white p-5 shadow-sm"
  >
    <h2 class="mb-2 text-lg font-semibold">{{ t("Automation") }}</h2>
    <p class="mb-4 text-sm text-gray-50">
      {{ t("Configure actions to run when the course opens or closes.") }}
    </p>

    <Message v-if="error" severity="error" :closable="false" class="mb-3">{{ error }}</Message>
    <Message v-if="notice" severity="success" :closable="false" class="mb-3">{{ notice }}</Message>

    <div class="mb-4 flex flex-wrap gap-3">
      <BaseButton
        :label="t('Course opens now')"
        icon="play-box-outline"
        type="success"
        name="automation-trigger-start"
        :disabled="busy || !hasActions('start')"
        @click="confirmExecution('start')"
      />
      <BaseButton
        :label="t('Course ends now')"
        icon="stop"
        type="danger"
        name="automation-trigger-end"
        :disabled="busy || !hasActions('end')"
        @click="confirmExecution('end')"
      />
    </div>

    <div class="grid gap-3 rounded-lg border border-gray-25 p-4 lg:grid-cols-2">
      <BaseSelect
        id="course-automation-event"
        v-model="eventType"
        :label="t('Event')"
        :options="eventOptions"
        name="automation-event"
      />
      <BaseSelect
        id="course-automation-type"
        v-model="actionType"
        :label="t('Action')"
        :options="actionOptions"
        name="automation-action-type"
      />
      <div v-if="isResourceAction" class="lg:col-span-2">
        <BaseMultiSelect
          input-id="course-automation-resources"
          v-model="resourceIds"
          :label="t('Resources')"
          :options="resourceOptions"
          option-label="label"
          option-value="id"
          name="automation-resources"
        />
      </div>
      <BaseSelect
        v-else-if="actionType === 'send_survey'"
        id="course-automation-survey"
        v-model="targetId"
        :label="t('Survey')"
        :options="surveyOptions"
        name="automation-survey"
      />
      <BaseSelect
        v-else-if="actionType === 'send_certificates'"
        id="course-automation-gradebook"
        v-model="targetId"
        :label="t('Gradebook')"
        :options="gradebookOptions"
        name="automation-gradebook"
      />
      <BaseSelect
        v-else-if="actionType === 'course_visibility'"
        id="course-automation-visibility"
        v-model="settingValue"
        :label="t('Course access')"
        :options="visibilityOptions"
        name="automation-visibility"
      />
      <div class="flex items-end">
        <BaseButton
          :label="t('Add action')"
          icon="plus"
          type="success"
          name="automation-add-action"
          :disabled="busy || !canAddAction"
          @click="addAction"
        />
      </div>
    </div>

    <div class="mt-5">
      <h3 class="mb-2 font-semibold">{{ t("Configured actions") }}</h3>
      <p v-if="!configuration.actions.length" class="text-sm text-gray-50">
        {{ t("No actions configured") }}
      </p>
      <div
        v-for="action in configuration.actions"
        :key="action.id"
        class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-25 py-3"
      >
        <div class="min-w-0">
          <p class="font-medium">{{ describeAction(action) }}</p>
          <p class="text-xs text-gray-50">
            {{ action.eventType === 'start' ? t('Course opens now') : t('Course ends now') }}
            · {{ t(action.status) }}
            <template v-if="action.lastExecutedAt"> · {{ new Date(action.lastExecutedAt).toLocaleString() }}</template>
            <template v-if="action.lastError"> · {{ action.lastError }}</template>
          </p>
        </div>
        <BaseButton
          :label="t('Delete')"
          icon="delete"
          only-icon
          size="small"
          type="danger-text"
          :name="`automation-remove-${action.id}`"
          :disabled="busy || action.status === 'running'"
          @click="confirmDeletion(action)"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Message from "primevue/message"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseAutomationService from "../../services/courseAutomationService"

const props = defineProps({
  courseId: { type: Number, required: true },
  sessionId: { type: Number, default: 0 },
})

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const configuration = reactive({
  canManage: false,
  canChangeCourseVisibility: false,
  canUseHiddenVisibility: false,
  actions: [],
  resources: [],
  surveys: [],
  gradebooks: [],
})
const eventType = ref("start")
const actionType = ref("publish_resources")
const resourceIds = ref([])
const targetId = ref(null)
const settingValue = ref(null)
const busy = ref(false)
const error = ref("")
const notice = ref("")

const context = computed(() => ({ cid: props.courseId, sid: props.sessionId }))
const eventOptions = computed(() => [
  { label: t("Course opens now"), value: "start" },
  { label: t("Course ends now"), value: "end" },
])
const types = {
  publish_resources: "Make content visible",
  hide_resources: "Make content invisible",
  send_survey: "Send survey invitation",
  send_certificates: "Send certificates to students",
  course_visibility: "Change course visibility",
}
const actionOptions = computed(() => {
  const names = eventType.value === "start"
    ? ["publish_resources", "send_survey", "course_visibility"]
    : ["hide_resources", "send_survey", "send_certificates", "course_visibility"]
  return names.filter((name) => name !== "course_visibility" || configuration.canChangeCourseVisibility)
    .map((name) => ({ value: name, label: t(types[name]) }))
})
const isResourceAction = computed(() => ["publish_resources", "hide_resources"].includes(actionType.value))
const resourceOptions = computed(() => configuration.resources
  .filter((resource) => eventType.value === "start" ? resource.visibility !== 2 : resource.visibility === 2)
  .map((resource) => ({ id: resource.id, label: `${resource.title} (${resource.type})` })))
const surveyOptions = computed(() => configuration.surveys.map((survey) => ({ label: survey.title, value: survey.id })))
const gradebookOptions = computed(() => configuration.gradebooks.map((category) => ({ label: category.title, value: category.id })))
const visibilityOptions = computed(() => [
  { label: t("Closed"), value: 0 },
  { label: t("Private"), value: 1 },
  { label: t("Open to platform"), value: 2 },
  { label: t("Public"), value: 3 },
  ...(configuration.canUseHiddenVisibility ? [{ label: t("Hidden"), value: 4 }] : []),
])
const canAddAction = computed(() => {
  if (!actionType.value || !eventType.value) return false
  if (isResourceAction.value) return resourceIds.value.length > 0
  if (actionType.value === "course_visibility") return settingValue.value !== null
  return Number(targetId.value) > 0
})

watch(eventType, (event) => {
  actionType.value = event === "start" ? "publish_resources" : "hide_resources"
  resourceIds.value = []
  targetId.value = null
  settingValue.value = null
})
watch(actionType, () => {
  resourceIds.value = []
  targetId.value = null
  settingValue.value = null
})

function hasActions(event) {
  return configuration.actions.some((action) => action.eventType === event && action.status !== "success")
}
function describeAction(action) {
  const label = t(types[action.actionType] || action.actionType)
  if (action.actionType === "course_visibility") {
    return `${label}: ${visibilityOptions.value.find((o) => o.value === action.settingValue)?.label || action.settingValue}`
  }
  const collection = action.actionType === "send_survey" ? configuration.surveys
    : action.actionType === "send_certificates" ? configuration.gradebooks : configuration.resources
  const target = collection.find((item) => Number(item.id) === Number(action.targetId))
  return target ? `${label}: ${target.title}` : label
}
async function load() {
  if (!props.courseId) return
  busy.value = true
  error.value = ""
  try {
    Object.assign(configuration, await courseAutomationService.getConfiguration(context.value))
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Unable to load automation")
  } finally {
    busy.value = false
  }
}
async function run(operation) {
  busy.value = true
  error.value = ""
  notice.value = ""
  try {
    const response = await operation()
    if (response) Object.assign(configuration, response)
    notice.value = response?.message || t("Updated")
  } catch (e) {
    error.value = e?.response?.data?.detail || e?.message || t("Automation action failed")
  } finally {
    busy.value = false
  }
}
function addAction() {
  if (!canAddAction.value) return
  run(() => courseAutomationService.addAction(context.value, {
    eventType: eventType.value,
    actionType: actionType.value,
    targetIds: resourceIds.value,
    targetId: targetId.value,
    settingValue: settingValue.value,
  }))
}
function confirmExecution(event) {
  requireConfirmation({
    message: t("Execute the configured course actions now? Completed actions will not run again."),
    accept: () => run(() => courseAutomationService.execute(context.value, event)),
  })
}
function confirmDeletion(action) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => run(async () => {
      await courseAutomationService.deleteAction(context.value, action.id)
      return await courseAutomationService.getConfiguration(context.value)
    }),
  })
}
watch(() => [props.courseId, props.sessionId], load)
onMounted(load)
</script>
