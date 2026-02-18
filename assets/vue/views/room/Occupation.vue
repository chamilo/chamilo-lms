<template>
  <div class="flex flex-col gap-4">
    <FullCalendar
      ref="cal"
      :options="calendarOptions"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import FullCalendar from "@fullcalendar/vue3"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import allLocales from "@fullcalendar/core/locales-all"
import roomService from "../../services/roomService"
import { useFormatDate } from "../../composables/formatDate"
import { useLocale } from "../../composables/locale"

const route = useRoute()
const { t } = useI18n()
const { getCurrentTimezone } = useFormatDate()
const { appLocale } = useLocale()

const roomId = route.params.id
const cal = ref(null)

const timezone = getCurrentTimezone()

const localeMapping = {
  en_US: "en",
  fr_FR: "fr",
  es_ES: "es",
  pt_BR: "pt-br",
  de_DE: "de",
  it_IT: "it",
}
const calendarLocaleCode = localeMapping[appLocale.value] || appLocale.value?.split("_")[0] || "en"

const calendarOptions = ref({
  timeZone: timezone,
  plugins: [dayGridPlugin, timeGridPlugin],
  locales: allLocales,
  locale: calendarLocaleCode,
  headerToolbar: {
    left: "today prev,next",
    center: "title",
    right: "timeGridWeek,timeGridDay",
  },
  nowIndicator: true,
  initialView: "timeGridWeek",
  selectable: false,
  editable: false,
  events: async (fetchInfo, successCallback, failureCallback) => {
    try {
      const events = await roomService.getOccupation(roomId, fetchInfo.start, fetchInfo.end)
      successCallback(events)
    } catch (e) {
      console.error("Failed to load occupation events", e)
      failureCallback(e)
    }
  },
})
</script>
