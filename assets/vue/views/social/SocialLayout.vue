<template>
    <div v-if="!isLoadingPage && hasPermission" class="flex flex-wrap md:flex-nowrap gap-4">
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
    <div v-if="!isLoadingPage && !hasPermission">
      <div class="flex flex-wrap md:flex-nowrap gap-4">
      <p> {{ t("You do not have permission to view this page") }}</p>
      </div>
    </div>
</template>

<script setup>
import { useStore } from "vuex"
import { onMounted, provide, computed, readonly, ref, watch, watchEffect } from "vue"
import { useRoute } from "vue-router"
import SocialWall from "./SocialWall.vue"
import SocialSearch from "./SocialSearch.vue"
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue"
import MyGroupsCard from "../../components/social/MyGroupsCard.vue"
import MyFriendsCard from "../../components/social/MyFriendsCard.vue"
import MySkillsCard from "../../components/social/MySkillsCard.vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import { useSocialStore } from "../../store/socialStore"
import { useI18n } from "vue-i18n"

const store = useStore()
const route = useRoute()
const { t } = useI18n()
const socialStore = useSocialStore()
const hasPermission = ref(false)
const isLoadingPage = ref(true)
const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

const currentUser = store.getters["security/getUser"]
const profileUserId = computed(() => route.query.id)

onMounted(async () => {
  if (profileUserId.value) {
    await socialStore.checkUserRelation(currentUser.id, profileUserId.value)
    hasPermission.value = socialStore.isProfileVisible
  } else {
    hasPermission.value = true
  }
  isLoadingPage.value = false
})

const isSearchPage = computed(() => route.path.includes('/social/search'))
const currentComponent = computed(() => isSearchPage.value ? SocialSearch : SocialWall)
</script>
