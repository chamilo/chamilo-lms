<template>
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

    <Button
      class="p-button-sm mb-4"
      label="Edit profile"
      @click="btnEditProfileOnClick"
    />

    <p>
      <router-link to="/resources/friends">
        {{ t("My friends") }}
      </router-link>
    </p>
    <p>
      <router-link to="/resources/personal_files">
        {{ t("My files") }}
      </router-link>
    </p>
  </div>
</template>

<script setup>
import { computed } from "vue"
import { useStore } from "vuex"

import Avatar from "primevue/avatar"
import { useI18n } from "vue-i18n"

const store = useStore()

const { t } = useI18n()

const user = computed(() => store.getters["security/getUser"])

function btnEditProfileOnClick() {
  window.location = "/account/edit"
}
</script>