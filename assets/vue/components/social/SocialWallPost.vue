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
              :to="{ name: 'SocialWall', query: { uid: post.sender.id } }"
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
              :to="{ name: 'SocialWall', query: { uid: post.sender.id } }"
            >
              {{ post.sender.fullName }}
            </BaseAppLink>
            <span v-else>
              {{ post.sender.fullName }}
            </span>
            &raquo;
            <BaseAppLink
              v-if="post.userReceiver?.id"
              :to="{ name: 'SocialWall', query: { uid: post.userReceiver.id } }"
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

        <LinkPreviewCard
          v-for="previewUrl in extractedUrls"
          :key="previewUrl"
          :url="previewUrl"
          class="mt-2"
        />

        <hr :class="{ 'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }" />

        <div
          :class="{ 'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }"
          class="border-t-0"
        >
          <button
            class="text-sm font-medium underline underline-offset-2"
            type="button"
            @click="toggleComments"
          >
            <span v-if="commentsLoading">Loading comments...</span>
            <span v-else-if="showComments">Hide comments ({{ commentsCount }})</span>
            <span v-else>Comments ({{ commentsCount }})</span>
          </button>
        </div>

        <div
          v-if="showComments"
          :class="{ 'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE }"
          class="border-t-0"
        >
          <div v-if="comments.length">
            <WallComment
              v-for="(comment, index) in comments"
              :key="index"
              :comment="comment"
              @comment-deleted="onCommentDeleted(comment)"
            />
          </div>

          <div
            v-else-if="!commentsLoading"
            class="text-sm text-gray-50"
          >
            No comments yet.
          </div>
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
import LinkPreviewCard from "./LinkPreviewCard.vue"
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

const comments = reactive([])
const attachments = ref([])
const commentsCount = ref(0)
const commentsLoaded = ref(false)
const commentsLoading = ref(false)
const showComments = ref(false)

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
  loadCommentsCount()
  loadAttachments()
})

watch(
  () => props.post?.["@id"],
  () => {
    resetCommentsState()
    attachments.value = []
    loadCommentsCount()
    loadAttachments()
  },
)

const computedAttachments = computed(() => attachments.value)

const extractedUrls = computed(() => {
  const content = props.post?.content || ""
  const text = content.replace(/<[^>]+>/g, " ")
  const urlRegex = /https?:\/\/[^\s<>"')\]]+/gi
  const matches = text.match(urlRegex) || []
  const hrefRegex = /href=["'](https?:\/\/[^"']+)["']/gi

  let hrefMatch
  while ((hrefMatch = hrefRegex.exec(content)) !== null) {
    if (!matches.includes(hrefMatch[1])) {
      matches.push(hrefMatch[1])
    }
  }

  return [...new Set(matches)].slice(0, 3)
})

function resetCommentsState() {
  comments.splice(0, comments.length)
  commentsCount.value = 0
  commentsLoaded.value = false
  commentsLoading.value = false
  showComments.value = false
}

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

async function loadCommentsCount() {
  try {
    const postIri = props.post?.["@id"]
    if (!postIri) return

    const { data } = await axios.get(ENTRYPOINT + "social_posts", {
      params: {
        parent: postIri,
        itemsPerPage: 1,
      },
    })

    commentsCount.value = Number(data?.["hydra:totalItems"] || 0)
  } catch (error) {
    console.error("There was an error loading the comments count!", error)
  }
}

async function loadComments() {
  if (commentsLoaded.value || commentsLoading.value) {
    return
  }

  commentsLoading.value = true

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

    comments.splice(0, comments.length, ...(data?.["hydra:member"] || []))
    commentsCount.value = Number(data?.["hydra:totalItems"] || comments.length)
    commentsLoaded.value = true
  } catch (error) {
    console.error("There was an error loading the comments!", error)
  } finally {
    commentsLoading.value = false
  }
}

async function toggleComments() {
  if (showComments.value) {
    showComments.value = false
    return
  }

  showComments.value = true

  if (!commentsLoaded.value) {
    await loadComments()
  }
}

function onCommentDeleted(eventComment) {
  const index = comments.findIndex((comment) => comment["@id"] === eventComment["@id"])

  if (index !== -1) {
    comments.splice(index, 1)
  }

  commentsCount.value = Math.max(0, commentsCount.value - 1)
}

function onCommentPosted(newComment) {
  const exists = comments.some((comment) => comment["@id"] === newComment["@id"])

  if (!exists) {
    comments.unshift(newComment)
  }

  commentsLoaded.value = true
  showComments.value = true
  commentsCount.value += 1
}

function onPostDeleted(post) {
  emit("post-deleted", post)
}

const isImageAttachment = (attachment) => {
  if (attachment?.filename) {
    const fileExtension = attachment.filename.split(".").pop().toLowerCase()
    return ["jpg", "jpeg", "png", "gif", "svg"].includes(fileExtension)
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
