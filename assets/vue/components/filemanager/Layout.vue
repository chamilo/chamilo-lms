<template>
  <router-view></router-view>
</template>
<script setup>
import { useStore } from "vuex"
import { useRoute } from "vue-router"
import { onMounted, provide, readonly, ref, watch } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"

const store = useStore()
const route = useRoute()

const { user, isCurrentUser, groupInfo, isGroup, loadUser } = useSocialInfo()

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(loadUser)
</script>
