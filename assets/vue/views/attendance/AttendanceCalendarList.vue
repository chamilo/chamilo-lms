<template>
  <section class="space-y-4">
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Calendar')"
      icon="calendar-clock"
    >
      <BaseButton
        :label="t('Back to attendance sheet')"
        icon="back"
        only-icon
        type="black-text"
        @click="redirectToAttendanceSheet"
      />
      <BaseButton
        :label="t('Add calendar event')"
        icon="plus"
        type="success"
        @click="redirectToAddCalendarEvent"
      />
      <BaseButton
        :disabled="isLoading || calendarEvents.length === 0"
        :label="t('Clear all')"
        icon="clear-all"
        type="danger"
        @click="clearAllEvents"
      />
    </SectionHeader>

    <p class="text-body-2 text-gray-50">
      {{
        t(
          "The attendance calendar allows you to register attendance lists (one per real session the students need to attend). Add new attendance lists here.",
        )
      }}
    </p>

    <div
      v-if="isLoading"
      aria-busy="true"
      class="flex flex-col gap-4"
    >
      <Skeleton
        v-for="index in CALENDAR_SKELETON.items"
        :key="index"
        :height="`${CALENDAR_SKELETON.itemHeight}px`"
      />
    </div>

    <p
      v-else-if="calendarEvents.length === 0"
      class="text-body-2 text-gray-50"
    >
      {{ t("No data available") }}
    </p>

    <ul
      v-else
      class="flex flex-col gap-4"
    >
      <li
        v-for="event in calendarEvents"
        :key="event.id"
        class="flex items-center justify-between gap-4 rounded-lg border border-gray-25 bg-white p-4 shadow-sm"
      >
        <div class="flex items-center gap-4">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-support-1 text-primary">
            <BaseIcon icon="calendar-clock" />
          </span>
          <div class="flex flex-col gap-1">
            <span class="text-body-1 font-semibold text-gray-90">
              {{ formatDateTime(event.dateTime) }}
            </span>
            <span
              v-if="event.duration !== null && event.duration !== undefined"
              class="text-body-2 text-gray-50"
            >
              {{ t("{0} min", [event.duration]) }}
            </span>
            <span
              v-if="event.effectiveRoom"
              class="text-body-2 text-gray-50"
            >
              {{ t("Room") }}: {{ formatRoom(event.effectiveRoom) }}
            </span>
            <RoomFloorPlanViewer
              v-if="event.effectiveRoom"
              :calendar-id="event.id"
            />
          </div>
        </div>
        <div class="flex shrink-0 gap-2">
          <BaseButton
            :label="t('Edit')"
            icon="pencil"
            only-icon
            size="small"
            type="secondary-text"
            @click="openEditDialog(event)"
          />
          <BaseButton
            :label="t('Delete')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="confirmDeleteEvent(event.id)"
          />
        </div>
      </li>
    </ul>

    <BaseDialog
      v-model:is-visible="editDialogVisible"
      :title="t('Edit calendar event')"
      header-icon="pencil"
    >
      <div class="flex flex-col gap-4">
        <BaseCalendar
          id="date_time"
          v-model="selectedEventDate"
          :label="t('Date')"
          :show-time="true"
        />

        <BaseInputNumber
          :id="`duration-${selectedEvent?.id || ''}`"
          v-model="selectedEventDuration"
          :label="t('Duration in minutes')"
          :min="0"
        />

        <BaseSelect
          id="attendance_calendar_room"
          v-model="selectedEventRoom"
          :label="t('Room')"
          :options="roomOptions"
        />
        <RoomFloorPlanPicker
          v-model="selectedEventRoom"
          :rooms="floorPlanRooms"
        />
      </div>
      <template #footer>
        <BaseButton
          :label="t('Save')"
          icon="save"
          type="success"
          @click="updateCalendarEvent"
        />
      </template>
    </BaseDialog>
  </section>
</template>
<script setup>
import { computed, ref, onMounted } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import attendanceService from "../../services/attendanceService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import Skeleton from "primevue/skeleton"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import roomService from "../../services/roomService"
import RoomFloorPlanPicker from "../../components/room/RoomFloorPlanPicker.vue"
import RoomFloorPlanViewer from "../../components/room/RoomFloorPlanViewer.vue"

// Date cards of the local portal at 1440 px (date, duration, no room).
const CALENDAR_SKELETON = { items: 4, itemHeight: 74 }

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification } = useNotification()
const calendarEvents = ref([])
const isLoading = ref(true)
const editDialogVisible = ref(false)
const selectedEvent = ref(null)

const selectedEventDate = ref(null)
const selectedEventDuration = ref(null)
const selectedEventRoom = ref(null)
const roomOptions = ref([])
const floorPlanRooms = computed(() =>
  roomOptions.value
    .filter((option) => option.room)
    .map((option) => ({
      id: option.value,
      name: option.label,
      branch: option.room.branch,
      roomMap: option.room.roomMap,
    })),
)

const formatDateTime = (dateTime) => {
  const date = dateTime instanceof Date ? dateTime : new Date(dateTime)
  return date.toLocaleString()
}

const formatRoom = (room) => {
  if (!room) return ""
  return [room.branchTitle, room.title].filter(Boolean).join(" — ")
}

const fetchCalendarEvents = async () => {
  try {
    isLoading.value = true
    const response = await attendanceService.getAttendance(route.params.id)

    if (response.calendars && Array.isArray(response.calendars)) {
      calendarEvents.value = response.calendars.map((calendar) => ({
        id: calendar.iid || calendar["@id"].split("/").pop(),
        title: calendar.title || "Unnamed Event",
        dateTime: calendar.dateTime ? new Date(calendar.dateTime) : new Date(),
        duration:
          typeof calendar.duration === "number"
            ? calendar.duration
            : calendar.duration !== undefined && calendar.duration !== null
              ? Number(calendar.duration)
              : null,
        room: typeof calendar.room === "string" ? calendar.room : calendar.room?.["@id"] || null,
        effectiveRoom: calendar.effectiveRoomData || null,
      }))
    } else {
      calendarEvents.value = []
    }
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

const openEditDialog = (event) => {
  selectedEvent.value = event
  // Ensure BaseCalendar/DatePicker receives a Date instance or null
  selectedEventDate.value = event?.dateTime ? new Date(event.dateTime) : null

  // Initialize duration from event, keeping numeric type
  if (event && event.duration !== undefined && event.duration !== null) {
    selectedEventDuration.value = Number(event.duration)
  } else {
    selectedEventDuration.value = null
  }

  selectedEventRoom.value = event?.room || null

  editDialogVisible.value = true
}

const updateCalendarEvent = async () => {
  if (!selectedEvent.value || !selectedEventDate.value) {
    console.warn("Calendar event or date is missing. Update aborted.")
    return
  }

  try {
    const payload = {
      dateTime: selectedEventDate.value,
      duration: selectedEventDuration.value,
      room: selectedEventRoom.value || null,
    }

    await attendanceService.updateCalendarEvent(selectedEvent.value.id, payload)

    closeEditDialog()
    await fetchCalendarEvents()
  } catch (error) {
    showErrorNotification(error)
  }
}

const closeEditDialog = () => {
  editDialogVisible.value = false
  selectedEvent.value = null
  selectedEventDate.value = null
  selectedEventDuration.value = null
  selectedEventRoom.value = null
}

/**
 * Ask for confirmation, then delete one calendar event and reload the list.
 * @param {number|string} id
 */
const confirmDeleteEvent = (id) => {
  requireConfirmation({
    title: t("Delete calendar event"),
    message: t("Are you sure you want to delete this event?"),
    accept: async () => {
      try {
        await attendanceService.deleteCalendarEvent(id)
        await fetchCalendarEvents()
      } catch (error) {
        showErrorNotification(error)
      }
    },
  })
}

/**
 * Ask for confirmation, then delete every calendar event of the attendance.
 */
const clearAllEvents = () => {
  requireConfirmation({
    message: t("Are you sure you want to delete all calendar events?"),
    accept: async () => {
      try {
        for (const event of calendarEvents.value) {
          await attendanceService.deleteCalendarEvent(event.id)
        }
        calendarEvents.value = []
      } catch (error) {
        showErrorNotification(error)
        await fetchCalendarEvents()
      }
    },
  })
}

const redirectToAttendanceSheet = () => {
  router.push({
    name: "AttendanceSheetList",
    params: { id: route.params.id },
    query: {
      cid: route.query.cid,
      sid: route.query.sid,
      gid: route.query.gid,
    },
  })
}

const redirectToAddCalendarEvent = () => {
  router.push({
    name: "AttendanceAddCalendarEvent",
    params: { node: route.params.node, id: route.params.id },
    query: {
      cid: route.query.cid,
      sid: route.query.sid,
      gid: route.query.gid,
    },
  })
}

const loadRooms = async () => {
  try {
    roomOptions.value = await roomService.getOptions({
      includeDefault: true,
      defaultLabel: t("Use attendance default room"),
      floorLabel: t("Floor"),
      capacityLabel: t("Capacity"),
    })
  } catch (error) {
    showErrorNotification(error)
  }
}

onMounted(async () => {
  await Promise.all([fetchCalendarEvents(), loadRooms()])
})
</script>
