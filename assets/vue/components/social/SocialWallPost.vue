<template>
  <BaseCard
    :class="{ 'border-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }"
    class="mb-4"
    plain
  >
    <div class="flex flex-col">
      <div class="flex gap-2 mb-4">
        <img
          :alt="post.sender.username"
          :src="post.sender.illustrationUrl"
          class="h-12 w-12 border border-gray-25"
        />

        <div class="flex flex-col">
          <div v-if="null === post.userReceiver || post.sender['@id'] === post.userReceiver['@id']">
            <BaseAppLink :to="{ name: 'SocialWall', query: { id: post.sender['@id'] } }">
              {{ post.sender.fullName }}
            </BaseAppLink>
          </div>

          <div v-else>
            <BaseAppLink :to="{ name: 'SocialWall', query: { id: post.sender['@id'] } }">
              {{ post.sender.fullName }}
            </BaseAppLink>
            &raquo;
            <BaseAppLink :to="{ name: 'SocialWall', query: { id: post.userReceiver['@id'] } }">
              {{ post.userReceiver.fullName }}
            </BaseAppLink>
          </div>

          <small>
            {{ relativeDatetime(post.sendDate) }}
          </small>
        </div>

        <WallActions
          :is-owner="isOwner"
          :social-post="post"
          class="ml-auto"
          @post-deleted="onPostDeleted(post)"
        />
      </div>

      <div class="flex flex-col gap-2">
        <div
          v-for="(attachment, index) in computedAttachments"
          :key="index"
        >
          <img
            v-if="isImageAttachment(attachment)"
            :alt="attachment.filename"
            :src="attachment.path"
          />

          <video
            v-if="isVideoAttachment(attachment)"
            controls
          >
            <source
              :src="attachment.path"
              :type="attachment.mimeType"
            />
          </video>
        </div>

        <div v-html="post.content" />

        <hr :class="{ 'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }" />

        <div
          v-if="comments.length"
          :class="{ 'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }"
          class="border-t-0"
        >
          <div>{{ $t("Comments") }}</div>
          <WallComment
            v-for="(comment, index) in comments"
            :key="index"
            :comment="comment"
            @comment-deleted="onCommentDeleted(comment)"
          />
        </div>

        <WallCommentForm
          :post="post"
          @comment-posted="onCommentPosted"
        />
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import WallCommentForm from "./SocialWallCommentForm.vue"
import { computed, onMounted, reactive, ref } from "vue"
import WallComment from "./SocialWallComment.vue"
import WallActions from "./Actions"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import BaseCard from "../basecomponents/BaseCard.vue"
import { SOCIAL_TYPE_PROMOTED_MESSAGE } from "./constants"
import { useFormatDate } from "../../composables/formatDate"
import { useSecurityStore } from "../../store/securityStore"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const props = defineProps({
  post: {
    type: Object,
    required: true,
  },
})
const emit = defineEmits(["post-deleted"])

const { relativeDatetime } = useFormatDate()

let comments = reactive([])
const attachments = ref([])
const securityStore = useSecurityStore()
const currentUser = securityStore.user

const isOwner = computed(() => currentUser["@id"] === props.post.sender["@id"])

onMounted(() => {
  loadComments()
  loadAttachments()
})
const computedAttachments = computed(() => {
  return attachments.value
})

async function loadAttachments() {
  try {
    const postIri = props.post["@id"]

    const response = await axios.get(`${postIri}/attachments`)
    attachments.value = response.data
  } catch (error) {
    console.error("There was an error loading the attachments!", error)
  }
}

async function loadComments() {
  const { data } = await axios.get(ENTRYPOINT + "social_posts", {
    params: {
      parent: props.post["@id"],
      "order[sendDate]": "desc",
      itemsPerPage: 3,
    },
  })

  comments.push(...data["hydra:member"])
}

function onCommentDeleted(eventComment) {
  const index = comments.findIndex((comment) => comment["@id"] === eventComment["@id"])
  if (-1 !== index) {
    comments.splice(index, 1)
  }
}

function onCommentPosted(newComment) {
  comments.unshift(newComment)
}

function onPostDeleted(post) {
  emit("post-deleted", post)
}

const isImageAttachment = (attachment) => {
  if (attachment.filename) {
    const fileExtension = attachment.filename.split(".").pop().toLowerCase()
    return ["jpg", "jpeg", "png", "gif"].includes(fileExtension)
  }

  return false
}
const isVideoAttachment = (attachment) => {
  if (attachment.filename) {
    const fileExtension = attachment.filename.split(".").pop().toLowerCase()
    return ["mp4", "webm", "ogg"].includes(fileExtension)
  }

  return false
}
</script>
