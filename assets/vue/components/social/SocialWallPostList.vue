<template>
  <div class="relative min-h-[300px]">
    <SocialWallPost
      v-for="socialPost in postList"
      :key="socialPost.id"
      :post="socialPost"
      @post-deleted="onPostDeleted($event)"
    />

    <div
      v-if="hasMore"
      ref="sentinelRef"
      class="h-4"
    />

    <Loading :visible="isLoading && !postList.length" />

    <div
      v-if="isLoadingMore"
      class="py-4 text-center text-gray-50"
    >
      {{ $t("Loading") }}
    </div>

    <div
      v-if="!isLoading && !postList.length"
      class="py-6 text-center text-gray-50"
    >
      {{ $t("No posts found") }}
    </div>
  </div>
</template>

<script setup>
import SocialWallPost from "./SocialWallPost.vue"
import { inject, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue"
import Loading from "../Loading"
import axios from "axios"
import { useRoute } from "vue-router"
import { SOCIAL_TYPE_PROMOTED_MESSAGE } from "./constants"
import { ref as vueRef } from "vue"

const route = useRoute()
const user = inject("social-user", vueRef(null))

const postList = reactive([])
const isLoading = ref(false)
const isLoadingMore = ref(false)
const currentPage = ref(1)
const hasMore = ref(true)
const sentinelRef = ref(null)

let observer = null
let requestSeq = 0

watch(
  [() => user.value?.["@id"], () => route.query.filterType],
  () => {
    resetAndLoadPosts()
  },
  { immediate: true },
)

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      const [entry] = entries

      if (entry?.isIntersecting) {
        loadMorePosts()
      }
    },
    {
      root: null,
      rootMargin: "300px 0px",
      threshold: 0,
    },
  )

  observeSentinel()
})

onBeforeUnmount(() => {
  disconnectObserver()
})

function refreshPosts() {
  resetAndLoadPosts()
}

defineExpose({
  refreshPosts,
})

async function resetAndLoadPosts() {
  requestSeq += 1

  currentPage.value = 1
  hasMore.value = true
  postList.splice(0, postList.length)

  await loadPostsPage(1, true)
}

async function loadMorePosts() {
  if (!hasMore.value || isLoading.value || isLoadingMore.value) {
    return
  }

  await loadPostsPage(currentPage.value + 1, false)
}

async function loadPostsPage(page, replace = false) {
  const seq = requestSeq
  const wallOwnerId = user.value?.id
  const wallOwnerIri = user.value?.["@id"]
  const filterType = route.query.filterType

  if (!wallOwnerId || !wallOwnerIri) {
    hasMore.value = false
    return
  }

  if (replace) {
    if (isLoading.value) {
      return
    }

    isLoading.value = true
  } else {
    if (isLoading.value || isLoadingMore.value || !hasMore.value) {
      return
    }

    isLoadingMore.value = true
  }

  try {
    const params = {
      socialwall_wallOwner: wallOwnerId,
      "order[sendDate]": "desc",
      "exists[parent]": false,
      itemsPerPage: 30,
      page,
    }

    if (filterType === "promoted") {
      params.type = SOCIAL_TYPE_PROMOTED_MESSAGE
    }

    const { data } = await axios.get("/api/social_posts", { params })

    if (seq !== requestSeq) {
      return
    }

    const items = data?.["hydra:member"] || []

    if (replace) {
      postList.splice(0, postList.length, ...items)
    } else {
      const existingIds = new Set(postList.map((post) => post["@id"] ?? post.id))
      const uniqueItems = items.filter((item) => !existingIds.has(item["@id"] ?? item.id))

      postList.push(...uniqueItems)
    }

    currentPage.value = page
    hasMore.value = Boolean(data?.["hydra:view"]?.["hydra:next"]) && items.length > 0
  } catch (error) {
    console.error("Failed to load social wall posts.", error)
  } finally {
    if (seq === requestSeq) {
      if (replace) {
        isLoading.value = false
      } else {
        isLoadingMore.value = false
      }
    }

    await nextTick()
    observeSentinel()
    maybeLoadMoreIfNeeded()
  }
}

function onPostDeleted(event) {
  const index = postList.findIndex((post) => post["@id"] === event["@id"])

  if (index >= 0) {
    postList.splice(index, 1)
  }
}

function disconnectObserver() {
  if (observer) {
    observer.disconnect()
  }
}

function observeSentinel() {
  if (!observer) {
    return
  }

  disconnectObserver()

  if (sentinelRef.value && hasMore.value) {
    observer.observe(sentinelRef.value)
  }
}

function maybeLoadMoreIfNeeded() {
  if (!hasMore.value || isLoading.value || isLoadingMore.value || !sentinelRef.value) {
    return
  }

  const rect = sentinelRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight

  if (rect.top <= viewportHeight + 150) {
    loadMorePosts()
  }
}
</script>
