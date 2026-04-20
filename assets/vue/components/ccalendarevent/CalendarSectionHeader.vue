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
      v-if="showAgendaListButton"
      class="flex items-center gap-1"
    >
      <BaseButton
        :label="t('Calendar')"
        icon="agenda-event"
        only-icon
        type="black"
        :disabled="isCalendarActive"
        :class="isCalendarActive ? '' : 'opacity-60 hover:opacity-100'"
        @click="goCalendar"
      />
      <BaseButton
        :label="t('Events list')"
        icon="agenda-list"
        only-icon
        type="black"
        :disabled="isListActive"
        :class="isListActive ? '' : 'opacity-60 hover:opacity-100'"
        @click="goList"
      />
    </div>

    <BaseButton
      v-if="showSessionPlanningButton"
      :label="t('Sessions plan calendar')"
      icon="agenda-plan"
      only-icon
      type="black"
      :disabled="isSessionPlanningActive"
      :class="isSessionPlanningActive ? '' : 'opacity-60 hover:opacity-100'"
      @click="emit('sessionPlanningClick')"
    />

    <BaseButton
      v-if="showMyStudentsScheduleButton"
      :label="t('My students schedule')"
      icon="agenda-user-event"
      only-icon
      type="black"
      :disabled="isMyStudentsScheduleActive"
      :class="isMyStudentsScheduleActive ? '' : 'opacity-60 hover:opacity-100'"
      @click="emit('myStudentsScheduleClick')"
    />
  </SectionHeader>
</template>
