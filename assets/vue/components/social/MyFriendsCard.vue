<template>
  <BaseCard plain class="my-groups-card bg-white mb-3">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t('My Friends') }}</h2>
      </div>
    </template>
    <hr class="-mt-2 mb-4 -mx-4">
    <div>
      <div v-if="isCurrentUser" class="input-group mb-3">
        <input
          type="search"
          class="form-control"
          placeholder="Search"
          v-model="searchQuery"
          @input="fetchFriends"
        >
        <button
          class="btn btn-outline-secondary"
          type="button"
          @click="search"
        >
          <i class="mdi mdi-magnify"></i>
        </button>
      </div>
      <ul class="list-group">
        <li v-for="friend in limitedFriends" :key="friend.id" class="list-group-item friend-item d-flex align-items-center">
          <BaseUserAvatar :image-url="friend.friend.illustrationUrl" />
          <span>{{ friend.friend.username }}</span>
        </li>
      </ul>
      <div v-if="friends.length > 10" class="mt-2 text-center">
        <a href="#" @click="viewAll">{{ t('View All Friends') }}</a>
      </div>
    </div>
    <div v-if="allowSocialMap && isCurrentUser" class="text-center mt-3">
      <BaseButton
        :label="t('Search user by geolocalization')"
        type="primary"
        @click="redirectToGeolocalization"
        icon="map-search"
      />
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import { useI18n } from "vue-i18n"
import { ref, computed, inject, watchEffect } from "vue"
import axios from 'axios'
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useRouter } from 'vue-router'
import { usePlatformConfig } from "../../store/platformConfig"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const friends = ref([])
const searchQuery = ref('')
const user = inject('social-user')
const isCurrentUser = inject('is-current-user')
const router = useRouter()
const platformConfigStore = usePlatformConfig()

const allowSocialMap = computed(() => platformConfigStore.getSetting("profile.allow_social_map_fields"))
const search = () => {
  router.push({ name: 'UserRelUserSearch', query: { search: searchQuery.value } })
}
const limitedFriends = computed(() => {
  return friends.value.slice(0, 10)
})

const redirectToGeolocalization = () => {
  window.location.href = '/main/social/map.php'
}
async function fetchFriends(userId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}user_rel_users?user=/api/users/${userId}&relationType=3`, {
      params: {
        'friend.username': searchQuery.value ? searchQuery.value : undefined,
      },
    })
    friends.value = response.data['hydra:member']
  } catch (error) {
    console.error('Error fetching friends:', error)
  }
}

const viewAll = () => {
  if (isCurrentUser) {
    router.push('/resources/friends')
  } else {
    router.push('/resources/friends?id=' + user.value.id)
  }
}
watchEffect(() => {
  if (user.value && user.value.id) {
    fetchFriends(user.value.id)
  }
})
</script>
