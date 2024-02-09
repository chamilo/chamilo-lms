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

        <!--Button
          class="p-button-sm mb-4"
          label="Edit profile"
          @click="btnEditProfileOnClick"
        /-->

      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, provide, readonly, ref, watch } from "vue"
import { useStore } from "vuex"

import Avatar from "primevue/avatar"
import { useI18n } from "vue-i18n"
import SocialSideMenu from "../../components/social/SocialSideMenu.vue";
import UserProfileCard from "../../components/social/UserProfileCard.vue"
import { useRoute } from "vue-router"

const store = useStore()
const route = useRoute()
const { t } = useI18n()
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

function btnEditProfileOnClick() {
  window.location = "/account/edit"
}
</script>
