<template>
  <q-item>
    <q-item-section
      avatar
      top
    >
      <q-avatar>
        <img :src="comment.sender.illustrationUrl" class="h-12 w-12 border border-gray-25" />
      </q-avatar>
    </q-item-section>

    <q-item-section top>
      <q-item-label lines="1">
        <p class="text-weight-medium">{{ comment.sender.fullName }}</p>
      </q-item-label>
      <q-item-label v-html="comment.content" />
      <q-item-label
        :title="abbreviatedDatetime(comment.sendDate)"
        caption
      >
        <p class="small">{{ relativeDatetime(comment.sendDate) }}</p>
      </q-item-label>
    </q-item-section>

    <q-item-section
      side
      top
    >
      <WallActions
        :is-owner="isOwner"
        :social-post="comment"
        @post-deleted="onCommentDeleted(comment)"
      />
    </q-item-section>
  </q-item>
</template>

<script setup>
import { computed } from "vue"
import WallActions from "./Actions"
import { useFormatDate } from "../../composables/formatDate"
import { useSecurityStore } from "../../store/securityStore"

const { abbreviatedDatetime, relativeDatetime } = useFormatDate()

const props = defineProps({
  comment: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(["comment-deleted"])

const securityStore = useSecurityStore()
const isOwner = computed(() => securityStore.user["@id"] === props.comment.sender["@id"])

function onCommentDeleted(eventComment) {
  emit("comment-deleted", eventComment)
}
</script>
