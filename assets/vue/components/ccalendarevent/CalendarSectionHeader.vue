<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useCalendarActionButtons } from "../../composables/calendar/calendarActionButtons"

const { t } = useI18n()
const route = useRoute()
const emit = defineEmits(["addClick"])

const props = defineProps({
  activeView: {
    type: String,
    default: "", // "calendar" | "list" | "session-planning" | "my-students-schedule"
  },
  // The state of the view that is not in its URL, e.g. the day shown by a calendar that does not sync it.
  navigationQuery: {
    type: Object,
    default: () => ({}),
  },
})

const { showAddButton, showAgendaListButton, showSessionPlanningButton, showMyStudentsScheduleButton } =
  useCalendarActionButtons()

// The context of the agenda, its filter and the day and view on screen; any other parameter is stale in another view.
const navigationKeys = ["cid", "sid", "gid", "type", "recipient", "date", "view"]

const linkQuery = computed(() => {
  const query = {}

  for (const key of navigationKeys) {
    const value = key in props.navigationQuery ? props.navigationQuery[key] : route.query[key]

    if (value !== undefined && value !== null && value !== "") {
      query[key] = value
    }
  }

  return query
})

const views = computed(() =>
  [
    {
      key: "calendar",
      label: t("Calendar"),
      icon: "agenda-event",
      routeName: "CCalendarEventList",
      visible: showAgendaListButton.value,
    },
    {
      key: "list",
      label: t("Events list"),
      icon: "agenda-list",
      routeName: "CCalendarEventListView",
      visible: showAgendaListButton.value,
    },
    {
      key: "session-planning",
      label: t("Sessions plan calendar"),
      icon: "agenda-plan",
      routeName: "CalendarSessionsPlan",
      visible: showSessionPlanningButton.value,
    },
    {
      key: "my-students-schedule",
      label: t("My students schedule"),
      icon: "agenda-user-event",
      routeName: "CalendarMyStudentsSchedule",
      visible: showMyStudentsScheduleButton.value,
    },
  ].filter((view) => view.visible),
)
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

    <template
      v-for="view in views"
      :key="view.key"
    >
      <BaseButton
        v-if="activeView === view.key"
        :icon="view.icon"
        :label="view.label"
        aria-current="page"
        class="calendar-view-button--active"
        disabled
        only-icon
        type="primary-text"
      />
      <BaseButton
        v-else
        :icon="view.icon"
        :label="view.label"
        :route="{ name: view.routeName, query: linkQuery }"
        only-icon
        type="primary-text"
      />
    </template>
  </SectionHeader>
</template>
