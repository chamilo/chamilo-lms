<template>
  <div>
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
          :label="t('Delete')"
          icon="delete"
          type="danger"
          @click="confirmDelete"
        />
        <BaseButton
          v-if="isEventEditable"
          :label="t('Edit')"
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
    >
      <div class="flex flex-col gap-4">
        <h5 v-text="sessionState.sessionAsEvent.title" />
        <p
          v-if="sessionState.sessionAsEvent.start"
          v-t="{
            path: 'From: {date}',
            args: {
              date: useAbbreviatedDatetime(sessionState.sessionAsEvent.start),
            },
          }"
        />
        <p
          v-if="sessionState.sessionAsEvent.end"
          v-t="{
            path: 'Until: {date}',
            args: {
              date: useAbbreviatedDatetime(sessionState.sessionAsEvent.end),
            },
          }"
        />
      </div>

      <template #footer>
        <a
          v-t="'Go to session'"
          :href="`/sessions/${sessionState.sessionAsEvent.id}/about`"
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
import { useAbbreviatedDatetime } from "../../composables/formatDate.js"
import { usePlatformConfig } from "../../store/platformConfig"

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
import { useCidReq } from "../../composables/cidReq"
import cCalendarEventService from "../../services/ccalendarevent"
import sessionRelUserService from "../../services/sessionRelUserService"
import { useCidReqStore } from "../../store/cidReq"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"

const store = useStore()
const confirm = useConfirm()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()

const item = ref({})
const dialog = ref(false)
const dialogShow = ref(false)
const isEventEditable = ref(false)

const currentUser = computed(() => store.getters["security/getUser"])
const { t, locale } = useI18n()

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

const { cid, sid, gid } = useCidReq()

async function getCalendarEvents({ startStr, endStr }) {
  const calendarEvents = await cCalendarEventService
    .findAll({
      params: {
        startDate: startStr,
        endDate: endStr,
        cid,
        sid,
        gid,
      },
    })
    .then((response) => response.json())

  return calendarEvents["hydra:member"].map((event) => ({
    ...event,
    start: event.startDate,
    end: event.endDate,
  }))
}

async function getSessions({ startStr, endStr }) {
  if ("true" !== platformConfigStore.getSetting("agenda.personal_calendar_show_sessions_occupation")) {
    return []
  }

  const sessions = await sessionRelUserService.findAll({
    user: currentUser.value["@id"],
    "displayStartDate[after]": startStr,
    "displayEndDate[before]": endStr,
    relationType: 3,
  })

  return sessions["hydra:member"].map((sessionRelUser) => ({
    ...sessionRelUser.session,
    title: sessionRelUser.session.name,
    start: sessionRelUser.session.displayStartDate,
    end: sessionRelUser.session.displayEndDate,
  }))
}

// @todo fix locale connection between fullcalendar + chamilo

if ("en_US" === locale.value) {
  locale.value = "en"
}

if ("fr_FR" === locale.value) {
  locale.value = "fr"
}

if ("pl_PL" === locale.value) {
  locale.value = "pl"
}

const calendarOptions = ref({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  locales: allLocales,
  locale: locale.value,
  customButtons: {
    addEvent: {
      text: t("Add event"),
      click: function () {
        item.value = {}
        item.value["parentResourceNodeId"] = currentUser.value.resourceNode["id"]
        item.value["collective"] = false

        dialog.value = true
      },
    },
  },
  headerToolbar: {
    left: "prev,next today,addEvent",
    center: "title",
    right: "dayGridMonth,timeGridWeek,timeGridDay",
  },
  nowIndicator: true,
  initialView: "dayGridMonth",
  startParam: "startDate[after]",
  endParam: "endDate[before]",
  selectable: true,
  eventClick(EventClickArg) {
    let event = EventClickArg.event.toPlainObject()

    if (event.extendedProps["@type"] && event.extendedProps["@type"] === "Session") {
      sessionState.sessionAsEvent = event
      sessionState.showSessionDialog = true
      EventClickArg.jsEvent.preventDefault()

      return
    }

    currentEvent = event

    item.value = { ...event.extendedProps }

    item.value["title"] = event.title
    item.value["startDate"] = event.start
    item.value["endDate"] = event.end
    item.value["parentResourceNodeId"] = event.extendedProps.resourceNode.creator.id

    isEventEditable.value = item.value["parentResourceNodeId"] === currentUser.value["id"]

    if (!isEventEditable.value && event.extendedProps.collective && event.extendedProps.resourceLinkListFromEntity) {
      const resourceLink = event.extendedProps.resourceLinkListFromEntity.find(
        (linkEntity) => linkEntity.user.id === currentUser.value.id,
      )

      if (resourceLink) {
        isEventEditable.value = true
      }
    }

    dialogShow.value = true
  },
  dateClick(info) {
    item.value = {}
    item.value["parentResourceNodeId"] = currentUser.value.resourceNode["id"]
    item.value["collective"] = false
    item.value["allDay"] = info.allDay
    item.value["startDate"] = info.startStr
    item.value["endDate"] = info.endStr

    dialog.value = true
  },
  select(info) {
    item.value = {}
    item.value["parentResourceNodeId"] = currentUser.value.resourceNode["id"]
    item.value["collective"] = false
    item.value["allDay"] = info.allDay
    item.value["startDate"] = info.startStr
    item.value["endDate"] = info.endStr

    dialog.value = true
  },
  events(info, successCallback) {
    Promise.all([getCalendarEvents(info), getSessions(info)]).then((values) => {
      const events = values[0].concat(values[1])

      successCallback(events)
    })
  },
})

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
    accept() {
      if (item.value["parentResourceNodeId"] === currentUser.value["id"]) {
        store.dispatch("ccalendarevent/del", item.value)

        dialogShow.value = false
        dialog.value = false
        reFetch()
      } else {
        let filteredLinks = item.value["resourceLinkListFromEntity"].filter(
          (resourceLinkFromEntity) => resourceLinkFromEntity["user"]["id"] === currentUser.value["id"],
        )

        if (filteredLinks.length > 0) {
          store.dispatch("resourcelink/del", {
            "@id": `/api/resource_links/${filteredLinks[0]["id"]}`,
          })

          currentEvent.remove()
          dialogShow.value = false
          dialog.value = false
          reFetch()
        }
      }
    },
  })
}

const isLoading = computed(() => store.getters["ccalendarevent/isLoading"])

const createForm = ref(null)

function onCreateEventForm() {
  if (createForm.value.v$.$invalid) {
    return
  }

  let itemModel = createForm.value.v$.item.$model

  if (itemModel["@id"]) {
    store.dispatch("ccalendarevent/update", itemModel)
  } else {
    if (cidReqStore.course) {
      itemModel.resourceLinkListFromEntity = [
        {
          cid: cidReqStore.course["id"],
          visibility: RESOURCE_LINK_PUBLISHED,
        },
      ]
    }

    store.dispatch("ccalendarevent/create", itemModel)
  }

  dialog.value = false
}

const toast = useToast()

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