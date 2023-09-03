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
        <img
          v-if="containsImage"
          :alt="attachment.comment"
          :src="attachment.contentUrl"
        >
        <video
          v-if="containsVideo"
          width="320"
          height="240"
          controls
        >
          <source
            :src="attachment.contentUrl"
          >
          {{ attachment.comment }}
        </video>

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
import {computed, onMounted, reactive} from "vue";
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

const { relativeDatetime } = useFormatDate()

const attachment = null;//props.post.attachments.length ? props.post.attachments[0] : null;
let comments = reactive([]);

const containsImage = false; //attachment && attachment.resourceNode.resourceFile.mimeType.includes('image/');
const containsVideo = false; //attachment && attachment.resourceNode.resourceFile.mimeType.includes('video/');

const currentUser = store.getters['security/getUser'];

const isOwner = computed(() => currentUser['@id'] === props.post.sender['@id'])

onMounted(loadComments);

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
</script>
