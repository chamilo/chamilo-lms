<template>
  <q-item>
    <q-item-section avatar top>
      <q-avatar>
        <img :src="comment.sender.illustrationUrl">
      </q-avatar>
    </q-item-section>

    <q-item-section top>
      <q-item-label lines="1">
        <span class="text-weight-medium">{{ comment.sender.fullName }}</span>
      </q-item-label>
      <q-item-label v-html="comment.content" />
      <q-item-label
        :title="$filters.abbreviatedDatetime(comment.sendDate)"
        caption
      >
        {{ $filters.relativeDatetime(comment.sendDate) }}
      </q-item-label>
    </q-item-section>

    <q-item-section side top>
      <WallActions
        :is-owner="isOwner"
        :social-post="comment"
        @post-deleted="onCommentDeleted($event)"
      />
    </q-item-section>
  </q-item>
</template>

<script>
import {useStore} from "vuex";
import {computed} from "vue";
import WallActions from "./Actions";

export default {
  name: "WallComment",
  components: {WallActions},
  props: {
    comment: {
      type: Object,
      required: true
    }
  },
  emits: ['comment-deleted'],
  setup(props, {emit}) {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    function onCommentDeleted(event) {
      emit('comment-deleted', event);
    }

    return {
      isOwner: computed(() => currentUser['@id'] === props.comment.sender['@id']),
      onCommentDeleted,
    };
  }
}
</script>
