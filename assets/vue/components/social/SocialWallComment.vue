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

<script setup>
import {useStore} from "vuex"
import {computed} from "vue"
import WallActions from "./Actions"

const props = defineProps({
  comment: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['comment-deleted'])

const store = useStore();
const currentUser = store.getters['security/getUser'];

const isOwner = computed(() =>  currentUser['@id'] === props.comment.sender['@id'])

function onCommentDeleted(event) {
  emit('comment-deleted', event);
}
</script>
