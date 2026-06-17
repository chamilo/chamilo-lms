<template>
  <section class="space-y-6">
    <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="flex items-start gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-gray-15">
            <BaseIcon
              icon="calendar-plus"
              size="big"
            />
          </div>
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">{{ t("Surveys") }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-90">
              {{ pageTitle }}
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-gray-600">
              {{ t("Meeting polls let invited users select the dates when they are available.") }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="!isLearningPathContext"
            :label="t('Back to surveys')"
            :route="buildListRoute()"
            icon="back"
            type="black"
          />
          <BaseButton
            v-if="!isEditorMode && meeting.canEdit"
            :label="t('Edit meeting poll')"
            :route="buildEditRoute()"
            icon="edit"
            type="secondary"
          />
          <BaseButton
            v-if="isEditorMode && meeting.surveyId"
            :label="t('View meeting poll')"
            :route="buildMeetingRoute()"
            icon="play-box-outline"
            type="primary-text"
          />
        </div>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage || meeting.message"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage || t(meeting.message) }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
    >
      <div class="flex items-center gap-3 text-sm text-gray-600">
        <BaseIcon
          icon="refresh"
          size="small"
        />
        {{ t("Loading") }}
      </div>
    </div>

    <form
      v-else-if="isEditorMode"
      class="space-y-6"
      novalidate
      @submit.prevent="saveMeeting"
    >
      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="information"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Basic information") }}</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <BaseInputText
            id="meeting_title"
            v-model="form.title"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Title')"
            :required="true"
            error-text="Required field"
            name="survey_title"
          />

          <div class="rounded-xl bg-gray-15 p-4 text-sm text-gray-700">
            {{ t("This will create a survey of type meeting poll. Answers and invitations are handled separately.") }}
          </div>
        </div>

        <div class="mt-6">
          <label
            class="mb-2 block text-sm font-semibold text-gray-700"
            for="meeting_description"
          >
            {{ t("Description") }}
          </label>
          <textarea
            id="meeting_description"
            v-model="form.description"
            class="min-h-28 w-full rounded-lg border border-gray-25 p-3 text-sm focus:border-primary focus:outline-none"
            name="survey_introduction"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="agenda-event"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Availability") }}</h2>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
          <BaseCalendar
            id="meeting_available_from"
            v-model="form.availableFrom"
            :error-text="t('Invalid date')"
            :is-invalid="formSubmitted && !form.availableFrom"
            :label="t('Start Date')"
            :show-time="true"
          />

          <BaseCalendar
            id="meeting_available_until"
            v-model="form.availableUntil"
            :error-text="t('Invalid date')"
            :is-invalid="formSubmitted && !form.availableUntil"
            :label="t('End Date')"
            :show-time="true"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-3">
            <BaseIcon
              icon="calendar-plus"
              size="small"
            />
            <h2 class="text-lg font-semibold text-gray-90">{{ t("Meeting dates") }}</h2>
          </div>
          <BaseButton
            :label="t('Add')"
            icon="plus"
            type="success"
            @click="addSlot"
          />
        </div>

        <div class="space-y-4">
          <div
            v-for="(slot, index) in form.slots"
            :key="slot.localKey"
            class="rounded-xl border border-gray-20 p-4"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <h3 class="font-semibold text-gray-800">{{ t("Date") }} {{ index + 1 }}</h3>
              <BaseButton
                v-if="form.slots.length > 1"
                :label="t('Remove')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="removeSlot(index)"
              />
            </div>

            <div class="grid gap-6 md:grid-cols-2">
              <BaseCalendar
                :id="`meeting_slot_${index}_start`"
                v-model="slot.start"
                :error-text="t('Invalid date')"
                :is-invalid="formSubmitted && !slot.start"
                :label="t('Start Date')"
                :show-time="true"
              />

              <BaseCalendar
                :id="`meeting_slot_${index}_end`"
                v-model="slot.end"
                :error-text="t('Invalid date')"
                :is-invalid="formSubmitted && !slot.end"
                :label="t('End Date')"
                :show-time="true"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-2">
        <BaseButton
          :label="t('Cancel')"
          :route="buildListRoute()"
          icon="close"
          type="secondary"
        />
        <BaseButton
          :is-loading="isSaving"
          :is-submit="true"
          :label="isCreateMode ? t('Create survey') : t('Save')"
          icon="save"
          type="success"
        />
      </div>
    </form>

    <div
      v-else
      class="space-y-6"
    >
      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-90">{{ displayText(meeting.title, t("Meeting poll")) }}</h2>
            <p
              v-if="displayText(meeting.description)"
              class="mt-2 text-sm text-gray-600"
            >
              {{ displayText(meeting.description) }}
            </p>
          </div>
          <span
            v-if="meeting.isAnswered"
            class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700"
          >
            {{ t("Answered") }}
          </span>
        </div>
      </div>

      <form
        v-if="meeting.canSubmit"
        class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm"
        @submit.prevent="submitAvailability"
      >
        <div class="mb-4 flex items-center gap-3">
          <BaseIcon
            icon="check"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("My availability") }}</h2>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <label
            v-for="slot in meeting.slots"
            :key="slot.id"
            class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-20 p-4 hover:border-primary"
          >
            <input
              v-model="selectedSlots"
              :name="`meeting_slot_${slot.id}`"
              :value="slot.id"
              class="h-4 w-4"
              type="checkbox"
            />
            <span class="text-sm font-semibold text-gray-800">{{ slot.label }}</span>
          </label>
        </div>

        <div class="mt-6 flex justify-end">
          <BaseButton
            :is-loading="isSaving"
            :is-submit="true"
            :label="t('Save')"
            icon="save"
            type="success"
          />
        </div>
      </form>

      <div class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-6 flex items-center gap-3">
          <BaseIcon
            icon="tracking"
            size="small"
          />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Meeting poll results") }}</h2>
        </div>

        <div
          v-if="!meeting.participants.length"
          class="rounded-xl bg-gray-15 p-4 text-sm text-gray-600"
        >
          {{ t("No invitations found") }}
        </div>

        <div
          v-else
          class="overflow-x-auto"
        >
          <table class="min-w-full divide-y divide-gray-20 text-sm">
            <thead>
              <tr>
                <th class="whitespace-nowrap px-3 py-3 text-left font-semibold text-gray-700">
                  {{ t("User") }}
                </th>
                <th
                  v-for="slot in meeting.slots"
                  :key="slot.id"
                  class="min-w-36 px-3 py-3 text-center font-semibold text-gray-700"
                >
                  {{ slot.label }}
                  <div class="mt-1 text-xs text-primary">
                    {{ slotTotal(slot.id) }} {{ t("available") }}
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-10">
              <tr
                v-for="participant in meeting.participants"
                :key="participant.id"
              >
                <td class="whitespace-nowrap px-3 py-3 font-semibold text-gray-800">
                  {{ participant.name }}
                </td>
                <td
                  v-for="slot in meeting.slots"
                  :key="`${participant.id}_${slot.id}`"
                  class="px-3 py-3 text-center"
                >
                  <span
                    :class="availabilityClass(participant, slot.id)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold"
                  >
                    {{ availabilitySymbol(participant, slot.id) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const meeting = ref({
  surveyId: null,
  mode: "answer",
  title: "",
  description: "",
  availableFrom: null,
  availableUntil: null,
  slots: [],
  selectedSlots: [],
  participants: [],
  matrix: { answers: {}, totals: {} },
  survey: {},
  canEdit: false,
  canSubmit: false,
  isAnswered: false,
  message: "",
})

const form = reactive({
  title: "",
  description: "",
  availableFrom: null,
  availableUntil: null,
  surveyLanguage: "",
  slots: [],
})

const selectedSlots = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

const isCreateMode = computed(() => route.name === "SurveyMeetingCreate")
const isEditorMode = computed(() => isCreateMode.value || route.name === "SurveyMeetingEdit")
const isLearningPathContext = computed(() => {
  const origin = String(getQueryValue(route.query.origin) || "")
  const returnToLp = String(getQueryValue(route.query.returnToLp) || "")

  return (
    origin === "learnpath" ||
    returnToLp === "1" ||
    Boolean(getQueryValue(route.query.lp_id)) ||
    Boolean(getQueryValue(route.query.lpItemId || route.query.lp_item_id))
  )
})
const pageTitle = computed(() => {
  if (isCreateMode.value) {
    return t("Create meeting poll")
  }

  if (isEditorMode.value) {
    return t("Edit meeting poll")
  }

  return t("Meeting poll")
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams(extra = {}) {
  return {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    lpItemId: getQueryValue(route.query.lpItemId || route.query.lp_item_id),
    lp_id: getQueryValue(route.query.lp_id),
    origin: getQueryValue(route.query.origin),
    type: getQueryValue(route.query.type),
    returnToLp: getQueryValue(route.query.returnToLp),
    embedded: getQueryValue(route.query.embedded),
    isStudentView: getQueryValue(route.query.isStudentView),
    ...extra,
  }
}

function normalizeDateForPayload(value) {
  if (!value) {
    return null
  }

  if (value instanceof Date) {
    return value.toISOString()
  }

  return value
}

function hydrateForm(data) {
  form.title = displayText(data.title)
  form.description = data.description || ""
  form.availableFrom = data.availableFrom ? new Date(data.availableFrom) : null
  form.availableUntil = data.availableUntil ? new Date(data.availableUntil) : null
  form.surveyLanguage = data.surveyLanguage || ""
  form.slots = Array.isArray(data.slots)
    ? data.slots.map((slot, index) => ({
        id: slot.id || null,
        start: slot.start ? new Date(slot.start) : null,
        end: slot.end ? new Date(slot.end) : null,
        localKey: `${slot.id || "new"}_${index}_${Date.now()}`,
      }))
    : []

  if (!form.slots.length) {
    addSlot()
  }
}

async function loadMeeting() {
  isLoading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const surveyId = route.params.surveyId ? Number(route.params.surveyId) : null
    const params = isEditorMode.value && surveyId ? getContextParams({ mode: "edit" }) : getContextParams()
    const response = await surveyService.getSurveyMeeting(params, surveyId)
    meeting.value = response
    selectedSlots.value = Array.isArray(response.selectedSlots) ? [...response.selectedSlots] : []
    hydrateForm(response)
  } catch (error) {
    console.error("Error loading meeting poll", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not load meeting poll")
  } finally {
    isLoading.value = false
  }
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function buildEditRoute() {
  return {
    name: "SurveyMeetingEdit",
    params: {
      node: route.params.node,
      surveyId: route.params.surveyId || meeting.value.surveyId,
    },
    query: getContextParams(),
  }
}

function buildMeetingRoute() {
  return {
    name: "SurveyMeeting",
    params: {
      node: route.params.node,
      surveyId: route.params.surveyId || meeting.value.surveyId,
    },
    query: getContextParams(),
  }
}

function addSlot() {
  const start = new Date()
  start.setDate(start.getDate() + 1)
  start.setHours(9, 0, 0, 0)
  const end = new Date(start)
  end.setHours(10, 0, 0, 0)

  form.slots.push({
    id: null,
    start,
    end,
    localKey: `new_${Date.now()}_${Math.random()}`,
  })
}

function removeSlot(index) {
  form.slots.splice(index, 1)
}

function validateForm() {
  if (!form.title.trim() || !form.availableFrom || !form.availableUntil) {
    return false
  }

  return form.slots.some((slot) => slot.start && slot.end && new Date(slot.start).getTime() < new Date(slot.end).getTime())
}

async function saveMeeting() {
  formSubmitted.value = true
  errorMessage.value = ""
  successMessage.value = ""

  if (!validateForm()) {
    errorMessage.value = t("Please complete the required fields")
    return
  }

  isSaving.value = true

  try {
    const payload = {
      title: form.title,
      description: form.description,
      availableFrom: normalizeDateForPayload(form.availableFrom),
      availableUntil: normalizeDateForPayload(form.availableUntil),
      surveyLanguage: form.surveyLanguage,
      slots: form.slots.map((slot) => ({
        id: slot.id,
        start: normalizeDateForPayload(slot.start),
        end: normalizeDateForPayload(slot.end),
      })),
      csrfToken: meeting.value.csrfToken,
    }

    const surveyId = isCreateMode.value ? null : Number(route.params.surveyId)
    const response = await surveyService.saveSurveyMeeting(payload, getContextParams(), surveyId)
    meeting.value = response
    successMessage.value = response.message ? t(response.message) : t("Saved.")

    if (isCreateMode.value && response.surveyId) {
      await router.replace({
        name: "SurveyMeetingEdit",
        params: {
          node: route.params.node,
          surveyId: response.surveyId,
        },
        query: getContextParams(),
      })
      return
    }

    hydrateForm(response)
  } catch (error) {
    console.error("Error saving meeting poll", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not save meeting poll")
  } finally {
    isSaving.value = false
  }
}

async function submitAvailability() {
  errorMessage.value = ""
  successMessage.value = ""
  isSaving.value = true

  try {
    const response = await surveyService.submitSurveyMeetingAnswer(
      {
        selectedSlots: selectedSlots.value,
        csrfToken: meeting.value.csrfToken,
      },
      getContextParams({ invitationCode: getQueryValue(route.query.invitationCode) }),
      Number(route.params.surveyId),
    )
    meeting.value = response
    selectedSlots.value = Array.isArray(response.selectedSlots) ? [...response.selectedSlots] : []
    successMessage.value = response.message ? t(response.message) : t("Saved.")
  } catch (error) {
    console.error("Error saving meeting availability", error)
    errorMessage.value = error?.response?.data?.["hydra:description"] || t("Could not save meeting availability")
  } finally {
    isSaving.value = false
  }
}

function slotTotal(slotId) {
  return Number(meeting.value.matrix?.totals?.[slotId] || 0)
}

function participantValue(participant, slotId) {
  const value = meeting.value.matrix?.answers?.[participant.id]?.[slotId]
  if (value === undefined || value === null) {
    return participant.answered ? 0 : null
  }

  return Number(value)
}

function availabilitySymbol(participant, slotId) {
  const value = participantValue(participant, slotId)
  if (value === 1) {
    return "✓"
  }

  if (value === 0) {
    return "×"
  }

  return "–"
}

function availabilityClass(participant, slotId) {
  const value = participantValue(participant, slotId)
  if (value === 1) {
    return "bg-green-100 text-green-700"
  }

  if (value === 0) {
    return "bg-gray-100 text-gray-600"
  }

  return "bg-yellow-100 text-yellow-700"
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  if (typeof document === "undefined") {
    return String(value)
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

onMounted(loadMeeting)

watch(
  () => [route.params.surveyId, route.name, route.query],
  () => loadMeeting(),
)
</script>
