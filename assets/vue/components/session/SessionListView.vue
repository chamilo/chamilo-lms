<template>
  <div class="space-y-6">
    <h2 class="text-xl font-bold text-gray-90">{{ t("My Sessions") }}</h2>
    <div
      v-for="session in props.uncategorizedSessions.filter((s) => s.courses?.length > 0)"
      :key="session.id"
      class="rounded-xl border border-gray-25 bg-gray-10 shadow-sm transition-all duration-200 hover:shadow-md overflow-hidden"
    >
      <div
        @click="toggleExpand(session.id)"
        class="flex cursor-pointer"
      >
        <div class="w-1.5 bg-primary rounded-l-xl" />

        <div class="flex-1 px-6 py-4 flex items-center justify-between">
          <div>
            <div class="text-sm font-bold text-gray-90">
              {{ session.name || session.title || "Untitled Session" }}
            </div>
            <div class="text-sm text-gray-50 mt-1">
              {{ session.displayStartDate ? formatDate(session.displayStartDate) : "" }}
              <span v-if="session.displayStartDate && session.displayEndDate"> - </span>
              {{ session.displayEndDate ? formatDate(session.displayEndDate) : "" }}
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
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 text-gray-400 transform transition-transform"
              :class="{ 'rotate-180': expandedSessions.has(session.id) }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>
        </div>
      </div>
      <div
        v-if="expandedSessions.has(session.id)"
        class="px-6 pb-6"
      >
        <SessionCardSimple :session="session" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { ref } from "vue"
import SessionCardSimple from "./SessionCardSimple.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()

const props = defineProps({
  uncategorizedSessions: Array,
  categories: Array,
  categoriesWithSessions: Map,
})

function formatDate(iso) {
  const date = new Date(iso)
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
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
