<template>
  <div class="flex flex-wrap md:flex-nowrap gap-4">
    <div class="flex flex-col w-full md:w-1/4 lg:w-1/6">
      <UserProfileCard />
      <SocialSideMenu />
    </div>

    <div class="flex-grow w-full md:basis-1/2 lg:basis-2/3">
      <component :is="currentComponent" />
    </div>

    <div class="flex flex-col w-full md:w-1/4 lg:w-1/6" v-if="!isSearchPage">
      <MyGroupsCard />
      <MyFriendsCard />
      <MySkillsCard />
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { onMounted, provide, computed, readonly, ref, watch } from "vue"
import { useRoute } from "vue-router"
import SocialWall from "./SocialWall.vue"
import SocialSearch from "./SocialSearch.vue"
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue"
import MyGroupsCard from "../../components/social/MyGroupsCard.vue"
import MyFriendsCard from "../../components/social/MyFriendsCard.vue"
import MySkillsCard from "../../components/social/MySkillsCard.vue"
import { useSocialInfo } from "../../composables/useSocialInfo"

const store = useStore()
const route = useRoute()

const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)

const isSearchPage = computed(() => route.path.includes('/social/search'))

const currentComponent = computed(() => {
  return isSearchPage.value ? SocialSearch : SocialWall
})
</script>
