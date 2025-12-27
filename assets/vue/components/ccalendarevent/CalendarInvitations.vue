<script setup>
import { computed, ref, watch, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import Fieldset from "primevue/fieldset"
import SelectButton from "primevue/selectbutton"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import EditLinks from "../resource_links/EditLinks.vue"
import { useCalendarInvitations } from "../../composables/calendar/calendarInvitations"
import BaseAutocomplete from "../basecomponents/BaseAutocomplete.vue"
import usergrupService from "../../services/usergroupService"
import { subscriptionVisibility, type } from "../../constants/entity/ccalendarevent"

const model = defineModel({ type: Object })
const { t } = useI18n()
const { allowCollectiveInvitations, allowSubscriptions } = useCalendarInvitations()

const invitationTypeList = [
  { name: t("My invitations"), value: type.invitation },
  { name: t("Subscriptions"), value: type.subscription },
]

const subscriptionVisibilityList = [
  { label: t("No"), value: subscriptionVisibility.no },
  { label: t("All system users"), value: subscriptionVisibility.all },
  { label: t("Users inside the class"), value: subscriptionVisibility.class },
]

const invitationTypeSelected = ref(type.invitation)
const subscriptionVisibilitySelected = ref(subscriptionVisibility.no)
const subscriptionItemSelected = ref(null)

const showInvitationsFieldset = computed(() => {
  if (allowCollectiveInvitations && !allowSubscriptions) return true
  return allowSubscriptions && type.invitation === model.value?.invitationType
})

const showSubscriptionsFieldset = computed(() => {
  if (allowSubscriptions && !allowCollectiveInvitations) return true
  return allowCollectiveInvitations && type.subscription === model.value?.invitationType
})

const subscriptionItemDisabled = computed(() => subscriptionVisibility.class !== subscriptionVisibilitySelected.value)
const maxSubscriptionsDisabled = computed(() => subscriptionVisibility.no === subscriptionVisibilitySelected.value)

function syncFromModel() {
  const it = model.value?.invitationType
  if (it && it !== invitationTypeSelected.value) invitationTypeSelected.value = it

  const sv = model.value?.subscriptionVisibility
  if (typeof sv === "number" && sv !== subscriptionVisibilitySelected.value) subscriptionVisibilitySelected.value = sv

  // Ensure defaults exist for new items
  if (!model.value?.invitationType) model.value.invitationType = invitationTypeSelected.value
  if (typeof model.value?.subscriptionVisibility !== "number") {
    model.value.subscriptionVisibility = subscriptionVisibilitySelected.value
  }
}

watch(
  () => model.value,
  () => syncFromModel(),
  { immediate: true },
)

onMounted(() => syncFromModel())

watch(
  invitationTypeSelected,
  (newValue) => {
    // Store plain value (not a computed/ref)
    model.value.invitationType = newValue
  },
  { immediate: true },
)

watch(
  subscriptionVisibilitySelected,
  (newValue) => {
    model.value.subscriptionVisibility = newValue
    if (subscriptionVisibility.class !== newValue) {
      model.value.subscriptionItemId = undefined
      subscriptionItemSelected.value = null
    }
  },
  { immediate: true },
)

const onSubscriptionItemSelected = (event) => {
  const selected = event?.value
  model.value.subscriptionItemId = selected?.id
}

watch(subscriptionItemSelected, (newValue) => {
  if (!newValue) model.value.subscriptionItemId = undefined
})

const findUsergroup = async (query) => {
  const response = await usergrupService.search(query)
  return response.items
}
</script>

<template>
  <div
    v-if="allowCollectiveInvitations && allowSubscriptions"
    class="mb-3"
  >
    <div class="inline-flex items-center rounded-full border border-gray-300 bg-gray-100/90 p-1">
      <SelectButton
        v-model="invitationTypeSelected"
        :options="invitationTypeList"
        option-label="name"
        option-value="value"
        class="calendar-mode-toggle"
        aria-label="Invitation mode selector"
        size="small"
      />
    </div>
  </div>

  <Fieldset
    v-if="showInvitationsFieldset"
    :legend="t('My invitations')"
  >
    <EditLinks
      v-model="model"
      :edit-status="false"
      :show-status="false"
      link-list-name="resourceLinkList"
      links-type="user_rel_users"
      show-share-with-user
    />
    <BaseCheckbox
      id="is_collective"
      v-model="model.collective"
      :label="t('Event editable by the invitees')"
      name="is_collective"
    />
  </Fieldset>

  <Fieldset
    v-if="showSubscriptionsFieldset"
    :legend="t('Subscriptions')"
  >
    <BaseSelect
      v-model="subscriptionVisibilitySelected"
      :label="t('Allow subscriptions')"
      :options="subscriptionVisibilityList"
    />
    <BaseAutocomplete
      id="subscription_item"
      v-model="subscriptionItemSelected"
      :disabled="subscriptionItemDisabled"
      :label="t('Class')"
      :search="findUsergroup"
      option-label="title"
      @item-select="onSubscriptionItemSelected"
    />
    <BaseInputNumber
      id="max_subscription"
      v-model="model.maxAttendees"
      :disabled="maxSubscriptionsDisabled"
      :help-text="t('Maximum number of subscriptions allowed. Leave at 0 to not limit it.')"
      :label="t('Maximum number of subscriptions')"
      min="0"
      step="1"
    />
    <div
      id="form_subscriptions_edit"
      style="display: none"
    ></div>
  </Fieldset>
</template>
