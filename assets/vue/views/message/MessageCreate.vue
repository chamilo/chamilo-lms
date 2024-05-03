<template>
  <MessageForm
    v-model:attachments="attachments"
    :values="item"
  >
    <div
      v-if="sendToUser"
      class="field"
    >
      <span v-t="'To'" />
      <BaseUserAvatar
        :image-url="sendToUser.illustrationUrl"
        :alt="t('Picture')"
      />
      <span v-text="sendToUser.fullName" />
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
      icon="plus"
      type="primary"
      @click="onSubmit"
    />
  </MessageForm>
  <Loading :visible="isLoading || isLoadingUser" />
</template>

<script setup>
import MessageForm from "../../components/message/Form.vue"
import Loading from "../../components/Loading.vue"
import { computed, ref } from "vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { MESSAGE_TYPE_INBOX } from "../../components/message/constants"
import userService from "../../services/userService"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useNotification } from "../../composables/notification"
import { capitalize } from "lodash"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useSecurityStore } from "../../store/securityStore"
import messageService from "../../services/message"
import messageAttachmentService from "../../services/messageattachment"

const securityStore = useSecurityStore()
const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const notification = useNotification()

const asyncFind = async (query) => {
  const { items } = await userService.findByUsername(query)

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
    receiverType: 1,
  })),
)

const receiversCc = computed(() =>
  usersCc.value.map((userCc) => ({
    receiver: userCc.value,
    receiverType: 2,
  })),
)

const isLoading = ref(false)

const onSubmit = async () => {
  item.value.receivers = [...receiversTo.value, ...receiversCc.value]
  isLoading.value = true

  try {
    const message = await messageService.create(item.value)
    console.log(message)
    const json_message = await message.json()
    console.log(json_message)
    if (attachments.value.length > 0) {
      for (const attachment of attachments.value) {
        await messageAttachmentService.createWithFormData({
          messageId: json_message.id,
          file: attachment,
        })
      }
    }
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

if (route.query.send_to_user) {
  isLoadingUser.value = true

  userService
    .find("/api/users/" + parseInt(route.query.send_to_user))
    .then((user) => {
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
    })
    .catch((e) => notification.showErrorNotification(e))
    .finally(() => (isLoadingUser.value = false))
}
</script>
