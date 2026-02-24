<template>
  <div v-if="item">
    <nav class="mb-3 flex items-center gap-2 text-sm text-gray-500">
      <BaseAppLink
        :to="{ name: 'MessageList', query: listQuery }"
        class="text-primary hover:underline"
      >
        {{ t("Messages") }}
      </BaseAppLink>
      <span class="text-gray-300">/</span>
      <span class="text-gray-700 line-clamp-1">{{ item.title }}</span>
    </nav>

    <SectionHeader :title="item.title">
      <BaseButton
        icon="back"
        only-icon
        type="black"
        :title="t('Back')"
        :aria-label="t('Back')"
        @click="goBackToList"
      />

      <BaseButton
        :disabled="isLoading"
        icon="reply"
        only-icon
        type="black"
        :title="t('Reply to this message')"
        :aria-label="t('Reply to this message')"
        @click="reply"
      />

      <BaseButton
        :disabled="isLoading"
        icon="reply-all"
        only-icon
        type="black"
        :title="t('Reply to all')"
        :aria-label="t('Reply to all')"
        @click="replyAll"
      />

      <BaseButton
        icon="calendar-plus"
        only-icon
        type="black"
        :title="t('Add to calendar')"
        :aria-label="t('Add to calendar')"
        @click="createEvent"
      />

      <BaseButton
        icon="delete"
        only-icon
        type="black"
        :title="t('Delete')"
        :aria-label="t('Delete')"
        @click="confirmDelete"
      />
    </SectionHeader>

    <div
      v-if="myReceiver"
      class="message-show__tags-container"
    >
      <div class="message-show__tags">
        <BaseChip
          v-for="tag in myReceiver.tags"
          :key="tag['@id']"
          :value="tag"
          is-removable
          label-field="tag"
          @remove="onRemoveTagFromMessage(tag)"
        />
      </div>

      <BaseAutocomplete
        id="search-tags"
        v-model="foundTag"
        :label="t('Tags')"
        :search="onSearchTags"
        class="message-show__tag-searcher"
        option-label="tag"
        @item-select="onItemSelect"
      />
    </div>

    <div class="field space-x-4">
      <span>{{ t("From") }}</span>
      <BaseAvatarList
        v-if="item.sender"
        :users="[item.sender]"
      />
      <span
        v-else
        v-text="t('No sender')"
      />
    </div>

    <div class="field space-x-4">
      <span>{{ t("To") }}</span>
      <BaseAvatarList
        :short-several="false"
        :users="mapReceiverListToUsers(item.receiversTo)"
      />
    </div>

    <div class="field space-x-4">
      <span>{{ t("Cc") }}</span>
      <BaseAvatarList
        :short-several="false"
        :users="mapReceiverListToUsers(item.receiversCc)"
      />
    </div>

    <hr />

    <p v-text="abbreviatedDatetime(item.sendDate)" />

    <div v-html="item.content" />

    <template v-if="item.attachments && item.attachments.length > 0">
      <BaseCard>
        <template #header>
          <p class="m-3">{{ item.attachments.length }} {{ $t("Attachments") }}</p>
        </template>

        <ul class="space-y-2">
          <li
            v-for="(attachment, index) in item.attachments"
            :key="index"
          >
            <audio
              v-if="attachment.resourceNode.firstResourceFile.audio"
              controls
            >
              <source :src="attachment.downloadUrl" />
            </audio>

            <a
              v-else
              :href="attachment.downloadUrl"
              class="btn btn--plain"
            >
              <BaseIcon icon="attachment" />
              {{ attachment.resourceNode.firstResourceFile.originalName }}
            </a>
          </li>
        </ul>
      </BaseCard>
    </template>
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import Loading from "../../components/Loading.vue"
import { computed, ref } from "vue"
import isEmpty from "lodash/isEmpty"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useConfirm } from "primevue/useconfirm"
import { useI18n } from "vue-i18n"
import BaseChip from "../../components/basecomponents/BaseChip.vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"
import { searchUserTags } from "../../services/messageTagService"
import messageRelUserService from "../../services/messagereluser"
import { useSecurityStore } from "../../store/securityStore"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseAvatarList from "../../components/basecomponents/BaseAvatarList.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseAppLink from "../../components/basecomponents/BaseAppLink.vue"
import { useNotification } from "../../composables/notification"
import { useMessageReceiverFormatter } from "../../composables/message/messageFormatter"
import { MESSAGE_TYPE_INBOX } from "../../constants/entity/message"

const confirm = useConfirm()
const { t } = useI18n()

const isLoadingSelect = ref(false)
const store = useStore()
const securityStore = useSecurityStore()
const route = useRoute()
const router = useRouter()
const messageRelUserStore = useMessageRelUserStore()

const { abbreviatedDatetime } = useFormatDate()

const { mapReceiverListToUsers } = useMessageReceiverFormatter()

let id = route.params.id
if (isEmpty(id)) {
  id = route.query.id
}

const isLoading = computed(() => store.state.message.isLoading)

const item = ref(null)
const myReceiver = ref(null)
const notification = useNotification()

const listQuery = computed(() => {
  const q = { ...route.query }
  // Keep navigation context, but drop message-specific params.
  delete q.id
  delete q.receiverType
  delete q.all
  delete q.send_to_user
  delete q.prefill
  return q
})

function goBackToList() {
  router.push({ name: "MessageList", query: listQuery.value })
}

const receiverType = route.query.receiverType ? parseInt(route.query.receiverType) : MESSAGE_TYPE_INBOX
store.dispatch("message/load", id).then((responseItem) => {
  item.value = responseItem

  const rawRt = route.query.receiverType ? Number(route.query.receiverType) : undefined
  myReceiver.value = rawRt ? findMyReceiver(responseItem, rawRt) : findMyReceiver(responseItem, MESSAGE_TYPE_INBOX)

  if (!myReceiver.value) {
    const all = [...responseItem.receiversTo, ...responseItem.receiversCc, ...responseItem.receiversSender]
    myReceiver.value = all.find(({ receiver }) => receiver["@id"] === securityStore.user["@id"])
  }

  if (myReceiver.value && !myReceiver.value.read) {
    messageRelUserService.update(myReceiver.value["@id"], { read: true }).then(() => {
      messageRelUserStore.findUnreadCount()
      window.dispatchEvent(
        new CustomEvent("message:read", {
          detail: {
            iri: responseItem["@id"],
            receiverId: myReceiver.value.receiver["@id"],
            receiverType: myReceiver.value.receiverType,
          },
        }),
      )
    })
  }
})

function findMyReceiver(message, receiverType) {
  const receivers = [...message.receiversTo, ...message.receiversCc, ...message.receiversSender]

  return receivers.find(({ receiver, receiverType: type }) => {
    const isSelf = receiver["@id"] === securityStore.user["@id"]
    return isSelf && type === receiverType
  })
}

async function deleteMessage(message) {
  try {
    const myReceiver = findMyReceiver(message)

    if (myReceiver) {
      await store.dispatch("messagereluser/del", myReceiver)

      notification.showSuccessNotification(t("Message deleted"))
    }

    await messageRelUserStore.findUnreadCount()
    await router.push({ name: "MessageList", query: listQuery.value })
  } catch (e) {
    notification.showErrorNotification(t("Error deleting message"))
  }
}

function confirmDelete() {
  confirm.require({
    header: t("Confirmation"),
    message: t(`Are you sure you want to delete {0}?`, [item.value.title]),
    accept: async () => {
      await deleteMessage(item.value)
    },
  })
}

function getTagIndex(tag) {
  return myReceiver.value.tags.findIndex((receiverTag) => receiverTag["@id"] === tag["@id"])
}

function onRemoveTagFromMessage(tag) {
  const index = getTagIndex(tag)

  if (index < 0) {
    return
  }

  myReceiver.value.tags.splice(index, 1)

  messageRelUserService
    .update(myReceiver.value["@id"], {
      tags: myReceiver.value.tags,
    })
    .then(({ tags: newTagList }) => (myReceiver.value.tags = newTagList))
    .catch((e) => console.log(e))
}

function reply() {
  router.push({ name: "MessageReply", query: { ...route.query } })
}

function replyAll() {
  router.push({ name: `MessageReply`, query: { ...route.query, all: 1 } })
}

function createEvent() {
  let params = route.query
  router.push({ name: "CCalendarEventCreate", query: params })
}

const foundTag = ref("")

async function onSearchTags(query) {
  isLoadingSelect.value = true

  const items = await searchUserTags(query)

  if (!items.length) {
    items.push({ tag: query })
  }

  isLoadingSelect.value = false

  return items
}

async function onItemSelect({ value }) {
  if (!value["@id"]) {
    myReceiver.value.tags.push({
      user: securityStore.user["@id"],
      tag: value.tag,
    })
  } else {
    const existingIndex = getTagIndex(value) >= 0

    if (existingIndex) {
      return
    }

    myReceiver.value.tags.push(value)
  }

  messageRelUserService
    .update(myReceiver.value["@id"], {
      tags: myReceiver.value.tags,
    })
    .then(({ tags: newTagList }) => (myReceiver.value.tags = newTagList))
    .catch((e) => console.log(e))

  foundTag.value = ""
}
</script>
