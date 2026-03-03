<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader
      active-view="calendar"
      @addClick="goToAddEvent"
      @agendaListClick="goToAgendaList"
      @sessionPlanningClick="goToSessionsPlan"
      @myStudentsScheduleClick="goToMyStudentsSchedule"
    />

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <div class="text-sm text-gray-700 font-semibold">
          {{ t("My students schedule") }}
        </div>

        <select
          v-model="selectedSid"
          class="border rounded px-2 py-1 text-sm bg-white"
        >
          <option value="">{{ t("Select a session") }}</option>
          <option
            v-for="s in sessions"
            :key="`sid-${s.id}`"
            :value="String(s.id)"
          >
            {{ s.name }}
          </option>
        </select>
      </div>

      <div class="text-xs text-gray-600">
        {{ t("Read-only view") }}
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="p-3 border rounded bg-white text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="!isLoadingSessions && sessions.length === 0 && !errorMessage"
      class="p-4 border rounded bg-white text-sm text-gray-700"
    >
      {{ t("No tutored sessions found") }}
    </div>

    <div class="border rounded bg-white overflow-hidden relative">
      <div
        v-if="isLoadingSessions"
        class="absolute inset-0 z-10 bg-white/70 flex items-center justify-center"
      >
        <div class="flex items-center gap-3 text-gray-700">
          <i class="pi pi-spin pi-spinner text-2xl" />
          <span class="text-sm">{{ t("Loading") }}</span>
        </div>
      </div>

      <div class="p-2">
        <FullCalendar
          :key="calendarKey"
          ref="cal"
          :options="calendarOptions"
        />
      </div>
    </div>

    <!-- Read-only dialog -->
    <Dialog
      v-model:visible="detailsVisible"
      modal
      :header="t('Event details')"
      :style="{ width: '520px' }"
    >
      <div class="flex flex-col gap-2 text-sm">
        <div class="font-semibold">
          {{ details.title || "-" }}
        </div>
        <div class="text-gray-700">
          <span class="font-semibold">{{ t("Course") }}:</span>
          {{ details.courseTitle || "-" }}
        </div>
        <div class="text-gray-700">
          <span class="font-semibold">{{ t("From") }}:</span>
          {{ details.start || "-" }}
        </div>
        <div class="text-gray-700">
          <span class="font-semibold">{{ t("Until") }}:</span>
          {{ details.end || "-" }}
        </div>
        <div class="text-gray-600 text-xs">
          {{ t("You cannot access the linked course from this view.") }}
        </div>
      </div>

      <template #footer>
        <button
          class="border rounded px-3 py-2 text-sm bg-white"
          @click="detailsVisible = false"
        >
          {{ t("Close") }}
        </button>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import FullCalendar from "@fullcalendar/vue3"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import Dialog from "primevue/dialog"
import { DateTime } from "luxon"

import CalendarSectionHeader from "../../components/ccalendarevent/CalendarSectionHeader.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const cal = ref(null)

function getCurrentTimezone() {
  return Intl.DateTimeFormat().resolvedOptions().timeZone || "UTC"
}

const timezone = getCurrentTimezone()
const isLoadingSessions = ref(false)
const errorMessage = ref("")
const sessions = ref([])
const selectedSid = ref(String(route.query.sid || ""))
const initialView = ref(String(route.query.view || "dayGridMonth"))
const initialDate = ref(String(route.query.date || DateTime.now().toISODate()))
const calendarKey = ref(0)
const lastCalendarSid = ref(null)

function snapshotCalendarState() {
  const api = cal.value?.getApi?.()
  if (!api) return
  initialView.value = api.view?.type ?? initialView.value
  initialDate.value = DateTime.fromJSDate(api.getDate()).setZone(timezone).toISODate()
}

// Keep selectedSid synced from route (avoid useless re-assignments)
watch(
  () => route.query.sid,
  (sid) => {
    const next = String(sid || "")
    if (selectedSid.value === next) return
    selectedSid.value = next
  },
)

// Push selectedSid to route (avoid infinite loop / redundant replace)
watch(
  () => selectedSid.value,
  (sid) => {
    const current = String(route.query.sid || "")
    if (sid === current) return

    const nextQuery = { ...route.query }
    if (sid) {
      nextQuery.sid = sid
    } else {
      delete nextQuery.sid
    }

    router
      .replace({
        name: route.name ?? "CalendarMyStudentsSchedule",
        params: route.params,
        query: nextQuery,
      })
      .catch(() => {})
  },
)

// Remount calendar when session changes (this guarantees refresh).
watch(
  () => selectedSid.value,
  async (sid) => {
    if (lastCalendarSid.value === sid) return
    lastCalendarSid.value = sid

    errorMessage.value = ""

    await nextTick()
    // Save current date/view before remounting (so user stays on same month/week/day).
    snapshotCalendarState()

    // Force FullCalendar to be recreated => it will re-fetch events for the new sid immediately.
    calendarKey.value += 1
  },
  { flush: "post" },
)

const detailsVisible = ref(false)
const details = ref({ title: "", start: "", end: "", courseTitle: "" })

function openReadOnlyDetails(event) {
  const start = event?.start ? DateTime.fromJSDate(event.start).setZone(timezone).toFormat("yyyy-LL-dd HH:mm") : "-"
  const end = event?.end ? DateTime.fromJSDate(event.end).setZone(timezone).toFormat("yyyy-LL-dd HH:mm") : start
  const courseTitle = event?.extendedProps?.courseTitle || ""

  details.value = {
    title: event?.title || "-",
    courseTitle,
    start,
    end,
  }
  detailsVisible.value = true
}

async function fetchSessions() {
  try {
    isLoadingSessions.value = true
    errorMessage.value = ""

    const resp = await fetch("/api/calendar/my-students-schedule", {
      method: "GET",
      headers: { Accept: "application/json" },
    })

    if (!resp.ok) {
      const text = await resp.text().catch(() => "")
      console.error("[MyStudentsSchedule] Sessions request failed", resp.status, text)
      errorMessage.value = t("Failed to load sessions")
      sessions.value = []
      return
    }

    const data = await resp.json()
    sessions.value = Array.isArray(data) ? data : []
  } catch (e) {
    console.error("[MyStudentsSchedule] Unexpected error", e)
    errorMessage.value = t("Failed to load sessions")
    sessions.value = []
  } finally {
    isLoadingSessions.value = false
  }
}

async function fetchEvents(info, successCallback, failureCallback) {
  try {
    const sid = selectedSid.value
    if (!sid) {
      successCallback([])
      return
    }

    const start = DateTime.fromJSDate(info.start).toISO()
    const end = DateTime.fromJSDate(info.end).toISO()

    const url = `/api/calendar/my-students-schedule?sid=${encodeURIComponent(sid)}&start=${encodeURIComponent(
      start,
    )}&end=${encodeURIComponent(end)}`

    const resp = await fetch(url, { method: "GET", headers: { Accept: "application/json" } })

    if (!resp.ok) {
      const text = await resp.text().catch(() => "")
      console.error("[MyStudentsSchedule] Events request failed", resp.status, text)
      errorMessage.value = resp.status === 403 ? t("Not allowed") : t("Failed to load events")
      successCallback([])
      return
    }

    const data = await resp.json()
    successCallback(Array.isArray(data) ? data : [])
  } catch (e) {
    console.error("[MyStudentsSchedule] Unexpected error", e)
    errorMessage.value = t("Failed to load events")
    failureCallback?.(e)
  }
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  timeZone: timezone,
  initialView: initialView.value,
  initialDate: initialDate.value,
  headerToolbar: {
    left: "prev,next today",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  // Keep our internal "current state" updated when user navigates.
  datesSet(arg) {
    try {
      const api = arg?.view?.calendar
      if (!api) return
      initialView.value = arg.view?.type ?? initialView.value
      initialDate.value = DateTime.fromJSDate(api.getDate()).setZone(timezone).toISODate()
    } catch (e) {
      console.error("[MyStudentsSchedule] datesSet error", e)
    }
  },
  events: fetchEvents,
  eventClick(info) {
    // IMPORTANT: read-only
    info.jsEvent.preventDefault()
    info.jsEvent.stopPropagation()
    openReadOnlyDetails(info.event)
  },
}))

function goToSessionsPlan() {
  router.push({ name: "CalendarSessionsPlan", query: { ...route.query } }).catch(() => {})
}
function goToMyStudentsSchedule() {
  router.push({ name: "CalendarMyStudentsSchedule", query: { ...route.query } }).catch(() => {})
}
function goToAgendaList() {
  router.push({ name: "CCalendarEventListView", query: { ...route.query } }).catch(() => {})
}
function goToAddEvent() {
  router.push({ name: "CCalendarEventList", query: { ...route.query, openAdd: "1" } }).catch(() => {})
}

onMounted(() => {
  fetchSessions()
})
</script>
