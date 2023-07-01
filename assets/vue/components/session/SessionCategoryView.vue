<template>
  <SessionListNoCategoryWrapper :sessions="sessionList" class="mb-4"/>
  <SessionCategoryListWrapper :categories="categories" :category-with-sessions="categoryWithSessions"/>
</template>

<script setup>
import SessionCategoryListWrapper from '../../components/session/SessionCategoryListWrapper'
import SessionListNoCategoryWrapper from "../../components/session/SessionListNoCategoryWrapper"
import isEmpty from "lodash/isEmpty"
import {computed, toRefs} from "vue"

const props = defineProps({
  resultSessions: {
    type: Object,
    required: true,
  },
})

const {resultSessions} = toRefs(props)

let sessions = computed(() => {
  if (
    resultSessions.value === null
    || resultSessions.value.sessionRelUsers === null
    || resultSessions.value.sessionRelUsers.edges === null
  ) {
    return []
  }

  let sessionList = [];
  resultSessions.value.sessionRelUsers.edges.map(({node}) => {
    const sessionExists = sessionList.findIndex(suSession => suSession._id === node.session._id) >= 0

    if (!sessionExists) {
      sessionList.push(node.session)
    }

    return sessionExists ? null : node.session
  })
  return sessionList
})

let categories = computed(() => {
  if (
    resultSessions.value === null
    || resultSessions.value.sessionRelUsers === null
    || resultSessions.value.sessionRelUsers.edges === null
  ) {
    return []
  }

  let categoryList = [];
  resultSessions.value.sessionRelUsers.edges.map(({node}) => {
    if (isEmpty(node.session.category)) {
      return
    }
    const categoryExists = categoryList.findIndex(cat => cat._id === node.session.category._id) >= 0
    if (!categoryExists) {
      if (!isEmpty(node.session.category)) {
        categoryList.push(node.session.category)
      }
    }
  })

  return categoryList
})

let sessionList = computed(() => sessions.value.filter(function(session) {
  if (isEmpty(session.category)) {
    return session
  }
}));

let categoryWithSessions = computed(() => {
  let categoriesIn = [];
  sessions.value.forEach(function(session) {
    if (!isEmpty(session.category)) {
      if (categoriesIn[session.category._id] === undefined) {
        categoriesIn[session.category._id] = []
        categoriesIn[session.category._id]['sessions'] = []
      }
      categoriesIn[session.category._id]['sessions'].push(session)
    }
  })

  return categoriesIn;
})
</script>
