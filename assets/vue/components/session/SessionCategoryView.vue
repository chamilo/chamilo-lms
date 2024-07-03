<template>
  <SessionListCategoryWrapper :sessions="uncategorizedSessions" />
  <SessionCategoryListWrapper
    :categories="categories"
    :category-with-sessions="categoriesWithSessions"
  />
</template>

<script setup>
import SessionCategoryListWrapper from "../../components/session/SessionCategoryListWrapper"
import SessionListCategoryWrapper from "./SessionListCategoryWrapper.vue"

defineProps({
  uncategorizedSessions: {
    type: Array,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
  categoriesWithSessions: {
    type: Map,
    required: true,
  },
})
</script>
