<script setup>
import { computed, ref, watch } from "vue"
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
import { type, subscriptionVisibility } from "../../constants/entity/ccalendarevent"

const model = defineModel({
  type: Object,
})

const { t } = useI18n()

const { allowCollectiveInvitations, allowSubscriptions } = useCalendarInvitations()

const showInvitationsFieldset = computed(() => {
  if (allowCollectiveInvitations && !allowSubscriptions) {
    return true
  }

  return allowSubscriptions && type.invitation === model.value?.invitationType
})

const showSubscriptionsFieldset = computed(() => {
  if (allowSubscriptions && !allowCollectiveInvitations) {
    return true
  }

  return allowCollectiveInvitations && type.subscription === model.value?.invitationType
})

const invitationTypeList = [
  { name: t("Invitations"), value: type.invitation },
  { name: t("Subscriptions"), value: type.subscription },
]

const invitationTypeSelected = ref()

model.value.invitationType = computed(() => invitationTypeSelected.value)

const subscriptionVisibilityList = [
  { label: t("No"), value: subscriptionVisibility.no },
  { label: t("All system users"), value: subscriptionVisibility.all },
  { label: t("Users inside the class"), value: subscriptionVisibility.class },
]

const subscriptionVisibilitySelected = ref(0)

model.value.subscriptionVisibility = computed(() => subscriptionVisibilitySelected.value)

const subscriptionItemSelected = ref()

const subscriptionItemDisabled = computed(() => 2 !== subscriptionVisibilitySelected.value)

const onSubscriptionItemSelected = (event) => (model.value.subscriptionItemId = event.value.id)

watch(subscriptionItemSelected, (newValue) => {
  if (!newValue) {
    model.value.subscriptionItemId = undefined
  }
})

const findUsergroup = async (query) => {
  const response = await usergrupService.search(query)

  return response.items
}

const maxSubscriptionsDisabled = computed(() => 0 === subscriptionVisibilitySelected.value)
</script>

<template>
  <div
    v-if="allowCollectiveInvitations && allowSubscriptions"
    class="field"
  >
    <SelectButton
      v-model="invitationTypeSelected"
      :options="invitationTypeList"
      option-label="name"
      option-value="value"
    />
  </div>

  <Fieldset
    v-if="showInvitationsFieldset"
    :legend="t('Invitations')"
  >
    <EditLinks
      v-model="model"
      :edit-status="false"
      links-type="user_rel_users"
      :show-status="false"
      show-share-with-user
      link-list-name="resourceLinkList"
    />
    <BaseCheckbox
      id="is_collective"
      v-model="model.collective"
      :label="t('Is it editable by the invitees?')"
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
      option-label="label"
      option-value="value"
    />
    <BaseAutocomplete
      id="subscription_item"
      v-model="subscriptionItemSelected"
      :search="findUsergroup"
      :label="t('Social group')"
      option-label="title"
      :disabled="subscriptionItemDisabled"
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
