<template>
  <div class="message-layout flex">
    <div class="sidebar">
      <UserProfileCard />
    </div>
    <div class="content flex-grow">
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
