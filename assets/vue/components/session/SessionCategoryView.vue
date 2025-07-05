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
const displayMode = computed(() => platformConfigStore.getSetting("session.user_session_display_mode") ?? "card")
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
      :uncategorized-sessions="uncategorizedSessions"
      :categories="categories"
      :categories-with-sessions="categoriesWithSessions"
    />
  </template>
</template>
