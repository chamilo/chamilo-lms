<template>
  <BaseCard plain>
    <div class="flex flex-col items-center p-4 user-profile-card">
      <BaseUserAvatar
        :image-url="user.illustrationUrl"
        :alt="t('Picture')"
        size="xlarge"
      />
      <div
        v-if="visibility.firstname && visibility.lastname"
        class="text-xl font-bold"
      >
        {{ user.fullName }}
      </div>

      <div v-if="visibility.language && languageInfo">
        <template v-if="flagIconExists(languageInfo.code)">
          <i :class="`mdi mdi-flag-${languageInfo.code.toLowerCase()}`"></i>
        </template>
        <template v-else>
          {{ t(languageInfo.code) }}
        </template>
      </div>

      <div class="mt-4">
        <p
          v-if="showFullProfile"
          class="flex items-center justify-center mb-2"
        >
          <a
            v-if="visibility.email && user.email"
            :href="'/resources/messages/new'"
            class="flex items-center justify-center mb-2"
          >
            <i class="mdi mdi-email-outline mr-2"></i> {{ user.email }}
          </a>
        </p>
        <p
          v-if="vCardUserLink"
          class="flex items-center justify-center mb-2"
        >
          <a
            :href="vCardUserLink"
            class="flex items-center justify-center"
            target="_blank"
          >
            <i class="mdi mdi-card-account-details-outline mr-2"></i> {{ t("Business card") }}
          </a>
        </p>
        <p
          v-if="user.skype"
          class="flex items-center justify-center mb-2"
        >
          <i class="mdi mdi-skype mr-2"></i> Skype: {{ user.skype }}
        </p>
        <p
          v-if="user.linkedin"
          class="flex items-center justify-center"
        >
          <i class="mdi mdi-linkedin mr-2"></i> LinkedIn: {{ user.linkedin }}
        </p>
      </div>

      <hr />
      <div
        v-if="extraInfo && extraInfo.length > 0"
        class="extra-info-container"
      >
        <dl class="extra-info-list">
          <template
            v-for="item in extraInfo"
            :key="item.variable"
          >
            <div v-if="item.value">
              <dt v-if="item.variable !== 'langue_cible'">{{ t(item.label) }}:</dt>
              <dd v-if="item.variable !== 'langue_cible'">{{ t(item.value) }}</dd>

              <div
                v-if="item.variable === 'langue_cible'"
                class="language-target"
              >
                <i
                  v-if="flagIconExists(item.value)"
                  :class="`flag-icon flag-icon-${item.value.toLowerCase()}`"
                ></i>
              </div>
            </div>
          </template>
        </dl>
      </div>

      <div v-if="chatEnabled && isUserOnline && !userOnlyInChat">
        <button @click="chatWith(user.id, user.fullName, user.isOnline, user.illustrationUrl)">
          {{ t("Chat") }} ({{ t("Online") }})
        </button>
      </div>

      <Divider />
      <BaseButton
        v-if="isCurrentUser || securityStore.isAdmin"
        :label="t('Edit profile')"
        class="mt-4"
        icon="edit"
        type="primary"
        @click="editProfile"
      />
      <BaseButton
        v-if="isCurrentUser || securityStore.isAdmin"
        :label="t('Change Password')"
        class="mt-2"
        icon="lock"
        type="secondary"
        @click="changePassword"
      />
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, inject, ref, watchEffect } from "vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import Divider from "primevue/divider"
import axios from "axios"
import { useSecurityStore } from "../../store/securityStore"
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"

const { t } = useI18n()
const securityStore = useSecurityStore()
const user = inject("social-user")
const isCurrentUser = inject("is-current-user")
const extraInfo = ref([])
const chatEnabled = ref(true)
const isUserOnline = ref(false)
const userOnlyInChat = ref(false)
const showFullProfile = computed(() => isCurrentUser.value || securityStore.isAdmin)
const languageInfo = ref(null)
const vCardUserLink = ref("")
const visibility = ref({})
watchEffect(() => {
  if (user.value && user.value.id) {
    fetchUserProfile(user.value.id)
  }
})

const editProfile = () => {
  window.location = "/account/edit"
}

const changePassword = () => {
  window.location = "/account/change-password"
}

async function fetchUserProfile(userId) {
  try {
    const { data } = await axios.get(`/social-network/user-profile/${userId}`)

    languageInfo.value = data.language
    vCardUserLink.value = data.vCardUserLink
    visibility.value = data.visibility
    extraInfo.value = data.extraFields
    isUserOnline.value = data.isUserOnline
    userOnlyInChat.value = data.userOnlyInChat
    chatEnabled.value = data.chatEnabled
  } catch (error) {
    console.error("Error fetching user profile data:", error)
  }
}

function flagIconExists(code) {
  const mdiFlagIcons = ["us", "fr", "de", "es", "it", "pl"]
  return mdiFlagIcons.includes(code.toLowerCase())
}

function chatWith(userId, completeName, isOnline, avatarSmall) {}
</script>
