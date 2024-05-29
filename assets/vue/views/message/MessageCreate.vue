<template>
  <MessageForm
    v-model:attachments="attachments"
    :values="item"
  >
    <div
      v-if="sendToUser"
      class="field space-x-4"
    >
      <span v-t="'To'" />
      <MessageCommunicationParty
        :username="sendToUser.username"
        :full-name="sendToUser.fullName"
        :profile-image-url="sendToUser.illustrationUrl"
      />
    </div>

    <BaseAutocomplete
      v-else
      id="to"
      v-model="usersTo"
      :label="t('To')"
      :search="asyncFind"
      is-multiple
    />

    <BaseAutocomplete
      v-if="!sendToUser"
      id="cc"
      v-model="usersCc"
      :label="t('Cc')"
      :search="asyncFind"
      is-multiple
    />

    <BaseTinyEditor
      v-model="item.content"
      editor-id="message"
      required
    />

    <BaseButton
      :label="t('Send')"
      :disabled="!canSubmitMessage"
      icon="plus"
      type="primary"
      class="mb-2"
      @click="onSubmit"
    />
  </MessageForm>
  <Loading :visible="isLoading || isLoadingUser" />
</template>

<script setup>
import MessageForm from "../../components/message/Form.vue"
import Loading from "../../components/Loading.vue"
import { computed, onMounted, ref } from "vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { MESSAGE_TYPE_INBOX } from "../../components/message/constants"
import userService from "../../services/userService"
import { useNotification } from "../../composables/notification"
import { capitalize } from "lodash"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useSecurityStore } from "../../store/securityStore"
import { messageService } from "../../services/message"
import MessageCommunicationParty from "./MessageCommunicationParty.vue"
import { MESSAGE_REL_USER_TYPE_CC, MESSAGE_REL_USER_TYPE_TO } from "../../constants/entity/messagereluser"

const securityStore = useSecurityStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const notification = useNotification()

const asyncFind = async (query) => {
  const { items } = await userService.findBySearchTerm(query)

  return items.map((member) => ({
    name: member.fullName,
    value: member["@id"],
  }))
}

const item = ref({
  sender: securityStore.user["@id"],
  receivers: [],
  msgType: MESSAGE_TYPE_INBOX,
  title: "",
  content: "",
})

const attachments = ref([])

const usersTo = ref([])

const usersCc = ref([])

const receiversTo = computed(() =>
  usersTo.value.map((userTo) => ({
    receiver: userTo.value,
    receiverType: MESSAGE_REL_USER_TYPE_TO,
  })),
)

const receiversCc = computed(() =>
  usersCc.value.map((userCc) => ({
    receiver: userCc.value,
    receiverType: MESSAGE_REL_USER_TYPE_CC,
  })),
)

const canSubmitMessage = computed(() => {
  return (
    (usersTo.value.length > 0 || usersCc.value.length > 0) &&
    item.value.title.trim() !== "" &&
    item.value.content.trim() !== ""
  )
})

const isLoading = ref(false)

const onSubmit = async () => {
  if (!canSubmitMessage.value) {
    return
  }
  item.value.receivers = [...receiversTo.value, ...receiversCc.value]
  isLoading.value = true

  try {
    await messageService.create(item.value)
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }

  await router.push({
    name: "MessageList",
    query: route.query,
  })
}

const isLoadingUser = ref(false)
const sendToUser = ref()

onMounted(async () => {
  if (route.query.send_to_user) {
    isLoadingUser.value = true

    try {
      let user = await userService.findById(route.query.send_to_user)
      sendToUser.value = user

      usersTo.value.push({
        name: user.fullName,
        value: user["@id"],
      })

      if (route.query.prefill) {
        const prefill = capitalize(route.query.prefill)

        item.value.title = t(prefill + "Title")
        item.value.content = t(prefill + "Content", [
          user.firstname,
          securityStore.user.firstname,
          securityStore.user.firstname,
        ])
      }
    } catch (error) {
      notification.showErrorNotification(error)
    } finally {
      isLoadingUser.value = false
    }
  }
})
</script>
