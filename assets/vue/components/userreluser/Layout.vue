<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
      <SocialSideMenu />
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
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

const store = useStore()
const route = useRoute()

const user = ref({})
const isCurrentUser = ref(true)

provide("social-user", readonly(user))
provide("is-current-user", readonly(isCurrentUser))

async function loadUser() {
  try {
    if (route.query.id) {
      user.value = await store.dispatch("user/load", '/api/users/' + route.query.id)
      isCurrentUser.value = false
    } else {
      user.value = store.getters["security/getUser"]
      isCurrentUser.value = true
    }
  } catch (e) {
    user.value = {}
    isCurrentUser.value = true
  }
}

onMounted(loadUser)

watch(() => route.query, loadUser)
</script>
