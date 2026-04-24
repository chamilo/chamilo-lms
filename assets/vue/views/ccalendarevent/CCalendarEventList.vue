<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader
      active-view="calendar"
      @addClick="showAddEventDialog"
      @agendaListClick="goToAgendaList"
      @sessionPlanningClick="goToSessionsPlan"
      @myStudentsScheduleClick="goToMyStudentsSchedule"
    />

    <FullCalendar
      ref="cal"
      :options="calendarOptions"
    />

    <Loading :visible="isLoading" />

    <!-- Add form-->
    <Dialog
      v-model:visible="dialog"
      :header="item['@id'] ? t('Edit event') : t('Add event')"
      :modal="true"
    >
      <CCalendarEventForm
        v-if="dialog"
        ref="createForm"
        :is-global="isGlobal"
        :values="item"
      />
      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="dialog = false"
        />
        <BaseButton
          :label="item['@id'] ? t('Edit') : t('Add')"
          icon="calendar-plus"
          type="secondary"
          @click="onCreateEventForm"
        />
      </template>
    </Dialog>

    <!-- Show form-->
    <Dialog
      v-model:visible="dialogShow"
      :header="t('Event')"
      :modal="true"
      :style="{ width: '30rem' }"
    >
      <CCalendarEventInfo :event="item" />

      <template #footer>
        <BaseButton
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="dialogShow = false"
        />

        <BaseButton
          v-if="allowToUnsubscribe"
          :label="t('Unsubscribe')"
          icon="join-group"
          type="black"
          @click="unsubscribeToEvent"
        />
        <BaseButton
          v-else-if="allowToSubscribe"
          :label="t('Subscribe')"
          icon="join-group"
          type="black"
          @click="subscribeToEvent"
        />

        <BaseButton
          v-if="allowToEdit && showDeleteButton"
          :label="t('Delete')"
          icon="delete"
          type="danger"
          @click="confirmDelete"
        />
        <BaseButton
          v-if="allowToEdit && showEditButton"
          :label="t('Edit')"
          icon="edit"
          type="secondary"
          @click="dialog = true"
        />
      </template>
    </Dialog>

    <!-- Show form-->
    <Dialog
      v-model:visible="sessionState.showSessionDialog"
      :header="t('Session')"
      :modal="true"
      :style="{ width: '35rem' }"
    >
      <div class="flex flex-col gap-4">
        <h5 v-text="sessionState.sessionAsEvent.title" />
        <p
          v-show="sessionState.sessionAsEvent.start"
          v-text="t('From %s', [abbreviatedDatetime(sessionState.sessionAsEvent.start)])"
        />
        <p
          v-show="sessionState.sessionAsEvent.end"
          v-text="t('Until %s', [abbreviatedDatetime(sessionState.sessionAsEvent.end)])"
        />
      </div>

      <template #footer>
        <BaseButton
          :label="t('Go to session')"
          :to-url="sessionState.sessionAsEvent.url"
          icon="sessions"
          type="secondary"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import { useRoute, useRouter } from "vue-router"
import { DateTime } from "luxon"

import Loading from "../../components/Loading.vue"
import FullCalendar from "@fullcalendar/vue3"
import dayGridPlugin from "@fullcalendar/daygrid"
import interactionPlugin from "@fullcalendar/interaction"
import timeGridPlugin from "@fullcalendar/timegrid"
import CCalendarEventForm from "../../components/ccalendarevent/CCalendarEventForm.vue"
import CCalendarEventInfo from "../../components/ccalendarevent/CCalendarEventInfo"
import allLocales from "@fullcalendar/core/locales-all"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useToast } from "primevue/usetoast"
import { useCidReqStore } from "../../store/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useLocale, useParentLocale } from "../../composables/locale"
import { storeToRefs } from "pinia"
import CalendarSectionHeader from "../../components/ccalendarevent/CalendarSectionHeader.vue"
import { useCalendarActionButtons } from "../../composables/calendar/calendarActionButtons"
import { useCalendarEvent } from "../../composables/calendar/calendarEvent"
import resourceLinkService from "../../services/resourceLinkService"
import { useSecurityStore } from "../../store/securityStore"
import { useCourseSettings } from "../../store/courseSettingStore"

const store = useStore()
const securityStore = useSecurityStore()
const { requireConfirmation } = useConfirmation()
const cidReqStore = useCidReqStore()

const { course, session, group } = storeToRefs(cidReqStore)
const { abbreviatedDatetime, getCurrentTimezone } = useFormatDate()
const { showAddButton } = useCalendarActionButtons()

const { isEditableByUser, allowSubscribeToEvent, allowUnsubscribeToEvent, getCalendarEvents } = useCalendarEvent()

const item = ref({})
const dialog = ref(false)
const dialogShow = ref(false)
const allowToEdit = ref(false)
const allowToSubscribe = ref(false)
const allowToUnsubscribe = ref(false)

const { t } = useI18n()
const { appLocale } = useLocale()
const route = useRoute()
const router = useRouter()
const isGlobal = ref(route.query.type === "global")

const courseSettingsStore = useCourseSettings()
const allowUserEditAgenda = ref(false)

const timezone = getCurrentTimezone()

function htmlToPlainText(input) {
  if (!input) return ""

  const raw = String(input)

  if (!/[<>]/.test(raw)) {
    return raw.replace(/\s+/g, " ").trim()
  }

  if (typeof document !== "undefined") {
    const div = document.createElement("div")
    div.innerHTML = raw

    return (div.textContent || div.innerText || "").replace(/\s+/g, " ").trim()
  }

  return raw
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim()
}

function buildEventTooltip(eventLike) {
  const eventObject = eventLike?.event ?? eventLike ?? {}
  const extendedProps = eventObject?.extendedProps ?? {}

  const lines = []
  const title = eventObject?.title || extendedProps?.title || ""
  const description = htmlToPlainText(extendedProps?.content || extendedProps?.description || "")

  if (title) {
    lines.push(title)
  }

  const start = eventObject?.start ? abbreviatedDatetime(eventObject.start) : ""
  const end = eventObject?.end ? abbreviatedDatetime(eventObject.end) : ""

  if (start && end) {
    lines.push(`${t("From")} ${start}`)
    lines.push(`${t("Until")} ${end}`)
  } else if (start) {
    lines.push(`${t("From")} ${start}`)
  }

  if (description) {
    lines.push(description)
  }

  return lines.join("\n")
}

function applyCalendarEventPresentation(info) {
  const tooltip = buildEventTooltip(info)
  const el = info?.el

  if (!el) {
    return
  }

  if (tooltip) {
    el.setAttribute("title", tooltip)
  } else {
    el.removeAttribute("title")
  }

  el.classList.add("calendar-event--wrapped")

  el.querySelectorAll("a, .fc-event-title, .fc-list-event-title").forEach((node) => {
    if (tooltip) {
      node.setAttribute("title", tooltip)
    } else {
      node.removeAttribute("title")
    }
  })
}

// Removes openAdd=1 from the current URL to avoid reopening the dialog.
function clearOpenAddFlag() {
  if (route.query.openAdd !== "1") return

  const nextQuery = { ...route.query }
  delete nextQuery.openAdd

  router
    .replace({
      name: route.name ?? "CCalendarEventList",
      params: route.params,
      query: nextQuery,
    })
    .catch(() => {})
}

function computeContextFromQuery(query) {
  if (query?.type === "global") return "global"
  if (query?.sid && query.sid !== "0") return "session"
  if (query?.cid && (!query.sid || query.sid === "0")) return "course"
  return "personal"
}

const currentContext = ref(computeContextFromQuery(route.query))

watch(
  () => route.query,
  (query) => {
    currentContext.value = computeContextFromQuery(query)
  },
  { immediate: true },
)

const handledOpenAdd = ref(false)

watch(
  () => [route.query.openAdd, securityStore.user?.resourceNode?.["id"]],
  ([openAdd, userNodeId]) => {
    if (openAdd !== "1") {
      handledOpenAdd.value = false
      return
    }

    if (handledOpenAdd.value) return
    if (!userNodeId) return

    handledOpenAdd.value = true
    showAddEventDialog()
    clearOpenAddFlag()
  },
  { immediate: true },
)

watch(
  () => dialog.value,
  (visible) => {
    if (!visible) {
      clearOpenAddFlag()
    }
  },
)

/**
 * Read the current calendar state to keep list and calendar coherent.
 * This provides both the current anchor date and the current view type.
 */
function getCalendarQueryState() {
  const api = cal.value?.getApi?.()
  if (!api) {
    return {
      date: route.query.date ?? null,
      view: route.query.view ?? null,
    }
  }

  const date = DateTime.fromJSDate(api.getDate()).setZone(timezone).toISODate()
  const view = api.view?.type ?? null
  return { date, view }
}

function buildAgendaNavigationQuery() {
  const { date, view } = getCalendarQueryState()
  const nextQuery = { ...route.query }

  if (date) {
    nextQuery.date = date
  }

  if (view) {
    nextQuery.view = view
  }

  return nextQuery
}

function goToAgendaList(mode = "list") {
  const nextQuery = buildAgendaNavigationQuery()
  const targetRoute = mode === "calendar" ? "CCalendarEventList" : "CCalendarEventListView"

  router.push({ name: targetRoute, query: nextQuery }).catch((e) => {
    console.error("[Calendar] Navigation error", e)
  })
}

watch(
  [course, session],
  async ([newCourse, newSession]) => {
    if (newCourse && newCourse.id) {
      const sessionId = newSession ? newSession.id : null
      const setting = courseSettingsStore.getSetting("allow_user_edit_agenda")
      allowUserEditAgenda.value = setting === "1"
      if (allowUserEditAgenda.value) {
        showAddButton.value = true
      }
    } else {
      allowUserEditAgenda.value = false
    }
  },
  { immediate: true },
)

let currentEvent = null

const sessionState = reactive({
  sessionAsEvent: {
    id: "",
    title: "",
    start: "",
    end: "",
    extendedProps: {},
  },
  showSessionDialog: false,
})

const calendarLocale = allLocales.find(
  (calLocale) =>
    calLocale.code === appLocale.value.replace("_", "-") || calLocale.code === useParentLocale(appLocale.value),
)

const HEX6 = /^#([0-9a-f]{6})$/i
const HEX3 = /^#([0-9a-f]{3})$/i
function normalizeHex(c) {
  if (!c) return null
  const s = String(c).trim()
  if (HEX6.test(s)) return s.toUpperCase()
  const m3 = s.match(HEX3)
  if (m3) {
    const [r, g, b] = m3[1].toUpperCase().split("")
    return `#${r}${r}${g}${g}${b}${b}`
  }
  const mRgb = s.match(/rgba?\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i)
  if (mRgb) {
    const r = Math.min(255, +mRgb[1])
    const g = Math.min(255, +mRgb[2])
    const b = Math.min(255, +mRgb[3])
    return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}`.toUpperCase()
  }
  const names = {
    YELLOW: "#FFFF00",
    BLUE: "#0000FF",
    RED: "#FF0000",
    GREEN: "#008000",
    STEELBLUE: "#4682B4",
    "STEEL BLUE": "#4682B4",
  }
  return names[s.toUpperCase()] || null
}

function defaultColorByContext(ctx) {
  return ctx === "global" ? "#FF0000" : ctx === "course" ? "#458B00" : ctx === "session" ? "#00496D" : "#4682B4"
}

// Build a safe default item for the modal form.
function buildDefaultEventItem() {
  const now = new Date()
  const end = new Date(now.getTime() + 60 * 60 * 1000)

  return {
    title: "",
    content: "",
    allDay: false,
    startDate: now,
    endDate: end,
    parentResourceNode: securityStore.user?.resourceNode?.["id"] ?? null,
    color: defaultColorByContext(currentContext?.value ?? "personal"),
  }
}

// Hoisted function declaration to be safe with immediate watchers.
function showAddEventDialog() {
  item.value = buildDefaultEventItem()
  dialog.value = true
}

const calendarOptions = ref({
  timeZone: timezone,
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  locales: allLocales,
  locale: calendarLocale?.code ?? "en-GB",
  headerToolbar: {
    left: "today prev,next",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  nowIndicator: true,
  initialView: "dayGridMonth",
  startParam: "startDate[after]",
  endParam: "endDate[before]",
  selectable: true,

  /**
   * Keep query date+view in sync so list view can use the same range.
   * This does not change any existing behavior, it only updates the URL.
   */
  datesSet(arg) {
    const api = arg?.view?.calendar
    if (!api) return

    const date = DateTime.fromJSDate(api.getDate()).setZone(timezone).toISODate()
    const view = api.view?.type ?? arg?.view?.type ?? null

    const nextQuery = { ...route.query }
    if (date) nextQuery.date = date
    if (view) nextQuery.view = view

    // Avoid infinite loops: only replace when something changed
    const sameDate = String(route.query.date || "") === String(nextQuery.date || "")
    const sameView = String(route.query.view || "") === String(nextQuery.view || "")
    if (sameDate && sameView) return

    router.replace({ name: route.name ?? "CCalendarEventList", params: route.params, query: nextQuery }).catch(() => {})
  },

  eventClick(eventClickInfo) {
    eventClickInfo.jsEvent.preventDefault()
    currentEvent = eventClickInfo.event

    const event = eventClickInfo.event.toPlainObject()

    if (event.extendedProps["objectType"] && event.extendedProps["objectType"] === "session") {
      allowToEdit.value =
        allowUserEditAgenda.value && event.extendedProps.resourceNode.creator.id === securityStore.user.id
      sessionState.sessionAsEvent = event
      sessionState.showSessionDialog = true
      return
    }

    item.value = { ...event.extendedProps }

    item.value["@id"] = "/api/c_calendar_events/" + event.id.match(/\d+$/)[0]
    item.value["title"] = event.title
    item.value["startDate"] = event.start ? new Date(event.start) : null
    item.value["endDate"] = event.end ? new Date(event.end) : null
    item.value["parentResourceNodeId"] = event.extendedProps?.resourceNode?.creator?.id

    const rawColor = event.extendedProps?.color ?? event.backgroundColor ?? event.borderColor ?? event.color ?? null
    item.value["color"] = normalizeHex(rawColor) || defaultColorByContext(currentContext.value)
    if (
      !(route.query.sid === "0" && item.value.type === "session") &&
      !(route.query.sid !== "0" && item.value.type === "course") &&
      !(route.query.type === "global" && item.value.type !== "global") &&
      !(!route.query.cid && !route.query.sid && !route.query.type && item.value.type !== "personal")
    ) {
      currentContext.value = item.value.type
    }

    allowToEdit.value =
      (isEditableByUser(item.value, securityStore.user.id) ||
        allowUserEditAgenda.value ||
        securityStore.isCourseAdmin ||
        securityStore.isSessionAdmin) &&
      (event.extendedProps?.resourceNode?.creator?.id === securityStore.user.id || securityStore.isCourseAdmin)

    allowToSubscribe.value = !allowToEdit.value && allowSubscribeToEvent(item.value)
    allowToUnsubscribe.value = !allowToEdit.value && allowUnsubscribeToEvent(item.value, securityStore.user.id)

    dialogShow.value = true
  },

  select(info) {
    if (!showAddButton.value) {
      return
    }

    let startDate
    let endDate

    if (info.allDay) {
      startDate = new Date(info.start)
      startDate.setHours(0, 0, 0, 0)

      endDate = new Date(info.end)
      endDate.setDate(endDate.getDate() - 1)
      endDate.setHours(23, 59, 0, 0)
    } else {
      startDate = new Date(info.start)
      endDate = new Date(info.end)
    }

    item.value = {}
    item.value["parentResourceNode"] = securityStore.user.resourceNode["id"]
    item.value["allDay"] = info.allDay
    item.value["startDate"] = startDate
    item.value["endDate"] = endDate
    item.value["color"] = defaultColorByContext(currentContext.value)

    dialog.value = true
  },

  events(info, successCallback) {
    const commonParams = {}

    if (course.value) {
      commonParams.cid = course.value.id
    }

    if (session.value) {
      commonParams.sid = session.value.id
    }

    if (route.query?.type === "global") {
      commonParams.type = "global"
    }

    const gidFromRoute = Number(route.query.gid ?? 0)
    const gidFromStore = Number(group.value?.id ?? 0)
    const effectiveGid = gidFromStore > 0 ? gidFromStore : gidFromRoute

    if (effectiveGid > 0) {
      commonParams.gid = effectiveGid
    }

    getCalendarEvents(info.start, info.end, commonParams).then((events) => successCallback(events))
  },

  eventDidMount(info) {
    applyCalendarEventPresentation(info)
  },
})

const allowAction = (eventType) => {
  const contextRules = {
    global: ["global"],
    course: ["course"],
    session: ["session"],
    personal: ["personal"],
  }

  return contextRules[currentContext.value].includes(eventType)
}

const showEditButton = computed(() => {
  return allowToEdit.value && allowAction(item.value.type)
})

const showDeleteButton = computed(() => {
  return (
    (isEditableByUser(item.value, securityStore.user.id) || allowUserEditAgenda.value) && allowAction(item.value.type)
  )
})

const cal = ref(null)

function reFetch() {
  cal.value.getApi().refetchEvents()
}

function confirmDelete() {
  requireConfirmation({
    title: t("Delete"),
    message: t("Are you sure you want to delete"),
    accept() {
      const isOwner = item.value["parentResourceNodeId"] === securityStore.user["id"]
      const isAdmin = securityStore.isCourseAdmin || securityStore.isSessionAdmin
      if (isOwner || isAdmin) {
        store.dispatch("ccalendarevent/del", item.value).then(() => {
          dialogShow.value = false
          dialog.value = false
          reFetch()
        })
      } else {
        const resourceLinks = Array.isArray(item.value["resourceLinkListFromEntity"])
          ? item.value["resourceLinkListFromEntity"]
          : []

        const userLink = resourceLinks.find((link) => link?.user?.id === securityStore.user["id"])

        if (userLink) {
          store
            .dispatch("resourcelink/del", {
              "@id": `/api/resource_links/${userLink["id"]}`,
            })
            .then(() => {
              currentEvent.remove()
              dialogShow.value = false
              dialog.value = false
              reFetch()
            })
        }
      }
    },
  })
}

async function subscribeToEvent() {
  try {
    await resourceLinkService.create({
      resourceNode: item.value.resourceNode["@id"],
      user: securityStore.user["@id"],
      visibility: RESOURCE_LINK_PUBLISHED,
    })

    allowToSubscribe.value = false
    allowToUnsubscribe.value = true
  } catch (e) {
    console.error(e)
  }
}

async function unsubscribeToEvent() {}

const isLoading = computed(() => store.getters["ccalendarevent/isLoading"])

const createForm = ref(null)

function goToSessionsPlan() {
  router.push({ name: "CalendarSessionsPlan", query: { ...buildAgendaNavigationQuery() } }).catch((e) => {
    console.error("[Calendar] Navigation error", e)
  })
}

function goToMyStudentsSchedule() {
  router.push({ name: "CalendarMyStudentsSchedule", query: { ...buildAgendaNavigationQuery() } }).catch((e) => {
    console.error("[Calendar] Navigation error", e)
  })
}

async function onCreateEventForm() {
  try {
    if (createForm.value.v$.$invalid) {
      return
    }

    let itemModel = createForm.value.v$.item.$model

    if (!itemModel.content) {
      itemModel = { ...itemModel, content: "" }
    }

    if (isGlobal.value) {
      itemModel.isGlobal = true
    }

    if (!itemModel.color) {
      itemModel.color = defaultColorByContext(currentContext.value)
    }

    if (itemModel["@id"]) {
      await store.dispatch("ccalendarevent/update", itemModel)
    } else {
      if (course.value) {
        const gidFromRoute = Number(route.query.gid ?? 0)
        const gidFromStore = Number(group.value?.id ?? 0)
        const effectiveGid = gidFromStore > 0 ? gidFromStore : gidFromRoute
        itemModel.resourceLinkList = [
          {
            cid: course.value.id,
            sid: session.value?.id ?? null,
            gid: effectiveGid > 0 ? effectiveGid : null,
            visibility: RESOURCE_LINK_PUBLISHED,
          },
        ]
      }
      await store.dispatch("ccalendarevent/create", itemModel)
    }

    dialog.value = false
    dialogShow.value = false
    reFetch()
  } catch (error) {
    console.error("An error occurred:", error)
  }
}

const toast = useToast()

watch(
  () => route.query.type,
  (newType) => {
    isGlobal.value = newType === "global"
    reFetch()
  },
)

watch(
  () => store.state.ccalendarevent.created,
  (created) => {
    toast.add({
      severity: "success",
      detail: t("{0} created", [created.resourceNode.title]),
      life: 3500,
    })

    reFetch()
  },
)

watch(
  () => store.state.ccalendarevent.updated,
  (updated) => {
    toast.add({
      severity: "success",
      detail: t("{0} updated", [updated.resourceNode.title]),
      life: 3500,
    })

    reFetch()
  },
)
</script>
