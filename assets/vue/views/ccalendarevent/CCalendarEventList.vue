<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader @add-click="showAddEventDialog" />

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
          v-if="allowToEdit && showDeleteButton"
          :label="t('Delete')"
          icon="delete"
          type="danger"
          @click="confirmDelete"
        />
        <BaseButton
          v-if="allowToEdit && showEditButton"
          :label="t('Edit')"
          type="secondary"
          icon="edit"
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

const { t } = useI18n()
const { appLocale } = useLocale()
const route = useRoute()
const isGlobal = ref(route.query.type === "global")

const courseSettingsStore = useCourseSettings()
const allowUserEditAgenda = ref(false);

watch([course, session], async ([newCourse, newSession]) => {
  if (newCourse && newCourse.id) {
    const sessionId = newSession ? newSession.id : null;
    await courseSettingsStore.loadCourseSettings(newCourse.id, sessionId);
    const setting = courseSettingsStore.getSetting("allow_user_edit_agenda");
    allowUserEditAgenda.value = setting === "1";
    if (allowUserEditAgenda.value) {
      showAddButton.value = true;
    }
  } else {
    allowUserEditAgenda.value = false;
  }
}, { immediate: true });

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

  if (route.query?.type === "global") {
    params.type = "global"
  }

  const calendarEvents = await cCalendarEventService.findAll({ params }).then((response) => response.json())

  return calendarEvents["hydra:member"].map((event) => {
    let color = event.color || "#007BFF"

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
  item.value["parentResourceNode"] = securityStore.user.resourceNode["id"]

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
  eventClick(eventClickInfo) {
    eventClickInfo.jsEvent.preventDefault()
    currentEvent = eventClickInfo.event

    let event = eventClickInfo.event.toPlainObject()

    if (event.extendedProps["@type"] && event.extendedProps["@type"] === "Session") {
      allowToEdit.value = allowUserEditAgenda.value && (event.extendedProps.resourceNode.creator.id === securityStore.user.id)
      sessionState.sessionAsEvent = event
      sessionState.showSessionDialog = true

      return
    }

    item.value = { ...event.extendedProps }

    item.value["title"] = event.title
    item.value["startDate"] = event.start
    item.value["endDate"] = event.end
    item.value["parentResourceNodeId"] = event.extendedProps.resourceNode.creator.id

    allowToEdit.value = (isEditableByUser(item.value, securityStore.user.id) || allowUserEditAgenda.value) && (event.extendedProps.resourceNode.creator.id === securityStore.user.id)
    allowToSubscribe.value = !allowToEdit.value && allowSubscribeToEvent(item.value)
    allowToUnsubscribe.value = !allowToEdit.value && allowUnsubscribeToEvent(item.value, securityStore.user.id)

    dialogShow.value = true
  },
  select(info) {
    if (!showAddButton.value) {
      return
    }

    item.value = {}
    item.value["parentResourceNode"] = securityStore.user.resourceNode["id"]
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
  if (route.query.type === "global") {
    return "global"
  } else if (course.value) {
    return "course"
  } else if (session.value) {
    return "session"
  } else {
    return "personal"
  }
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

const showEditButton = computed(() => allowToEdit.value && allowAction(item.value.type));
const showDeleteButton = computed(() => (isEditableByUser(item.value, securityStore.user.id) || allowUserEditAgenda.value) && allowAction(item.value.type));

const cal = ref(null)

function reFetch() {
  cal.value.getApi().refetchEvents()
}

function confirmDelete() {
  confirm.require({
    message: t("Are you sure you want to delete"),
    header: t("Delete"),
    icon: "pi pi-exclamation-triangle",
    acceptClass: "p-button-danger",
    rejectClass: "p-button-plain p-button-outlined",
    acceptLabel: t("Yes"),
    rejectLabel: t("Cancel"),
    accept() {
      if (item.value["parentResourceNodeId"] === securityStore.user["id"]) {
        store.dispatch("ccalendarevent/del", item.value).then(() => {
          dialogShow.value = false
          dialog.value = false
          reFetch()
        })
      } else {
        let filteredLinks = item.value["resourceLinkListFromEntity"].filter(
          (resourceLinkFromEntity) => resourceLinkFromEntity["user"]["id"] === securityStore.user["id"],
        )

        if (filteredLinks.length > 0) {
          store.dispatch("resourcelink/del", {
            "@id": `/api/resource_links/${filteredLinks[0]["id"]}`,
          }).then(() => {
            currentEvent.remove();
            dialogShow.value = false
            dialog.value = false
            reFetch()
          })
        }
      }
    },
    reject() {},
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

    if (itemModel["@id"]) {
      await store.dispatch("ccalendarevent/update", itemModel)
    } else {
      if (course.value) {
        itemModel.resourceLinkList = [
          {
            cid: course.value.id,
            sid: session.value?.id ?? null,
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
