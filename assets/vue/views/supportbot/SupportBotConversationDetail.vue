<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import supportBotService from "../../services/supportBotService"

const { t } = useI18n()
const route = useRoute()
const notification = useNotification()
const isLoading = ref(false)
const detail = ref(null)
const treeId = Number(route.params.id || 0)
const conversationId = Number(route.params.conversationId || 0)

async function loadConversation() {
  isLoading.value = true
  try {
    detail.value = await supportBotService.getConversation(treeId, conversationId)
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function formatDate(value) {
  if (!value) return ""
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat(undefined, {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date)
}

onMounted(loadConversation)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Support bot conversation')">
      <div class="flex gap-2">
        <BaseButton
          :label="t('Back')"
          :route="{ name: 'SupportBotConversationList', params: { id: treeId } }"
          icon="back"
          only-icon
          type="secondary-text"
        />
        <BaseButton
          v-if="detail?.ticketId"
          :label="t('View ticket')"
          :route="{ name: 'TicketDetail', params: { id: detail.ticketId } }"
          icon="ticket"
          only-icon
          type="primary"
        />
      </div>
    </SectionHeader>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="detail">
      <div class="grid gap-4 rounded-xl border border-gray-20 bg-white p-5 shadow-sm md:grid-cols-2 lg:grid-cols-4">
        <div>
          <div class="text-xs uppercase text-gray-50">{{ t("User") }}</div>
          <div class="font-medium">{{ detail.user?.name || detail.user?.username || "" }}</div>
        </div>
        <div>
          <div class="text-xs uppercase text-gray-50">{{ t("Outcome") }}</div>
          <div class="font-medium">{{ detail.outcome }}</div>
        </div>
        <div>
          <div class="text-xs uppercase text-gray-50">{{ t("Started at") }}</div>
          <div class="font-medium">{{ formatDate(detail.startedAt) }}</div>
        </div>
        <div>
          <div class="text-xs uppercase text-gray-50">{{ t("Version") }}</div>
          <div class="font-medium">{{ detail.treeVersion }}</div>
        </div>
      </div>

      <div class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold">{{ detail.treeTitle }}</h2>
        <div class="flex flex-col gap-4">
          <div
            v-for="step in detail.steps"
            :key="step.id || `${step.createdAt}-${step.nodeContent}`"
            class="flex flex-col gap-2"
          >
            <div class="max-w-3xl self-start rounded-xl bg-gray-10 px-4 py-3 text-gray-900">
              <div class="whitespace-pre-wrap">{{ step.nodeContent }}</div>
              <div class="mt-1 text-xs text-gray-50">{{ formatDate(step.createdAt) }}</div>
            </div>
            <div
              v-if="step.selectedOptionLabel"
              class="max-w-3xl self-end rounded-xl bg-primary/10 px-4 py-3 text-gray-900"
            >
              {{ step.selectedOptionLabel }}
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
