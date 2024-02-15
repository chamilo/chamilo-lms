<template>
  <BaseCard plain>
    <div class="p-4 text-center">
      <img
        :src="user.illustrationUrl"
        class="mb-4 w-24 h-24 mx-auto rounded-full"
        alt="Profile picture"
      />
      <div class="text-xl font-bold">{{ user.fullName }}</div>
      <div class="text-lg">{{ user.username }}</div>
      <div class="mt-4">
        <p class="flex items-center justify-center mb-2">
          <i class="mdi mdi-email-outline mr-2"></i>
          {{ user.email }}
        </p>
        <p class="flex items-center justify-center mb-2">
          <i class="mdi mdi-card-account-details-outline mr-2"></i>
          {{ t('Business card') }}
        </p>
        <p class="flex items-center justify-center mb-2">
          <i class="mdi mdi-skype mr-2"></i>
          Skype
        </p>
        <p class="flex items-center justify-center">
          <i class="mdi mdi-linkedin mr-2"></i>
          LinkedIn
        </p>
      </div>
      <BaseButton
        v-if="isCurrentUser"
        :label="t('Edit profile')"
        type="primary"
        class="mt-4"
        @click="editProfile"
        icon="edit"
      />
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, inject, onMounted, ref, watch } from "vue"
import { useStore } from 'vuex'
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"

const { t } = useI18n()
const store = useStore()
const route = useRoute()
const user = inject('social-user')
const isCurrentUser = inject('is-current-user')

const editProfile = () => {
  window.location = "/account/edit"
}
</script>
