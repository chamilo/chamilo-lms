<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader
      active-view="session-planning"
      @addClick="goToAddEvent"
      @agendaListClick="goToAgendaList"
      @sessionPlanningClick="goToSessionsPlan"
      @myStudentsScheduleClick="goToMyStudentsSchedule"
    />

    <div class="calendar-toolbar">
      <div class="calendar-toolbar__group">
        <div class="calendar-toolbar__nav">
          <BaseButton
            :label="t('Previous')"
            icon="chevron-left"
            only-icon
            type="primary"
            @click="setYear(year - 1)"
          />
          <BaseButton
            :label="t('Next')"
            icon="chevron-right"
            only-icon
            type="primary"
            @click="setYear(year + 1)"
          />
        </div>
        <div class="flex flex-col">
          <span
            class="calendar-toolbar__view"
            v-text="t('Sessions plan calendar')"
          />
          <div class="calendar-toolbar__title">
            <h2 v-text="year" />
          </div>
        </div>
      </div>

      <div class="calendar-toolbar__title">
        <span
          class="text-body-2 text-gray-50"
          v-text="t('Weeks are displayed from 1 to 52')"
        />
        <span
          class="calendar-toolbar__count"
          v-text="`${t('Sessions')}: ${sessions.length}`"
        />
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-danger bg-white p-3 text-body-2 text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="!isLoading && sessions.length === 0 && !errorMessage"
      class="calendar-list__empty"
    >
      <span class="calendar-list__empty-icon">
        <BaseIcon icon="agenda-plan" />
      </span>
      <p v-text="t('No session available')" />
    </div>

    <div
      v-if="isLoading || sessions.length"
      class="calendar-plan"
    >
      <div
        v-if="isLoading"
        class="calendar-plan__loading"
      >
        <i class="mdi mdi-loading mdi-spin text-2xl" />
        <span class="text-body-2">{{ t("Loading") }}</span>
      </div>

      <div class="overflow-x-auto">
        <div class="min-w-[1400px]">
          <!-- Header -->
          <div class="plan-grid calendar-plan__header">
            <div class="px-3 py-2">
              {{ t("Session") }}
            </div>
            <div
              v-for="w in 52"
              :key="`w-${w}`"
              class="border-s border-gray-25 px-1 py-2 text-center"
            >
              {{ w }}
            </div>
          </div>

          <!-- Rows -->
          <div
            v-for="s in sessions"
            :key="`s-${s.id}`"
            class="plan-grid calendar-plan__row"
          >
            <div class="px-3 py-2">
              <div
                class="truncate text-body-2 font-semibold text-gray-90"
                :title="s.title"
              >
                {{ s.title }}
              </div>
              <div class="text-tiny text-gray-50">
                {{ t("From {0} to {1}", [s.startDate || "—", s.endDate || "—"]) }}
              </div>
              <div
                v-if="s.humanDate"
                class="mt-0.5 text-tiny text-gray-50"
              >
                {{ s.humanDate }}
              </div>
            </div>

            <div class="weeks-cell border-s border-gray-25">
              <div
                v-if="showBar(s)"
                class="bar"
                :style="barStyle(s)"
                :title="barTooltip(s)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { DateTime } from "luxon"
import calendarService from "../../services/calendarService"
import CalendarSectionHeader from "../../components/ccalendarevent/CalendarSectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useRoute, useRouter } from "vue-router"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

function tOrFallback(key, fallback) {
  const out = t(key)
  return out === key ? fallback : out
}

function parseQueryYear(yearValue, dateValue) {
  const current = DateTime.now().year
  const y = Number(yearValue)
  if (Number.isFinite(y) && y >= 1970 && y <= 2100) {
    return y
  }
  if (dateValue) {
    const dt = DateTime.fromISO(String(dateValue))
    if (dt.isValid) {
      return dt.year
    }
  }
  return current
}

const year = ref(parseQueryYear(route.query.year, route.query.date))

watch(
  () => [route.query.year, route.query.date],
  ([y, d]) => {
    year.value = parseQueryYear(y, d)
  },
)

function syncYearToQuery(nextYear) {
  if (String(route.query.year || "") === String(nextYear)) return
  const nextQuery = { ...route.query, year: String(nextYear) }
  router.replace({ name: route.name ?? "CalendarSessionsPlan", params: route.params, query: nextQuery }).catch(() => {})
}

function setYear(nextYear) {
  year.value = nextYear
  syncYearToQuery(nextYear)
}

function buildAgendaNavigationQuery() {
  const nextQuery = { ...route.query }

  if (!nextQuery.date && year.value) {
    nextQuery.date = `${year.value}-01-01`
  }

  return nextQuery
}

function goToSessionsPlan() {
  router.push({ name: "CalendarSessionsPlan", query: { ...route.query, year: String(year.value) } }).catch(() => {})
}

function goToMyStudentsSchedule() {
  router.push({ name: "CalendarMyStudentsSchedule", query: { ...buildAgendaNavigationQuery() } }).catch(() => {})
}

function goToAgendaList(mode = "list") {
  const targetRoute = mode === "calendar" ? "CCalendarEventList" : "CCalendarEventListView"
  router.push({ name: targetRoute, query: { ...buildAgendaNavigationQuery() } }).catch(() => {})
}

function goToAddEvent() {
  router.push({ name: "CCalendarEventList", query: { ...buildAgendaNavigationQuery(), openAdd: "1" } }).catch(() => {})
}

const isLoading = ref(false)
const errorMessage = ref("")
const sessions = ref([])

async function fetchPlan() {
  try {
    isLoading.value = true
    errorMessage.value = ""

    const data = await calendarService.getSessionsPlan(year.value)
    const items = Array.isArray(data) ? data : Array.isArray(data?.["hydra:member"]) ? data["hydra:member"] : []

    sessions.value = items.map((x) => ({
      id: x.id,
      title: x.title,
      startDate: x.startDate ?? null,
      endDate: x.endDate ?? null,
      humanDate: x.humanDate ?? null,
      start: Number.isFinite(Number(x.start)) ? Number(x.start) : 0,
      duration: Number.isFinite(Number(x.duration)) ? Math.max(1, Number(x.duration)) : 1,
      color: x.color || "rgba(70,130,180,0.9)",
    }))
  } catch (e) {
    console.error("[SessionsPlan] Request failed", e?.response?.status ?? e)
    if (e?.response?.status === 403) {
      errorMessage.value = tOrFallback("TooMuchSessionsInPlanification", "Too many sessions in your plan")
    } else {
      errorMessage.value = tOrFallback("Failed to load sessions plan", "Failed to load sessions plan")
    }
    sessions.value = []
  } finally {
    isLoading.value = false
  }
}

watch(
  () => year.value,
  () => fetchPlan(),
  { immediate: true },
)

function showBar(s) {
  return Number.isFinite(s.start) && Number.isFinite(s.duration) && s.duration > 0
}

function barTooltip(s) {
  const from = s.startDate || "—"
  const until = s.endDate || "—"
  return `${s.title}\n${t("From")} ${from}\n${t("Until")} ${until}`
}

function barStyle(s) {
  const start = Math.min(51, Math.max(0, s.start))
  const duration = Math.min(52, Math.max(1, s.duration))
  const leftPercent = (start / 52) * 100
  const widthPercent = (duration / 52) * 100
  return {
    left: `${leftPercent}%`,
    width: `${widthPercent}%`,
    background: s.color,
  }
}
</script>
<style scoped>
.plan-grid {
  display: grid;
  grid-template-columns: 320px repeat(52, minmax(10px, 1fr));
}
.calendar-plan__header > div,
.calendar-plan__row > div {
  min-height: 44px;
}
.weeks-cell {
  grid-column: 2 / -1;
  position: relative;
  min-height: 44px;
}
.bar {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  height: 20px;
  border-radius: 8px;
  opacity: 0.9;
}
</style>
