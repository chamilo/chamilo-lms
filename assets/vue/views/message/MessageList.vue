<template>
  <div class="message-list flex flex-col">
    <SectionHeader :title="title">
      <BaseButton
        icon="email-plus"
        only-icon
        type="black"
        :title="t('New message')"
        :aria-label="t('New message')"
        @click="goToCompose"
      />

      <BaseButton
        :disabled="isLoading"
        icon="refresh"
        only-icon
        type="black"
        :title="t('Refresh')"
        :aria-label="t('Refresh')"
        @click="refreshMessages"
      />

      <BaseButton
        :disabled="0 === selectedItems.length || isLoading"
        icon="delete"
        only-icon
        type="black"
        :title="t('Delete')"
        :aria-label="t('Delete')"
        @click="showDlgConfirmDeleteMultiple"
      />

      <BaseButton
        :disabled="0 === selectedItems.length || isLoading"
        icon="multiple-marked"
        only-icon
        popup-identifier="course-messages-list-tmenu"
        type="black"
        :title="t('Mark as')"
        :aria-label="t('Mark as')"
        @click="mToggleMessagesList"
      />

      <BaseMenu
        id="course-messages-list-tmenu"
        ref="mMessageList"
        :model="mItemsMarkAs"
      />
    </SectionHeader>

    <div class="message-list__actions">
      <BaseButton
        :label="t('Inbox')"
        class="w-full md:w-auto"
        icon="inbox"
        type="black"
        :title="t('Inbox')"
        :aria-label="t('Inbox')"
        @click="showInbox"
      />

      <BaseButton
        :label="t('Unread')"
        class="w-full md:w-auto"
        icon="email-unread"
        type="black"
        :title="t('Unread')"
        :aria-label="t('Unread')"
        @click="showUnread"
      />

      <BaseButton
        :label="t('Sent')"
        class="w-full md:w-auto"
        icon="sent"
        type="black"
        :title="t('Sent')"
        :aria-label="t('Sent')"
        @click="showSent"
      />

      <BaseButton
        v-for="tag in tags"
        :key="tag.id"
        :label="tag.tag"
        class="w-full md:w-auto"
        icon="tag-outline"
        type="black"
        :title="tag.tag"
        :aria-label="tag.tag"
        @click="showInboxByTag(tag)"
      />
    </div>

    <div class="hidden md:block overflow-x-auto">
      <BaseTable
        ref="dtMessages"
        v-model:selected-items="selectedItems"
        :is-loading="isLoading"
        :row-class="rowClass"
        :rows="initialRowsPerPage"
        :sort-order="-1"
        :total-items="totalItems"
        :values="items"
        data-key="@id"
        lazy
        sort-field="sendDate"
        @page="onPage"
        @sort="sortingChanged"
      >
        <template #header>
          <form
            class="message-list__searcher-container"
            @submit.prevent="onSearch"
          >
            <InputGroup>
              <InputText
                v-model="searchText"
                :placeholder="t('Search')"
                type="text"
              />
              <BaseButton
                icon="search"
                is-submit
                type="primary"
                :title="t('Search')"
                :aria-label="t('Search')"
              />
              <BaseButton
                icon="close"
                type="primary"
                :title="t('Reset')"
                :aria-label="t('Reset')"
                @click="onResetSearch"
              />
            </InputGroup>
          </form>
        </template>

        <Column selection-mode="multiple" />
        <Column :header="showingInbox ? t('From') : t('To')">
          <template #body="slotProps">
            <BaseAvatarList
              v-if="showingInbox && slotProps.data.sender"
              :users="[slotProps.data.sender]"
            />
            <div
              v-else-if="showingInbox && !slotProps.data.sender"
              v-text="t('No sender')"
            />
            <BaseAvatarList
              v-else-if="!showingInbox"
              :users="mapReceiverMixToUsers(slotProps.data)"
            />
          </template>
        </Column>

        <Column
          :header="t('Title')"
          :sortable="true"
          field="title"
        >
          <template #body="slotProps">
            <BaseAppLink
              :to="{
                name: 'MessageShow',
                query: {
                  id: slotProps.data['@id'],
                  receiverType: showingInbox ? MESSAGE_TYPE_INBOX : MESSAGE_TYPE_SENDER,
                },
              }"
              :class="['text-primary', { 'font-bold': showingInbox && !findMyReceiver(slotProps.data)?.read }]"
            >
              {{ slotProps.data.title }}
            </BaseAppLink>

            <BaseTag
              v-for="tag in findMyReceiver(slotProps.data)?.tags"
              :key="tag.id"
              :label="tag.tag"
              type="info"
            />
          </template>
        </Column>

        <Column
          :header="t('Sent date')"
          :sortable="true"
          class="truncate w-24 md:w-auto"
          field="sendDate"
        >
          <template #body="slotProps">
            {{ abbreviatedDatetime(slotProps.data.sendDate) }}
          </template>
        </Column>

        <Column :header="t('Actions')">
          <template #body="slotProps">
            <BaseButton
              icon="delete"
              size="small"
              type="danger"
              :title="t('Delete')"
              :aria-label="t('Delete')"
              @click="showDlgConfirmDeleteSingle(slotProps)"
            />
          </template>
        </Column>
      </BaseTable>
    </div>

    <!-- List for small screens with pagination -->
    <div class="block md:hidden">
      <ul class="space-y-4">
        <li
          v-for="item in paginatedItems"
          :key="item.id"
          class="bg-white shadow-md rounded-lg p-4"
        >
          <div class="flex items-center space-x-4">
            <BaseAvatarList
              v-if="showingInbox && item.sender"
              :users="[item.sender]"
            />
            <div
              v-else-if="showingInbox && !item.sender"
              class="text-sm text-gray-600"
              v-text="t('No sender')"
            />
            <BaseAvatarList
              v-else
              :users="mapReceiverMixToUsers(item)"
            />
            <div class="flex-1">
              <div
                v-if="showingInbox && item.sender"
                class="font-bold text-lg"
              >
                {{ item.sender.name }}
              </div>
              <div
                v-if="showingInbox && item.sender"
                class="text-sm text-gray-600"
              >
                {{ item.sender.email }}
              </div>
            </div>
          </div>

          <div class="mt-4">
            <div class="text-sm font-bold">{{ t("Title") }}:</div>
            <BaseAppLink
              :to="{
                name: 'MessageShow',
                query: {
                  id: item['@id'],
                  receiverType: showingInbox ? MESSAGE_TYPE_INBOX : MESSAGE_TYPE_SENDER,
                },
              }"
              :class="['text-base text-blue-600', { 'font-bold': showingInbox && !findMyReceiver(item)?.read }]"
            >
              {{ item.title }}
            </BaseAppLink>
          </div>

          <div
            v-if="findMyReceiver(item)?.tags.length"
            class="mt-2"
          >
            <div class="text-sm font-bold">{{ t("Tags") }}:</div>
            <div>
              <BaseTag
                v-for="tag in findMyReceiver(item)?.tags"
                :key="tag.id"
                :label="tag.tag"
                type="info"
              />
            </div>
          </div>

          <div class="mt-2">
            <div class="text-sm font-bold">{{ t("Sent date") }}:</div>
            <div class="text-base text-gray-500">{{ abbreviatedDatetime(item.sendDate) }}</div>
          </div>

          <div class="mt-4 flex space-x-2">
            <BaseButton
              icon="delete"
              size="small"
              type="danger"
              :title="t('Delete')"
              :aria-label="t('Delete')"
              @click="showDlgConfirmDeleteSingle(item)"
            />
          </div>
        </li>
      </ul>

      <div class="flex justify-between items-center mt-4">
        <BaseButton
          :disabled="isPrevDisabled"
          icon="back"
          type="black"
          :title="t('Previous page')"
          :aria-label="t('Previous page')"
          @click="prevPage"
        />
        <span>
          {{ t("Page") }} {{ currentPage }} {{ t("of") }} {{ totalPages }} ({{ totalItems }} {{ t("messages") }})
        </span>
        <BaseButton
          :disabled="isNextDisabled"
          icon="next"
          type="black"
          :title="t('Next page')"
          :aria-label="t('Next page')"
          @click="nextPage"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { useFormatDate } from "../../composables/formatDate"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import BaseAvatarList from "../../components/basecomponents/BaseAvatarList.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTag from "../../components/basecomponents/BaseTag.vue"
import Column from "primevue/column"
import { useConfirm } from "primevue/useconfirm"
import { useQuery } from "@vue/apollo-composable"
import { MESSAGE_TYPE_INBOX, MESSAGE_TYPE_SENDER } from "../../constants/entity/message"
import { GET_USER_MESSAGE_TAGS } from "../../graphql/queries/MessageTag"
import { useNotification } from "../../composables/notification"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"
import { useSecurityStore } from "../../store/securityStore"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import InputGroup from "primevue/inputgroup"
import InputText from "primevue/inputtext"
import messageRelUserService from "../../services/messagereluser"
import { useMessageReceiverFormatter } from "../../composables/message/messageFormatter"
import { usePlatformConfig } from "../../store/platformConfig"

const route = useRoute()
const router = useRouter()
const store = useStore()
const securityStore = useSecurityStore()
const { t } = useI18n()

const confirm = useConfirm()
const notification = useNotification()

const messageRelUserStore = useMessageRelUserStore()

const { abbreviatedDatetime } = useFormatDate()

const { mapReceiverMixToUsers } = useMessageReceiverFormatter()
const platformConfigStore = usePlatformConfig()
const messagingEnabled = computed(() => platformConfigStore.getSetting("message.allow_message_tool") === "true")

const mItemsMarkAs = ref([
  {
    label: t("As read"),
    command: () => {
      const promises = selectedItems.value.map((message) => {
        const myReceiver = findMyReceiver(message)

        if (!myReceiver) {
          return undefined
        }

        myReceiver.read = true

        return messageRelUserService.update(myReceiver["@id"], myReceiver)
      })

      Promise.all(promises)
        .then(() => messageRelUserStore.findUnreadCount())
        .catch((e) => notification.showErrorNotification(e))
        .finally(() => (selectedItems.value = []))
    },
  },
  {
    label: t("As unread"),
    command: async () => {
      const promises = selectedItems.value.map((message) => {
        const myReceiver = findMyReceiver(message)

        if (!myReceiver) {
          return undefined
        }

        myReceiver.read = false

        return messageRelUserService.update(myReceiver["@id"], myReceiver)
      })

      Promise.all(promises)
        .then(() => messageRelUserStore.findUnreadCount())
        .catch((e) => notification.showErrorNotification(e))
        .finally(() => (selectedItems.value = []))
    },
  },
])

const mMessageList = ref(null)

const mToggleMessagesList = (event) => mMessageList.value.toggle(event)

const dtMessages = ref(null)
const initialRowsPerPage = 10

const goToCompose = () => {
  router.push({
    name: "MessageCreate",
    query: route.query,
  })
}

const { result: messageTagsResult } = useQuery(
  GET_USER_MESSAGE_TAGS,
  { user: securityStore.user["@id"] },
  { fetchPolicy: "cache-and-network" },
)

const tags = computed(() => messageTagsResult.value?.messageTags?.edges.map(({ node }) => node) ?? [])

const items = computed(() => store.getters["message/getRecents"])
const isLoading = computed(() => store.getters["message/isLoading"])
const totalItems = computed(() => store.getters["message/getTotalItems"])

const title = ref(null)

const selectedTag = ref(null)
const searchText = ref("")

const selectedItems = ref([])

const rowClass = (data) => {
  const myReceiver = findMyReceiver(data)

  if (!myReceiver) {
    return []
  }

  return [{ "font-semibold": !myReceiver.read }]
}

let fetchPayload = {}

const rows = ref(10)
const currentPage = ref(1)

const totalPages = computed(() => {
  return Math.ceil(totalItems.value / rows.value)
})

const paginatedItems = computed(() => {
  if (!items.value.length) {
    return []
  }

  return items.value
})

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
    fetchPayload.page = currentPage.value
    fetchPayload.itemsPerPage = rows.value
    loadMessages(false)
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
    fetchPayload.page = currentPage.value
    fetchPayload.itemsPerPage = rows.value
    loadMessages(false)
  }
}

const isPrevDisabled = computed(() => currentPage.value === 1)
const isNextDisabled = computed(() => currentPage.value === totalPages.value)

function loadMessages(reset = true) {
  if (reset) {
    store.dispatch("message/resetList")
    dtMessages.value.resetPage()
  }

  fetchPayload.msgType = MESSAGE_TYPE_INBOX
  fetchPayload.status = 0

  if (selectedTag.value) {
    fetchPayload["receivers.tags.tag"] = selectedTag.value.tag
  }

  if (showingInbox.value) {
    fetchPayload["receivers.receiver"] = securityStore.user["@id"]
  } else {
    fetchPayload.sender = securityStore.user["@id"]
  }

  if (searchText.value) {
    fetchPayload.search = searchText.value
  }

  store.dispatch("message/fetchAll", fetchPayload)
}

const showingInbox = ref(false)

function showInbox() {
  showingInbox.value = true
  title.value = t("Inbox")
  selectedTag.value = null

  fetchPayload = {
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
    "receivers.receiver": securityStore.user["@id"],
    "receivers.receiverType": MESSAGE_TYPE_INBOX,
    "exists[receivers.deletedAt]": false,
  }

  loadMessages()
}

function showInboxByTag(tag) {
  showingInbox.value = true
  title.value = tag.tag
  selectedTag.value = tag

  fetchPayload = {
    "order[sendDate]": "desc",
    "receivers.receiver": securityStore.user["@id"],
    itemsPerPage: initialRowsPerPage,
    page: 1,
    "receivers.receiverType": MESSAGE_TYPE_INBOX,
    "exists[receivers.deletedAt]": false,
  }

  loadMessages()
}

function showUnread() {
  showingInbox.value = true
  title.value = t("Unread")
  selectedTag.value = null

  fetchPayload = {
    "order[sendDate]": "desc",
    "receivers.receiver": securityStore.user["@id"],
    "receivers.read": false,
    itemsPerPage: initialRowsPerPage,
    page: 1,
    "receivers.receiverType": MESSAGE_TYPE_INBOX,
    "exists[receivers.deletedAt]": false,
  }

  loadMessages()
}

function showSent() {
  showingInbox.value = false
  title.value = t("Sent")
  selectedTag.value = null

  fetchPayload = {
    sender: securityStore.user["@id"],
    "receivers.receiverType": MESSAGE_TYPE_SENDER,
    "exists[receivers.deletedAt]": false,
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function refreshMessages() {
  fetchPayload.itemsPerPage = initialRowsPerPage
  fetchPayload.page = 1
  fetchPayload["exists[receivers.deletedAt]"] = false

  loadMessages()
}

function onPage(event) {
  delete fetchPayload["order[title]"]
  delete fetchPayload["order[sendDate]"]

  fetchPayload.page = event.page + 1
  fetchPayload.itemsPerPage = event.rows
  fetchPayload[`order[${event.sortField}]`] = event.sortOrder === -1 ? "desc" : "asc"

  loadMessages(false)
}

function sortingChanged(event) {
  delete fetchPayload["order[title]"]
  delete fetchPayload["order[sendDate]"]

  fetchPayload[`order[${event.sortField}]`] = event.sortOrder === -1 ? "desc" : "asc"

  loadMessages(true)
}

function findMyReceiver(message, receiverType = showingInbox.value ? MESSAGE_TYPE_INBOX : MESSAGE_TYPE_SENDER) {
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
    loadMessages()
  } catch (e) {
    notification.showErrorNotification(t("Error deleting message"))
  }
}

function showDlgConfirmDeleteSingle(dataOrItem) {
  const item = dataOrItem.data || dataOrItem

  confirm.require({
    header: t("Confirmation"),
    message: t("Are you sure you want to delete {0}?", [item.title]),
    acceptLabel: t("Yes"),
    rejectLabel: t("No"),
    accept: async () => {
      await deleteMessage(item)
    },
  })
}

function showDlgConfirmDeleteMultiple() {
  confirm.require({
    header: t("Confirmation"),
    message: t("Are you sure you want to delete the selected items?"),
    acceptLabel: t("Yes"),
    rejectLabel: t("No"),
    accept: async () => {
      for (const message of selectedItems.value) {
        await deleteMessage(message)
      }
      selectedItems.value = []
      loadMessages()
    },
  })
}

onMounted(() => {
  if (!messagingEnabled.value) {
    router.replace("/social")
    return
  }
  showInbox()
})

function onSearch() {
  fetchPayload = {
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function onResetSearch() {
  searchText.value = ""

  fetchPayload = {
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

let onMessageRead

onMounted(() => {
  onMessageRead = (ev) => {
    const { iri, receiverId, receiverType } = ev.detail || {}
    const msg = items.value?.find?.((m) => m["@id"] === iri)
    if (!msg) return

    const receivers = [...msg.receiversTo, ...msg.receiversCc, ...msg.receiversSender]
    const mine = receivers.find((r) => r.receiver?.["@id"] === receiverId && r.receiverType === receiverType)
    if (mine) {
      mine.read = true
    }
  }
  window.addEventListener("message:read", onMessageRead)
})

onBeforeUnmount(() => {
  if (onMessageRead) {
    window.removeEventListener("message:read", onMessageRead)
  }
})
</script>
