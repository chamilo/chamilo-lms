<template>
  <div class="flex flex-wrap md:flex-nowrap gap-4">
    <div class="flex flex-col w-full md:w-1/4 lg:w-1/6">
      <UserProfileCard />
      <SocialSideMenu />
    </div>

    <div class="flex-grow w-full md:basis-1/2 lg:basis-2/3">
      <SocialNetworkWall />
    </div>

    <div class="flex flex-col w-full md:w-1/4 lg:w-1/6">
      <MyGroupsCard />
      <MyFriendsCard />
      <MySkillsCard />
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { onMounted, provide, readonly, ref, watch } from "vue"
import SocialNetworkWall from "./SocialWall.vue"
import { useRoute } from "vue-router"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue"
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import MyGroupsCard from "../../components/social/MyGroupsCard.vue"
import MyFriendsCard from "../../components/social/MyFriendsCard.vue"
import MySkillsCard from "../../components/social/MySkillsCard.vue"

const store = useStore()
const route = useRoute()

const user = ref({})

provide("social-user", readonly(user))

async function loadUser() {
  try {
    user.value = route.query.id ? await store.dispatch("user/load", route.query.id) : store.getters["security/getUser"]
  } catch (e) {
    user.value = {}
  }
}

onMounted(loadUser)

watch(() => route.query, loadUser)
</script>
