<template>
  <div>
      <div class="discussions-header relative">
        <BaseButton
          :label="t('Create thread')"
          icon="add-topic"
          class="create-thread-btn absolute right-0"
          @click="showCreateThreadDialog = true"
          type="button"
        />
      </div>
      <div v-for="(discussion, index) in discussions" :key="discussion.id">
        <div class="discussion-item" @click="selectDiscussion(discussion)">
          <div class="discussion-content">
            <div class="discussion-title" v-html="discussion.title"></div>
            <div class="discussion-details">
              <i class="mdi mdi-message-reply-text icon"></i>
              <span>{{ discussion.repliesCount }} {{ t("Replies") }}</span>
              <i class="mdi mdi-clock-outline icon"></i>
              <span>{{ t("Created") }} {{ relativeDatetime(discussion.sendDate) }}</span>
            </div>
          </div>
          <div class="discussion-author">
            <div class="author-avatar">
              <img v-if="discussion.sender.illustrationUrl" :src="discussion.sender.illustrationUrl" alt="Author avatar">
              <i v-else class="mdi mdi-account-circle-outline"></i>
            </div>
            <div class="author-name mt-4">{{ discussion.sender.username }}</div>
          </div>
        </div>
    </div>
  </div>

  <Dialog header="Create Thread" v-model:visible="showCreateThreadDialog" modal closable>
    <form @submit.prevent="handleSubmit">
      <BaseInputText id="title" label="Title" v-model="title" :isInvalid="titleError" />
      <BaseTinyEditor v-model="message" editor-id="messageEditor" title="Message" />
      <BaseFileUploadMultiple v-model="files" label="Add files" accept="image/png, image/jpeg" />
      <BaseButton type="button" label="Send message" icon="save" @click="handleSubmit" class="mt-8" />
    </form>
  </Dialog>
</template>

<script setup>
import { ref, onMounted, toRefs, reactive } from "vue"
import { useRoute, useRouter } from "vue-router"
import axios from 'axios'
import { useI18n } from "vue-i18n"
import { useFormatDate } from "../../composables/formatDate"
import { useSocialInfo } from "../../composables/useSocialInfo"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseFileUploadMultiple from "../basecomponents/BaseFileUploadMultiple.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const route = useRoute()
const discussions = ref([])
const groupId = ref(route.params.group_id)
const { t } = useI18n()
const { relativeDatetime } = useFormatDate()
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()
const router = useRouter()
function selectDiscussion(discussion) {
  router.push({
    name: 'UserGroupDiscussions',
    params: {
      group_id: groupId.value,
      discussion_id: discussion.id
    }
  })
}

const state = reactive({
  showCreateThreadDialog: false,
  title: '',
  message: '',
  files: [],
  titleError: false,
})
const { showCreateThreadDialog, title, message, files, titleError } = toRefs(state)
async function handleSubmit() {
  if (title.value.trim() === '') {
    titleError.value = true
    return
  }

  const filesArray = files.value
  const formData = new FormData()
  formData.append('action', 'add_message_group')
  formData.append('title', title.value)
  formData.append('content', message.value)
  formData.append('userId', user.value.id)
  formData.append('groupId', groupId.value)
  for (let i = 0; i < filesArray.length; i++) {
    formData.append('files[]', filesArray[i])
  }

  try {
    const response = await axios.post('/social-network/group-action', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  } catch (error) {
    console.error('Error when making request', error)
  }
}

onMounted(async () => {
  if (groupId.value) {
    try {
      const response = await axios.get(`/api/messages/by-group/list?groupId=${groupId.value}`)
      discussions.value = response.data['hydra:member'].map(discussion => ({
        ...discussion,
        repliesCount: discussion.receiversTo.length + discussion.receiversCc.length
      }))
    } catch (error) {
      console.error('Error fetching discussions:', error)
      discussions.value = []
    }
  }
})
</script>
