<template>
  <CCalendarEventForm
    ref="createForm"
    :values="item"
  />

  <BaseButton
    :label="t('Add')"
    class="ml-auto"
    icon="plus"
    type="primary"
    @click="onClickCreateEvent"
  />
  <Loading :visible="isLoading" />
</template>

<script setup>
import { useStore } from "vuex"
import CCalendarEventForm from "../../components/ccalendarevent/CCalendarEventForm.vue"
import Loading from "../../components/Loading.vue"
import { onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import isEmpty from "lodash/isEmpty"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink.js"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useSecurityStore } from "../../store/securityStore"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"

//const { DateTime } = require("luxon");

const item = ref({})
const store = useStore()
const securityStore = useSecurityStore()
const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const createForm = ref(null)

let id = route.params.id
if (isEmpty(id)) {
  id = route.query.id
}

const isLoading = ref(true)

onMounted(async () => {
  isLoading.value = true

  const response = await store.dispatch("message/load", id)
  const currentUser = securityStore.user
  item.value = await response

  isLoading.value = false

  // Remove unused properties:
  delete item.value["status"]
  delete item.value["msgType"]
  delete item.value["@type"]
  delete item.value["@context"]
  delete item.value["@id"]
  delete item.value["id"]
  delete item.value["firstReceiver"]
  //delete item.value['receivers'];
  delete item.value["sendDate"]

  item.value.parentResourceNodeId = currentUser.resourceNode["id"]
  //item.value['startDate'] = date.now();
  //item.value['endDate'] = new Date();
  //item.value['originalSender'] = item.value['sender'];
  // New sender.
  //item.value['sender'] = currentUser['@id'];

  item.value.resourceLinkListFromEntity = []
  const receivers = [...item.value.receiversTo, ...item.value.receiversCc]
  let itemsAdded = []
  receivers.forEach((receiver) => {
    // Skip current user.
    if (currentUser["@id"] === receiver.receiver["@id"]) {
      return
    }
    item.value.resourceLinkListFromEntity.push({
      uid: receiver.receiver["id"],
      user: { username: receiver.receiver["username"] },
      visibility: RESOURCE_LINK_PUBLISHED,
    })
    itemsAdded.push(receiver.receiver["username"])
  })

  // Sender is not added to the list.
  if (!itemsAdded.includes(item.value["sender"]["username"])) {
    // Set the sender too.
    item.value["resourceLinkListFromEntity"].push({
      uid: item.value["sender"]["id"],
      user: { username: item.value["sender"]["username"] },
      visibility: RESOURCE_LINK_PUBLISHED,
    })
  }

  delete item.value["sender"]
})

const onClickCreateEvent = async () => {
  if (createForm.value.v$.$invalid) {
    return
  }

  isLoading.value = true

  const itemModel = createForm.value.v$.item.$model

  try {
    await store.dispatch("ccalendarevent/create", itemModel)
  } catch (e) {
    isLoading.value = false

    notification.showErrorNotification(e)

    return
  }

  await router.push({ name: "CCalendarEventList" })
}

const notification = useNotification()

watch(
  () => store.state.ccalendarevent.created,
  (created) => {
    notification.showSuccessNotification(t("{resource} created", { resource: created.resourceNode.title }))
  },
)
</script>
