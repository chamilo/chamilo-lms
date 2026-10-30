<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useCalendarActionButtons } from "../../composables/calendar/calendarActionButtons"

const { t } = useI18n()
const emit = defineEmits(["addClick", "agendaListClick", "myStudentsScheduleClick", "sessionPlanningClick"])

const props = defineProps({
  activeView: {
    type: String,
    default: "", // "calendar" | "list" | "session-planning" | "my-students-schedule"
  },
})

const { showAddButton, showAgendaListButton, showSessionPlanningButton, showMyStudentsScheduleButton } =
  useCalendarActionButtons()

const isCalendarActive = computed(() => props.activeView === "calendar")
const isListActive = computed(() => props.activeView === "list")
const isSessionPlanningActive = computed(() => props.activeView === "session-planning")
const isMyStudentsScheduleActive = computed(() => props.activeView === "my-students-schedule")

/**
 * The button of the view on screen stands out from the others, as in the "tabs / view" of the component library.
 *
 * @param {boolean} isActive
 * @returns {string}
 */
function viewButtonType(isActive) {
  return isActive ? "primary-text" : "black-text"
}

function goCalendar() {
  emit("agendaListClick", "calendar")
}
function goList() {
  emit("agendaListClick", "list")
}
</script>

<template>
  <SectionHeader :title="t('Agenda')">
    <BaseButton
      v-if="showAddButton"
      :label="t('Add event')"
      icon="calendar-plus"
      only-icon
      type="success"
      @click="emit('addClick')"
    />

    <div
      v-if="showAgendaListButton || showSessionPlanningButton || showMyStudentsScheduleButton"
      class="calendar-view-switch"
    >
      <template v-if="showAgendaListButton">
        <BaseButton
          :label="t('Calendar')"
          :type="viewButtonType(isCalendarActive)"
          :disabled="isCalendarActive"
          icon="agenda-event"
          only-icon
          size="small"
          @click="goCalendar"
        />
        <BaseButton
          :label="t('Events list')"
          :type="viewButtonType(isListActive)"
          :disabled="isListActive"
          icon="agenda-list"
          only-icon
          size="small"
          @click="goList"
        />
      </template>

      <BaseButton
        v-if="showSessionPlanningButton"
        :label="t('Sessions plan calendar')"
        :type="viewButtonType(isSessionPlanningActive)"
        :disabled="isSessionPlanningActive"
        icon="agenda-plan"
        only-icon
        size="small"
        @click="emit('sessionPlanningClick')"
      />

      <BaseButton
        v-if="showMyStudentsScheduleButton"
        :label="t('My students schedule')"
        :type="viewButtonType(isMyStudentsScheduleActive)"
        :disabled="isMyStudentsScheduleActive"
        icon="agenda-user-event"
        only-icon
        size="small"
        @click="emit('myStudentsScheduleClick')"
      />
    </div>
  </SectionHeader>
</template>
