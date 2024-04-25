<template>
  <div class="message-layout flex">
    <div class="sidebar">
      <UserProfileCard />
      <SocialSideMenu />
    </div>
    <div class="content flex-grow">
      <router-view></router-view>
    </div>
  </div>
</template>
<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import SocialSideMenu from "../social/SocialSideMenu.vue"
import { onMounted, provide } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import { useSecurityStore } from "../../store/securityStore"


const { isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

const { user } = useSecurityStore()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
