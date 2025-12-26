<script setup>
import { computed } from "vue"
import SessionCategoryListWrapper from "./SessionCategoryListWrapper.vue"
import SessionListCategoryWrapper from "./SessionListCategoryWrapper.vue"
import SessionListView from "./SessionListView.vue"
import { usePlatformConfig } from "../../store/platformConfig"

const props = defineProps({
  uncategorizedSessions: Array,
  categories: Array,
  categoriesWithSessions: Map,
})

const platformConfigStore = usePlatformConfig()
const displayMode = computed(() => {
  const raw = platformConfigStore.getSetting("session.user_session_display_mode")
  const mode = String(raw ?? "card").toLowerCase()

  return mode === "list" || mode === "card" ? mode : "card"
})
</script>

<template>
  <template v-if="displayMode === 'card'">
    <SessionListCategoryWrapper :sessions="uncategorizedSessions" />
    <SessionCategoryListWrapper
      :categories="categories"
      :category-with-sessions="categoriesWithSessions"
    />
  </template>

  <template v-else>
    <SessionListView
      :categories="categories"
      :categories-with-sessions="categoriesWithSessions"
      :uncategorized-sessions="uncategorizedSessions"
    />
  </template>
</template>
