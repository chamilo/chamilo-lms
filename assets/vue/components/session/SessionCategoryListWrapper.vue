<script setup>
import SessionListCategoryWrapper from "../../components/session/SessionListCategoryWrapper"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
  categoryWithSessions: {
    type: Map,
    required: true,
  },
})

const getSessionsFromCategory = (category) => props.categoryWithSessions.get(category["@id"]).sessions
</script>

<template>
  <div
    v-if="categories.length"
    class="grid gap-4"
  >
    <div
      v-for="category in categories"
      :key="category.id"
    >
      <h5 class="mb-2">
        <BaseIcon icon="folder-generic" />
        {{ category.title }}
      </h5>
      <SessionListCategoryWrapper :sessions="getSessionsFromCategory(category)" />
    </div>
  </div>
</template>
