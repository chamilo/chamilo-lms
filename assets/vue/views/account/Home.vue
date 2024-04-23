<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
      <SocialSideMenu />
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
      <div id="account-home">
        <div class="flex mb-4">
          <Avatar
            :image="user.illustrationUrl + '?w=80&h=80&fit=crop'"
            class="flex-none mr-2"
            shape="circle"
            size="large"
          />
          <div class="flex-1">
            <p class="text-body-1">
              {{ user.fullName }}
            </p>
            <p class="text-caption">
              {{ user.username }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, provide } from "vue"

import Avatar from "primevue/avatar"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue";
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import { useSocialInfo } from "../../composables/useSocialInfo"


const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo();

provide("social-user", user);
provide("is-current-user", isCurrentUser);
provide("group-info", groupInfo);
provide("is-group", isGroup);

onMounted(loadUser);
</script>
