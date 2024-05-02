<template>
  <!--        :handle-submit="onSendMessageForm"-->
  <MessageForm
    v-model:attachments="attachments"
    :values="item"
  >
    <!--          @input="v$.item.receiversTo.$touch()"-->

    <div
      v-if="sendToUser"
      class="field"
    >
      <span
        class="mr-2"
        v-t="'To'"
      />
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
import messageService from "../../services/message"
import messageAttachmentService from "../../services/messageattachment"
import MessageCommunicationParty from "./MessageCommunicationParty.vue"

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

const browser = (callback, value, meta) => {
  let url = "/resources/personal_files/"

  if (meta.filetype === "image") {
    url = url + "&type=images"
  } else {
    url = url + "&type=files"
  }

  window.addEventListener("message", function (event) {
    var data = event.data
    if (data.url) {
      url = data.url
      console.log(meta) // {filetype: "image", fieldname: "src"}
      callback(url)
    }
  })

  tinymce.activeEditor.windowManager.openUrl(
    {
      url: url,
      title: "file manager",
    },
    {
      oninsert: function (file, fm) {
        var url, reg, info

        url = fm.convAbsUrl(file.url)

        info = file.name + " (" + fm.formatSize(file.size) + ")"

        if (meta.filetype === "file") {
          callback(url, { text: info, title: info })
        }

        if (meta.filetype === "image") {
          callback(url, { alt: info })
        }

        if (meta.filetype === "media") {
          callback(url)
        }
      },
    },
  )
}

const isLoadingUser = ref(false)
const sendToUser = ref()

onMounted(async () => {
  if (route.query.send_to_user) {
    isLoadingUser.value = true

    try {
      let user = await userService.find(route.query.send_to_user)
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
