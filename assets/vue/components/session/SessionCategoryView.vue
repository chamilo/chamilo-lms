<template>
  <SessionListCategoryWrapper :sessions="sessionList" />
  <SessionCategoryListWrapper
    :categories="categories"
    :category-with-sessions="categoryWithSessions"
  />
</template>

<script setup>
import SessionCategoryListWrapper from "../../components/session/SessionCategoryListWrapper"
import isEmpty from "lodash/isEmpty"
import { computed } from "vue"
import SessionListCategoryWrapper from "./SessionListCategoryWrapper.vue"

const props = defineProps({
  resultSessions: {
    type: Array,
    required: true,
  },
})

let categories = computed(() => {
  let categoryList = []
  props.resultSessions.forEach((session) => {
    if (session.category) {
      const alreadyAdded = categoryList.findIndex((cat) => cat["@id"] === session.category["@id"]) >= 0

      if (!alreadyAdded) {
        categoryList.push(session.category)
      }
    }
  })

  return categoryList
})

const sessionList = computed(() => props.resultSessions.filter((session) => isEmpty(session.category)))

let categoryWithSessions = computed(() => {
  let categoriesIn = []
  props.resultSessions.forEach(function (session) {
    if (!isEmpty(session.category)) {
      if (categoriesIn[session.category["@id"]] === undefined) {
        categoriesIn[session.category["@id"]] = []
        categoriesIn[session.category["@id"]]["sessions"] = []
      }
      categoriesIn[session.category["@id"]]["sessions"].push(session)
    }
  })

  return categoriesIn
})
</script>
