<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
      <h2 v-t="'My files'" class="mr-auto" />
      <hr />
      <router-view></router-view>
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
