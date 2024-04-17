<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
      <SocialSideMenu />
    </div>
    <div class="md:basis-2/3 lg:basis-1/2 2xl:basis-4/6">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import SocialSideMenu from "../social/SocialSideMenu.vue"

import { onMounted, provide } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"


const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
