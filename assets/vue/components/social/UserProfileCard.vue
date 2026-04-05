<template>
  <BaseCard
    plain
    class="overflow-hidden"
  >
    <div class="user-profile-card flex flex-col items-center p-5">
      <div class="mb-4">
        <div
          v-if="avatarState === 'loading'"
          class="flex h-24 w-24 items-center justify-center rounded-full border border-gray-25 bg-gray-20 shadow-sm"
        >
          <i
            class="mdi mdi-loading mdi-spin text-3xl text-gray-50"
            aria-hidden="true"
          ></i>
        </div>

        <div
          v-else-if="avatarState === 'ready'"
          class="rounded-full border border-gray-25 bg-white p-1 shadow-sm"
        >
          <BaseUserAvatar
            :alt="t('Picture')"
            :image-url="user.illustrationUrl"
            size="xlarge"
          />
        </div>

        <div
          v-else
          class="flex h-24 w-24 items-center justify-center rounded-full border border-gray-25 bg-gray-15 shadow-sm"
        >
          <i
            class="mdi mdi-account-outline text-4xl text-gray-50"
            aria-hidden="true"
          ></i>
        </div>
      </div>
      <div
        v-if="visibility.firstname && visibility.lastname"
        class="text-center text-xl font-bold text-gray-90"
      >
        {{ user.fullName }}
      </div>

      <div
        v-if="visibility.language && languageInfo"
        class="mt-2 inline-flex items-center gap-2 rounded-full bg-support-2 px-3 py-1 text-caption text-gray-90"
      >
        <i
          v-if="countryFlag"
          :class="`flag-icon flag-icon-${countryFlag}`"
        ></i>
        <span>{{ languageDisplay }}</span>
      </div>

      <div
        v-if="hasContactInfo"
        class="mt-4 flex w-full max-w-xs flex-col gap-2"
      >
        <a
          v-if="showFullProfile && visibility.email && user.email"
          href="/resources/messages/new"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-gray-15 px-3 py-2 text-body-2 text-gray-90 transition hover:bg-support-2"
        >
          <i
            class="mdi mdi-email-outline"
            aria-hidden="true"
          ></i>
          <span class="truncate">{{ user.email }}</span>
        </a>

        <a
          v-if="vCardUserLink"
          :href="vCardUserLink"
          target="_blank"
          class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-gray-25 bg-gray-15 px-4 py-2 text-body-2 font-medium text-gray-90 transition hover:bg-support-2"
        >
          <i
            class="mdi mdi-card-account-details-outline"
            aria-hidden="true"
          ></i>
          <span>{{ t("Business card") }}</span>
        </a>

        <div
          v-if="user.skype"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-gray-15 px-3 py-2 text-body-2 text-gray-90"
        >
          <i
            class="mdi mdi-skype"
            aria-hidden="true"
          ></i>
          <span class="truncate">Skype: {{ user.skype }}</span>
        </div>

        <div
          v-if="user.linkedin"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-25 bg-gray-15 px-3 py-2 text-body-2 text-gray-90"
        >
          <i
            class="mdi mdi-linkedin"
            aria-hidden="true"
          ></i>
          <span class="truncate">LinkedIn: {{ user.linkedin }}</span>
        </div>
      </div>

      <div
        v-if="hasExtraInfo"
        class="mt-4 w-full rounded-2xl border border-gray-25 bg-gray-15 p-4"
      >
        <dl class="space-y-2">
          <template
            v-for="item in extraInfo"
            :key="item.variable"
          >
            <div
              v-if="item.value && item.variable !== 'langue_cible'"
              class="grid grid-cols-[auto_1fr] gap-x-2 gap-y-1"
            >
              <dt class="text-caption font-semibold text-gray-90">{{ t(item.label) }}:</dt>
              <dd class="text-caption text-gray-50">{{ t(item.value) }}</dd>
            </div>

            <div
              v-if="item.value && item.variable === 'langue_cible'"
              class="flex items-center justify-center"
            >
              <i
                v-if="flagIconExists(item.value)"
                :class="`flag-icon flag-icon-${item.value.toLowerCase()}`"
              ></i>
            </div>
          </template>
        </dl>
      </div>

      <div
        v-if="chatEnabled && isUserOnline && !userOnlyInChat"
        class="mt-4"
      >
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl border border-success bg-support-2 px-4 py-2 text-body-2 font-semibold text-gray-90 transition hover:bg-gray-15"
          @click="chatWith(user.id, user.fullName, user.isOnline, user.illustrationUrl)"
        >
          {{ t("Chat") }} ({{ t("Online") }})
        </button>
      </div>

      <div
        v-if="pushEnabled"
        class="mt-4 w-full rounded-2xl border border-gray-25 bg-gray-15 p-4 text-center"
      >
        <p
          v-if="loading || isSubscribed === null"
          class="inline-flex items-center gap-2 text-caption text-gray-50"
        >
          <i
            class="mdi mdi-loading mdi-spin"
            aria-hidden="true"
          ></i>
          {{ t("Checking push subscription...") }}
        </p>

        <div
          v-else
          class="space-y-3"
        >
          <template v-if="isSubscribed">
            <div class="flex flex-col items-center">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                <i
                  class="mdi mdi-bell-ring-outline text-3xl text-success"
                  aria-hidden="true"
                ></i>
              </div>

              <p class="mt-2 text-caption font-semibold text-gray-90">
                {{ t("You're subscribed to push notifications in this browser.") }}
              </p>
              <BaseButton
                :label="t('Unsubscribe')"
                class="mt-2"
                icon="bell-off"
                type="danger"
                size="small"
                :loading="loading"
                @click="handleUnsubscribe"
              />

              <button
                type="button"
                class="text-caption text-white hover:underline"
                @click="toggleDetails"
              >
                {{ showDetails ? t("Hide") : t("Show") }}
              </button>

              <div
                v-if="showDetails"
                class="w-full rounded-xl border border-gray-25 bg-white p-3 text-left text-tiny text-gray-50 break-all"
              >
                <strong class="text-gray-90">{{ t("Endpoint") }}:</strong>
                <br />
                {{ subscriptionInfo?.endpoint }}
              </div>
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col items-center">
              <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white shadow-sm">
                <i
                  class="mdi mdi-bell-off-outline text-3xl text-gray-50"
                  aria-hidden="true"
                ></i>
              </div>

              <p class="mt-2 text-caption text-gray-90">
                {{ t("Push notifications are not enabled in this browser.") }}
              </p>
              <BaseButton
                :label="t('Enable notifications')"
                class="mt-2"
                icon="bell"
                type="primary"
                size="small"
                :loading="loading"
                @click="handleSubscribe"
              />
            </div>
          </template>
        </div>
      </div>

      <div
        v-if="isCurrentUser || securityStore.isAdmin"
        class="mt-5 w-full max-w-xs rounded-2xl border border-gray-25 bg-gray-15 p-3"
      >
        <div class="flex flex-col gap-2">
          <button
            type="button"
            class="group flex min-h-[56px] w-full items-center rounded-xl bg-primary px-4 py-3 text-left text-white shadow-sm transition hover:opacity-95 hover:shadow-xl"
            @click="editProfile"
          >
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-primary shadow-sm">
              <i
                class="mdi mdi-pencil text-lg"
                aria-hidden="true"
              ></i>
            </span>

            <span class="ml-3 min-w-0 flex-1 text-body-2 font-semibold leading-snug">
              {{ t("Edit profile") }}
            </span>

            <i
              class="mdi mdi-chevron-right ml-2 text-lg opacity-80 transition group-hover:translate-x-0.5"
              aria-hidden="true"
            ></i>
          </button>

          <button
            type="button"
            class="group flex min-h-[56px] w-full items-center rounded-xl bg-secondary px-4 py-3 text-left text-secondary-button-text shadow-sm transition hover:opacity-95 hover:shadow-xl"
            @click="changePassword"
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-secondary shadow-sm"
            >
              <i
                class="mdi mdi-lock-outline text-lg"
                aria-hidden="true"
              ></i>
            </span>

            <span class="ml-3 min-w-0 flex-1 text-body-2 font-semibold leading-snug">
              {{ t("Change Password") }}
            </span>

            <i
              class="mdi mdi-chevron-right ml-2 text-lg opacity-80 transition group-hover:translate-x-0.5"
              aria-hidden="true"
            ></i>
          </button>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, inject, ref, watch } from "vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
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
const avatarState = ref("empty")

const {
  isSubscribed,
  subscriptionInfo,
  subscribe,
  unsubscribe,
  loading,
  checkSubscription,
  loadVapidKey,
  pushEnabled,
  registerServiceWorker,
} = usePushSubscription()

const showDetails = ref(false)

const hasExtraInfo = computed(() => {
  return Array.isArray(extraInfo.value) && extraInfo.value.some((item) => item?.value)
})

const hasContactInfo = computed(() => {
  return Boolean(
    (showFullProfile.value && visibility.value.email && user.value?.email) ||
    vCardUserLink.value ||
    user.value?.skype ||
    user.value?.linkedin,
  )
})

function toggleDetails() {
  showDetails.value = !showDetails.value
}

function editProfile() {
  window.location.href = "/account/edit"
}

function changePassword() {
  window.location.href = "/account/change-password"
}

async function fetchUserProfile(userId) {
  try {
    const { data } = await axios.get(`/social-network/user-profile/${userId}`)

    languageInfo.value = data.language
    vCardUserLink.value = data.vCardUserLink
    visibility.value = data.visibility || {}
    extraInfo.value = data.extraFields || []
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

function preloadAvatar(url) {
  if (!url) {
    avatarState.value = "empty"
    return
  }

  avatarState.value = "loading"

  const image = new window.Image()

  image.onload = () => {
    avatarState.value = "ready"
  }

  image.onerror = () => {
    avatarState.value = "empty"
  }

  image.src = url
}

watch(
  () => user.value?.illustrationUrl,
  (url) => {
    preloadAvatar(url)
  },
  { immediate: true },
)

watch(
  () => user.value?.id,
  async (userId) => {
    if (!userId) {
      return
    }

    await fetchUserProfile(userId)
    loadVapidKey()
    await registerServiceWorker()
    await checkSubscription(userId)
  },
  { immediate: true },
)

async function handleSubscribe() {
  if (user.value?.id) {
    await subscribe(user.value.id)
    return
  }

  console.error("[Push] No user id for subscription.")
}

async function handleUnsubscribe() {
  if (user.value?.id) {
    await unsubscribe(user.value.id)
    return
  }

  console.error("[Push] No user id for unsubscription.")
}

const languageDisplay = computed(() => {
  return languageInfo.value?.label ?? languageInfo.value?.value ?? languageInfo.value?.code?.toUpperCase() ?? ""
})

const countryFlag = computed(() => {
  const code = languageInfo.value?.code || ""
  const match = code.match(/[-_](?<region>[A-Za-z]{2})$/)
  return match?.groups?.region?.toLowerCase() || null
})
</script>
