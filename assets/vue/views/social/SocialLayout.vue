<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
      <BaseCard plain>
        <img
          :src="user.illustrationUrl"
          class="mb-2 p-3 rounded-full"
        />
        <div class="flex flex-col text-center">
          <div class="text-xl">{{ user.fullName }}</div>
          <div class="text-lg">{{ user.username }}</div>
        </div>
      </BaseCard>
      <SocialSideMenu />
    </div>

    <div class="md:basis-2/3 lg:basis-1/2 2xl:basis-4/6">
      <SocialNetworkWall />
    </div>

    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6">
      <AddFriendSidebar />
    </div>
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import { onMounted, provide, readonly, ref, watch } from "vue"
import SocialNetworkWall from "./SocialWall.vue"
import { useRoute } from "vue-router"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue"
import AddFriendSidebar from "../../components/userreluser/AddFriendSidebar.vue";
import UserProfileCard from "../../components/userreluser/UserProfileCard.vue";

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
