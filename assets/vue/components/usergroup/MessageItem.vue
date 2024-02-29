<template>
  <div class="message-item social-group-messages" :style="{ paddingLeft: indentation + 'px' }">
    <div class="message-avatar">
      <img :src="message.avatar" alt="Avatar" class="avatar">
    </div>
    <div class="message-body">
      <div class="message-meta">
        <span class="message-author">{{ message.user }}</span>
        <span class="message-date">{{ relativeDatetime(message.created) }}</span>
      </div>
      <div class="message-content" v-html="message.content"></div>
      <div class="message-attachments mt-8" v-if="message.attachment && message.attachment.length">
        <div v-for="(attachment, index) in message.attachment" :key="index" class="attachment-link">
          <a :href="attachment.link" target="_blank">{{ attachment.filename }}</a>
          <span> ({{ formatSize(attachment.size) }})</span>
        </div>
      </div>
      <div class="message-actions">
        <BaseIcon icon="reply" size="normal" @click="$emit('replyMessage', message)" />
        <div>
          <BaseIcon icon="edit" v-if="isMessageCreator(message)" size="normal" @click="$emit('editMessage', message)" />
          <BaseIcon icon="delete" size="normal" v-if="isMainMessage && isModerator" @click="$emit('deleteMessage', message)" />
        </div>
      </div>
      <div class="child-messages">
        <MessageItem
          v-for="child in message.children"
          :key="child.id"
          :message="child"
          :currentUser="currentUser"
          :indentation="indentation + 20"
          @replyMessage="$emit('replyMessage', $event)"
          @editMessage="$emit('editMessage', $event)"
          @deleteMessage="$emit('deleteMessage', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useFormatDate } from "../../composables/formatDate"

const { relativeDatetime } = useFormatDate()
const { message, indentation, currentUser, isMainMessage, isModerator } = defineProps({
  message: Object,
  indentation: {
    type: Number,
    default: 0,
  },
  currentUser: Object,
  isMainMessage: Boolean,
  isModerator: Boolean
})
const formatSize = (size) => {
  if (size < 1024) return size + ' B'
  let i = Math.floor(Math.log(size) / Math.log(1024))
  let num = (size / Math.pow(1024, i)).toFixed(2)
  let unit = ['B', 'KB', 'MB', 'GB', 'TB'][i]
  return `${num} ${unit}`
}
const isMessageCreator = (message) => {
  return message.senderId === currentUser.id
}
</script>
