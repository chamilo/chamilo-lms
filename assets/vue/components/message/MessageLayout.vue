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

const store = useStore()
const route = useRoute()

const user = ref({})

provide("social-user", readonly(user))

async function loadUser() {
  try {
    user.value = route.query.id ? await store.dispatch("user/load", '/api/users/' + route.query.id) : store.getters["security/getUser"]
  } catch (e) {
    user.value = {}
  }
}

onMounted(loadUser)

watch(() => route.query, loadUser)
</script>
