<template>
  <div>
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

const user = inject("social-user")

const postList = reactive([])
const isLoading = ref(false)

const route = useRoute()

watch(
  [() => user.value, () => route.query.filterType],
  () => {
    listPosts()
  },
  { immediate: true },
)
onMounted(listPosts)

function refreshPosts() {
  listPosts()
}

defineExpose({
  refreshPosts,
})

async function listPosts() {
  postList.splice(0, postList.length)
  if (!user.value["@id"]) {
    return
  }

  const filterType = route.query.filterType
  isLoading.value = true
  const params = {
    socialwall_wallOwner: user.value["id"],
    "order[sendDate]": "desc",
    "exists[parent]": false,
  }
  if (filterType === "promoted") {
    params.type = SOCIAL_TYPE_PROMOTED_MESSAGE
  }

  const { data } = await axios.get(ENTRYPOINT + "social_posts", { params })
  postList.push(...data["hydra:member"])

  isLoading.value = false
}

function onPostDeleted(event) {
  const index = postList.findIndex((post) => post["@id"] === event["@id"])
  if (index >= 0) {
    postList.splice(index, 1)
  }
}
</script>
