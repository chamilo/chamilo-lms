<template>
  <div class="flex flex-col gap-4">
    <CalendarSectionHeader
      active-view="list"
      @addClick="goToAddEvent"
      @agendaListClick="goToCalendar"
      @sessionPlanningClick="goToSessionsPlan"
      @myStudentsScheduleClick="goToMyStudentsSchedule"
    />

    <CalendarRecipientFilter />

    <div class="calendar-toolbar">
      <div class="calendar-toolbar__group">
        <BaseButton
          :label="t('Today')"
          type="black"
          @click="goToday"
        />
        <div class="calendar-toolbar__nav">
          <BaseButton
            :label="t('Previous')"
            icon="chevron-left"
            only-icon
            type="primary"
            @click="shiftRange(-1)"
          />
          <BaseButton
            :label="t('Next')"
            icon="chevron-right"
            only-icon
            type="primary"
            @click="shiftRange(1)"
          />
        </div>
      </div>

      <div class="calendar-toolbar__title">
        <div class="flex flex-col">
          <span
            class="calendar-toolbar__view"
            v-text="t('Events list')"
          />
          <h2 v-text="rangeLabel" />
        </div>
        <span
          class="calendar-toolbar__count"
          v-text="`${t('Events')}: ${filteredEvents.length}`"
        />
      </div>
    </div>

    <div
      v-if="showScopeFilters"
      class="calendar-tabs"
      role="tablist"
    >
      <button
        v-for="f in filters"
        :key="f.key"
        :aria-selected="activeFilter === f.key"
        :class="{ 'calendar-tabs__tab--active': activeFilter === f.key }"
        class="calendar-tabs__tab"
        role="tab"
        type="button"
        @click="activeFilter = f.key"
      >
        {{ f.label }}
      </button>
    </div>

    <!-- Results -->
    <div class="calendar-list">
      <div
        v-if="isLoading && !showSkeleton"
        class="calendar-list__loading"
      >
        <i class="mdi mdi-loading mdi-spin text-2xl" />
        <span class="text-body-2">{{ t("Loading") }}</span>
      </div>

      <template v-if="showSkeleton">
        <div
          v-for="d in skeletonDaysCount"
          :key="`sk-day-${d}`"
          class="calendar-list__day"
        >
          <div class="h-4 w-40 rounded bg-gray-20 animate-pulse" />

          <div
            v-for="i in skeletonItemsPerDay"
            :key="`sk-item-${d}-${i}`"
            class="calendar-list__card"
          >
            <div class="calendar-list__card-color bg-gray-20" />
            <div class="calendar-list__card-body">
              <div class="flex items-start justify-between gap-3">
                <div class="h-4 w-64 rounded bg-gray-20 animate-pulse" />
                <div class="h-3 w-24 rounded bg-gray-20 animate-pulse" />
              </div>
              <div class="h-3 w-5/6 rounded bg-gray-20 animate-pulse" />
              <div class="flex gap-2">
                <div class="h-6 w-20 rounded-full bg-gray-20 animate-pulse" />
                <div class="h-6 w-28 rounded-full bg-gray-20 animate-pulse" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <div
        v-if="groupedDays.length === 0 && !isLoading && !showSkeleton"
        class="calendar-list__empty"
      >
        <span class="calendar-list__empty-icon">
          <BaseIcon icon="agenda-event" />
        </span>
        <p v-text="t('No events found')" />
      </div>

      <section
        v-for="day in groupedDays"
        :key="day.key"
        class="calendar-list__day"
      >
        <h3
          class="calendar-list__day-title"
          v-text="day.label"
        />

        <article
          v-for="ev in day.items"
          :key="ev.key"
          class="calendar-list__card"
        >
          <div
            class="calendar-list__card-color"
            :style="{ background: ev.color }"
          />
          <div class="calendar-list__card-body">
            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between md:gap-3">
              <h4 class="calendar-list__card-title">
                <a
                  v-if="ev.url"
                  :href="ev.url"
                >
                  {{ ev.title }}
                </a>
                <span v-else>{{ ev.title }}</span>
              </h4>
              <span class="calendar-list__card-time">
                <BaseIcon
                  icon="calendar-clock"
                  size="small"
                />
                {{ ev.range }}
              </span>
            </div>

            <p
              v-if="ev.content"
              class="calendar-list__card-text"
            >
              {{ ev.content }}
            </p>

            <p
              v-if="ev.comment"
              class="calendar-list__card-text whitespace-pre-line"
            >
              <strong>{{ t("Comment") }}:</strong>
              {{ ev.comment }}
            </p>

            <div
              v-if="ev.scope || ev.course || ev.session || ev.type"
              class="calendar-list__tags"
            >
              <span v-if="ev.scope">{{ ev.scope }}</span>
              <span v-if="ev.course">{{ t("Course") }}: {{ ev.course }}</span>
              <span v-if="ev.session">{{ t("Session") }}: {{ ev.session }}</span>
              <span v-if="ev.type">{{ t("Type") }}: {{ ev.type }}</span>
            </div>
          </div>
        </article>
      </section>
    </div>
    <!-- /Results -->
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { DateTime } from "luxon"
import CalendarSectionHeader from "../../components/ccalendarevent/CalendarSectionHeader.vue"
import CalendarRecipientFilter from "../../components/ccalendarevent/CalendarRecipientFilter.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useLocale } from "../../composables/locale"
import { useCalendarEvent } from "../../composables/calendar/calendarEvent"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { getCurrentTimezone } = useFormatDate()
const { appLocaleTag } = useLocale()
const { getCalendarEvents } = useCalendarEvent()
const timezone = getCurrentTimezone()

function safeToLocaleString(dt, fmt) {
  if (!dt) return ""
  try {
    return dt.setLocale(appLocaleTag.value).toLocaleString(fmt)
  } catch (e) {
    return dt.setLocale("en").toLocaleString(fmt)
  }
}

function computeContextFromQuery(query) {
  if (query?.type === "global") return "global"
  if (query?.sid && String(query.sid) !== "0") return "session"
  if (query?.cid && String(query.cid) !== "0" && (!query.sid || String(query.sid) === "0")) return "course"
  return "personal"
}

const currentContext = computed(() => computeContextFromQuery(route.query))
const showScopeFilters = computed(() => {
  const ctx = currentContext.value
  return ctx !== "course" && ctx !== "session"
})

function parseQueryDate(value) {
  if (!value) return DateTime.now().setZone(timezone).startOf("day")
  const dt = DateTime.fromISO(String(value), { zone: timezone })
  return dt.isValid ? dt.startOf("day") : DateTime.now().setZone(timezone).startOf("day")
}

function syncDateToQuery(dt) {
  const dateStr = dt.toISODate()
  if (!dateStr) return
  if (route.query.date === dateStr) return

  const nextQuery = { ...route.query, date: dateStr }
  router
    .replace({ name: route.name ?? "CCalendarEventListView", params: route.params, query: nextQuery })
    .catch(() => {})
}

const viewType = computed(() => String(route.query.view || "timeGridWeek"))
const viewMode = computed(() => {
  const v = viewType.value
  if (v.includes("Month")) return "month"
  if (v.includes("Day")) return "day"
  return "week"
})

const anchor = ref(parseQueryDate(route.query.date))
watch(
  () => route.query.date,
  (d) => {
    anchor.value = parseQueryDate(d)
  },
)

const activeFilter = ref("all")

const filters = computed(() => {
  if (!showScopeFilters.value) return [{ key: "all", label: t("All") }]

  return [
    { key: "all", label: t("All") },
    { key: "personal", label: t("Personal") },
    { key: "course", label: t("Course") },
    { key: "session", label: t("Session") },
    { key: "assignment", label: t("Assignments") },
  ]
})

watch(
  () => showScopeFilters.value,
  (enabled) => {
    if (!enabled && activeFilter.value !== "all") activeFilter.value = "all"
  },
  { immediate: true },
)

function goToday() {
  anchor.value = DateTime.now().setZone(timezone).startOf("day")
  syncDateToQuery(anchor.value)
}

function shiftRange(direction) {
  if (viewMode.value === "month") {
    anchor.value = anchor.value.plus({ months: direction }).startOf("day")
  } else if (viewMode.value === "day") {
    anchor.value = anchor.value.plus({ days: direction }).startOf("day")
  } else {
    anchor.value = anchor.value.plus({ days: 7 * direction }).startOf("day")
  }
  syncDateToQuery(anchor.value)
}

const rangeStart = computed(() => {
  const dt = anchor.value.setZone(timezone)
  if (viewMode.value === "month") return dt.startOf("month").startOf("week")
  if (viewMode.value === "day") return dt.startOf("day")
  return dt.startOf("week")
})

const rangeEnd = computed(() => {
  const dt = anchor.value.setZone(timezone)
  if (viewMode.value === "month") return dt.endOf("month").endOf("week")
  if (viewMode.value === "day") return dt.endOf("day")
  return dt.endOf("week")
})

// Same as FullCalendar fetch semantics (end exclusive)
const apiRangeStart = computed(() => rangeStart.value.startOf("day"))
const apiRangeEnd = computed(() => rangeEnd.value.plus({ days: 1 }).startOf("day"))

const rangeLabel = computed(() => {
  const start = rangeStart.value
  const end = rangeEnd.value

  if (viewMode.value === "month") {
    return safeToLocaleString(anchor.value, { month: "long", year: "numeric" })
  }
  if (viewMode.value === "day") {
    return safeToLocaleString(start, DateTime.DATE_FULL)
  }
  const a = safeToLocaleString(start, DateTime.DATE_MED)
  const b = safeToLocaleString(end, DateTime.DATE_MED)
  return `${a} — ${b}`
})

/**
 * Goes to another agenda view with only the context of this one: the course, session, group or global agenda,
 * and the recipient filter. The other parameters of the list would be stale there.
 *
 * @param {string} name
 * @param {Object} extraQuery
 */
function pushWithContext(name, extraQuery = {}) {
  const query = {}

  for (const key of ["cid", "sid", "gid", "type", "recipient"]) {
    if (route.query[key] !== undefined && route.query[key] !== "") {
      query[key] = route.query[key]
    }
  }

  router.push({ name, query: { ...query, ...extraQuery } }).catch(() => {})
}

function goToSessionsPlan() {
  pushWithContext("CalendarSessionsPlan")
}

function goToMyStudentsSchedule() {
  pushWithContext("CalendarMyStudentsSchedule")
}

function goToCalendar() {
  // The calendar opens on the day and in the view of the list.
  pushWithContext("CCalendarEventList", { date: anchor.value.toISODate(), view: viewType.value })
}

function goToAddEvent() {
  pushWithContext("CCalendarEventList", { openAdd: "1" })
}

const isLoading = ref(false)
const rawEvents = ref([])

const skeletonDaysCount = 3
const skeletonItemsPerDay = 2
const showSkeleton = computed(() => isLoading.value && groupedDays.value.length === 0)

function computeCommonParams() {
  const q = route.query || {}
  const params = {}

  const cid = Number(q.cid ?? 0)
  const sid = Number(q.sid ?? 0)
  const gid = Number(q.gid ?? 0)
  const type = String(q.type ?? "")

  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid
  if (gid > 0) params.gid = gid
  if (type === "global") params.type = "global"
  if (q.recipient) params.recipient = String(q.recipient)

  return params
}

async function loadEvents() {
  try {
    isLoading.value = true
    const params = computeCommonParams()
    const events = await getCalendarEvents(apiRangeStart.value.toJSDate(), apiRangeEnd.value.toJSDate(), params)
    rawEvents.value = Array.isArray(events) ? events : []
  } catch (e) {
    console.error("[CalendarList] Failed to load events", e)
    rawEvents.value = []
  } finally {
    isLoading.value = false
  }
}

const HEX6 = /^#([0-9a-f]{6})$/i
const HEX3 = /^#([0-9a-f]{3})$/i

function normalizeHex(c) {
  if (!c) return null
  const s = String(c).trim()
  if (HEX6.test(s)) return s.toUpperCase()
  const m3 = s.match(HEX3)
  if (m3) {
    const [r, g, b] = m3[1].toUpperCase().split("")
    return `#${r}${r}${g}${g}${b}${b}`
  }
  const mRgb = s.match(/rgba?\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i)
  if (mRgb) {
    const r = Math.min(255, +mRgb[1])
    const g = Math.min(255, +mRgb[2])
    const b = Math.min(255, +mRgb[3])
    return `#${r.toString(16).padStart(2, "0")}${g.toString(16).padStart(2, "0")}${b.toString(16).padStart(2, "0")}`.toUpperCase()
  }
  return null
}

function defaultColorByContext(ctx) {
  return ctx === "global" ? "#FF0000" : ctx === "course" ? "#458B00" : ctx === "session" ? "#00496D" : "#4682B4"
}

function toLuxon(value) {
  if (!value) return null

  if (value instanceof Date) return DateTime.fromJSDate(value, { zone: timezone })

  if (typeof value === "number" && Number.isFinite(value)) {
    const isMillis = value > 1e12
    return isMillis ? DateTime.fromMillis(value, { zone: timezone }) : DateTime.fromSeconds(value, { zone: timezone })
  }

  if (typeof value === "string") {
    const s = value.trim()
    if (/^\d{10,13}$/.test(s)) {
      const n = Number(s)
      const isMillis = n > 1e12
      return isMillis ? DateTime.fromMillis(n, { zone: timezone }) : DateTime.fromSeconds(n, { zone: timezone })
    }
    const iso = s.length === 10 ? `${s}T00:00:00` : s
    const dt = DateTime.fromISO(iso, { zone: timezone })
    return dt.isValid ? dt : null
  }

  if (typeof value === "object" && typeof value.toISOString === "function") {
    const dt = DateTime.fromISO(value.toISOString(), { zone: timezone })
    return dt.isValid ? dt : null
  }

  return null
}

function overlapsRange(start, end) {
  if (!start) return false
  const e = end || start
  return start < apiRangeEnd.value && e > apiRangeStart.value
}

function scopeLabel(scope) {
  if (!scope) return null
  const s = String(scope).toLowerCase()
  if (s === "personal") return t("Personal")
  if (s === "course") return t("Course")
  if (s === "session") return t("Session")
  if (s === "global") return t("Global")
  return scope
}

function plainTextFromHtml(input) {
  if (!input) return ""
  const s = String(input)

  if (!/[<>]/.test(s)) {
    return s.replace(/\s+/g, " ").trim()
  }

  const text = s
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<!doctype[^>]*>/gi, " ")
    .replace(/<!--[\s\S]*?-->/g, " ")
    .replace(/<\/?[^>]+>/g, " ")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/g, "'")
    .replace(/\s+/g, " ")
    .trim()

  return text.length > 220 ? `${text.slice(0, 220)}…` : text
}

function getPayload(e) {
  // Support both shapes:
  // - FullCalendar event objects (scope in extendedProps)
  // - API objects (scope in root fields)
  const ep = e?.extendedProps
  if (ep && typeof ep === "object" && Object.keys(ep).length > 0) return ep
  return e ?? {}
}

function getLinks(payload) {
  return Array.isArray(payload?.resourceLinkListFromEntity) ? payload.resourceLinkListFromEntity : []
}

function resolveScopeRaw(payload) {
  // 1) Best: explicit "type" from API ("personal"|"course"|"session"|"global")
  const explicit = payload?.type ?? payload?.scope ?? payload?.context ?? null
  const explicitLower = String(explicit || "")
    .toLowerCase()
    .trim()
  if (["personal", "course", "session", "global"].includes(explicitLower)) return explicitLower

  // 2) Otherwise infer from resource links
  const links = getLinks(payload)
  if (links.length) {
    // Prefer session over course when both exist
    if (links.some((l) => l?.session)) return "session"
    if (links.some((l) => l?.course)) return "course"
    if (links.some((l) => l?.user)) return "personal"
  }

  return "personal"
}

function extractCourse(payload) {
  if (payload?.course?.code) return payload.course.code
  if (payload?.course?.title) return payload.course.title
  const link = getLinks(payload).find((l) => l?.course)
  return link?.course?.resourceNode?.title || link?.course?.title || link?.course?.code || null
}

function extractSession(payload) {
  if (payload?.session?.title) return payload.session.title
  if (payload?.session?.name) return payload.session.name
  const link = getLinks(payload).find((l) => l?.session)
  return link?.session?.title || link?.session?.name || null
}

function extractType(payload) {
  return (
    payload?.resourceNode?.resourceType?.name ||
    payload?.resourceNode?.resourceType?.title ||
    payload?.resourceNode?.resourceType ||
    payload?.objectType ||
    payload?.eventType ||
    null
  )
}

function mapToBaseItem(e) {
  const payload = getPayload(e)

  const title = e?.title || payload?.title || ""
  const scopeRawLower = resolveScopeRaw(payload)
  const typeValue = extractType(payload)

  let start =
    toLuxon(e?.start) ||
    toLuxon(e?.startStr) ||
    toLuxon(e?.startDate) ||
    toLuxon(payload?.startDate) ||
    toLuxon(payload?.start) ||
    null

  let end =
    toLuxon(e?.end) ||
    toLuxon(e?.endStr) ||
    toLuxon(e?.endDate) ||
    toLuxon(payload?.endDate) ||
    toLuxon(payload?.end) ||
    null

  if (start && end && end < start) end = start.plus({ days: 1 })

  const durationDays = start && end ? end.diff(start, "days").days : 0
  const looksMultiDay = Number.isFinite(durationDays) && durationDays >= 1

  const allDay =
    Boolean(e?.allDay === true) ||
    Boolean(payload?.allDay === true) ||
    looksMultiDay ||
    (typeof e?.start === "string" && String(e.start).length === 10)

  if (allDay && start) {
    start = start.startOf("day")
    if (end) end = end.startOf("day")
    if (!end || end <= start) end = start.plus({ days: 1 })
  }

  const rawColor = payload?.color ?? e?.backgroundColor ?? e?.borderColor ?? e?.color ?? null
  const color = normalizeHex(rawColor) || defaultColorByContext(scopeRawLower)

  const content = plainTextFromHtml(payload?.content || payload?.description || "")

  return {
    keyBase: e?.id || payload?.["@id"] || payload?.id || `${title}-${start?.toISO() || ""}`,
    title,
    content,
    comment: payload?.comment || "",
    color,
    url: e?.url || payload?.url || null,
    scope: scopeLabel(scopeRawLower),
    course: extractCourse(payload),
    session: extractSession(payload),
    type: typeValue,

    _start: start,
    _end: end,
    _allDay: allDay,
    _scopeRaw: scopeRawLower,
    _isAssignment: String(typeValue || "")
      .toLowerCase()
      .includes("assign"),
  }
}

/**
 * Expand multi-day all-day events into one row per day within the current range.
 */
function expandToOccurrences(base) {
  if (!base._start) return []
  const end = base._end || base._start
  if (!overlapsRange(base._start, end)) return []

  if (!base._allDay) {
    const range =
      base._start && end
        ? `${base._start.toFormat("HH:mm")} - ${end.toFormat("HH:mm")}`
        : base._start
          ? base._start.toFormat("HH:mm")
          : ""

    return [
      {
        ...base,
        key: base.keyBase,
        _groupDay: base._start.startOf("day"),
        range,
      },
    ]
  }

  const startDay = base._start.startOf("day")
  let endDay = (end || base._start).startOf("day")

  if (end && end > base._start) {
    endDay = end.minus({ days: 1 }).startOf("day")
  } else {
    endDay = startDay
  }

  const rangeFrom = apiRangeStart.value.startOf("day")
  const rangeTo = apiRangeEnd.value.minus({ days: 1 }).startOf("day")

  const from = startDay < rangeFrom ? rangeFrom : startDay
  const to = endDay > rangeTo ? rangeTo : endDay
  if (from > to) return []

  const out = []
  let cursor = from

  while (cursor <= to) {
    out.push({
      ...base,
      key: `${base.keyBase}-${cursor.toISODate()}`,
      _groupDay: cursor,
      range: t("All day"),
    })
    cursor = cursor.plus({ days: 1 })
  }

  return out
}

const listItems = computed(() => {
  const base = (rawEvents.value || []).map(mapToBaseItem)
  const out = []
  for (const b of base) out.push(...expandToOccurrences(b))
  return out
})

const filteredEvents = computed(() => {
  const items = listItems.value
  if (activeFilter.value === "all") return items
  if (activeFilter.value === "assignment") return items.filter((e) => e._isAssignment)
  return items.filter((e) => e._scopeRaw === activeFilter.value)
})

const groupedDays = computed(() => {
  const groups = new Map()
  const sorted = [...filteredEvents.value].sort((a, b) => a._groupDay.toMillis() - b._groupDay.toMillis())

  for (const ev of sorted) {
    const key = ev._groupDay.toISODate()
    const label = safeToLocaleString(ev._groupDay, DateTime.DATE_FULL)
    if (!groups.has(key)) groups.set(key, { key, label, items: [] })
    groups.get(key).items.push(ev)
  }

  return Array.from(groups.values())
})

const fetchKey = computed(() => {
  const params = computeCommonParams()
  return [
    apiRangeStart.value.toISO(),
    apiRangeEnd.value.toISO(),
    viewType.value,
    params.cid ?? "",
    params.sid ?? "",
    params.gid ?? "",
    params.type ?? "",
    params.recipient ?? "",
  ].join("|")
})

watch(
  fetchKey,
  () => {
    loadEvents()
  },
  { immediate: true },
)
</script>
