<template>
  <div v-if="!isLoadingPage && hasPermission" class="flex flex-wrap md:flex-nowrap gap-4">
    <div class="flex flex-col w-full md:w-1/5">
      <UserProfileCard />
      <MyGroupsCard v-if="!hideSocialGroupBlock" />
      <MyFriendsCard />
      <MySkillsCard />
    </div>
    <div class="flex-grow w-full md:w-4/5">
      <component :is="currentComponent" />
    </div>
  </div>
  <div v-if="!isLoadingPage && !hasPermission">
    <div class="flex flex-wrap md:flex-nowrap gap-4">
      <p> {{ t("You do not have permission to view this page") }}</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, provide, computed, ref } from "vue"
import { useRoute } from "vue-router"
import SocialWall from "./SocialWall.vue"
import SocialSearch from "./SocialSearch.vue"
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import MyGroupsCard from "../../components/social/MyGroupsCard.vue"
import MyFriendsCard from "../../components/social/MyFriendsCard.vue"
import MySkillsCard from "../../components/social/MySkillsCard.vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"

const platformConfigStore = usePlatformConfig()
const hideSocialGroupBlock = "true" === platformConfigStore.getSetting("social.hide_social_groups_block")
const route = useRoute()
const { t } = useI18n()
const securityStore = useSecurityStore()
const hasPermission = ref(false)
const isLoadingPage = ref(true)
const { user, isCurrentUser, groupInfo, isGroup } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(async () => {
  isLoadingPage.value = false
  if (securityStore.user.id) {
    hasPermission.value = true
  }
})

const isSearchPage = computed(() => route.path.includes('/social/search'))
const currentComponent = computed(() => isSearchPage.value ? SocialSearch : SocialWall)
</script>
