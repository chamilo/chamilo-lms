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
          <div v-if="!post.userReceiver || post.sender['@id'] === post.userReceiver?.['@id']">
            <BaseAppLink
              v-if="post.sender?.id"
              :to="{ name: 'SocialWall', query: { id: post.sender.id } }"
            >
              {{ post.sender.fullName }}
            </BaseAppLink>
            <span v-else>
              {{ post.sender.fullName }}
            </span>
          </div>
          <div v-else>
            <BaseAppLink
              v-if="post.sender?.id"
              :to="{ name: 'SocialWall', query: { id: post.sender.id } }"
            >
              {{ post.sender.fullName }}
            </BaseAppLink>
            <span v-else>
              {{ post.sender.fullName }}
            </span>
            &raquo;
            <BaseAppLink
              v-if="post.userReceiver?.id"
              :to="{ name: 'SocialWall', query: { id: post.userReceiver.id } }"
            >
              {{ post.userReceiver.fullName }}
            </BaseAppLink>
            <span v-else>
              {{ post.userReceiver?.fullName }}
            </span>
          </div>
          <small>
            {{ relativeDatetime(post.sendDate) }}
          </small>
        </div>

        <WallActions
          v-if="canShowActions"
          :is-owner="canDelete"
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
import { computed, inject, onMounted, reactive, ref, watch } from "vue"
import WallComment from "./SocialWallComment.vue"
import WallActions from "./Actions"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import BaseCard from "../basecomponents/BaseCard.vue"
import { SOCIAL_TYPE_PROMOTED_MESSAGE } from "./constants"
import { useFormatDate } from "../../composables/formatDate"
import { useSecurityStore } from "../../store/securityStore"

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
const wallUser = inject("social-user", ref(null))
const meIri = computed(() => currentUser?.["@id"] || null)
const wallIri = computed(() => wallUser.value?.["@id"] || null)
const isWallOwner = computed(() => {
  return !!(meIri.value && wallIri.value && meIri.value === wallIri.value)
})
const canDelete = computed(() => {
  if (!meIri.value) return false
  if (securityStore.isAdmin) return true
  return isWallOwner.value
})
const canShowActions = computed(() => canDelete.value)

onMounted(() => {
  loadComments()
  loadAttachments()
})

// If the post changes (reused component), reload data safely.
watch(
  () => props.post?.["@id"],
  () => {
    comments.splice(0, comments.length)
    attachments.value = []
    loadComments()
    loadAttachments()
  },
)

const computedAttachments = computed(() => attachments.value)

async function loadAttachments() {
  try {
    const postIri = props.post?.["@id"]
    if (!postIri) return

    const response = await axios.get(`${postIri}/attachments`)
    attachments.value = response.data
  } catch (error) {
    console.error("There was an error loading the attachments!", error)
  }
}

async function loadComments() {
  try {
    const postIri = props.post?.["@id"]
    if (!postIri) return

    const { data } = await axios.get(ENTRYPOINT + "social_posts", {
      params: {
        parent: postIri,
        "order[sendDate]": "desc",
        itemsPerPage: 3,
      },
    })

    comments.push(...(data?.["hydra:member"] || []))
  } catch (error) {
    console.error("There was an error loading the comments!", error)
  }
}

function onCommentDeleted(eventComment) {
  const index = comments.findIndex((comment) => comment["@id"] === eventComment["@id"])
  if (index !== -1) {
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
  if (attachment?.filename) {
    const fileExtension = attachment.filename.split(".").pop().toLowerCase()
    return ["jpg", "jpeg", "png", "gif"].includes(fileExtension)
  }
  return false
}
const isVideoAttachment = (attachment) => {
  if (attachment?.filename) {
    const fileExtension = attachment.filename.split(".").pop().toLowerCase()
    return ["mp4", "webm", "ogg"].includes(fileExtension)
  }
  return false
}
</script>
