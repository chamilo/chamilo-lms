<template>
  <div v-if="item">
    <div class="flex gap-4 items-center">
      <h2
        class="mr-auto"
        v-text="item.title"
      />

      <BaseButton
        :disabled="isLoading"
        icon="reply"
        only-icon
        type="black"
        @click="reply"
      />

      <BaseButton
        :disabled="isLoading"
        icon="reply-all"
        only-icon
        type="black"
        @click="replyAll"
      />

      <BaseButton
        icon="calendar-plus"
        only-icon
        type="black"
        @click="createEvent"
      />

      <BaseButton
        icon="delete"
        only-icon
        type="black"
        @click="confirmDelete"
      />
    </div>

    <hr />

    <div class="flex justify-end gap-2">
      <div v-if="myReceiver">
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
        v-if="item.sender['@id'] !== user['@id']"
        id="search-tags"
        v-model="foundTag"
        :label="t('Tags')"
        :search="onSearchTags"
        option-label="tag"
        @item-select="onItemSelect"
      />
    </div>

    <div>
      {{ t("From") }}

      <BaseChip
        :value="item.sender"
        image-field="illustrationUrl"
        label-field="username"
      />
    </div>

    <div>
      {{ t("To") }}

      <BaseChip
        v-for="receiver in item.receiversTo"
        :key="receiver.receiver.id"
        :value="receiver.receiver"
        image-field="illustrationUrl"
        label-field="username"
      />
    </div>

    <div>
      {{ t("Cc") }}

      <BaseChip
        v-for="receiver in item.receiversCc"
        :key="receiver.receiver.id"
        :value="receiver.receiver"
        image-field="illustrationUrl"
        label-field="username"
      />
    </div>

    <hr />

    <p v-text="relativeDatetime(item.sendDate)" />

    <div v-html="item.content" />

    <q-card>
      <q-card-section v-if="item.attachments && item.attachments.length > 0">
        <p class="my-3">{{ item.attachments.length }} {{ $t("Attachments") }}</p>

        <div class="q-gutter-y-sm q-gutter-x-sm row">
          <div
            v-for="(attachment, index) in item.attachments"
            :key="index"
          >
            <div v-if="attachment.resourceNode.resourceFile.audio">
              <audio controls>
                <source :src="attachment.downloadUrl" />
              </audio>
            </div>

            <q-btn
              v-else
              :href="attachment.downloadUrl"
              flat
              icon="attachment"
              type="a"
            >
              {{ attachment.resourceNode.resourceFile.originalName }}
            </q-btn>
          </div>
        </div>
      </q-card-section>
    </q-card>
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { useStore } from "vuex"
import Loading from "../../components/Loading.vue"
import { computed, ref } from "vue"
import isEmpty from "lodash/isEmpty"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useConfirm } from "primevue/useconfirm"
import { useI18n } from "vue-i18n"
import BaseChip from "../../components/basecomponents/BaseChip.vue"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useMessageRelUserStore } from "../../store/messageRelUserStore"

const confirm = useConfirm()
const { t } = useI18n()

const isLoadingSelect = ref(false)
const store = useStore()
const user = store.getters["security/getUser"]
//const find = store.getters["message/find"];
const route = useRoute()
const router = useRouter()
const messageRelUserStore = useMessageRelUserStore()

const { relativeDatetime } = useFormatDate()

let id = route.params.id
if (isEmpty(id)) {
  id = route.query.id
}

const isLoading = computed(() => store.state.message.isLoading)

const item = ref(null)
const myReceiver = ref(null)

store.dispatch("message/load", id).then((responseItem) => {
  item.value = responseItem

  myReceiver.value = [...responseItem.receiversTo, ...responseItem.receiversCc].find(
    ({ receiver }) => receiver["@id"] === user["@id"],
  )

  // Change to read.
  if (myReceiver.value && false === myReceiver.value.read) {
    store
      .dispatch("messagereluser/update", {
        "@id": myReceiver.value["@id"],
        read: true,
      })
      .then(() => messageRelUserStore.findUnreadCount())
  }
})

function confirmDelete() {
  confirm.require({
    header: t("Confirmation"),
    message: t(`Are you sure you want to delete "${item.value.title}"?`),
    accept: async () => {
      await store.dispatch("message/del", item)

      await router.push({
        name: "MessageList",
      })
    },
  })
}

function getTagIndex(tag) {
  return myReceiver.value.tags.findIndex((receiverTag) => receiverTag["@id"] === tag["@id"])
}

function mapTagsToIds() {
  return myReceiver.value.tags.map((receiverTag) => receiverTag["@id"])
}

async function onRemoveTagFromMessage(tag) {
  const index = getTagIndex(tag)

  if (index < 0) {
    return
  }

  myReceiver.value.tags.splice(index, 1)

  const newTagIds = mapTagsToIds()

  await store.dispatch("messagereluser/update", {
    "@id": myReceiver.value["@id"],
    tags: newTagIds,
  })
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

function onSearchTags(query) {
  isLoadingSelect.value = true

  return axios
    .get(ENTRYPOINT + "message_tags", {
      params: {
        user: user["@id"],
        tag: query,
      },
    })
    .then((response) => {
      isLoadingSelect.value = false

      return response.data["hydra:member"]
    })
    .catch(function (error) {
      isLoadingSelect.value = false
      console.log(error)
    })
}

async function onItemSelect({ value }) {
  const newTag = computed(() => store.state.messagetag.created)

  if (!value["@id"]) {
    try {
      await store.dispatch("messagetag/create", {
        user: user["@id"],
        tag: value.tag,
      })
    } catch (e) {
      return
    }
  } else {
    const existingIndex = getTagIndex(value["@id"]) >= 0

    if (existingIndex) {
      return
    }
  }

  foundTag.value = ""

  if (myReceiver.value && newTag.value) {
    myReceiver.value.tags.push(newTag.value)

    const newTagIds = mapTagsToIds()

    await store.dispatch("messagereluser/update", {
      "@id": myReceiver.value["@id"],
      tags: newTagIds,
    })
  }
}
</script>