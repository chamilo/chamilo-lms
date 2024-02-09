<template>
  <BaseCard
    class="mb-4"
    :class="{ 'border-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE}"
    plain
  >
    <div class="flex flex-col">
      <div class="flex gap-2 mb-4">
        <img class="h-12 w-12 border border-gray-25" :src="post.sender.illustrationUrl" :alt="post.sender.username">

        <div class="flex flex-col">
          <div v-if="null === post.userReceiver || post.sender['@id'] === post.userReceiver['@id']">
            <router-link :to="{ name: 'SocialWall', query: { id: post.sender['@id']} }">
              {{ post.sender.fullName }}
            </router-link>
          </div>

          <div v-else>
            <router-link :to="{ name: 'SocialWall', query: { id: post.sender['@id']} }">
              {{ post.sender.fullName }}
            </router-link>
            &raquo;
            <router-link :to="{ name: 'SocialWall', query: { id: post.userReceiver['@id']} }">
              {{ post.userReceiver.fullName }}
            </router-link>
          </div>

          <small>
            {{ relativeDatetime(post.sendDate) }}
          </small>
        </div>

        <WallActions
          class="ml-auto"
          :is-owner="isOwner"
          :social-post="post"
          @post-deleted="onPostDeleted(post)"
        />
      </div>

      <div class="flex flex-col gap-2">
        <div v-for="(attachment, index) in computedAttachments" :key="index">
          <img
            v-if="isImageAttachment(attachment)"
            :src="attachment.path"
            :alt="attachment.filename"
          >

          <video v-if="isVideoAttachment(attachment)" controls>
            <source :src="attachment.path" :type="attachment.mimeType">
          </video>
        </div>

        <div v-html="post.content"/>

        <hr :class="{'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE}">

        <div
          v-if="comments.length"
          :class="{'text-success': post.type === SOCIAL_TYPE_PROMOTED_MESSAGE}"
          class="border-t-0"
        >
          <div>{{ $t('Comments') }}</div>
          <WallComment
            v-for="(comment, index) in comments"
            :key="index"
            :comment="comment"
            @comment-deleted="onCommentDeleted($event)"
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
import WallCommentForm from "./SocialWallCommentForm.vue";
import { ref, computed, onMounted, reactive, inject } from "vue"
import WallComment from "./SocialWallComment.vue";
import WallActions from "./Actions";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import {useStore} from "vuex";
import BaseCard from "../basecomponents/BaseCard.vue";
import {SOCIAL_TYPE_PROMOTED_MESSAGE} from "./constants";
import { useFormatDate } from "../../composables/formatDate"

const props = defineProps({
  post: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(["post-deleted"]);

const store = useStore();
import { useSecurityStore } from "../../store/securityStore"
const { relativeDatetime } = useFormatDate()

let comments = reactive([]);
const attachments = ref([]);
const securityStore = useSecurityStore()

const currentUser = inject('social-user')
const isCurrentUser = inject('is-current-user')
const isOwner = computed(() => currentUser['@id'] === props.post.sender['@id'])

onMounted(async () => {
  loadComments();

  await loadAttachments();
});

const computedAttachments = computed(() => {
  return attachments.value;
});

async function loadAttachments() {
  try {
    const postIri = props.post["@id"]

    const response = await axios.get(`${postIri}/attachments`)
    attachments.value = response.data
  } catch (error) {
    console.error("There was an error loading the attachments!", error)
  }
}

function loadComments() {
  axios
    .get(ENTRYPOINT + 'social_posts', {
      params: {
        parent: props.post['@id'],
        'order[sendDate]': 'desc',
        itemsPerPage: 3,
      }
    })
    .then(response => comments.push(...response.data['hydra:member']))
  ;
}

function onCommentDeleted(event) {
  const index = comments.findIndex(comment => comment['@id'] === event.comment['@id']);

  if (-1 !== index) {
    comments.splice(index, 1);
  }
}

function onCommentPosted(newComment) {
  comments.unshift(newComment);
}

function onPostDeleted(post) {
  emit('post-deleted', post);
}

const isImageAttachment = (attachment) => {
  if (attachment.filename) {
    const fileExtension = attachment.filename.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
  }

  return false;
};

const isVideoAttachment = (attachment) => {
  if (attachment.filename) {
    const fileExtension = attachment.filename.split('.').pop().toLowerCase();
    return ['mp4', 'webm', 'ogg'].includes(fileExtension);
  }

  return false;
};
</script>
