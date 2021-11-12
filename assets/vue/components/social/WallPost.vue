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
            {{ post.sender.username }}
          </router-link>
        </q-item-label>

        <q-item-label v-else>
          <router-link :to="{ name: 'SocialWall', query: { id: post.sender['@id']} }">
            {{ post.sender.username }}
          </router-link>
          &raquo;
          <router-link :to="{ name: 'SocialWall', query: { id: post.userReceiver['@id']} }">
            {{ post.userReceiver.username }}
          </router-link>
        </q-item-label>

        <q-item-label caption>
          {{ $filters.abbreviatedDatetime(post.sendDate) }}
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
    >
      <q-item-label header>{{ $t('Comments') }}</q-item-label>

      <WallComment
        v-for="(comment, index) in comments"
        :key="index"
        :comment="comment"
        @deleted="onDeletedComment(index)"
      />
    </q-list>

    <WallCommentForm :post="post" />
  </q-card>
</template>

<script>
import WallCommentForm from "./CommentForm";
import {onMounted, reactive} from "vue";
import WallComment from "./WallComment";
import {SOCIAL_TYPE_WALL_COMMENT} from "./constants";
import {useStore} from "vuex";

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
      const store = useStore();

      store
        .dispatch(
          'socialpost/findAll',
          {
            parent: props.post['@id'],
            type: SOCIAL_TYPE_WALL_COMMENT,
            'order[sendDate]': 'desc',
            itemsPerPage: 3
          }
        )
        .then(response => comments.push(...response));
    }

    function onDeletedComment(index) {
      comments.splice(index, 1);
    }

    onMounted(loadComments);

    return {
      attachment,
      containsImage,
      containsVideo,
      comments,
      onDeletedComment,
    }
  }
}
</script>
