<template>
  <div class="relative min-h-[300px]">
    <SocialWallPost
      v-for="socialPost in postList"
      :key="socialPost.id"
      :post="socialPost"
      @post-deleted="onPostDeleted($event)"
    />

    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import SocialWallPost from "./SocialWallPost.vue"
import { inject, onMounted, reactive, ref, watch } from "vue"
import Loading from "../Loading"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useRoute } from "vue-router"
import { SOCIAL_TYPE_PROMOTED_MESSAGE } from "./constants"
import { ref as vueRef } from "vue"

const route = useRoute()
const user = inject("social-user", vueRef(null))
const postList = reactive([])
const isLoading = ref(false)

watch(
  // Watch only the stable identifier instead of the whole object.
  [() => user.value?.["@id"], () => route.query.filterType],
  () => {
    listPosts()
  },
  { immediate: true },
)

onMounted(() => {
  listPosts()
})

function refreshPosts() {
  listPosts()
}

defineExpose({
  refreshPosts,
})

async function listPosts() {
  // Clear current list immediately to avoid showing stale posts when switching walls.
  postList.splice(0, postList.length)

  const wallOwnerId = user.value?.id
  const wallOwnerIri = user.value?.["@id"]

  // If wall user is not ready yet (null while loading), just do nothing.
  if (!wallOwnerId || !wallOwnerIri) {
    return
  }

  const filterType = route.query.filterType
  isLoading.value = true
  try {
    const params = {
      socialwall_wallOwner: wallOwnerId,
      "order[sendDate]": "desc",
      "exists[parent]": false,
    }

    if (filterType === "promoted") {
      params.type = SOCIAL_TYPE_PROMOTED_MESSAGE
    }

    const { data } = await axios.get(ENTRYPOINT + "social_posts", { params })
    postList.push(...(data?.["hydra:member"] || []))
  } catch (e) {
    console.error("Failed to load social wall posts.", e)
  } finally {
    isLoading.value = false
  }
}

function onPostDeleted(event) {
  const index = postList.findIndex((post) => post["@id"] === event["@id"])
  if (index >= 0) {
    postList.splice(index, 1)
  }
}
</script>
