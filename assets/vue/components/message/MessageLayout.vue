<template>
  <div class="message-layout flex flex-col md:flex-row">
    <div class="sidebar hidden md:block md:w-1/4">
      <UserProfileCard />
    </div>
    <div class="content flex-grow w-full">
      <router-view></router-view>
    </div>
  </div>
</template>
<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { onMounted, provide } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const { isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

const securityStore = useSecurityStore()

const { user } = storeToRefs(securityStore)

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
