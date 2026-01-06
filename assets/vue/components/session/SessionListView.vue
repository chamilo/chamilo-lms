<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-90">{{ t("My sessions") }}</h2>
    <div
      v-for="session in props.uncategorizedSessions.filter((s) => s.courses?.length > 0)"
      :key="session.id"
      class="rounded-xl border border-gray-25 bg-gray-10 shadow-sm transition-all duration-200 hover:shadow-md overflow-hidden"
    >
      <div
        class="flex cursor-pointer"
        @click="toggleExpand(session.id)"
      >
        <div class="w-1.5 bg-primary rounded-l-xl" />

        <div class="flex-1 px-6 py-4 flex items-center justify-between">
          <div>
            <div class="text-sm font-bold text-gray-90">
              {{ session.name || session.title || "Untitled Session" }}
            </div>
            <div class="text-sm text-gray-50 mt-1">
              {{ getSessionDisplayLabel(session) }}
            </div>
          </div>
          <div class="flex items-center gap-4">
            <div
              v-if="securityStore.isAdmin"
              class="text-sm font-medium text-primary cursor-pointer"
              @click.stop="goToEdit(session.id)"
            >
              {{ t("Edit") }}
            </div>
            <svg
              :class="{ 'rotate-180': expandedSessions.has(session.id) }"
              class="h-5 w-5 text-gray-400 transform transition-transform"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M19 9l-7 7-7-7"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
              />
            </svg>
          </div>
        </div>
      </div>
      <div
        v-if="expandedSessions.has(session.id)"
        class="px-6 pb-6 overflow-hidden"
      >
        <SessionCardSimple :session="session" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import SessionCardSimple from "./SessionCardSimple.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

const props = defineProps({
  uncategorizedSessions: Array,
  categories: Array,
  categoriesWithSessions: Map,
})

const showRemainingDays = computed(
  () => platformConfigStore.getSetting("session.session_list_view_remaining_days") === "true",
)

function formatDate(iso) {
  const date = new Date(iso)
  return date.toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })
}

function extractIdFromIri(iri) {
  if (!iri) return null
  const match = iri.match(/\/(\d+)$/)
  return match ? parseInt(match[1], 10) : null
}

function getCurrentUserId() {
  return (
    securityStore.user?.id ||
    securityStore.user?._id ||
    extractIdFromIri(securityStore.user?.["@id"]) ||
    securityStore.userId ||
    securityStore.currentUser?.id ||
    null
  )
}

function isDurationSession(session) {
  return Number(session?.duration ?? 0) > 0
}

function isCoachForSession(session) {
  if (securityStore.isAdmin) return true

  const uid = getCurrentUserId()
  if (!uid) return false

  const lists = [
    session.generalCoachesSubscriptions,
    session.sessionCoaches,
    session.coaches,
    session.generalCoaches,
    session.coachSubscriptions,
    session.sessionCoachSubscriptions,
  ].filter(Boolean)

  for (const list of lists) {
    if (!Array.isArray(list)) continue

    const found = list.some((item) => {
      // item could be a SessionRelUser or a User-like object
      const user = item?.user ?? item
      const id = user?.id || user?._id || extractIdFromIri(user?.["@id"] || user) || extractIdFromIri(item?.["@id"])
      return id && Number(id) === Number(uid)
    })

    if (found) return true
  }

  if (Array.isArray(session.courseCoachesSubscriptions)) {
    const found = session.courseCoachesSubscriptions.some((sub) => {
      const user = sub?.user
      const id = user?.id || user?._id || extractIdFromIri(user?.["@id"] || user)
      return id && Number(id) === Number(uid)
    })
    if (found) return true
  }

  return false
}

function getDateRangeLabel(session) {
  const left = session.displayStartDate ? formatDate(session.displayStartDate) : ""
  const right = session.displayEndDate ? formatDate(session.displayEndDate) : ""
  if (left && right) return `${left} - ${right}`
  return left || right || ""
}

function getDurationLabel(session) {
  const d = Number(session?.duration ?? 0)
  if (!d) return ""
  return d === 1 ? "1 day duration" : `${d} days duration`
}

function getRemainingLabelFromDaysLeft(session) {
  const daysLeft = Number(session?.daysLeft)
  if (!Number.isFinite(daysLeft)) return ""

  if (daysLeft > 1) return `${daysLeft} days remaining`
  if (daysLeft === 1) return t("Ends tomorrow")
  if (daysLeft === 0) return t("Ends today")
  return t("Expired")
}

function getSessionDisplayLabel(session) {
  // Default: always dates.
  if (!showRemainingDays.value) {
    return getDateRangeLabel(session)
  }

  // Setting ON but NOT a duration session: still show dates.
  if (!isDurationSession(session)) {
    return getDateRangeLabel(session)
  }

  // Duration session:
  // - Coaches/admins: show duration
  // - Students: show remaining using daysLeft (from API)
  if (isCoachForSession(session)) {
    return getDurationLabel(session)
  }

  return getRemainingLabelFromDaysLeft(session) || getDateRangeLabel(session)
}

function goToEdit(sessionId) {
  window.location.href = `/main/session/resume_session.php?id_session=${sessionId}`
}

const expandedSessions = ref(new Set())

function toggleExpand(id) {
  if (expandedSessions.value.has(id)) {
    expandedSessions.value.delete(id)
  } else {
    expandedSessions.value.add(id)
  }
}
</script>
