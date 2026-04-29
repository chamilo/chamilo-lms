<template>
  <BaseCard
    plain
    class="overflow-hidden bg-white"
  >
    <template #header>
      <div class="border-b border-gray-25 bg-gray-15 px-4 py-3">
        <h2 class="text-xl font-semibold text-gray-90">{{ friendsTitle }}</h2>
      </div>
    </template>

    <div class="space-y-4 px-4 py-4">
      <div
        v-if="isOwnWall"
        class="flex items-center"
      >
        <input
          v-model="searchQuery"
          :placeholder="t('Search')"
          type="search"
          class="h-11 min-w-0 flex-grow rounded-l-xl border-gray-25 bg-white px-3 text-body-2 text-gray-90 placeholder:text-gray-50 focus:border-primary focus:ring-primary"
          @input="onSearchInput"
          @keyup.enter="search"
        />
        <button
          type="button"
          class="flex h-11 w-11 items-center justify-center rounded-r-xl border border-l-0 border-gray-25 bg-gray-15 text-gray-90 transition hover:bg-gray-20"
          @click="search"
        >
          <i
            class="mdi mdi-magnify text-lg"
            aria-hidden="true"
          ></i>
        </button>
      </div>

      <ul
        v-if="limitedFriends.length > 0"
        class="space-y-2"
      >
        <li
          v-for="friend in limitedFriends"
          :key="friend.id"
        >
          <a
            href="#"
            class="group flex items-center gap-3 rounded-2xl border border-gray-25 bg-white px-3 py-3 text-decoration-none transition hover:bg-support-2"
            @click.prevent="goToWall(friend.friend.id)"
          >
            <div class="relative shrink-0">
              <BaseUserAvatar
                :alt="t('Picture')"
                :image-url="friend.friend.illustrationUrl"
              />
              <span
                class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white"
                :class="friend.friend.isOnline ? 'bg-success' : 'bg-gray-50'"
                :title="friend.friend.isOnline ? t('Online') : t('Offline')"
              ></span>
            </div>

            <div class="min-w-0 flex-1">
              <div class="truncate text-body-2 font-semibold text-gray-90">
                {{ friend.friend.firstname }} {{ friend.friend.lastname }}
              </div>
              <div class="truncate text-caption text-gray-50">({{ friend.friend.username }})</div>
            </div>

            <span class="shrink-0 text-tiny font-medium text-gray-50">
              {{ friend.friend.isOnline ? t("Online") : t("Offline") }}
            </span>
          </a>
        </li>
      </ul>

      <div
        v-else
        class="rounded-2xl border border-dashed border-gray-25 bg-gray-15 px-4 py-6 text-center"
      >
        <i
          class="mdi mdi-account-multiple-outline text-3xl text-gray-50"
          aria-hidden="true"
        ></i>
        <p class="mt-2 text-body-2 text-gray-50">{{ t("No friends found") }}</p>
      </div>

      <div
        v-if="friends.length > 10"
        class="text-center"
      >
        <a
          href="#"
          class="inline-flex items-center justify-center rounded-xl border border-gray-25 bg-gray-15 px-4 py-2 text-body-2 font-semibold text-gray-90 transition hover:bg-support-2"
          @click.prevent="viewAll"
        >
          {{ t("View all friends") }}
        </a>
      </div>

      <div
        v-if="allowSocialMap && isOwnWall"
        class="pt-1 text-center"
      >
        <BaseButton
          :label="t('By geolocalization')"
          icon="map-search"
          type="primary"
          @click="redirectToGeolocalization"
        />
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseUserAvatar from "../basecomponents/BaseUserAvatar.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { computed, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import axios from "axios"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()
const allowSocialMap = computed(() => platformConfigStore.getSetting("profile.allow_social_map_fields"))

const wallIdFromRoute = computed(() => {
  const raw = route.query.id

  if (!raw) {
    return null
  }

  const id = Number(raw)
  return Number.isFinite(id) && id > 0 ? id : null
})

const isOwnWall = computed(() => !wallIdFromRoute.value)
const targetUserId = computed(() => Number(wallIdFromRoute.value || securityStore.user?.id || 0))
const titleUser = ref(null)

const titleUserName = computed(() => {
  const currentUser = titleUser.value
  return (
    currentUser?.fullName ||
    [currentUser?.firstname, currentUser?.lastname].filter(Boolean).join(" ") ||
    currentUser?.username ||
    ""
  )
})

const friendsTitle = computed(() => {
  if (isOwnWall.value) {
    return t("My friends")
  }

  if (!titleUserName.value) {
    return t("Friends")
  }

  return `${t("Friends of {0}", [titleUserName.value])}`.trim()
})

async function loadTitleUser() {
  if (isOwnWall.value) {
    titleUser.value = securityStore.user || null
    return
  }

  const userId = targetUserId.value

  if (!userId) {
    titleUser.value = null
    return
  }

  titleUser.value = {
    id: userId,
    "@id": `/api/users/${userId}`,
    fullName: "",
    firstname: "",
    lastname: "",
    username: "",
  }

  try {
    const { data } = await axios.get(`/api/users/${userId}`)
    titleUser.value = data
  } catch (error) {
    console.warn("Failed to load wall owner for friends card title.", error)
  }
}

watch([() => targetUserId.value, () => securityStore.user?.["@id"]], () => loadTitleUser(), { immediate: true })

const friends = ref([])
const allFriends = ref([])
const searchQuery = ref("")
const limitedFriends = computed(() => friends.value.slice(0, 10))

function search() {
  router.push({ name: "SocialSearch", query: { query: searchQuery.value, type: "user" } })
}

function redirectToGeolocalization() {
  window.location.href = "/main/social/map.php"
}

function goToWall(userId) {
  router.push({ path: "/social", query: { id: userId } })
}

function buildUserIri(userId) {
  return `/api/users/${userId}`
}

function normalizeFriendRelation(rel, currentUserIri) {
  const userIri = rel?.user?.["@id"]
  const friendIri = rel?.friend?.["@id"]

  if (!userIri || !friendIri) {
    return null
  }

  if (userIri === currentUserIri && friendIri === currentUserIri) {
    return null
  }

  if (userIri === currentUserIri) {
    if (friendIri === currentUserIri) {
      return null
    }

    return rel
  }

  if (friendIri === currentUserIri) {
    const swapped = { ...rel, user: rel.friend, friend: rel.user }

    if (swapped.friend?.["@id"] === currentUserIri) {
      return null
    }

    return swapped
  }

  return null
}

function applySearchFilter() {
  const query = (searchQuery.value || "").trim().toLowerCase()

  if (!query) {
    friends.value = [...allFriends.value]
    return
  }

  friends.value = allFriends.value.filter((rel) => {
    const username = (rel.friend?.username || "").toLowerCase()
    const firstname = (rel.friend?.firstname || "").toLowerCase()
    const lastname = (rel.friend?.lastname || "").toLowerCase()

    return username.includes(query) || firstname.includes(query) || lastname.includes(query)
  })
}

function onSearchInput() {
  applySearchFilter()
}

async function fetchFriends(forUserId) {
  try {
    const safeUserId = Number(forUserId || 0)

    if (!safeUserId) {
      console.warn("Friends list: target user is not ready yet.")
      return
    }

    const currentUserIri = buildUserIri(safeUserId)

    const [forward, backward] = await Promise.all([
      axios.get(`/api/user_rel_users`, { params: { user: currentUserIri, relationType: 3 } }),
      axios.get(`/api/user_rel_users`, { params: { friend: currentUserIri, relationType: 3 } }),
    ])

    const raw = [...(forward.data?.["hydra:member"] || []), ...(backward.data?.["hydra:member"] || [])]

    const seen = new Set()
    const normalized = []

    for (const rel of raw) {
      const fixed = normalizeFriendRelation(rel, currentUserIri)

      if (!fixed) {
        continue
      }

      const otherIri = fixed.friend?.["@id"]

      if (!otherIri || seen.has(otherIri)) {
        continue
      }

      seen.add(otherIri)
      normalized.push(fixed)
    }

    const friendIds = normalized.map((relation) => relation.friend?.id).filter(Boolean)

    if (friendIds.length) {
      const onlineStatusResponse = await axios.post(`/social-network/online-status`, { userIds: friendIds })
      const onlineStatuses = onlineStatusResponse.data || {}

      normalized.forEach((relation) => {
        const friendId = relation.friend?.id

        if (friendId) {
          relation.friend.isOnline = !!onlineStatuses[friendId]
        }
      })
    }

    allFriends.value = normalized
    applySearchFilter()
  } catch (error) {
    console.error("Error fetching friends:", error)
    friends.value = []
    allFriends.value = []
  }
}

function viewAll() {
  const userId = targetUserId.value

  if (isOwnWall.value) {
    router.push("/resources/friends")
    return
  }

  if (userId) {
    router.push(`/resources/friends?id=${userId}`)
  }
}

watch(
  () => targetUserId.value,
  (newUserId) => {
    friends.value = []
    allFriends.value = []
    searchQuery.value = ""

    if (newUserId) {
      fetchFriends(newUserId)
    }
  },
  { immediate: true },
)
</script>
