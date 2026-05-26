<script setup>
import { useI18n } from "vue-i18n"
import ShowLinks from "../resource_links/ShowLinks.vue"
import { subscriptionVisibility } from "../../constants/entity/ccalendarevent"

const { t } = useI18n()

defineProps({
  event: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div class="invitations-info">
    <h6
      v-text="t('Subscriptions')"
      class="invitations-info__title"
    />

    <div class="invitations-info__item">
      <p v-text="t('Allow subscriptions')" />
      <p
        v-if="subscriptionVisibility.no === event.subscriptionVisibility"
        v-text="'No'"
      />
      <p
        v-else-if="subscriptionVisibility.all === event.subscriptionVisibility"
        v-text="'All system users'"
      />
      <p
        v-else-if="subscriptionVisibility.class === event.subscriptionVisibility"
        v-text="'Users inside the class'"
      />
      <p
        v-if="subscriptionVisibility.class === event.subscriptionVisibility"
        v-text="event.subscriptionItemTitle"
      />
    </div>

    <div
      v-if="event.maxAttendees"
      class="invitations-info__item"
    >
      <p v-text="t('Maximum number of subscriptions')" />
      <p v-text="event.maxAttendees" />
    </div>

    <div
      v-if="event.maxAttendees"
      class="invitations-info__item"
    >
      <p v-text="t('Subscriptions count')" />
      <p v-text="event.resourceLinkListFromEntity.length" />
    </div>

    <div
      v-if="event.resourceLinkListFromEntity.length"
      class="invitations-info__item"
    >
      <p v-text="t('Subscribers')" />
      <div>
        <ShowLinks
          :item="event"
          :show-status="false"
        />
      </div>
    </div>
  </div>
</template>
