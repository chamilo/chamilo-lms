<template>
  <div class="flex flex-col md:flex-row gap-4 w-full">
    <div class="flex flex-col w-full md:w-1/5">
      <UserProfileCard />
    </div>
    <div class="flex-grow w-full md:w-4/5">
      <router-view />
    </div>
  </div>
</template>

<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { onMounted, provide } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"


const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
