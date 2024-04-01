<template>
  <div class="social-group-show group-info text-center">
    <div class="group-header">
      <h1 class="group-title">{{ groupInfo?.title || "..." }}</h1>
      <p class="group-description">{{ groupInfo?.description }}</p>
    </div>
  </div>
  <div
    v-if="!isLoading"
    class="discussion"
  >
    <BaseButton
      :label="t('Back to the list')"
      class="back-button mb-8"
      icon="back"
      type="button"
      @click="goBack"
    />
    <h2>{{ firstMessageTitle || "Discussion Thread" }}</h2>
    <div class="message-list mt-8">
      <MessageItem
        v-for="message in messages"
        :key="message.id"
        :current-user="user"
        :indentation="0"
        :is-main-message="message.parentId === null || message.parentId === 0"
        :is-moderator="groupInfo.isModerator"
        :message="message"
        @delete-message="deleteMessage"
        @edit-message="openDialogForEdit"
        @reply-message="openDialogForReply"
      />
    </div>
  </div>

  <Dialog
    v-model:visible="showMessageDialog"
    closable
    header="Reply/Edit Message"
    modal
  >
    <form @submit.prevent="handleSubmit">
      <BaseInputText
        v-if="isEditMode"
        id="title"
        v-model="messageTitle"
        :is-invalid="titleError"
        :label="t('Title')"
      />
      <BaseTinyEditor
        v-model="messageContent"
        editor-id="messageEditor"
        title="Message"
      />
      <BaseFileUploadMultiple
        v-model="files"
        :label="t('Add files')"
        accept="image/png, image/jpeg"
      />
      <BaseButton
        :label="t('Send message')"
        class="mt-8"
        icon="save"
        type="button"
        @click="handleSubmit"
      />
    </form>
  </Dialog>
</template>

<script setup>
import { computed, onMounted, reactive, ref, toRefs } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useSocialInfo } from "../../composables/useSocialInfo"
import axios from "axios"
import MessageItem from "./MessageItem.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseFileUploadMultiple from "../basecomponents/BaseFileUploadMultiple.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const router = useRouter()
const route = useRoute()
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()

const messages = ref([])
const { t } = useI18n()

const firstMessageTitle = computed(() => {
  return messages.value.length > 0 ? messages.value[0].title : null
})
const showMessageDialog = ref(false)
const isEditMode = ref(false)
const currentMessageId = ref(null)
const state = reactive({
  messageTitle: "",
  messageContent: "",
  files: [],
  titleError: false,
})
const { messageTitle, messageContent, files, titleError } = toRefs(state)
const getIndentation = (message) => {
  let indent = 0
  let parent = messages.value.find((m) => m.id === message.parentId)
  while (parent) {
    indent += 30
    parent = messages.value.find((m) => m.id === parent.parentId)
  }
  return `${indent}px`
}
const fetchMessages = async () => {
  const groupId = route.params.group_id
  const discussionId = route.params.discussion_id
  try {
    const response = await axios.get(`/social-network/group/${groupId}/discussion/${discussionId}/messages`)
    messages.value = response.data
  } catch (error) {
    console.error("Error fetching messages:", error)
  }
}

function openDialogForReply(message) {
  isEditMode.value = false
  currentMessageId.value = message.id
  showMessageDialog.value = true
}

function openDialogForEdit(message) {
  isEditMode.value = true
  currentMessageId.value = message.id
  messageTitle.value = message.title
  messageContent.value = message.content
  showMessageDialog.value = true
}

async function handleSubmit() {
  if (isEditMode.value && title.value.trim() === "") {
    titleError.value = true
    return
  }
  const filesArray = files.value
  const formData = new FormData()
  formData.append("action", isEditMode.value ? "edit_message_group" : "reply_message_group")
  formData.append("title", messageTitle.value)
  formData.append("content", messageContent.value)
  if (isEditMode.value) {
    formData.append("messageId", currentMessageId.value)
  } else {
    formData.append("parentId", currentMessageId.value)
  }
  formData.append("userId", user.value.id)
  formData.append("groupId", groupInfo.value.id)
  for (let i = 0; i < filesArray.length; i++) {
    formData.append("files[]", filesArray[i])
  }
  try {
    await axios.post("/social-network/group-action", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    showMessageDialog.value = false
    await fetchMessages()
  } catch (error) {
    console.error("Error submitting the form:", error)
  }
}

const deleteMessage = async (message) => {
  try {
    const confirmed = confirm(`Are you sure you want to delete this message: ${message.title}?`)
    if (!confirmed) {
      return
    }
    const data = {
      action: "delete_message_group",
      messageId: message.id,
      userId: user.value.id,
      groupId: groupInfo.value.id,
    }
    await axios.post("/social-network/group-action", data)
    await router.push({ name: "UserGroupShow", params: { group_id: groupInfo.value.id } })
  } catch (error) {
    console.error("Error deleting the message:", error)
  }
}
onMounted(() => {
  fetchMessages()
})
const goBack = () => {
  router.back()
}
</script>
