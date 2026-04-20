<template>
  <CCalendarEventForm
    ref="createForm"
    :values="item"
    :is-global="effectiveIsGlobal"
    :allow-career-promotion-fields="effectiveAllowCareerPromotionFields"
    :career-options="careerOptions"
    :promotion-options="promotionOptions"
  />

  <BaseButton
    :label="t('Add')"
    class="ml-auto"
    icon="plus"
    type="success"
    @click="onClickCreateEvent"
  />
  <Loading :visible="isLoading" />
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex"
import isEmpty from "lodash/isEmpty"
import CCalendarEventForm from "../../components/ccalendarevent/CCalendarEventForm.vue"
import Loading from "../../components/Loading.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink.js"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import baseService from "../../services/baseService"

const item = ref({})
const careerOptions = ref([])
const promotionOptions = ref([])

const store = useStore()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const route = useRoute()
const router = useRouter()
const notification = useNotification()
const { t } = useI18n()

const createForm = ref(null)
const isLoading = ref(true)

const routeIsGlobalContext = computed(() => {
  return "global" === String(route.query.type || "")
})

const effectiveIsGlobal = computed(() => {
  return routeIsGlobalContext.value
})

const effectiveAllowCareerPromotionFields = computed(() => {
  return effectiveIsGlobal.value && "true" === platformConfigStore.getSetting("agenda.allow_careers_in_global_agenda")
})

let id = route.params.id
if (isEmpty(id)) {
  id = route.query.id
}

onMounted(async () => {
  isLoading.value = true

  try {
    const response = await store.dispatch("message/load", id)
    const currentUser = securityStore.user

    item.value = await response

    delete item.value.status
    delete item.value.msgType
    delete item.value["@type"]
    delete item.value["@context"]
    delete item.value["@id"]
    delete item.value.id
    delete item.value.firstReceiver
    delete item.value.sendDate

    item.value.parentResourceNodeId = currentUser.resourceNode.id
    item.value.resourceLinkListFromEntity = []

    const receivers = [...item.value.receiversTo, ...item.value.receiversCc]
    const itemsAdded = []

    receivers.forEach((receiver) => {
      if (currentUser["@id"] === receiver.receiver["@id"]) {
        return
      }

      item.value.resourceLinkListFromEntity.push({
        uid: receiver.receiver.id,
        user: { username: receiver.receiver.username },
        visibility: RESOURCE_LINK_PUBLISHED,
      })

      itemsAdded.push(receiver.receiver.username)
    })

    if (!itemsAdded.includes(item.value.sender.username)) {
      item.value.resourceLinkListFromEntity.push({
        uid: item.value.sender.id,
        user: { username: item.value.sender.username },
        visibility: RESOURCE_LINK_PUBLISHED,
      })
    }

    delete item.value.sender

    if (undefined === item.value.career) {
      item.value.career = null
    }

    if (undefined === item.value.promotion) {
      item.value.promotion = null
    }

    await loadCareerAndPromotionOptions()
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
})

watch(
  () => effectiveAllowCareerPromotionFields.value,
  async (enabled) => {
    if (!enabled && item.value) {
      item.value.career = null
      item.value.promotion = null
      careerOptions.value = []
      promotionOptions.value = []

      return
    }

    await loadCareerAndPromotionOptions()
  },
)

const onClickCreateEvent = async () => {
  if (createForm.value.v$.$invalid) {
    createForm.value.v$.$touch()

    return
  }

  isLoading.value = true
  const itemModel = { ...createForm.value.v$.item.$model }

  const cid = Number(route.query.cid ?? 0)
  const sid = Number(route.query.sid ?? 0)
  const gid = Number(route.query.gid ?? 0)

  if (cid > 0) {
    itemModel.resourceLinkList = [
      {
        cid,
        sid: sid > 0 ? sid : null,
        gid: gid > 0 ? gid : null,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ]
  }

  itemModel.isGlobal = effectiveIsGlobal.value

  if (!effectiveAllowCareerPromotionFields.value) {
    itemModel.career = null
    itemModel.promotion = null
  }

  try {
    await store.dispatch("ccalendarevent/create", itemModel)
    await router.push({ name: "CCalendarEventList", query: route.query })
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

async function loadCareerAndPromotionOptions() {
  if (!effectiveAllowCareerPromotionFields.value) {
    careerOptions.value = []
    promotionOptions.value = []

    return
  }

  try {
    const data = await baseService.get("/calendar/career-promotion-options")

    careerOptions.value = Array.isArray(data.careers) ? data.careers : []
    promotionOptions.value = Array.isArray(data.promotions)
      ? data.promotions.map((promotion) => ({
          id: promotion.id,
          title: promotion.title,
          career: promotion.careerId,
        }))
      : []
  } catch (e) {
    careerOptions.value = []
    promotionOptions.value = []
  }
}

watch(
  () => store.state.ccalendarevent.created,
  (created) => {
    if (!created?.resourceNode?.title) {
      return
    }

    notification.showSuccessNotification(t("{0} created", [created.resourceNode.title]))
  },
)
</script>
