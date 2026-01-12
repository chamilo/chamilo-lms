<template>
  <BaseCard
    class="my-groups-card bg-white mb-3"
    plain
  >
    <template #header>
      <div class="px-4 py-3 bg-gray-200">
        <h2 class="text-xl font-semibold">{{ friendsTitle }}</h2>
      </div>
    </template>
    <hr class="my-2" />
    <div class="px-4">
      <div
        v-if="isOwnWall"
        class="flex items-center mb-4"
      >
        <input
          v-model="searchQuery"
          :placeholder="t('Search')"
          class="flex-grow p-2 h-[44px] border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          type="search"
          @input="onSearchInput"
        />
        <button
          class="p-2 h-[44px] bg-gray-200 border border-gray-300 rounded-r-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
          type="button"
          @click="search"
        >
          <i class="mdi mdi-magnify text-gray-700"></i>
        </button>
      </div>
      <ul class="list-group mb-4">
        <li
          v-for="friend in limitedFriends"
          :key="friend.id"
          class="list-group-item friend-item d-flex align-items-center mb-2"
        >
          <a
            href="#"
            class="d-flex align-items-center text-decoration-none"
            @click.prevent="goToWall(friend.friend.id)"
          >
            <div class="relative mr-2 inline-block">
              <BaseUserAvatar
                :alt="t('Picture')"
                :image-url="friend.friend.illustrationUrl"
              />
              <span>
                {{ friend.friend.firstname }} {{ friend.friend.lastname }}
                <small class="text-muted">({{ friend.friend.username }})</small>
              </span>
              <span
                class="absolute -top-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white"
                :style="{ backgroundColor: friend.friend.isOnline ? '#22c55e' : '#9ca3af' }"
                :title="friend.friend.isOnline ? 'Online' : 'Offline'"
              ></span>
            </div>
          </a>
        </li>
      </ul>
      <div
        v-if="friends.length > 10"
        class="mt-2 text-center"
      >
        <a
          href="#"
          @click.prevent="viewAll"
        >
          {{ t("View all friends") }}
        </a>
      </div>
    </div>
    <div
      v-if="allowSocialMap && isOwnWall"
      class="text-center mt-3"
    >
      <BaseButton
        :label="t('By geolocalization')"
        icon="map-search"
        type="primary"
        @click="redirectToGeolocalization"
      />
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
import { ENTRYPOINT } from "../../config/entrypoint"
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
  if (!raw) return null
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : null
})
const isOwnWall = computed(() => !wallIdFromRoute.value)
const targetUserId = computed(() => Number(wallIdFromRoute.value || securityStore.user?.id || 0))
const titleUser = ref(null)
const titleUserName = computed(() => {
  const u = titleUser.value
  return u?.fullName || [u?.firstname, u?.lastname].filter(Boolean).join(" ") || u?.username || ""
})
const friendsTitle = computed(() => {
  if (isOwnWall.value) return t("My friends")
  if (!titleUserName.value) return t("Friends")
  return `${t("Friends of {0}", [titleUserName.value])}`.trim()
})

async function loadTitleUser() {
  // Own wall: logged-in user
  if (isOwnWall.value) {
    titleUser.value = securityStore.user || null
    return
  }

  const id = targetUserId.value
  if (!id) {
    titleUser.value = null
    return
  }

  titleUser.value = {
    id,
    "@id": `/api/users/${id}`,
    fullName: "",
    firstname: "",
    lastname: "",
    username: "",
  }

  try {
    const { data } = await axios.get(`${ENTRYPOINT}users/${id}`)
    titleUser.value = data
  } catch (e) {
    console.warn("Failed to load wall owner for friends card title.", e)
  }
}

watch([() => targetUserId.value, () => securityStore.user?.["@id"]], () => loadTitleUser(), { immediate: true })

const friends = ref([])
const allFriends = ref([])
const searchQuery = ref("")
const limitedFriends = computed(() => friends.value.slice(0, 10))
const search = () => {
  router.push({ name: "SocialSearch", query: { query: searchQuery.value, type: "user" } })
}
const redirectToGeolocalization = () => {
  window.location.href = "/main/social/map.php"
}

function goToWall(userId) {
  router.push({ path: "/social", query: { id: userId } })
}

function buildUserIri(userId) {
  return `/api/users/${userId}`
}

function normalizeFriendRelation(rel, meIri) {
  const userIri = rel?.user?.["@id"]
  const friendIri = rel?.friend?.["@id"]

  if (!userIri || !friendIri) return null
  if (userIri === meIri && friendIri === meIri) return null

  if (userIri === meIri) {
    if (friendIri === meIri) return null
    return rel
  }

  if (friendIri === meIri) {
    const swapped = { ...rel, user: rel.friend, friend: rel.user }
    if (swapped.friend?.["@id"] === meIri) return null
    return swapped
  }

  return null
}

function applySearchFilter() {
  const q = (searchQuery.value || "").trim().toLowerCase()
  if (!q) {
    friends.value = [...allFriends.value]
    return
  }

  friends.value = allFriends.value.filter((rel) => {
    const username = (rel.friend?.username || "").toLowerCase()
    const firstname = (rel.friend?.firstname || "").toLowerCase()
    const lastname = (rel.friend?.lastname || "").toLowerCase()
    return username.includes(q) || firstname.includes(q) || lastname.includes(q)
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

    const meIri = buildUserIri(safeUserId)

    const [forward, backward] = await Promise.all([
      axios.get(`${ENTRYPOINT}user_rel_users`, { params: { user: meIri, relationType: 3 } }),
      axios.get(`${ENTRYPOINT}user_rel_users`, { params: { friend: meIri, relationType: 3 } }),
    ])

    const raw = [...(forward.data?.["hydra:member"] || []), ...(backward.data?.["hydra:member"] || [])]

    const seen = new Set()
    const normalized = []

    for (const rel of raw) {
      const fixed = normalizeFriendRelation(rel, meIri)
      if (!fixed) continue

      const otherIri = fixed.friend?.["@id"]
      if (!otherIri || seen.has(otherIri)) continue

      seen.add(otherIri)
      normalized.push(fixed)
    }

    const friendIds = normalized.map((r) => r.friend?.id).filter(Boolean)
    if (friendIds.length) {
      const onlineStatusResponse = await axios.post(`/social-network/online-status`, { userIds: friendIds })
      const onlineStatuses = onlineStatusResponse.data || {}

      normalized.forEach((r) => {
        const id = r.friend?.id
        if (id) r.friend.isOnline = !!onlineStatuses[id]
      })
    }

    allFriends.value = normalized
    applySearchFilter()
  } catch (error) {
    console.error("Error fetching friends:", error)
  }
}

const viewAll = () => {
  const id = targetUserId.value
  if (isOwnWall.value) {
    router.push("/resources/friends")
    return
  }
  if (id) {
    router.push("/resources/friends?id=" + id)
  }
}

watch(
  () => targetUserId.value,
  (newId) => {
    friends.value = []
    allFriends.value = []
    searchQuery.value = ""

    if (newId) {
      fetchFriends(newId)
    }
  },
  { immediate: true },
)
</script>
