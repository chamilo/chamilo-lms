<template>
  <BaseCard plain class="my-groups-card bg-white mb-3">
    <template #header>
      <div class="px-2 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t('My communities') }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4">
    <div class="px-2">
      <ul class="mb-3">
        <li
          class="list-group-item"
          v-for="group in groups"
          :key="group.id"
        >
          <a :href="group.url || '#'" v-if="group.url">{{ group.name }}</a>
          <span v-else>{{ group.name }}</span>
        </li>
      </ul>
      <div v-if="isValidGlobalForumsCourse" class="text-center mb-3">
        <a :href="goToUrl" class="btn btn-primary">{{ t('See all communities') }}</a>
      </div>
      <div v-else >
        <div v-if="isCurrentUser" class="input-group mb-3">
          <input
            type="search"
            class="form-control"
            placeholder="Search"
            v-model="searchQuery"
          >
          <button
            class="btn btn-outline-secondary"
            type="button"
            @click="search"
          >
            <i class="mdi mdi-magnify"></i>
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
