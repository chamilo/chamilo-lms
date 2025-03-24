<script setup>
import { ref } from "vue"

import Skeleton from "primevue/skeleton"

import { useNotification } from "../../composables/notification"

import * as skillProfileService from "../../services/skillProfileService"

const { showErrorNotification } = useNotification()

const containerEl = ref()
const isLoading = ref(false)

/**
 * @param {Array<Object>} skills
 * @returns {Promise<void>}
 */
async function searchProfileMatches(skills) {
  isLoading.value = true

  const skillIdList = skills.map((skill) => skill.id)

  try {
    containerEl.value.innerHTML = await skillProfileService.matchProfiles(skillIdList)
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

defineExpose({
  searchProfileMatches,
})
</script>

<template>
  <Skeleton
    v-if="isLoading"
    height="10rem"
  />
  <div
    v-show="!isLoading"
    ref="containerEl"
  />
</template>
