<template>
  <div class="overflow-x-auto rounded-xl border border-gray-25 bg-white shadow-sm">
    <table class="border-collapse text-sm">
      <thead>
        <tr class="bg-gray-10 text-xs font-semibold text-gray-70">
          <th class="whitespace-nowrap px-2 py-1 text-start">{{ t("Tutor") }}</th>
          <th class="whitespace-nowrap px-2 py-1 text-center">{{ t("Sessions") }}</th>
          <th
            v-for="week in weeks"
            :key="week.key"
            class="whitespace-nowrap px-2 py-1 text-center"
          >
            {{ week.key }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="tutor in rows"
          :key="tutor.id"
          class="border-t border-gray-15"
        >
          <td class="whitespace-nowrap px-2 py-1 align-top">{{ tutor.tutor }}</td>
          <td class="whitespace-nowrap px-2 py-1 text-center align-top">{{ tutor.sessions.length }}</td>
          <td
            v-for="week in weeks"
            :key="week.key"
            class="px-2 py-1 text-center align-top"
            :class="{ 'bg-success': tutor.weeks[week.key] }"
          >
            <div class="w-28 space-y-2 overflow-hidden">
              <a
                v-for="session in tutor.weeks[week.key]?.firstWeekSessions"
                :key="session.id"
                :href="session.url"
                :title="session.title"
                class="block break-words text-xs font-semibold text-success-button-text hover:underline"
                target="_blank"
              >
                {{ session.title }}
              </a>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  tutors: {
    type: Array,
    required: true,
  },
  // Applied date filter (YYYY-MM-DD). When empty, the range spans the listed sessions.
  startDate: {
    type: String,
    default: "",
  },
  endDate: {
    type: String,
    default: "",
  },
})

const { t } = useI18n()

const DAY = 24 * 60 * 60 * 1000

function toUtcDay(value) {
  const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(value || ""))

  return match ? Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])) : null
}

// Monday of the ISO week containing the day
function weekMonday(day) {
  return day - ((new Date(day).getUTCDay() + 6) % 7) * DAY
}

// ISO 8601 "year-week" label, as the legacy report's PHP format "o-W"
function isoWeekKey(day) {
  const thursday = new Date(weekMonday(day) + 3 * DAY)
  const yearStart = Date.UTC(thursday.getUTCFullYear(), 0, 1)
  const week = Math.floor((thursday.getTime() - yearStart) / DAY / 7) + 1

  return `${thursday.getUTCFullYear()}-${String(week).padStart(2, "0")}`
}

const range = computed(() => {
  const sessionStarts = props.tutors.flatMap((tutor) => tutor.sessions.map((s) => toUtcDay(s.startDate)))
  const sessionEnds = props.tutors.flatMap((tutor) =>
    tutor.sessions.map((s) => toUtcDay(s.endDate) ?? toUtcDay(s.startDate)),
  )
  const start = toUtcDay(props.startDate) ?? Math.min(...sessionStarts.filter((d) => null !== d))
  const end = toUtcDay(props.endDate) ?? Math.max(...sessionEnds.filter((d) => null !== d))

  return Number.isFinite(start) && Number.isFinite(end) && start <= end ? { start, end } : null
})

const weeks = computed(() => {
  if (!range.value) {
    return []
  }

  const list = []
  for (let monday = weekMonday(range.value.start); monday <= range.value.end; monday += 7 * DAY) {
    list.push({ key: isoWeekKey(monday), monday })
  }

  return list
})

// Per tutor, the weeks each session covers (from its start week to its end week, or only its
// start week when it has no end date). Its title shows in its first week only, as in the legacy report.
const rows = computed(() =>
  props.tutors.map((tutor) => {
    const tutorWeeks = {}

    for (const session of tutor.sessions) {
      const start = toUtcDay(session.startDate)

      if (null === start) {
        continue
      }

      const end = toUtcDay(session.endDate) ?? start
      let first = true

      for (let monday = weekMonday(start); monday <= end; monday += 7 * DAY) {
        const key = isoWeekKey(monday)
        tutorWeeks[key] ??= { firstWeekSessions: [] }

        if (first) {
          tutorWeeks[key].firstWeekSessions.push(session)
          first = false
        }
      }
    }

    return { ...tutor, weeks: tutorWeeks }
  }),
)
</script>
