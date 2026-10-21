<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"

import Skeleton from "primevue/skeleton"

import { useNotification } from "../../composables/notification"

import * as skillProfileService from "../../services/skillProfileService"

const { t } = useI18n()
const { showErrorNotification } = useNotification()

const containerEl = ref()
const isLoading = ref(false)
const hasMatches = ref(false)

/**
 * @param {Array<Object>} skills
 * @returns {Promise<void>}
 */
async function searchProfileMatches(skills) {
  isLoading.value = true
  hasMatches.value = false

  const skillIdList = skills.map((skill) => skill.id)

  try {
    containerEl.value.innerHTML = await skillProfileService.matchProfiles(skillIdList)
    hasMatches.value = Boolean(containerEl.value.querySelector(".items-user"))
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
    v-show="!isLoading && hasMatches"
    ref="containerEl"
  />
  <div
    v-if="!isLoading && !hasMatches"
    class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-60"
  >
    {{ t("No results found") }}
  </div>
</template>
