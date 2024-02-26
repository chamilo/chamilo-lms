<script setup>
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useCalendarActionButtons } from "../../composables/calendar/calendarActionButtons"

const { t } = useI18n()

const { showAddButton, showSessionPlanningButton, showMyStudentsScheduleButton } = useCalendarActionButtons()

defineEmits(["addClick", "myStudentsScheduleClick", "sessionPlanningClick"])
</script>

<template>
  <SectionHeader :title="t('Agenda')">
    <BaseButton
      v-if="showAddButton"
      :label="t('Add event')"
      icon="agenda-event"
      only-icon
      type="black"
      @click="$emit('addClick')"
    />

    <BaseButton
      v-if="showSessionPlanningButton"
      :label="t('Sessions plan calendar')"
      icon="agenda-plan"
      only-icon
      type="black"
      @click="$emit('sessionPlanningClick')"
    />

    <BaseButton
      v-if="showMyStudentsScheduleButton"
      :label="t('My students schedule')"
      icon="agenda-user-event"
      only-icon
      type="black"
      @click="$emit('myStudentsScheduleClick')"
    />
  </SectionHeader>
</template>
