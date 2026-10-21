<script setup>
import { onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import supportBotService from "../../services/supportBotService"

const { t } = useI18n()
const route = useRoute()
const notification = useNotification()
const isLoading = ref(false)
const treeTitle = ref("")
const conversations = ref([])
const treeId = Number(route.params.id || 0)

const filters = reactive({
  outcome: "",
  startDate: "",
  endDate: "",
  keyword: "",
})

const outcomeOptions = [
  { label: t("All"), value: "" },
  { label: t("In progress"), value: "in_progress" },
  { label: t("Resolved"), value: "resolved" },
  { label: t("Escalated"), value: "escalated" },
  { label: t("Abandoned"), value: "abandoned" },
]

async function loadConversations() {
  isLoading.value = true
  try {
    const data = await supportBotService.getConversations(treeId, filters)
    treeTitle.value = data.treeTitle || ""
    conversations.value = Array.isArray(data.conversations) ? data.conversations : []
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  filters.outcome = ""
  filters.startDate = ""
  filters.endDate = ""
  filters.keyword = ""
  loadConversations()
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

function outcomeLabel(outcome) {
  const labels = {
    in_progress: t("In progress"),
    resolved: t("Resolved"),
    escalated: t("Escalated"),
    abandoned: t("Abandoned"),
  }
  return labels[outcome] || outcome
}

function outcomeClass(outcome) {
  const classes = {
    in_progress: "bg-info/10 text-info",
    resolved: "bg-success/10 text-success",
    escalated: "bg-gray-20 text-gray-70",
    abandoned: "bg-danger/10 text-danger",
  }
  return classes[outcome] || "bg-gray-10 text-gray-700"
}

onMounted(loadConversations)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader
      :title="treeTitle ? `${t('Support bot conversations')} — ${treeTitle}` : t('Support bot conversations')"
    >
      <BaseButton
        :label="t('Back')"
        :route="{ name: 'SupportBotTreeList' }"
        icon="back"
        only-icon
        type="secondary-text"
      />
    </SectionHeader>

    <div class="grid gap-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:grid-cols-4">
      <BaseSelect
        id="support_bot_conversation_outcome"
        v-model="filters.outcome"
        :label="t('Outcome')"
        :options="outcomeOptions"
        name="outcome"
      />
      <BaseInputText
        id="support_bot_conversation_start_date"
        v-model="filters.startDate"
        :label="t('Start date')"
        name="startDate"
        type="date"
      />
      <BaseInputText
        id="support_bot_conversation_end_date"
        v-model="filters.endDate"
        :label="t('End date')"
        name="endDate"
        type="date"
      />
      <BaseInputText
        id="support_bot_conversation_keyword"
        v-model="filters.keyword"
        :label="t('Search')"
        name="keyword"
      />
      <div class="flex gap-2 md:col-span-4 md:justify-end">
        <BaseButton
          :label="t('Clear')"
          icon="clear-all"
          type="secondary"
          @click="clearFilters"
        />
        <BaseButton
          :is-loading="isLoading"
          :label="t('Search')"
          icon="search"
          type="primary"
          @click="loadConversations"
        />
      </div>
    </div>

    <BaseTable
      :is-loading="isLoading"
      :lazy="false"
      :text-for-empty="t('No results found')"
      :total-items="conversations.length"
      :values="conversations"
      data-key="id"
    >
      <Column
        field="userName"
        :header="t('User')"
        sortable
      />
      <Column
        field="startedAt"
        :header="t('Started at')"
        sortable
      >
        <template #body="{ data }">{{ formatDate(data.startedAt) }}</template>
      </Column>
      <Column
        field="endedAt"
        :header="t('Ended at')"
        sortable
      >
        <template #body="{ data }">{{ formatDate(data.endedAt) }}</template>
      </Column>
      <Column :header="t('Outcome')">
        <template #body="{ data }">
          <span
            :class="outcomeClass(data.outcome)"
            class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
          >
            {{ outcomeLabel(data.outcome) }}
          </span>
        </template>
      </Column>
      <Column
        field="treeVersion"
        :header="t('Version')"
        sortable
      />
      <Column
        :header="t('Actions')"
        class="w-28"
      >
        <template #body="{ data }">
          <div class="flex flex-nowrap justify-end gap-1">
            <BaseButton
              :label="t('View conversation')"
              :route="{ name: 'SupportBotConversationDetail', params: { id: treeId, conversationId: data.id } }"
              icon="preview"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              v-if="data.ticketId"
              :label="t('View ticket')"
              :route="{ name: 'TicketDetail', params: { id: data.ticketId } }"
              icon="ticket"
              only-icon
              size="small"
              type="secondary-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>
  </div>
</template>
