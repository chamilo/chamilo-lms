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
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
}

function getCurrentUserId() {
  // Try multiple shapes to be resilient across environments.
  return (
    securityStore.user?.id ||
    securityStore.user?._id ||
    securityStore.userId ||
    securityStore.currentUser?.id ||
    null
  )
}

function extractIdFromIri(iri) {
  if (!iri) return null
  const match = iri.match(/\/(\d+)$/)
  return match ? parseInt(match[1], 10) : null
}

function isCoachForSession(session) {
  // Admins generally have permanent access too.
  if (securityStore.isAdmin) return true

  const uid = getCurrentUserId()
  if (!uid) return false

  // Session creator/owner often has permanent access in practice.
  const creatorId =
    session.creator?.id ||
    session.creatorId ||
    session.createdBy?.id ||
    session.owner?.id ||
    session.user?.id ||
    null

  if (creatorId && Number(creatorId) === Number(uid)) return true

  // General coach lists (try common fields)
  const sessionCoachLists = [
    session.sessionCoaches,
    session.coaches,
    session.generalCoaches,
    session.coachSubscriptions,
    session.sessionCoachSubscriptions,
  ].filter(Boolean)

  for (const list of sessionCoachLists) {
    if (Array.isArray(list)) {
      const found = list.some((item) => {
        const id = item?.id || item?._id || item?.user?.id || item?.user?._id || extractIdFromIri(item?.["@id"])
        return id && Number(id) === Number(uid)
      })
      if (found) return true
    }
  }

  // Course coach subscriptions exist in your DTO (you already use it in CourseCard).
  if (Array.isArray(session.courseCoachesSubscriptions)) {
    const found = session.courseCoachesSubscriptions.some((sub) => {
      const user = sub?.user
      const id = user?.id || user?._id || extractIdFromIri(user?.["@id"])
      return id && Number(id) === Number(uid)
    })
    if (found) return true
  }

  return false
}

function getRemainingText(session) {
  if (!session?.displayEndDate) return null

  const endDate = new Date(session.displayEndDate)
  if (isNaN(endDate.getTime())) return null

  const today = new Date()
  const diff = Math.floor((endDate - today) / (1000 * 60 * 60 * 24))

  if (diff > 1) return `${diff} days remaining`
  if (diff === 1) return t("Ends tomorrow")
  if (diff === 0) return t("Ends today")
  return t("Expired")
}

function getDurationText(session) {
  if (!session?.displayStartDate || !session?.displayEndDate) return null

  const start = new Date(session.displayStartDate)
  const end = new Date(session.displayEndDate)

  if (isNaN(start.getTime()) || isNaN(end.getTime())) return null

  const msPerDay = 1000 * 60 * 60 * 24
  const rawDiff = Math.floor((end - start) / msPerDay) + 1
  const days = rawDiff > 0 ? rawDiff : 1

  return days === 1 ? "1 day duration" : `${days} days duration`
}

function getSessionDisplayLabel(session) {
  // Setting OFF: keep old behavior.
  if (!showRemainingDays.value) {
    const left = session.displayStartDate ? formatDate(session.displayStartDate) : ""
    const right = session.displayEndDate ? formatDate(session.displayEndDate) : ""
    if (left && right) return `${left} - ${right}`
    return left || right || ""
  }

  // Setting ON:
  // - Coaches/creator/admin: show duration
  // - Students: show remaining/expired
  if (isCoachForSession(session)) {
    return getDurationText(session) || ""
  }

  return getRemainingText(session) || ""
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
