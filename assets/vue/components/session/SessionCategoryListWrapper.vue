<template>
  <div v-if="categories.length" class="grid gap-4">
    <div v-for="category in categories" :key="category.id">
      <h5 class="mb-2">
        <BaseIcon icon="folder-generic"/>
        {{ category.name }}
      </h5>
      <SessionListCategoryWrapper :sessions="getSessionsFromCategory(category)"/>
    </div>
  </div>
</template>

<script setup>
import SessionListCategoryWrapper from '../../components/session/SessionListCategoryWrapper'
import {toRefs} from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const props = defineProps({
  categories: {
    type: Array,
    required: true,
  },
  categoryWithSessions: {
    type: Array,
    required: true,
  },
})

const {categoryWithSessions} = toRefs(props)

function getSessionsFromCategory(category) {
  return categoryWithSessions.value[category._id]['sessions']
}
</script>
