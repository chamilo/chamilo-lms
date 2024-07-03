<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
      <SocialWall :hidePostForm="true" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, provide } from "vue"
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import SocialWall from "../social/SocialWall.vue"


const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo();

provide("social-user", user);
provide("is-current-user", isCurrentUser);
provide("group-info", groupInfo);
provide("is-group", isGroup);

onMounted(loadUser);
</script>
