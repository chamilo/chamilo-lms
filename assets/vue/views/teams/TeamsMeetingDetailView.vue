<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Meeting details')"
    >
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        :route="listRoute"
        type="primary-text"
      />
      <BaseButton
        v-if="meeting?.canJoin"
        :label="t('Join meeting')"
        icon="link-external"
        only-icon
        type="primary-text"
        @click="joinMeeting"
      />
      <BaseButton
        v-if="meeting?.canJoin"
        :label="t('Copy meeting link')"
        icon="copy"
        only-icon
        type="primary-text"
        @click="copyMeetingLink"
      />
      <BaseButton
        v-if="canExportCalendar"
        :label="t('Add to calendar')"
        icon="calendar-plus"
        only-icon
        type="primary-text"
        @click="downloadCalendar"
      />
      <BaseButton
        v-if="meeting?.canManage"
        :label="t('Edit')"
        icon="pencil"
        only-icon
        :route="editRoute"
        type="secondary-text"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <BaseCard v-else-if="meeting">
      <template #title>
        <div class="flex min-w-0 flex-wrap items-center gap-3">
          <h2 class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90">
            {{ meeting.title }}
          </h2>
          <span :class="statusClass(meeting.status)">
            {{ statusLabel(meeting.status) }}
          </span>
        </div>
      </template>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
          <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ t("Organizer") }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-90">
            {{ meeting.organizerName || "—" }}
          </div>
        </div>

        <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
          <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ t("Start") }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-90">
            {{ formatMeetingDate(meeting.startAt) }}
          </div>
        </div>

        <div class="rounded-xl border border-gray-20 bg-gray-10 p-4">
          <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ t("End") }}
          </div>
          <div class="mt-1 text-sm font-medium text-gray-90">
            {{ formatMeetingDate(meeting.endAt) }}
          </div>
        </div>

      </div>

      <div
        v-if="meeting.calendarId"
        class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      >
        {{ t("Add to calendar") }} ✓
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import teamsService from "../../services/teamsService"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const route = useRoute()
const { course, session } = storeToRefs(useCidReqStore())
const { abbreviatedDatetime } = useFormatDate()
const { showErrorNotification, showSuccessNotification, showWarningNotification } = useNotification()

const meeting = ref(null)
const loading = ref(false)
const loadError = ref(false)

const isCourseScope = computed(() => route.meta.teamsCourseScope === true)
const hubScope = computed(() => (route.query.scope === "global" ? "global" : "personal"))
const courseContext = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))
const itemParams = computed(() => (isCourseScope.value ? courseContext.value : {}))
const canExportCalendar = computed(() =>
  ["upcoming", "in_progress"].includes(meeting.value?.status),
)

const listRoute = computed(() => {
  if (isCourseScope.value) {
    return { name: "TeamsCourseList", query: route.query }
  }

  return { name: "TeamsHubList", query: { ...route.query, scope: hubScope.value } }
})

const editRoute = computed(() => {
  if (!meeting.value) {
    return listRoute.value
  }

  if (isCourseScope.value) {
    return {
      name: "TeamsCourseEdit",
      params: { meetingId: meeting.value.id },
      query: route.query,
    }
  }

  return {
    name: "TeamsHubEdit",
    params: { meetingId: meeting.value.id },
    query: { ...route.query, scope: hubScope.value },
  }
})

watch(
  () => `${route.params.meetingId ?? ""}:${JSON.stringify(itemParams.value)}`,
  () => loadMeeting(),
  { immediate: true },
)

async function loadMeeting() {
  const meetingId = Number(route.params.meetingId ?? 0)
  if (!Number.isInteger(meetingId) || meetingId <= 0) {
    loadError.value = true
    return
  }

  loading.value = true
  loadError.value = false

  try {
    meeting.value = await teamsService.getMeeting(meetingId, itemParams.value)
  } catch (error) {
    meeting.value = null
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function joinMeeting() {
  const meetingId = Number(meeting.value?.id ?? 0)
  if (!Number.isInteger(meetingId) || meetingId <= 0) {
    showWarningNotification(t("The meeting join link is not available."))
    return
  }

  window.open(`/conference/teams-auth/join/${meetingId}`, "_blank", "noopener,noreferrer")
}

async function copyMeetingLink() {
  const url = teamsService.getProtectedJoinUrl(meeting.value?.id)
  if (!url) {
    showWarningNotification(t("The meeting join link is not available."))
    return
  }

  try {
    if (!navigator.clipboard || !window.isSecureContext) {
      throw new Error("Clipboard API unavailable")
    }

    await navigator.clipboard.writeText(url)
    showSuccessNotification(t("Meeting link copied."))
  } catch (error) {
    console.error("Error copying Microsoft Teams meeting link", error)
    window.prompt(t("Copy link"), url)
  }
}

function downloadCalendar() {
  if (!teamsService.downloadCalendarFile(meeting.value)) {
    showWarningNotification(t("The meeting join link is not available."))
  }
}

function formatMeetingDate(value) {
  if (!value) {
    return "—"
  }

  try {
    return abbreviatedDatetime(value)
  } catch {
    return value
  }
}

function statusLabel(status) {
  const labels = {
    upcoming: t("Upcoming"),
    in_progress: t("In progress"),
    past: t("Past"),
    cancelled: t("Cancelled"),
  }

  return labels[status] || status || "—"
}

function statusClass(status) {
  const base = "inline-flex rounded-full px-2 py-1 text-xs font-medium"
  const classes = {
    upcoming: "bg-blue-100 text-blue-700",
    in_progress: "bg-green-100 text-green-700",
    past: "bg-gray-100 text-gray-700",
    cancelled: "bg-red-100 text-red-700",
  }

  return `${base} ${classes[status] || "bg-gray-100 text-gray-700"}`
}

</script>
