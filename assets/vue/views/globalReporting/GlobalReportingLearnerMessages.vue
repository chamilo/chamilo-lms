<template>
  <section
    class="overflow-hidden rounded-xl border border-gray-25 bg-white shadow-sm"
    data-testid="learner-messages"
  >
    <header class="border-b border-gray-25 p-4">
      <h2 class="text-lg font-semibold text-gray-90">{{ t("Messages") }}</h2>
    </header>
    <div class="space-y-3 p-4">
      <Panel
        v-for="message in messages"
        :key="message.id"
        toggleable
        collapsed
      >
        <template #header>
          <span class="font-semibold text-gray-90">
            {{ formatDateTime(message.sendDate) }} {{ message.sender.fullName }} {{ message.title }}
          </span>
        </template>
        <template #icons>
          <BaseButton
            v-if="message.canDelete"
            :label="t('Delete message')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="confirmDelete(message)"
          />
        </template>
        <div
          class="text-sm text-gray-90"
          v-html="sanitizeHtml(message.content)"
        />
        <p class="mt-3 text-xs text-gray-50">{{ t("Author") }}: {{ message.sender.username }}</p>
      </Panel>

      <BaseButton
        v-if="!composing"
        :label="t('New message')"
        icon="plus"
        type="success"
        @click="composing = true"
      />
      <form
        v-else
        class="space-y-4"
        @submit.prevent="sendMessage"
      >
        <BaseInputText
          id="learner_message_subject"
          v-model="newMessage.subject"
          name="subject"
          :label="t('Subject')"
          :is-invalid="messageSubmitted && !newMessage.subject.trim()"
          :error-text="t('Required field')"
        />
        <BaseTinyEditor
          v-model="newMessage.content"
          editor-id="learner_message_content"
          :title="t('Message')"
          :full-page="false"
          show-required-marker
        />
        <small
          v-if="messageSubmitted && !newMessage.content.trim()"
          class="block text-error"
        >
          {{ t("Required field") }}
        </small>
        <div class="flex gap-2">
          <BaseButton
            :label="t('Send message')"
            icon="send"
            type="success"
            is-submit
            :is-loading="sendingMessage"
          />
          <BaseButton
            :label="t('Cancel')"
            icon="close"
            type="plain"
            @click="composing = false"
          />
        </div>
      </form>
    </div>
  </section>
</template>

<script setup>
import Panel from "primevue/panel"
import { reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import globalReportingService from "../../services/globalReportingService"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

// Follow-up messages about a learner, shown on the learner reports when
// message.private_messages_about_user is on. The parent reloads its report on "changed".
const props = defineProps({
  userId: { type: Number, required: true },
  messages: { type: Array, required: true },
})
const emit = defineEmits(["changed"])

const { t } = useI18n()
const { abbreviatedDatetime } = useFormatDate()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showErrorNotification } = useNotification()

const composing = ref(false)
const sendingMessage = ref(false)
const messageSubmitted = ref(false)
const newMessage = reactive({ subject: "", content: "" })

// The API sends UTC dates as "Y-m-d H:i:s".
function formatDateTime(value) {
  return abbreviatedDatetime(String(value).replace(" ", "T")) || String(value)
}

async function sendMessage() {
  messageSubmitted.value = true
  if (!newMessage.subject.trim() || !newMessage.content.trim()) {
    return
  }

  sendingMessage.value = true

  try {
    await globalReportingService.sendLearnerMessage(props.userId, newMessage.subject, newMessage.content)
    showSuccessNotification(t("Message Sent"))
    Object.assign(newMessage, { subject: "", content: "" })
    messageSubmitted.value = false
    composing.value = false
    emit("changed")
  } catch (error) {
    showErrorNotification(error)
  } finally {
    sendingMessage.value = false
  }
}

function confirmDelete(message) {
  requireConfirmation({
    message: t("Are you sure you want to delete the selected message?"),
    accept: async () => {
      try {
        await globalReportingService.deleteLearnerMessage(props.userId, message.id)
        showSuccessNotification(t("Message deleted"))
        emit("changed")
      } catch (error) {
        showErrorNotification(error)
      }
    },
  })
}
</script>
