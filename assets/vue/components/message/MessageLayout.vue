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
import { useStore } from "vuex"
import { useRoute } from "vue-router"
import { onMounted, provide, readonly, ref, watch } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"

const store = useStore()
const route = useRoute()

const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
