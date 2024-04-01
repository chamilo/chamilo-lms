<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader
      @add-click="showAddEventDialog"
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
        :values="item"
        :is-global="isGlobal"
        :notifications-data="selectedEventNotifications"
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
          type="black"
          icon="join-group"
          @click="unsubscribeToEvent"
        />
        <BaseButton
          v-else-if="allowToSubscribe"
          :label="t('Subscribe')"
          type="black"
          icon="join-group"
          @click="subscribeToEvent"
        />

        <BaseButton
          v-if="showDeleteButton"
          :label="t('Delete')"
          icon="delete"
          type="danger"
          @click="confirmDelete"
        />
        <BaseButton
          v-if="allowToEdit && showEditButton"
          :label="t('Edit')"
          type="secondary"
          @click="dialog = true"
          icon="delete"/>
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
          v-t="{
            path: 'From %s',
            args: [abbreviatedDatetime(sessionState.sessionAsEvent.start)],
          }"
        />
        <p
          v-show="sessionState.sessionAsEvent.end"
          v-t="{
            path: 'Until %s',
            args: [abbreviatedDatetime(sessionState.sessionAsEvent.end)],
          }"
        />
      </div>

      <template #footer>
        <a
          v-t="'Go to session'"
          :href="sessionState.sessionAsEvent.url"
          class="btn btn--secondary"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useConfirm } from "primevue/useconfirm"
import { useFormatDate } from "../../composables/formatDate"
import { useRoute } from "vue-router"

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
import cCalendarEventService from "../../services/ccalendarevent"
import { useCidReqStore } from "../../store/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import { useLocale, useParentLocale } from "../../composables/locale"
import { storeToRefs } from "pinia"
import CalendarSectionHeader from "../../components/ccalendarevent/CalendarSectionHeader.vue"
import { useCalendarActionButtons } from "../../composables/calendar/calendarActionButtons"
import { useCalendarEvent } from "../../composables/calendar/calendarEvent"
import resourceLinkService from "../../services/resourceLinkService"
import axios from "axios"
import { usePlatformConfig } from "../../store/platformConfig"

const store = useStore()
const confirm = useConfirm()
const cidReqStore = useCidReqStore()

const { course, session, group } = storeToRefs(cidReqStore)

const { abbreviatedDatetime } = useFormatDate()

const { showAddButton } = useCalendarActionButtons()

const { isEditableByUser, allowSubscribeToEvent, allowUnsubscribeToEvent } = useCalendarEvent()

const item = ref({})
const dialog = ref(false)
const dialogShow = ref(false)
const allowToEdit = ref(false)
const allowToSubscribe = ref(false)
const allowToUnsubscribe = ref(false)

const currentUser = computed(() => store.getters["security/getUser"])
const { t } = useI18n()
const { appLocale } = useLocale()
const route = useRoute()
const isGlobal = ref(route.query.type === 'global')
const selectedEventNotifications = ref([])

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

const platformConfigStore = usePlatformConfig()
const agendaRemindersEnabled = computed(() => {
  return "true" === platformConfigStore.getSetting("agenda.agenda_reminders")
})

function parseDateInterval(dateInterval) {
  const regex = /P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/
  const matches = dateInterval.match(regex)
  if (matches) {
    const [, , , , hours, minutes] = matches.map(Number)
    if (hours) return { count: hours, period: 'h' }
    if (minutes) return { count: minutes, period: 'i' }
  }
  return { count: 0, period: 'i' } // Default value
}

async function loadEventNotifications(compositeEventId) {
  const matches = compositeEventId.match(/\d+/)
  const eventId = matches ? matches[0] : null

  if (!eventId) {
    console.error("Invalid event ID format:", compositeEventId)
    return
  }

  try {
    const response = await axios.get(`/api/agenda_reminders?eventId=${eventId}`)
    selectedEventNotifications.value = response.data['hydra:member'].map(notification => parseDateInterval(notification.dateInterval))
  } catch (error) {
    console.error("Error loading event notifications:", error)
    selectedEventNotifications.value = []
  }
}

async function getCalendarEvents({ startStr, endStr }) {
  const params = {
    "startDate[after]": startStr,
    "endDate[before]": endStr,
  }

  if (course.value) {
    params.cid = course.value.id
  }

  if (session.value) {
    params.sid = session.value.id
  }

  if (group.value) {
    params.gid = group.value.id
  }

  if (route.query?.type === 'global') {
    params.type = 'global'
  }

  const calendarEvents = await cCalendarEventService.findAll({ params }).then((response) => response.json())

  return calendarEvents["hydra:member"].map((event) => {
    let color = event.color || '#007BFF'

    return {
      ...event,
      start: event.startDate,
      end: event.endDate,
      color,
    }
  })
}

const calendarLocale = allLocales.find(
  (calLocale) =>
    calLocale.code === appLocale.value.replace("_", "-") || calLocale.code === useParentLocale(appLocale.value),
)

const showAddEventDialog = () => {
  item.value = {}
  item.value["parentResourceNode"] = currentUser.value.resourceNode["id"]

  dialog.value = true
}

const goToMyStudentsSchedule = () => {
  window.location.href = "/main/calendar/planification.php"
}

const goToSessionPanning = () => {
  window.location.href = "/main/my_space/calendar_plan.php"
}

const calendarOptions = ref({
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
  eventClick: async (eventClickInfo) => {
    eventClickInfo.jsEvent.preventDefault()
    currentEvent = eventClickInfo.event

    let event = eventClickInfo.event.toPlainObject()

    if (event.extendedProps["@type"] && event.extendedProps["@type"] === "Session") {
      sessionState.sessionAsEvent = event
      sessionState.showSessionDialog = true

      return
    }

    await loadEventNotifications(event.id);
    item.value = { ...event.extendedProps }

    item.value["title"] = event.title
    item.value["startDate"] = event.start
    item.value["endDate"] = event.end
    item.value["parentResourceNodeId"] = event.extendedProps.resourceNode.creator.id

    allowToEdit.value = isEditableByUser(item.value, currentUser.value.id)
    allowToSubscribe.value = !allowToEdit.value && allowSubscribeToEvent(item.value)
    allowToUnsubscribe.value = !allowToEdit.value && allowUnsubscribeToEvent(item.value, currentUser.value.id)

    dialogShow.value = true
  },
  select(info) {
    if (!showAddButton.value) {
      return
    }

    item.value = {}
    item.value["parentResourceNode"] = currentUser.value.resourceNode["id"]
    item.value["allDay"] = info.allDay
    item.value["startDate"] = info.start
    item.value["endDate"] = info.end

    dialog.value = true
  },
  events(info, successCallback) {
    getCalendarEvents(info).then((events) => successCallback(events))
  },
})

const currentContext = computed(() => {
  if (route.query.type === 'global') {
    return 'global'
  } else if (course.value) {
    return 'course'
  } else if (session.value) {
    return 'session'
  } else {
    return 'personal'
  }
})

const allowAction = (eventType) => {
  const contextRules = {
    global: ['global'],
    course: ['course'],
    session: ['session'],
    personal: ['personal']
  }

  return contextRules[currentContext.value].includes(eventType)
}

const showEditButton = computed(() => allowAction(item.value.type))
const showDeleteButton = computed(() => allowAction(item.value.type))

const cal = ref(null)

function reFetch() {
  cal.value.getApi().refetchEvents()
}

function confirmDelete() {
  confirm.require({
    message: t("Are you sure you want to delete this event?"),
    header: t("Delete"),
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    rejectClass: "p-button-plain p-button-outlined",
    accept: async () => {
      try {
        const eventId = item.value['@id'].split('/').pop()
        await axios.post(`/api/agenda_reminders/delete_by_event`, { eventId })

        if (item.value["parentResourceNodeId"] === currentUser.value["id"]) {
          await store.dispatch("ccalendarevent/del", item.value)
        } else {
          let filteredLinks = item.value["resourceLinkListFromEntity"].filter(
            (resourceLinkFromEntity) => resourceLinkFromEntity["user"]["id"] === currentUser.value["id"]
          )
          if (filteredLinks.length > 0) {
            await store.dispatch("resourcelink/del", {
              "@id": `/api/resource_links/${filteredLinks[0]["id"]}`,
            })
          }
        }
        dialogShow.value = false
        dialog.value = false
        reFetch()
        toast.add({ severity: "success", detail: t("Event and its notifications deleted successfully."), life: 3500 })
      } catch (error) {
        console.error("Error deleting event or notifications: ", error)
        toast.add({ severity: "error", detail: t("Error deleting event or notifications"), life: 3500 })
      }
    },
  })
}

async function subscribeToEvent() {
  try {
    await resourceLinkService.post({
      resourceNode: item.value.resourceNode["@id"],
      user: currentUser.value["@id"],
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

async function onCreateEventForm() {
  if (createForm.value.v$.$invalid) {
    return
  }

  let itemModel = createForm.value.v$.item.$model

  if (isGlobal.value) {
    itemModel.isGlobal = true
  }

  try {
    let response;
    if (itemModel["@id"]) {
      // Update the existing event
      response = await store.dispatch("ccalendarevent/update", itemModel)
    } else {
      // Create a new event
      if (course.value) {
        itemModel.resourceLinkList = [{
          cid: course.value.id,
          sid: session.value?.id ?? null,
          visibility: RESOURCE_LINK_PUBLISHED,
        }]
      }
      response = await store.dispatch("ccalendarevent/create", itemModel)
    }

    if (response && response.iid) {
      let successDetail = t("Event saved successfully")
      if (agendaRemindersEnabled.value) {
        if (createForm.value.notifications && createForm.value.notifications.length > 0 && !createForm.value.v$.notifications.$error) {
          await sendNotifications(response.iid, createForm.value.notifications)
          successDetail += t(" with notifications sent")
        }
      }
      dialog.value = false
      dialogShow.value = false
      toast.add({ severity: "success", detail: successDetail, life: 3500 })
      reFetch()
    } else {
      throw new Error("Failed to obtain event ID from the response.")
    }
  } catch (error) {
    console.error("Error saving event or notifications: ", error)
    toast.add({ severity: "error", detail: "Error saving event or notifications", life: 3500 })
  }
}

const toast = useToast()

// Function to send notifications to the server
async function sendNotifications(eventId, notifications) {
  try {
    const response = await axios.post(`/api/agenda_reminders/delete_by_event`, { eventId })
    console.log(response.data.message)
  } catch (error) {
    console.error(`Error deleting existing notifications for event ${eventId}:`, error)
  }
  const promises = notifications.map(notification => {
    const notificationData = {
      eventId: eventId,
      count: notification.count,
      period: notification.period,
    }
    return fetch('/api/agenda_reminders', {
      method: 'POST',
      headers: {
        'Accept': 'application/ld+json',
        'Content-Type': 'application/ld+json',
      },
      body: JSON.stringify(notificationData),
    })
      .then(response => {
        if (!response.ok) {
          return response.json().then(errorBody => {
            throw new Error(`Error: ${response.status} ${errorBody['hydra:description'] || 'Unknown error'}`)
          })
        }
        return response.json()
      })
      .catch(error => {
        console.error('Failed to send a notification:', error.message)
      })
  })
  await Promise.allSettled(promises)
}

watch(() => route.query.type, (newType) => {
  isGlobal.value = newType === 'global'
  reFetch()
})

watch(
  () => store.state.ccalendarevent.created,
  (created) => {
    toast.add({
      severity: "success",
      detail: t("{resource} created", { resource: created.resourceNode.title }),
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
      detail: t("{resource} updated", { resource: updated.resourceNode.title }),
      life: 3500,
    })

    reFetch()
  },
)
</script>
