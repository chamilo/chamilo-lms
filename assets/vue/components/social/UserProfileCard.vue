<template>
  <BaseCard plain>
    <div class="flex flex-col items-center p-4 user-profile-card">
      <BaseUserAvatar
        :alt="t('Picture')"
        :image-url="user.illustrationUrl"
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
      <div
        v-if="pushEnabled"
        class="mt-4 w-full text-center"
      >
        <p
          v-if="loading || isSubscribed === null"
          class="text-gray-500 text-sm"
        >
          <i class="mdi mdi-loading mdi-spin mr-2"></i>
          {{ t("Checking push subscription...") }}
        </p>

        <div v-else>
          <template v-if="isSubscribed">
            <div class="flex flex-col items-center text-green-700">
              <i class="mdi mdi-bell-ring-outline text-4xl mb-2"></i>
              <p class="text-sm font-semibold">
                {{ t("You're subscribed to push notifications in this browser.") }}
              </p>
              <BaseButton
                :label="t('Unsubscribe')"
                class="mt-2"
                icon="bell-off"
                type="danger"
                size="small"
                @click="handleUnsubscribe"
                :loading="loading"
              />
              <div
                v-if="showDetails"
                class="mt-2 bg-gray-100 rounded p-2 text-gray-800 text-xs break-all max-w-full"
              >
                <strong>{{ t("Endpoint") }}:</strong>
                <br />
                {{ subscriptionInfo?.endpoint }}
              </div>
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col items-center text-red-700">
              <i class="mdi mdi-bell-off-outline text-4xl mb-2"></i>
              <p class="text-sm">
                {{ t("Push notifications are not enabled in this browser.") }}
              </p>
              <BaseButton
                :label="t('Enable notifications')"
                class="mt-2"
                icon="bell"
                type="primary"
                size="small"
                @click="handleSubscribe"
                :loading="loading"
              />
            </div>
          </template>
        </div>
      </div>

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
import { computed, inject, onMounted, ref, watchEffect } from "vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import Divider from "primevue/divider"
import axios from "axios"
import { useSecurityStore } from "../../store/securityStore"
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"
import { usePushSubscription } from "../../composables/usePushSubscription"

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

const {
  isSubscribed,
  subscriptionInfo,
  subscribe,
  unsubscribe,
  loading,
  checkSubscription,
  loadVapidKey,
  vapidPublicKey,
  pushEnabled,
  registerServiceWorker,
} = usePushSubscription()

const showDetails = ref(false)

function toggleDetails() {
  showDetails.value = !showDetails.value
}

function editProfile() {
  window.location = "/account/edit"
}

function changePassword() {
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
  return mdiFlagIcons.includes(code?.toLowerCase())
}

function chatWith(userId, completeName, isOnline, avatarSmall) {}

watchEffect(async () => {
  if (user.value && user.value.id) {
    fetchUserProfile(user.value.id)
    loadVapidKey()
    await registerServiceWorker()
    await checkSubscription(user.value.id)
  }
})

async function handleSubscribe() {
  if (user.value?.id) {
    await subscribe(user.value.id)
  } else {
    console.error("[Push] No user id for subscription.")
  }
}

async function handleUnsubscribe() {
  if (user.value?.id) {
    await unsubscribe(user.value.id)
  } else {
    console.error("[Push] No user id for unsubscription.")
  }
}
</script>
