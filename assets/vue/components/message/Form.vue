<template>
  <div class="flex flex-col md:flex-row md:space-x-4">
    <div class="flex-1">
      <BaseInputText
        id="item_title"
        v-model="messagePayload.title"
        :label="t('Title')"
      />

      <BaseAutocomplete
        id="to"
        v-model="usersTo"
        :label="t('To')"
        :search="asyncFind"
        is-multiple
      />

      <BaseAutocomplete
        id="cc"
        v-model="usersCc"
        :label="t('Cc')"
        :search="asyncFind"
        is-multiple
      />

      <BaseTinyEditor
        v-model="messagePayload.content"
        :full-page="false"
        editor-id="message"
        required
      />

      <slot></slot>

      <BaseButton
        :disabled="!canSubmitMessage"
        :label="t('Send')"
        icon="plus"
        type="primary"
        class="mt-4"
        @click="onSubmit"
      />
    </div>

    <div class="mt-4 md:mt-0 md:w-1/3">
      <p class="text-h6">
        <BaseIcon icon="attachment" />
        {{ t("Attachments") }}
      </p>

      <ul
        v-if="resourceFileList && resourceFileList.length > 0"
        class="space-y-2"
      >
        <li
          v-for="(resourceFile, index) in resourceFileList"
          :key="index"
          class="text-body-2"
          v-text="resourceFile.originalName"
        />
      </ul>

      <BaseUploader
        field-name="file"
        :endpoint="resourceFileService.endpoint"
        @upload-success="onUploadSuccess"
        @upload="onUpload"
        @complete="onUploadComplete"
      />
    </div>
  </div>
</template>

<script setup>
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import { computed, ref, watch } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseUploader from "../basecomponents/BaseUploader.vue"
import resourceFileService from "../../services/resourceFileService"
import BaseAutocomplete from "../basecomponents/BaseAutocomplete.vue"
import userService from "../../services/userService"
import { MESSAGE_TYPE_INBOX } from "../../constants/entity/message"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useSecurityStore } from "../../store/securityStore"
import { MESSAGE_REL_USER_TYPE_CC, MESSAGE_REL_USER_TYPE_TO } from "../../constants/entity/messagereluser"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const props = defineProps({
  title: {
    type: String,
    required: false,
    default: "",
  },
  receiversTo: {
    type: Array,
    required: false,
    default: () => [],
  },
  receiversCc: {
    type: Array,
    required: false,
    default: () => [],
  },
  content: {
    type: String,
    required: false,
    default: "",
  },
  attachments: {
    type: Array,
    required: false,
    default: () => [],
  },
  msgType: {
    type: Number,
    required: false,
    default: MESSAGE_TYPE_INBOX,
  },
})

const emit = defineEmits(["submit"])

const { t } = useI18n()

const securityStore = useSecurityStore()

const messagePayload = ref({
  sender: securityStore.user["@id"],
  msgType: MESSAGE_TYPE_INBOX,
  title: "",
  content: "",
  receivers: [],
  attachments: [],
})

const usersTo = ref([])
const usersCc = ref([])
const resourceFileList = ref([])

watch(
  () => props.title,
  (newTitle) => (messagePayload.value.title = newTitle),
)

watch(
  () => props.content,
  (newContent) => (messagePayload.value.content = newContent),
)

watch(
  () => props.msgType,
  (newMsgType) => (messagePayload.value.msgType = newMsgType),
)

watch(
  () => props.receiversTo,
  (newReceiversTo) => {
    usersTo.value.push(
      ...newReceiversTo.map((messageRelUser) => ({
        name: messageRelUser.fullName,
        value: messageRelUser["@id"],
      })),
    )
  },
)

watch(
  () => props.receiversCc,
  (newReceiversCc) => {
    usersTo.value.push(
      ...newReceiversCc.map((messageRelUser) => ({
        name: messageRelUser.fullName,
        value: messageRelUser["@id"],
      })),
    )
  },
)

async function asyncFind(query) {
  const { items } = await userService.findBySearchTerm(query)
  return items
    .filter((member) => member.active === 1)
    .map((member) => ({
      name: member.fullName,
      value: member["@id"],
    }))
}

function onUploadSuccess({ response }) {
  resourceFileList.value.push(response)
}

const isUploading = ref(false)

function onUpload() {
  isUploading.value = true
}

function onUploadComplete() {
  isUploading.value = false
}

const canSubmitMessage = computed(() => {
  return (
    (usersTo.value.length > 0 || usersCc.value.length > 0) &&
    messagePayload.value.title.trim() !== "" &&
    messagePayload.value.content.trim() !== "" &&
    !isUploading.value
  )
})

function onSubmit() {
  messagePayload.value.receivers = [
    ...usersTo.value.map((userTo) => ({
      receiver: userTo.value,
      receiverType: MESSAGE_REL_USER_TYPE_TO,
    })),
    ...usersCc.value.map((userCc) => ({
      receiver: userCc.value,
      receiverType: MESSAGE_REL_USER_TYPE_CC,
    })),
  ]

  messagePayload.value.attachments = resourceFileList.value.map((resourceFile) => ({
    resourceFileToAttach: resourceFile["@id"],
  }))

  emit("submit", messagePayload.value)
}
</script>
