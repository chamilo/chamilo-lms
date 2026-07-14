<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-6 text-center text-gray-600"
      role="status"
    >
      {{ t("Loading") }}
    </div>

    <template v-else-if="detail">
      <BaseToolbar>
        <template #start>
          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              icon="arrow-left"
              :label="t('Tickets')"
              only-icon
              :route="{ name: 'TicketList', query: listQuery }"
              size="normal"
              type="primary"
            />

            <BaseButton
              :icon="detail.isSubscribed ? 'email-unread' : 'email-plus'"
              :is-loading="isActionLoading"
              :label="detail.isSubscribed ? t('Unsubscribe') : t('Subscribe')"
              only-icon
              size="normal"
              :type="detail.isSubscribed ? 'secondary' : 'primary'"
              @click="toggleSubscription"
            />

            <BaseButton
              v-if="detail.canClose && Number(detail.ticket.status?.id) !== 4"
              icon="close"
              :label="t('Close')"
              only-icon
              size="normal"
              type="danger"
              @click="confirmClose"
            />

            <BaseButton
              v-if="detail.isAdmin"
              icon="tracking"
              :label="t('Assignment history')"
              only-icon
              size="normal"
              type="secondary"
              @click="historyVisible = true"
            />
          </div>
        </template>
      </BaseToolbar>

      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2 text-sm text-gray-600">
              <BaseIcon
                icon="ticket"
                size="small"
              />
              <span>{{ t("Ticket number") }}: {{ detail.ticket.code }}</span>
            </div>
            <h1 class="mt-2 break-words text-2xl font-semibold text-gray-90">
              {{ detail.ticket.subject }}
            </h1>
          </div>

          <span
            class="inline-flex self-start rounded-full px-3 py-1 text-sm font-medium"
            :class="statusClass(detail.ticket.status?.code)"
          >
            {{ detail.ticket.status?.title || "-" }}
          </span>
        </div>

        <dl class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Project") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ detail.ticket.project?.title || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Category") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ detail.ticket.category?.title || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Priority") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ detail.ticket.priority?.title || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Created by") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">
              {{ detail.ticket.creator?.fullName || detail.ticket.creator?.username || "-" }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Assigned to") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">
              <span v-if="detail.ticket.assignee">
                {{ detail.ticket.assignee.fullName || detail.ticket.assignee.username }}
              </span>
              <span v-else>{{ t("Unassigned") }}</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Created") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ formatDate(detail.ticket.createdAt) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-600">{{ t("Last update") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ formatDate(detail.ticket.updatedAt) }}</dd>
          </div>
          <div v-if="detail.ticket.session">
            <dt class="text-sm font-medium text-gray-600">{{ t("Session") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">{{ detail.ticket.session.title || "-" }}</dd>
          </div>
          <div v-if="detail.ticket.course">
            <dt class="text-sm font-medium text-gray-600">{{ t("Course") }}</dt>
            <dd class="mt-1 text-sm text-gray-90">
              <router-link
                class="text-primary hover:underline"
                :to="detail.ticket.course.url"
              >
                {{ detail.ticket.course.title || detail.ticket.course.code || "-" }}
              </router-link>
            </dd>
          </div>
        </dl>

        <div
          v-if="detail.showLearningPathInfo && (detail.ticket.exercise || detail.ticket.learningPath)"
          class="mt-6 flex flex-wrap gap-3"
        >
          <a
            v-if="detail.ticket.exercise"
            class="text-sm font-medium text-primary hover:underline"
            :href="detail.ticket.exercise.url"
          >
            {{ t("Test") }} #{{ detail.ticket.exercise.id }}
          </a>
          <a
            v-if="detail.ticket.learningPath"
            class="text-sm font-medium text-primary hover:underline"
            :href="detail.ticket.learningPath.url"
          >
            {{ t("Learning path") }} #{{ detail.ticket.learningPath.id }}
          </a>
        </div>

        <div class="mt-6 border-t border-gray-20 pt-6">
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Description") }}</h2>
          <div
            class="prose mt-3 max-w-none"
            v-html="detail.ticket.messageHtml"
          />
        </div>
      </article>

      <section class="space-y-4">
        <h2 class="text-xl font-semibold text-gray-90">
          {{ t("Message") }} ({{ detail.messages.length }})
        </h2>

        <article
          v-for="message in detail.messages"
          :id="`message-${message.number}`"
          :key="message.id"
          class="rounded-xl border border-gray-20 bg-white p-5 shadow-sm"
        >
          <header
            class="flex flex-col gap-2 border-b border-gray-20 pb-3 sm:flex-row sm:items-start sm:justify-between"
          >
            <div>
              <p class="font-semibold text-gray-90">
                {{ message.author.fullName || message.author.username }}
              </p>
              <p class="text-sm text-gray-600">
                {{ formatDate(message.createdAt) }}
              </p>
            </div>
            <a
              class="text-sm font-medium text-primary hover:underline"
              :href="`#message-${message.number}`"
            >
              #{{ message.number }}
            </a>
          </header>

          <p
            v-if="message.subject"
            class="mt-4 text-sm font-semibold text-gray-90"
          >
            {{ t("Subject") }}: {{ message.subject }}
          </p>

          <div
            v-if="message.messageHtml"
            class="prose mt-4 max-w-none"
            v-html="message.messageHtml"
          />

          <div
            v-if="message.attachments.length"
            class="mt-4 border-t border-gray-20 pt-4"
          >
            <h3 class="text-sm font-semibold text-gray-90">{{ t("Attachments") }}</h3>
            <ul class="mt-2 space-y-2">
              <li
                v-for="attachment in message.attachments"
                :key="attachment.id"
              >
                <a
                  class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                  :href="attachment.url"
                >
                  <BaseIcon
                    icon="file-generic"
                    size="small"
                  />
                  <span>{{ attachment.filename }} ({{ formatFileSize(attachment.size) }})</span>
                </a>
              </li>
            </ul>
          </div>
        </article>
      </section>

      <form
        v-if="detail.canReply"
        class="space-y-6 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
        @submit.prevent="submitReply"
      >
        <h2 class="text-xl font-semibold text-gray-90">{{ t("Reply") }}</h2>

        <div
          v-if="detail.canManage"
          class="grid grid-cols-1 gap-4 lg:grid-cols-3"
        >
          <BaseSelect
            id="ticket-reply-status"
            v-model="reply.statusId"
            :label="t('Status')"
            name="status_id"
            option-label="label"
            option-value="id"
            :options="detail.statuses"
          />

          <BaseSelect
            id="ticket-reply-priority"
            v-model="reply.priorityId"
            :label="t('Priority')"
            name="priority_id"
            option-label="label"
            option-value="id"
            :options="detail.priorities"
          />

          <div class="field min-w-0">
            <FloatLabel
              class="block w-full"
              variant="on"
            >
              <AutoComplete
                id="ticket-reply-assignee"
                v-model="selectedAssignee"
                class="w-full"
                data-key="id"
                :dropdown="true"
                fluid
                :force-selection="true"
                :show-clear="true"
                input-id="ticket-reply-assignee-input"
                name="assigned_user"
                option-label="label"
                :suggestions="assigneeSuggestions"
                @complete="searchAssignees"
              />
              <label for="ticket-reply-assignee-input">{{ t("Assigned to") }}</label>
            </FloatLabel>
          </div>
        </div>

        <BaseInputText
          id="ticket-reply-subject"
          v-model="reply.subject"
          :label="t('Subject')"
          name="subject"
        />

        <BaseTinyEditor
          v-model="reply.content"
          editor-id="ticket-reply-content"
          :full-page="false"
          :title="t('Message')"
        />

        <div class="space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h3 class="text-base font-semibold text-gray-90">{{ t("Files attachments") }}</h3>
              <p class="text-sm text-gray-600">
                {{ t("Maximun file size: %s", [formatFileSize(detail.maxUploadSize)]) }}
              </p>
            </div>
            <BaseButton
              v-if="uploadSlots.length < detail.maxAttachments"
              icon="plus"
              :label="t('Add one more file')"
              size="small"
              type="success"
              @click="addUploadSlot"
            />
          </div>

          <div
            v-for="(slot, index) in uploadSlots"
            :key="slot.id"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-20 p-3"
          >
            <div class="min-w-0 flex-1">
              <BaseFileUpload
                :label="t('Select a file')"
                size="small"
                @file-selected="(file) => selectFile(index, file)"
              />
            </div>
            <BaseButton
              v-if="uploadSlots.length > 1"
              icon="delete"
              :label="t('Delete')"
              only-icon
              size="small"
              type="danger-text"
              @click="removeUploadSlot(index)"
            />
          </div>
        </div>

        <div class="flex justify-end border-t border-gray-20 pt-4">
          <BaseButton
            icon="send"
            :is-loading="isSubmitting"
            is-submit
            :label="t('Send message')"
            type="success"
          />
        </div>
      </form>

      <div
        v-else-if="Number(detail.ticket.status?.id) === 4"
        class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
        role="status"
      >
        {{ t("Ticket closed") }}
      </div>

      <BaseDialog
        v-model:is-visible="historyVisible"
        :title="t('Assignment history')"
      >
        <div
          v-if="detail.assignmentHistory.length === 0"
          class="text-sm text-gray-600"
        >
          {{ t("No results found") }}
        </div>
        <div
          v-else
          class="space-y-3"
        >
          <div
            v-for="item in detail.assignmentHistory"
            :key="item.id"
            class="rounded-lg border border-gray-20 p-3"
          >
            <p class="font-medium text-gray-90">
              {{ item.assignee.fullName || item.assignee.username }}
            </p>
            <p class="mt-1 text-sm text-gray-600">
              {{ formatDate(item.assignedAt) }} ·
              {{ item.actor?.fullName || item.actor?.username || "-" }}
            </p>
          </div>
        </div>
      </BaseDialog>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import AutoComplete from "primevue/autocomplete"
import FloatLabel from "primevue/floatlabel"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import ticketService from "../../services/ticketService"

const { t, locale } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showErrorNotification } = useNotification()

const detail = ref(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const isActionLoading = ref(false)
const errorMessage = ref("")
const historyVisible = ref(false)
const selectedAssignee = ref(null)
const assigneeSuggestions = ref([])
const uploadSlots = ref([{ id: 1, file: null }])
let nextUploadId = 2

const reply = reactive({
  subject: "",
  content: "",
  statusId: null,
  priorityId: null,
})

const listQuery = computed(() => {
  const projectId = detail.value?.ticket?.project?.id
  return projectId ? { project_id: String(projectId) } : {}
})

onMounted(loadDetail)

async function loadDetail() {
  const ticketId = Number(route.params.id || 0)
  if (!ticketId) {
    errorMessage.value = t("An error occurred")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    detail.value = await ticketService.getDetail(ticketId)
    reply.subject = `${t("Re:")} ${detail.value.ticket.subject}`
    reply.content = ""
    reply.statusId = Number(detail.value.ticket.status?.id || 0) || null
    reply.priorityId = Number(detail.value.ticket.priority?.id || 0) || null
    selectedAssignee.value = detail.value.ticket.assignee
      ? {
          id: detail.value.ticket.assignee.id,
          label: detail.value.ticket.assignee.fullName || detail.value.ticket.assignee.username,
          username: detail.value.ticket.assignee.username,
        }
      : null
    uploadSlots.value = [{ id: nextUploadId++, file: null }]
  } catch (error) {
    console.error("[TicketDetail] Failed to load ticket", error)
    errorMessage.value = t("An error occurred")
    detail.value = null
  } finally {
    isLoading.value = false
  }
}

async function toggleSubscription() {
  if (!detail.value || isActionLoading.value) {
    return
  }

  isActionLoading.value = true
  try {
    const response = detail.value.isSubscribed
      ? await ticketService.unsubscribe(detail.value.id, detail.value.csrfToken)
      : await ticketService.subscribe(detail.value.id, detail.value.csrfToken)
    detail.value.isSubscribed = Boolean(response.subscribed)
    showSuccessNotification(response.message)
  } catch (error) {
    console.error("[TicketDetail] Failed to update subscription", error)
    showErrorNotification(error?.response?.data?.detail || t("An error occurred"))
  } finally {
    isActionLoading.value = false
  }
}

function confirmClose() {
  requireConfirmation({
    message: t("Are you sure"),
    accept: closeTicket,
  })
}

async function closeTicket() {
  if (!detail.value || isActionLoading.value) {
    return
  }

  isActionLoading.value = true
  try {
    const response = await ticketService.close(detail.value.id, detail.value.csrfToken)
    showSuccessNotification(response.message)
    await loadDetail()
  } catch (error) {
    console.error("[TicketDetail] Failed to close ticket", error)
    showErrorNotification(error?.response?.data?.detail || t("An error occurred"))
  } finally {
    isActionLoading.value = false
  }
}

async function searchAssignees(event) {
  try {
    const response = await ticketService.searchUsers(event.query || "")
    assigneeSuggestions.value = Array.isArray(response.items) ? response.items : []
  } catch (error) {
    console.error("[TicketDetail] Failed to search assignees", error)
    assigneeSuggestions.value = []
  }
}

function addUploadSlot() {
  if (!detail.value || uploadSlots.value.length >= detail.value.maxAttachments) {
    return
  }
  uploadSlots.value.push({ id: nextUploadId++, file: null })
}

function removeUploadSlot(index) {
  uploadSlots.value.splice(index, 1)
}

function selectFile(index, file) {
  uploadSlots.value[index].file = file
}

async function submitReply() {
  if (!detail.value || isSubmitting.value) {
    return
  }

  isSubmitting.value = true
  try {
    const payload = new FormData()
    payload.append("csrfToken", detail.value.csrfToken)
    payload.append("subject", reply.subject || "")
    payload.append("content", reply.content || "")
    payload.append("statusId", String(reply.statusId || 0))
    payload.append("priorityId", String(reply.priorityId || 0))
    payload.append("assignedUserId", String(selectedAssignee.value?.id || 0))
    uploadSlots.value.forEach((slot) => {
      if (slot.file) {
        payload.append("attachments[]", slot.file)
      }
    })

    const response = await ticketService.reply(detail.value.id, payload)
    showSuccessNotification(response.message || t("Saved."))
    await loadDetail()
  } catch (error) {
    console.error("[TicketDetail] Failed to reply", error)
    showErrorNotification(error?.response?.data?.detail || t("An error occurred"))
  } finally {
    isSubmitting.value = false
  }
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return "-"
  }

  const intlLocale = String(locale.value || "en-US").replace(/_/g, "-")

  return new Intl.DateTimeFormat(intlLocale, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function formatFileSize(bytes) {
  const value = Number(bytes || 0)
  if (value <= 0) {
    return "0 B"
  }

  const units = ["B", "KB", "MB", "GB"]
  const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1)
  const normalized = value / 1024 ** index
  return `${normalized.toFixed(index === 0 ? 0 : 1)} ${units[index]}`
}

function statusClass(code) {
  switch (String(code || "")) {
    case "1":
      return "bg-blue-100 text-blue-700"
    case "2":
    case "3":
      return "bg-yellow-100 text-yellow-800"
    case "4":
      return "bg-gray-100 text-gray-700"
    case "5":
      return "bg-green-100 text-green-700"
    default:
      return "bg-gray-100 text-gray-700"
  }
}
</script>
