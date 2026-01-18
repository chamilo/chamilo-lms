<template>
  <div class="p-4">
    <!-- Toolbar -->
    <BaseToolbar>
      <template #start>
        <BaseButton
          icon="back"
          size="normal"
          type="black"
          @click="redirectToAttendanceSheet"
          :title="t('Back to attendance sheet')"
        />
        <BaseButton
          icon="plus"
          size="normal"
          type="black"
          @click="redirectToAddCalendarEvent"
          :title="t('Add calendar event')"
        />
        <BaseButton
          icon="clear-all"
          size="normal"
          type="black"
          @click="clearAllEvents"
          :title="t('Clear all')"
        />
      </template>
    </BaseToolbar>

    <!-- Informative Message -->
    <div class="p-4 mb-4 text-primary bg-gray-15 border border-gray-25 rounded">
      <p>
        {{
          t(
            "The attendance calendar allows you to register attendance lists (one per real session the students need to attend). Add new attendance lists here.",
          )
        }}
      </p>
    </div>

    <!-- Calendar Events List -->
    <div class="calendar-list flex flex-col gap-4">
      <div
        v-for="event in calendarEvents"
        :key="event.id"
        class="calendar-item flex justify-between items-center border border-gray-25 rounded bg-gray-10 p-4"
      >
        <div class="flex items-center gap-2">
          <i class="pi pi-calendar text-primary text-lg"></i>
          <span class="text-gray-90">
            {{ formatDateTime(event.dateTime) }}
            <template v-if="event.duration !== null && event.duration !== undefined">
              · {{ t("{0} min", [event.duration]) }}
            </template>
          </span>
        </div>
        <div class="calendar-actions flex gap-2">
          <BaseButton
            icon="edit"
            type="warning"
            @click="openEditDialog(event)"
          />
          <BaseButton
            icon="delete"
            type="danger"
            @click="openDeleteDialog(event.id)"
          />
        </div>
      </div>
    </div>

    <!-- Edit Dialog -->
    <Dialog
      v-model:visible="editDialogVisible"
      :header="t('Edit calendar event')"
      :modal="true"
      :closable="false"
    >
      <div class="p-fluid flex flex-col gap-4">
        <BaseCalendar
          id="date_time"
          v-model="selectedEventDate"
          :label="t('Date')"
          :show-time="true"
        />

        <div class="field">
          <label
            class="block mb-1 text-gray-90"
            :for="`duration-${selectedEvent?.id || ''}`"
          >
            {{ t("Duration") }}
          </label>
          <input
            :id="`duration-${selectedEvent?.id || ''}`"
            v-model.number="selectedEventDuration"
            type="number"
            min="0"
            class="w-full rounded border border-gray-25 px-3 py-2 text-sm text-gray-90 focus:outline-none"
            :placeholder="t('Duration in minutes')"
          />
        </div>
      </div>
      <div class="dialog-actions mt-4">
        <BaseButton
          :label="t('Save')"
          icon="check"
          type="success"
          @click="updateCalendarEvent"
        />
        <BaseButton
          :label="t('Cancel')"
          icon="times"
          type="danger"
          @click="closeEditDialog"
        />
      </div>
    </Dialog>

    <!-- Delete Dialog -->
    <Dialog
      v-model:visible="deleteDialogVisible"
      :header="t('Delete calendar event')"
      :modal="true"
      :closable="false"
    >
      <p>{{ t("Are you sure you want to delete this event?") }}</p>
      <div class="dialog-actions">
        <BaseButton
          :label="t('Yes')"
          icon="check"
          type="danger"
          @click="deleteCalendarEventConfirmed"
        />
        <BaseButton
          :label="t('No')"
          icon="times"
          type="secondary"
          @click="closeDeleteDialog"
        />
      </div>
    </Dialog>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import attendanceService from "../../services/attendanceService"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import Dialog from "primevue/dialog"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const calendarEvents = ref([])
const isLoading = ref(false)
const editDialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const selectedEvent = ref(null)

const selectedEventDate = ref(null)
const selectedEventDuration = ref(null)

const eventIdToDelete = ref(null)

const formatDateTime = (dateTime) => {
  const date = dateTime instanceof Date ? dateTime : new Date(dateTime)
  return date.toLocaleString()
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
      }))
    } else {
      calendarEvents.value = []
    }
  } catch (error) {
    console.error("Error fetching calendar events:", error)
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
    }

    await attendanceService.updateCalendarEvent(selectedEvent.value.id, payload)

    const index = calendarEvents.value.findIndex((e) => e.id === selectedEvent.value.id)
    if (index !== -1) {
      calendarEvents.value[index] = {
        ...calendarEvents.value[index],
        dateTime: selectedEventDate.value,
        duration: selectedEventDuration.value,
      }
    }

    closeEditDialog()
  } catch (error) {
    console.error("Error updating calendar event:", error)
  }
}

const closeEditDialog = () => {
  editDialogVisible.value = false
  selectedEvent.value = null
  selectedEventDate.value = null
  selectedEventDuration.value = null
}

const openDeleteDialog = (id) => {
  eventIdToDelete.value = id
  deleteDialogVisible.value = true
}

const deleteCalendarEventConfirmed = async () => {
  try {
    await attendanceService.deleteCalendarEvent(eventIdToDelete.value)
    closeDeleteDialog()
    await fetchCalendarEvents()
  } catch (error) {
    console.error("Error deleting calendar event:", error)
  }
}

const clearAllEvents = async () => {
  try {
    if (confirm(t("Are you sure you want to delete all calendar events?"))) {
      for (const event of calendarEvents.value) {
        await attendanceService.deleteCalendarEvent(event.id)
      }
      calendarEvents.value = []
    }
  } catch (error) {
    console.error("Error clearing all events:", error)
  }
}

const closeDeleteDialog = () => {
  deleteDialogVisible.value = false
  eventIdToDelete.value = null
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

onMounted(fetchCalendarEvents)
</script>
<style scoped>
.calendar-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.calendar-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border: 1px solid #ddd;
  padding: 0.75rem;
  border-radius: 8px;
  background-color: #f9f9f9;
}
.calendar-actions {
  display: flex;
  gap: 0.5rem;
}
.dialog-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
</style>
