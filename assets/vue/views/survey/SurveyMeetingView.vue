<template>
  <section class="space-y-6">
    <div>
      <SectionHeader
        :title="pageTitle"
        icon="calendar-plus"
      >
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
      </SectionHeader>
      <p class="text-body-2 text-gray-50">
        {{ t("Meeting polls let invited users select the dates when they are available.") }}
      </p>
    </div>

    <div
      v-if="isLoading"
      aria-busy="true"
      class="space-y-6"
    >
      <div
        v-for="(card, index) in loadingSkeleton"
        :key="index"
        :class="CARD_CLASS"
      >
        <Skeleton
          v-if="card.heading"
          :height="`${card.heading}px`"
          :style="{ marginBottom: `${card.gap}px` }"
          width="16rem"
        />
        <Skeleton :height="`${card.body}px`" />
      </div>
    </div>

    <form
      v-else-if="isEditorMode && !loadFailed"
      class="space-y-6"
      novalidate
      @submit.prevent="saveMeeting"
    >
      <div :class="CARD_CLASS">
        <div class="mb-6 flex items-center gap-3">
          <span :class="ICON_BOX_CLASS">
            <BaseIcon
              icon="information"
              size="small"
            />
          </span>
          <h2 class="text-body-1 font-semibold text-gray-90">{{ t("Basic information") }}</h2>
        </div>

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

        <BaseTextArea
          id="meeting_description"
          v-model="form.description"
          label="Description"
          name="survey_introduction"
          rows="4"
        />

        <p class="text-body-2 text-gray-50">
          {{ t("This will create a survey of type meeting poll. Answers and invitations are handled separately.") }}
        </p>
      </div>

      <div :class="CARD_CLASS">
        <div class="mb-6 flex items-center gap-3">
          <span :class="ICON_BOX_CLASS">
            <BaseIcon
              icon="agenda-event"
              size="small"
            />
          </span>
          <h2 class="text-body-1 font-semibold text-gray-90">{{ t("Availability") }}</h2>
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
            :label="t('End date')"
            :show-time="true"
          />
        </div>
      </div>

      <div :class="CARD_CLASS">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-3">
            <span :class="ICON_BOX_CLASS">
              <BaseIcon
                icon="calendar-plus"
                size="small"
              />
            </span>
            <h2 class="text-body-1 font-semibold text-gray-90">{{ t("Meeting dates") }}</h2>
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
            class="rounded-lg border border-gray-25 bg-support-2 p-4"
          >
            <div class="mb-4 flex items-center justify-between gap-3">
              <h3 class="text-body-2 font-semibold text-gray-90">{{ t("Date") }} {{ index + 1 }}</h3>
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
                :label="t('End date')"
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
          type="black"
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
      v-else-if="!loadFailed"
      class="space-y-6"
    >
      <div :class="CARD_CLASS">
        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-90">{{ displayText(meeting.title, t("Meeting poll")) }}</h2>
            <p
              v-if="displayText(meeting.description)"
              class="mt-2 text-body-2 text-gray-50"
            >
              {{ displayText(meeting.description) }}
            </p>
          </div>
          <span
            v-if="meeting.isAnswered"
            class="rounded-full bg-success/10 px-3 py-1 text-caption font-semibold text-success"
          >
            {{ t("Answered") }}
          </span>
        </div>
      </div>

      <form
        v-if="meeting.canSubmit"
        :class="CARD_CLASS"
        @submit.prevent="submitAvailability"
      >
        <div class="mb-4 flex items-center gap-3">
          <span :class="ICON_BOX_CLASS">
            <BaseIcon
              icon="check"
              size="small"
            />
          </span>
          <h2 class="text-body-1 font-semibold text-gray-90">{{ t("My availability") }}</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <label
            v-for="slot in meeting.slots"
            :key="slot.id"
            :class="selectedSlots.includes(slot.id) ? 'border-primary bg-support-1' : 'border-gray-25 bg-white'"
            class="flex cursor-pointer items-center gap-3 rounded-lg border p-4 hover:border-primary"
          >
            <Checkbox
              v-model="selectedSlots"
              :input-id="`meeting_slot_${slot.id}`"
              :name="`meeting_slot_${slot.id}`"
              :value="slot.id"
            />
            <span class="text-body-2 font-semibold text-gray-90">{{ slot.label }}</span>
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

      <div :class="CARD_CLASS">
        <div class="mb-4 flex items-center gap-3">
          <span :class="ICON_BOX_CLASS">
            <BaseIcon
              icon="tracking"
              size="small"
            />
          </span>
          <h2 class="text-body-1 font-semibold text-gray-90">{{ t("Meeting poll results") }}</h2>
        </div>

        <p
          v-if="!meeting.participants.length"
          class="text-body-2 text-gray-50"
        >
          {{ t("No invitations found") }}
        </p>

        <div
          v-else
          class="overflow-x-auto"
        >
          <table class="min-w-full text-body-2">
            <thead>
              <tr class="bg-support-2">
                <th class="whitespace-nowrap rounded-s-lg px-4 py-3 text-start font-semibold text-gray-90">
                  {{ t("User") }}
                </th>
                <th
                  v-for="slot in meeting.slots"
                  :key="slot.id"
                  class="min-w-36 px-4 py-3 text-center font-semibold text-gray-90 last:rounded-e-lg"
                >
                  {{ slot.label }}
                  <div class="mt-2">
                    <span class="rounded-full bg-support-1 px-2 py-1 text-caption font-semibold text-primary">
                      {{ slotTotal(slot.id) }} {{ t("available") }}
                    </span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="participant in meeting.participants"
                :key="participant.id"
                class="border-b border-gray-25 last:border-b-0"
              >
                <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-90">
                  {{ participant.name }}
                </td>
                <td
                  v-for="slot in meeting.slots"
                  :key="`${participant.id}_${slot.id}`"
                  class="px-4 py-3 text-center"
                >
                  <span
                    :class="availabilityClass(participant, slot.id)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full font-bold"
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
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import Checkbox from "primevue/checkbox"
import Skeleton from "primevue/skeleton"
import { useTranslatedHtml } from "../../composables/useTranslatedHtml"
import surveyService from "../../services/surveyService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()

const CARD_CLASS = "rounded-lg border border-gray-25 bg-white p-6 shadow-sm"
const ICON_BOX_CLASS = "flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-support-1 text-primary"

// One entry per card: heading height, gap under it and body height, as measured on the loaded
// page at 1440 px with a poll of three dates and three participants. The answer form card is
// absent because the page only knows after loading whether the user may answer.
const ANSWER_SKELETON = [
  { heading: 0, gap: 0, body: 52 },
  { heading: 32, gap: 16, body: 235 },
]
const EDITOR_SKELETON = [
  { heading: 32, gap: 24, body: 189 },
  { heading: 32, gap: 24, body: 62 },
  { heading: 40, gap: 24, body: 464 },
]
const route = useRoute()
const router = useRouter()
const { displayTranslatedHtml } = useTranslatedHtml()

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
const loadFailed = ref(false)

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
const loadingSkeleton = computed(() => (isEditorMode.value ? EDITOR_SKELETON : ANSWER_SKELETON))
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
  loadFailed.value = false

  try {
    const surveyId = route.params.surveyId ? Number(route.params.surveyId) : null
    const params = isEditorMode.value && surveyId ? getContextParams({ mode: "edit" }) : getContextParams()
    const response = await surveyService.getSurveyMeeting(params, surveyId)
    meeting.value = response
    selectedSlots.value = Array.isArray(response.selectedSlots) ? [...response.selectedSlots] : []
    hydrateForm(response)
  } catch (error) {
    console.error("Error loading meeting poll", error)
    loadFailed.value = true
    showErrorNotification(error?.response?.data?.["hydra:description"] || t("Could not load meeting poll"))
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

  if (!validateForm()) {
    showErrorNotification(t("Please complete the required fields"))
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
    }

    const surveyId = isCreateMode.value ? null : Number(route.params.surveyId)
    const response = await surveyService.saveSurveyMeeting(payload, getContextParams(), surveyId)
    meeting.value = response
    showSuccessNotification(response.message ? t(response.message) : t("Saved"))

    if (response.surveyId) {
      const meetingRoute = {
        name: "SurveyMeeting",
        params: {
          node: route.params.node,
          surveyId: response.surveyId,
        },
        query: getContextParams(),
      }
      // Replace the create form so going back cannot submit the same poll twice.
      await (isCreateMode.value ? router.replace(meetingRoute) : router.push(meetingRoute))
      return
    }

    hydrateForm(response)
  } catch (error) {
    console.error("Error saving meeting poll", error)
    showErrorNotification(error?.response?.data?.["hydra:description"] || t("Could not save meeting poll"))
  } finally {
    isSaving.value = false
  }
}

async function submitAvailability() {
  isSaving.value = true

  try {
    const response = await surveyService.submitSurveyMeetingAnswer(
      {
        selectedSlots: selectedSlots.value,
      },
      getContextParams({ invitationCode: getQueryValue(route.query.invitationCode) }),
      Number(route.params.surveyId),
    )
    meeting.value = response
    selectedSlots.value = Array.isArray(response.selectedSlots) ? [...response.selectedSlots] : []
    showSuccessNotification(response.message ? t(response.message) : t("Saved"))
    if (isLearningPathContext.value && window.parent !== window) {
      window.parent.postMessage({ type: "chamilo:learning-path:refresh" }, window.location.origin)
    }
  } catch (error) {
    console.error("Error saving meeting availability", error)
    showErrorNotification(error?.response?.data?.["hydra:description"] || t("Could not save meeting availability"))
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
    return "bg-success/10 text-success"
  }

  if (value === 0) {
    return "bg-gray-20 text-gray-50"
  }

  return "bg-warning/10 text-warning"
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
  const decodedValue = decodeHtml(displayTranslatedHtml(value))
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
