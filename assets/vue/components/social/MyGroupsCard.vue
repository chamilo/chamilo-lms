<template>
  <BaseCard plain class="my-groups-card bg-white mt-3 mb-3">
    <template #header>
      <div class="px-4 py-3 bg-gray-200">
        <h2 class="text-xl font-semibold">{{ t('My communities') }}</h2>
      </div>
    </template>
    <hr class="my-2">
    <div class="px-4">
      <ul class="mb-4">
        <li
          class="mb-2"
          v-for="group in groups"
          :key="group.id"
        >
          <a :href="group.url || '#'" v-if="group.url" class="text-blue-600 hover:underline">{{ group.name }}</a>
          <span v-else>{{ group.name }}</span>
        </li>
      </ul>
      <div v-if="isValidGlobalForumsCourse" class="text-center mb-4">
        <a :href="goToUrl" class="btn btn-primary">{{ t('See all communities') }}</a>
      </div>
      <div v-else >
        <div v-if="isCurrentUser" class="flex items-center mb-4">
          <input
            type="search"
            class="flex-grow p-2 h-[44px] border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('Search')"
            v-model="searchQuery"
          >
          <button
            class="p-2 h-[44px] bg-gray-200 border border-gray-300 rounded-r-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
            type="button"
            @click="search"
          >
            <i class="mdi mdi-magnify text-gray-700"></i>
          </button>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { ref, inject, watchEffect, computed } from "vue"
import axios from 'axios'
import { usePlatformConfig } from "../../store/platformConfig"
import { useRouter } from "vue-router"

const { t } = useI18n()
const searchQuery = ref('')
const groups = ref([])
const goToUrl = ref('')
const user = inject('social-user')
const isCurrentUser = inject('is-current-user')
const platformConfigStore = usePlatformConfig()
const globalForumsCourse = computed(() => platformConfigStore.getSetting("forum.global_forums_course_id"))
const isValidGlobalForumsCourse = computed(() => {
  const courseId = globalForumsCourse.value
  return courseId !== null && courseId !== undefined && courseId > 0
})

const router = useRouter()
function search() {
  router.push({ name: 'UserGroupSearch', query: { q: searchQuery.value } })
}

async function fetchGroups(userId) {
  try {
    const response = await axios.get(`/social-network/groups/${userId}`)
    if (response.data) {
      groups.value = response.data.items
      goToUrl.value = response.data.go_to
    }
  } catch (error) {
    groups.value = []
    goToUrl.value = ''
  }
}


watchEffect(() => {
  if (user.value && user.value.id) {
    fetchGroups(user.value.id)
  }
})
</script>
