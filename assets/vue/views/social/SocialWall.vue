<template>
  <div id="social-wall-container">
    <h2 class="text-xl font-semibold mb-4">
      {{ wallTitle }}
    </h2>
    <div
      class="flex justify-center mb-6 space-x-4"
      role="tablist"
      aria-label="Social wall filters"
    >
      <button
        :class="tabClasses(null)"
        role="tab"
        :aria-selected="!filterType"
        type="button"
        @click="filterMessages(null)"
      >
        {{ t("All Messages") }}
      </button>
      <button
        :class="tabClasses('promoted')"
        role="tab"
        :aria-selected="filterType === 'promoted'"
        type="button"
        @click="filterMessages('promoted')"
      >
        {{ t("Promoted Messages") }}
      </button>
    </div>

    <SocialWallPostForm
      v-if="canShowPostForm"
      :key="wallKey"
      class="mb-6"
      @post-created="refreshPosts"
    />
    <SocialWallPostList
      ref="postListRef"
      :key="wallKey"
      class="mb-6"
    />
  </div>
</template>

<script setup>
import { computed, provide, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import axios from "axios"
import SocialWallPostForm from "../../components/social/SocialWallPostForm.vue"
import SocialWallPostList from "../../components/social/SocialWallPostList.vue"
import { useSecurityStore } from "../../store/securityStore"
import { ENTRYPOINT } from "../../config/entrypoint"

const { t } = useI18n()

const props = defineProps({
  hidePostForm: { type: Boolean, default: false },
})

const postListRef = ref(null)
const route = useRoute()
const router = useRouter()
const filterType = ref(route.query.filterType || null)
const securityStore = useSecurityStore()
const isAdmin = computed(() => !!securityStore.isAdmin)
const currentUserIri = computed(() => securityStore.user?.["@id"] || null)
const wallUser = ref(null)
provide("social-user", wallUser)

const wallIdFromRoute = computed(() => {
  const raw = route.query.id
  if (!raw) return null
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : null
})

const isOwnWallByRoute = computed(() => !wallIdFromRoute.value)

const wallName = computed(() => {
  const u = wallUser.value
  return u?.fullName || [u?.firstname, u?.lastname].filter(Boolean).join(" ") || u?.username || ""
})

const isWallLoading = computed(() => !isOwnWallByRoute.value && !wallName.value)

const wallTitle = computed(() => {
  if (isOwnWallByRoute.value) return ''
  if (isWallLoading.value) return `${t("Social wall")}`
  return `${t("Wall of {0}", [wallName.value])}`.trim()
})

// Remount children when wall id or filter changes (prevents stale state reuse)
const wallKey = computed(() => `wall:${wallIdFromRoute.value || "me"}:${filterType.value || "all"}`)

async function loadWallUser() {
  if (isOwnWallByRoute.value) {
    wallUser.value = securityStore.user || null
    return
  }

  const targetId = wallIdFromRoute.value
  if (!targetId) {
    wallUser.value = securityStore.user || null
    return
  }

  // Stub user to prevent null-access while loading.
  wallUser.value = {
    id: targetId,
    "@id": `/api/users/${targetId}`,
    fullName: "",
    firstname: "",
    lastname: "",
    username: "",
  }

  try {
    const { data } = await axios.get(`${ENTRYPOINT}users/${targetId}`)
    wallUser.value = data
  } catch (e) {
    console.warn("Failed to load wall user. Keeping stub.", e)
  }
}

watch([() => wallIdFromRoute.value, () => currentUserIri.value], () => loadWallUser(), { immediate: true })

const canWriteOnOtherWall = ref(null)
let permissionSeq = 0
async function hasFriendship(meIri, wallIri) {
  if (!meIri || !wallIri) return false

  try {
    const [a, b] = await Promise.all([
      axios.get(`${ENTRYPOINT}user_rel_users`, { params: { relationType: 3, user: meIri, friend: wallIri } }),
      axios.get(`${ENTRYPOINT}user_rel_users`, { params: { relationType: 3, user: wallIri, friend: meIri } }),
    ])

    const aCount = Array.isArray(a.data?.["hydra:member"]) ? a.data["hydra:member"].length : 0
    const bCount = Array.isArray(b.data?.["hydra:member"]) ? b.data["hydra:member"].length : 0
    return aCount + bCount > 0
  } catch (e) {
    console.warn("Friendship check failed; posting on other wall disabled.", e)
    return false
  }
}

async function refreshOtherWallPermission() {
  const seq = ++permissionSeq

  if (isOwnWallByRoute.value) {
    canWriteOnOtherWall.value = null
    return
  }

  const meIri = currentUserIri.value
  const targetWallId = wallIdFromRoute.value
  const wallIri = targetWallId ? `/api/users/${targetWallId}` : null

  if (!meIri || !wallIri) {
    canWriteOnOtherWall.value = null
    return
  }

  // Reset immediately so UI doesn't reuse previous wall permission
  canWriteOnOtherWall.value = null
  const allowed = await hasFriendship(meIri, wallIri)

  // Ignore outdated results (race condition guard)
  if (seq !== permissionSeq) {
    console.debug("Ignoring outdated friendship result (race condition).")
    return
  }

  // Also ensure we are still on the same wall
  if (targetWallId !== wallIdFromRoute.value) {
    console.debug("Ignoring friendship result: wall changed during request.")
    return
  }
  canWriteOnOtherWall.value = allowed
}

watch(
  [() => currentUserIri.value, () => wallIdFromRoute.value],
  () => {
    canWriteOnOtherWall.value = isOwnWallByRoute.value ? null : null
    refreshOtherWallPermission()
  },
  { immediate: true },
)

watch(
  () => route.query.filterType,
  (newFilterType) => {
    filterType.value = newFilterType || null
    postListRef.value?.refreshPosts()
  },
)

const canShowPostForm = computed(() => {
  if (props.hidePostForm) return false

  if (!wallUser.value?.["@id"]) return false
  if (filterType.value === "promoted") {
    return isOwnWallByRoute.value && isAdmin.value
  }

  if (isOwnWallByRoute.value) return true

  return canWriteOnOtherWall.value === true
})

function refreshPosts() {
  postListRef.value?.refreshPosts()
}

function filterMessages(type) {
  const nextQuery = { ...route.query }
  if (type === null) {
    delete nextQuery.filterType
  } else {
    nextQuery.filterType = type
  }
  router.push({ path: "/social", query: nextQuery })
}

function tabClasses(type) {
  const isActive = type ? filterType.value === type : !filterType.value
  return [
    "inline-flex items-center rounded-full border px-4 py-2 text-body-2 font-medium transition-colors duration-150",
    "focus:outline-none focus:ring-2 focus:ring-primary",
    isActive
      ? "bg-primary border-primary text-white shadow-sm hover:bg-primary/90"
      : "bg-white border-gray-25 text-gray-90 hover:bg-gray-15",
  ]
}
</script>
