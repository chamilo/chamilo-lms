<template>
  <div class="calendar-event-info">
    <div class="calendar-event-info__header">
      <span
        :style="event.color ? { background: event.color } : null"
        class="calendar-event-info__color"
      />
      <div class="flex flex-col gap-1">
        <h5
          class="calendar-event-info__title"
          v-text="event.title"
        />
        <p class="calendar-event-info__date">
          <BaseIcon
            icon="calendar-clock"
            size="small"
          />
          <span v-text="abbreviatedDatetime(event.startDate)" />
          <template v-if="event.endDate">
            <span aria-hidden="true">–</span>
            <span v-text="abbreviatedDatetime(event.endDate)" />
          </template>
        </p>
      </div>
    </div>

    <div
      dir="auto"
      v-html="sanitizeHtml(event.content)"
    />

    <p
      v-if="event.comment"
      class="mt-2 whitespace-pre-line"
    >
      <strong>{{ t("Comment") }}:</strong>
      {{ event.comment }}
    </p>

    <div
      v-if="event.attachments?.length"
      class="mt-2"
    >
      <strong>{{ t("Attachments") }}:</strong>
      <ul class="space-y-1">
        <li
          v-for="attachment in event.attachments"
          :key="attachment.id"
          class="flex items-center gap-2"
        >
          <BaseIcon
            icon="attachment"
            size="small"
          />
          <a
            :href="`/api/calendar-events/${eventId}/attachments/${attachment.id}/download`"
            class="break-all hover:underline"
            >{{ attachment.filename }}</a
          >
          <span
            v-if="attachment.comment"
            class="text-gray-50"
            >{{ attachment.comment }}</span
          >
        </li>
      </ul>
    </div>

    <CalendarEventSubscriptionsInfo
      v-if="type.subscription === event.invitationType"
      :event="event"
    />
    <CalendarEventInvitationsInfo
      v-else-if="type.invitation === event.invitationType"
      :event="event"
    />
    <div
      v-else-if="event.objectType === 'learning_calendar' && event.eventType"
      class="mt-2"
    >
      <strong>{{ t("Type") }}:</strong>
      {{ event.eventType }}
    </div>

    <ShowLinks
      v-else
      :clickable-course="true"
      :item="event"
      :show-status="false"
    />

    <CalendarRemindersInfo
      v-if="event.objectType !== 'learning_calendar'"
      :event="event"
    />

    <div
      v-if="event.room"
      class="mt-2"
    >
      <strong>{{ t("Room") }}:</strong>
      {{ event.room.branchTitle ? `${event.room.branchTitle} - ${event.room.title}` : event.room.title }}
    </div>
  </div>
</template>

<script setup>
import { useFormatDate } from "../../composables/formatDate"
import { useI18n } from "vue-i18n"
import ShowLinks from "../resource_links/ShowLinks"
import { type } from "../../constants/entity/ccalendarevent"
import CalendarEventSubscriptionsInfo from "./CalendarEventSubscriptionsInfo.vue"
import CalendarEventInvitationsInfo from "./CalendarEventInvitationsInfo.vue"
import CalendarRemindersInfo from "./CalendarRemindersInfo.vue"
import { sanitizeHtml } from "../../utils/sanitizeHtml"
import { computed } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"

const { abbreviatedDatetime } = useFormatDate()
const { t } = useI18n()

const props = defineProps({
  event: {
    type: Object,
    required: true,
  },
})

const eventId = computed(() => Number(String(props.event["@id"] ?? props.event.id ?? "").match(/\d+$/)?.[0] ?? 0))
</script>
