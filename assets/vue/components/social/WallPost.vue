<template>
  <q-card
    bordered
    class="mb-4"
    flat
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

    <q-separator />

    <q-list
      v-if="comments.length"
      bordered
    >
      <q-item-label header>{{ $t('Comments') }}</q-item-label>

      <WallComment
        v-for="(comment, index) in comments"
        :key="index"
        :comment="comment"
        @comment-deleted="onCommentDeleted(index)"
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
import {onMounted, reactive} from "vue";
import WallComment from "./WallComment";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";

export default {
  name: "WallPost",
  components: {WallComment, WallCommentForm},
  props: {
    post: {
      type: Object,
      required: true
    }
  },
  setup(props) {
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

    function onCommentDeleted(index) {
      comments.splice(index, 1);
    }

    function onCommentPosted(newComment) {
      comments.unshift(newComment);
    }

    onMounted(loadComments);

    return {
      attachment,
      containsImage,
      containsVideo,
      comments,
      onCommentDeleted,
      onCommentPosted,
    }
  }
}
</script>
