<template>
  <q-card
    :class="{ 'border-success': post.type === 4 }"
    :flat="post.type !== 4"
    bordered
    class="mb-4"
  >
    <q-item>
      <q-item-section side>
        <q-avatar>
          <q-img :alt="post.sender.username" :src="post.sender.illustrationUrl" />
        </q-avatar>
      </q-item-section>

      <q-item-section>
        <q-item-label
          v-if="null === post.userReceiver || post.sender['@id'] === post.userReceiver['@id']"
        >
          <router-link :to="{ name: 'SocialWall', query: { id: post.sender['@id']} }">
            {{ post.sender.fullName }}
          </router-link>
        </q-item-label>

        <q-item-label v-else>
          <router-link :to="{ name: 'SocialWall', query: { id: post.sender['@id']} }">
            {{ post.sender.fullName }}
          </router-link>
          &raquo;
          <router-link :to="{ name: 'SocialWall', query: { id: post.userReceiver['@id']} }">
            {{ post.userReceiver.fullName }}
          </router-link>
        </q-item-label>

        <q-item-label
          :title="$filters.abbreviatedDatetime(post.sendDate)"
          caption
        >
          {{ $filters.relativeDatetime(post.sendDate) }}
        </q-item-label>
      </q-item-section>

      <q-item-section side top>
        <WallActions
          :is-owner="isOwner"
          :social-post="post"
          @post-deleted="onPostDeleted(post)"
        />
      </q-item-section>
    </q-item>

    <q-img
      v-if="containsImage"
      :alt="attachment.comment"
      :src="attachment.contentUrl"
    />

    <q-video
      v-if="containsVideo"
      :alt="attachment.comment"
      :src="attachment.contentUrl"
    />
    <q-card-section v-html="post.content" />

    <q-separator :color="post.type === 4 ? 'green-400' : false" />

    <q-list
      v-if="comments.length"
      :class="{ 'border-success': post.type === 4 }"
      bordered
      class="border-t-0"
      separator
    >
      <q-item-label header>{{ $t('Comments') }}</q-item-label>

      <WallComment
        v-for="(comment, index) in comments"
        :key="index"
        :comment="comment"
        @comment-deleted="onCommentDeleted($event)"
      />
    </q-list>

    <WallCommentForm
      :post="post"
      @comment-posted="onCommentPosted"
    />
  </q-card>
</template>

<script>
import WallCommentForm from "./CommentForm";
import {computed, onMounted, reactive} from "vue";
import WallComment from "./WallComment";
import WallActions from "./Actions";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import {useStore} from "vuex";

export default {
  name: "WallPost",
  components: {WallComment, WallCommentForm, WallActions},
  props: {
    post: {
      type: Object,
      required: true
    }
  },
  emits: ["post-deleted"],
  setup(props, {emit}) {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    const attachment = null;//props.post.attachments.length ? props.post.attachments[0] : null;
    let comments = reactive([]);

    const containsImage = false; //attachment && attachment.resourceNode.resourceFile.mimeType.includes('image/');
    const containsVideo = false; //attachment && attachment.resourceNode.resourceFile.mimeType.includes('video/');

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

    onMounted(loadComments);

    return {
      attachment,
      containsImage,
      containsVideo,
      comments,
      onCommentDeleted,
      onCommentPosted,
      onPostDeleted,
      isOwner: computed(() => currentUser['@id'] === props.post.sender['@id']),
    }
  }
}
</script>
