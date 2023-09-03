<template>
  <div class="flex gap-4 items-center">
    <h2
      class="mr-auto"
      v-text="title"
    />

    <BaseButton
      icon="email-plus"
      only-icon
      type="black"
      @click="goToCompose"
    />

    <BaseButton
      :disabled="isLoading"
      icon="refresh"
      only-icon
      type="black"
      @click="refreshMessages"
    />

    <BaseButton
      :disabled="0 === selectedItems.length || isLoading"
      icon="delete"
      only-icon
      type="black"
      @click="showDlgConfirmDeleteMultiple"
    />

    <BaseButton
      :disabled="0 === selectedItems.length || isLoading"
      icon="multiple-marked"
      only-icon
      popup-identifier="course-messages-list-tmenu"
      type="black"
      @click="mToggleMessagesList"
    />

    <BaseMenu
      id="course-messages-list-tmenu"
      ref="mMessageList"
      :model="mItemsMarkAs"
    />
  </div>

  <hr />

  <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div class="space-y-4">
      <div class="flex md:flex-col gap-2">
        <BaseButton
          :label="t('Inbox')"
          icon="inbox"
          type="black"
          @click="showInbox"
        />

        <BaseButton
          :label="t('Unread')"
          icon="email-unread"
          type="black"
          @click="showUnread"
        />

        <BaseButton
          :label="t('Sent')"
          icon="sent"
          type="black"
          @click="showSent"
        />

        <BaseButton
          v-for="tag in tags"
          :key="tag.id"
          :label="tag.tag"
          icon="tag-outline"
          type="black"
          @click="showInboxByTag(tag)"
        />
      </div>
    </div>

    <div class="md:col-span-4">
      <DataTable
        ref="dtMessages"
        v-model:selection="selectedItems"
        :loading="isLoading"
        :row-class="rowClass"
        :rows="initialRowsPerPage"
        :rows-per-page-options="[10, 20, 50]"
        :total-records="totalItems"
        :value="items"
        current-page-report-template="{first} to {last} of {totalRecords}"
        data-key="@id"
        lazy
        paginator
        paginator-template="RowsPerPageDropdown FirstPageLink PrevPageLink CurrentPageReport NextPageLink LastPageLink"
        responsive-layout="scroll"
        sort-by="sendDate"
        @page="onPage($event)"
        @row-click="onRowClick"
      >
        <Column selection-mode="multiple" />
        <Column :header="t('From')">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <BaseUserAvatar :image-url="slotProps.data.sender.illustrationUrl" />

              {{ slotProps.data.sender.username }}
            </div>
          </template>
        </Column>
        <Column :header="t('Title')">
          <template #body="slotProps">
            <div class="flex gap-2 pb-2">
              {{ slotProps.data.title }}
            </div>

            <BaseTag
              v-for="tag in findMyReceiver(slotProps.data)?.tags"
              :key="tag.id"
              :label="tag.tag"
              type="info"
            />
          </template>
        </Column>
        <Column :header="t('Send date')">
          <template #body="slotProps">
            {{ relativeDatetime(slotProps.data.sendDate) }}
          </template>
        </Column>
        <Column :header="t('Actions')">
          <template #body="slotProps">
            <BaseButton
              icon="delete"
              size="small"
              type="danger"
              @click="showDlgConfirmDeleteSingle(slotProps)"
            />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useStore } from "vuex"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { useFormatDate } from "../../composables/formatDate"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseMenu from "../../components/basecomponents/BaseMenu.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import BaseTag from "../../components/basecomponents/BaseTag.vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import { useConfirm } from "primevue/useconfirm"
import { useQuery } from "@vue/apollo-composable"
import { MESSAGE_STATUS_DELETED, MESSAGE_TYPE_INBOX } from "../../components/message/constants"
import { GET_USER_MESSAGE_TAGS } from "../../graphql/queries/MessageTag"
import { useNotification } from "../../composables/notification"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"

const route = useRoute()
const router = useRouter()
const store = useStore()
const { t } = useI18n()

const confirm = useConfirm()
const notification = useNotification()

const messageRelUserStore = useMessageRelUserStore()

const { relativeDatetime } = useFormatDate()

const user = computed(() => store.getters["security/getUser"])

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

        return store.dispatch("messagereluser/update", myReceiver)
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

        return store.dispatch("messagereluser/update", myReceiver)
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
  { user: user.value["@id"] },
  { fetchPolicy: "cache-and-network" },
)

const tags = computed(() => messageTagsResult.value?.messageTags?.edges.map(({ node }) => node) ?? [])

const items = computed(() => store.getters["message/getRecents"])
const isLoading = computed(() => store.getters["message/isLoading"])
const totalItems = computed(() => store.getters["message/getTotalItems"])

const title = ref(null)

const selectedItems = ref([])

const rowClass = (data) => {
  const myReceiver = findMyReceiver(data)

  if (!myReceiver) {
    return []
  }

  return [{ "font-semibold": !myReceiver.read }]
}

let fetchPayload = {
  "order[sendDate]": "desc",
  itemsPerPage: initialRowsPerPage,
  page: 1,
}

function loadMessages(reset = true) {
  if (reset) {
    store.dispatch("message/resetList")
    dtMessages.value.resetPage()
  }

  store.dispatch("message/fetchAll", fetchPayload)
}

function showInbox() {
  title.value = t("Inbox")

  fetchPayload = {
    msgType: MESSAGE_TYPE_INBOX,
    "receivers.receiver": user.value["@id"],
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function showInboxByTag(tag) {
  title.value = tag.tag

  fetchPayload = {
    msgType: MESSAGE_TYPE_INBOX,
    "receivers.receiver": user.value["@id"],
    "receivers.tags.tag": tag.tag,
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function showUnread() {
  title.value = t("Unread")

  fetchPayload = {
    msgType: MESSAGE_TYPE_INBOX,
    "receivers.receiver": user.value["@id"],
    "order[sendDate]": "desc",
    "receivers.read": false,
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function showSent() {
  title.value = t("Sent")

  fetchPayload = {
    msgType: MESSAGE_TYPE_INBOX,
    sender: user.value["@id"],
    "order[sendDate]": "desc",
    itemsPerPage: initialRowsPerPage,
    page: 1,
  }

  loadMessages()
}

function refreshMessages() {
  fetchPayload.itemsPerPage = initialRowsPerPage
  fetchPayload.page = 1

  loadMessages()
}

function onPage(event) {
  fetchPayload.page = event.page + 1
  fetchPayload.itemsPerPage = event.rows

  loadMessages(false)
}

function onRowClick({ data }) {
  router.push({
    name: "MessageShow",
    query: {
      id: data["@id"],
    },
  })
}

function findMyReceiver(message) {
  const receivers = [...message.receiversTo, ...message.receiversCc]

  return receivers.find(({ receiver }) => receiver["@id"] === user.value["@id"])
}

async function deleteMessage(message) {
  if (message.sender["@id"] === user.value["@id"]) {
    message.status = MESSAGE_STATUS_DELETED

    await store.dispatch("message/update", message)
  } else {
    const myReceiver = findMyReceiver(message)

    if (myReceiver) {
      await store.dispatch("messagereluser/del", myReceiver)
    }
  }
}

function showDlgConfirmDeleteSingle({ data }) {
  confirm.require({
    header: t("Confirmation"),
    message: t(`Are you sure you want to delete "${data.title}"?`),
    accept: async () => {
      await deleteMessage(data)

      loadMessages()

      notification.showSuccessNotification(t("Message deleted"))
    },
  })
}

function showDlgConfirmDeleteMultiple() {
  confirm.require({
    header: t("Confirmation"),
    message: t("Are you sure you want to delete the selected items?"),
    accept: async () => {
      for (const message of selectedItems.value) {
        await deleteMessage(message)
      }

      loadMessages()

      notification.showSuccessNotification(t("Messages deleted"))

      selectedItems.value = []
    },
  })
}

onMounted(() => {
  showInbox()
})
</script>