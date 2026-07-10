<template>
  <section class="space-y-4">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          :route="listRoute"
          size="large"
          :tooltip="t('Back')"
          type="primary-text"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadErrorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ loadErrorMessage }}
    </div>

    <form
      v-else
      class="space-y-4"
      novalidate
      @submit.prevent="saveAnnouncement"
    >
      <div
        v-if="formErrorMessage"
        ref="formErrorRef"
        class="flex items-start gap-3 rounded-lg border border-error bg-error/10 p-4 text-sm text-error shadow-sm"
        role="alert"
        aria-live="assertive"
        tabindex="-1"
      >
        <BaseIcon
          class="mt-0.5 shrink-0"
          icon="alert"
          size="small"
        />
        <span>{{ formErrorMessage }}</span>
      </div>

      <div
        v-if="formWarningMessage"
        ref="formWarningRef"
        class="flex items-start gap-3 whitespace-pre-line rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 shadow-sm"
        role="alert"
        aria-live="assertive"
        tabindex="-1"
      >
        <BaseIcon
          class="mt-0.5 shrink-0"
          icon="alert"
          size="small"
        />
        <span>{{ formWarningMessage }}</span>
      </div>

      <BaseCard>
        <template #title>
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="announcement"
              size="small"
            />
            <span>{{ form.id ? t("Edit announcement") : t("Add an announcement") }}</span>
          </div>
        </template>

        <div class="space-y-4">
          <BaseSelect
            v-if="form.classes.length"
            id="announcement_class"
            v-model="selectedClassId"
            :allow-clear="true"
            :label="form.classLabel || t('Classes')"
            name="announcement_class"
            option-label="label"
            option-value="id"
            :options="form.classes"
            @change="applyClassRecipients"
          />

          <BaseMultiSelect
            input-id="announcement_recipients"
            :error-text="t('Required field')"
            :is-invalid="formSubmitted && form.recipients.length === 0"
            :label="t('Recipients')"
            :model-value="form.recipients"
            option-label="label"
            option-value="value"
            :options="recipientOptions"
            @update:model-value="updateRecipients"
          />
          <input
            name="recipients"
            type="hidden"
            :value="form.recipients.join(',')"
          />

          <BaseInputText
            id="announcement_subject"
            v-model="form.title"
            :error-text="t('Required field')"
            :form-submitted="formSubmitted"
            :is-invalid="formSubmitted && !form.title.trim()"
            :label="t('Subject')"
            name="title"
            required
          />

          <details
            v-if="form.tags.length"
            class="rounded-lg border border-gray-20 bg-gray-10 px-4 py-3"
          >
            <summary class="cursor-pointer text-sm font-semibold text-primary">
              {{ t("Tags") }}
            </summary>
            <div class="mt-3">
              <p class="mb-3 text-sm text-gray-600">
                {{
                  t(
                    "Tags can be copied and pasted inside the text area below and will be dynamically replaced with their value for each user individually when sending them.",
                  )
                }}
              </p>
              <div class="flex flex-wrap gap-2">
                <code
                  v-for="tag in form.tags"
                  :key="tag"
                  class="rounded bg-white px-2 py-1 text-sm text-gray-700"
                >
                  {{ tag }}
                </code>
              </div>
            </div>
          </details>

          <BaseTinyEditor
            v-model="form.content"
            editor-id="announcement_content"
            :editor-config="editorConfig"
            :full-page="false"
            required
            :title="t('Description')"
          />

          <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
            <div class="space-y-5">
              <BaseSelect
                v-if="form.languages.length > 2"
                id="resource_language"
                v-model="form.language"
                :label="t('Language')"
                name="language"
                option-label="label"
                option-value="value"
                :options="form.languages"
              />

              <div class="space-y-3 rounded-lg border border-gray-20 bg-white p-4">
                <p class="text-sm font-semibold text-gray-90">
                  {{ t("Email") }}
                </p>

                <p
                  v-if="form.emailAlreadySent"
                  class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800"
                >
                  {{ t("This announcement has already been sent by email.") }}
                </p>

                <BaseCheckbox
                  v-else
                  id="announcement_send_by_email"
                  v-model="form.sendByEmail"
                  :label="t('Send this announcement by email to selected groups/users')"
                  name="sendByEmail"
                />

                <div
                  v-if="form.sendByEmail && !form.emailAlreadySent"
                  class="space-y-3 pl-6"
                >
                  <BaseCheckbox
                    v-if="form.sendToSessionsAvailable"
                    id="announcement_send_to_users_in_sessions"
                    v-model="form.sendToUsersInSessions"
                    :label="t('Send to users in all sessions of this course')"
                    name="sendToUsersInSessions"
                  />

                  <BaseCheckbox
                    v-if="form.sendToHrmAvailable && !form.scheduleByDate"
                    id="announcement_send_to_hrm_users"
                    v-model="form.sendToHrmUsers"
                    :label="t('Send a copy to HR managers of selected students')"
                    name="sendToHrmUsers"
                  />
                </div>

                <div
                  v-if="form.scheduleAvailable && form.sendByEmail && !form.emailAlreadySent"
                  class="space-y-3 rounded-lg border border-gray-20 bg-gray-10 p-3"
                >
                  <BaseCheckbox
                    id="announcement_schedule_by_date"
                    v-model="form.scheduleByDate"
                    :label="t('Send notification at a specific date')"
                    name="scheduleByDate"
                  />

                  <BaseInputText
                    v-if="form.scheduleByDate"
                    id="announcement_schedule_date"
                    v-model="form.scheduleDate"
                    :error-text="t('Required field')"
                    :is-invalid="formSubmitted && !form.scheduleDate"
                    :label="t('Date to send notification')"
                    :min="form.scheduleMinimumDate"
                    name="scheduleDate"
                    type="date"
                  />
                </div>

                <BaseCheckbox
                  id="announcement_send_copy_to_self"
                  v-model="form.sendCopyToSelf"
                  :label="t('Send a copy by email to myself.')"
                  name="sendCopyToSelf"
                />
              </div>

              <div
                v-if="form.calendarAvailable"
                class="space-y-4 rounded-lg border border-gray-20 bg-white p-4"
              >
                <BaseCheckbox
                  id="announcement_add_to_calendar"
                  v-model="form.addToCalendar"
                  :label="t('Add event in course calendar')"
                  name="addToCalendar"
                />

                <div
                  v-if="form.addToCalendar"
                  class="space-y-4 pl-6"
                >
                  <div class="grid gap-4 md:grid-cols-2">
                    <BaseCalendar
                      id="announcement_event_start_date"
                      v-model="form.eventStartDate"
                      :is-invalid="formSubmitted && !form.eventStartDate"
                      :label="t('Start date')"
                      show-time
                    />

                    <BaseCalendar
                      id="announcement_event_end_date"
                      v-model="form.eventEndDate"
                      :is-invalid="formSubmitted && !form.eventEndDate"
                      :label="t('End date')"
                      show-time
                    />
                  </div>

                  <CalendarRemindersEditor v-model="form" />
                </div>
              </div>

              <div
                v-if="form.attachmentsEnabled"
                class="space-y-3"
              >
                <div>
                  <label
                    class="mb-2 block text-sm font-semibold text-gray-90"
                    for="announcement_attachments"
                  >
                    {{ t("Add attachment") }}
                  </label>
                  <input
                    id="announcement_attachments"
                    ref="attachmentInputRef"
                    class="block w-full rounded-lg border border-gray-30 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-20 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-90 hover:file:bg-gray-25"
                    multiple
                    name="attachments[]"
                    type="file"
                    @change="handleAttachmentFiles"
                  />
                </div>

                <ul
                  v-if="attachmentFiles.length"
                  class="space-y-1 text-sm text-gray-600"
                >
                  <li
                    v-for="file in attachmentFiles"
                    :key="`${file.name}-${file.size}-${file.lastModified}`"
                    class="flex items-center gap-2"
                  >
                    <BaseIcon
                      icon="attachment"
                      size="small"
                    />
                    <span class="break-all">{{ file.name }}</span>
                  </li>
                </ul>

                <BaseTextArea
                  v-if="attachmentFiles.length"
                  id="announcement_file_comment"
                  v-model="fileComment"
                  :label="t('File comment')"
                  name="file_comment"
                  rows="3"
                />

                <div v-if="form.attachments.length">
                  <p class="mb-2 text-sm font-semibold text-gray-90">
                    {{ t("Attachments") }}
                  </p>
                  <ul class="space-y-2">
                    <li
                      v-for="attachment in form.attachments"
                      :key="attachment.id"
                      class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-20 bg-white px-3 py-2"
                    >
                      <BaseIcon
                        icon="attachment"
                        size="small"
                      />
                      <a
                        class="font-medium text-primary hover:underline"
                        :href="attachment.downloadUrl"
                      >
                        {{ attachment.filename }}
                      </a>
                      <span
                        v-if="attachment.comment"
                        class="min-w-0 flex-1 text-sm text-gray-600"
                      >
                        {{ attachment.comment }}
                      </span>
                      <BaseButton
                        icon="delete"
                        :is-loading="deletingAttachmentId === Number(attachment.id)"
                        :label="t('Delete')"
                        only-icon
                        size="small"
                        :tooltip="t('Delete')"
                        type="danger-text"
                        @click="confirmDeleteAttachment(attachment)"
                      />
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </BaseAdvancedSettingsButton>
        </div>
      </BaseCard>

      <div
        v-if="previewRecipients.length"
        class="rounded-lg border border-gray-20 bg-gray-10 p-4"
      >
        <p class="mb-2 font-semibold text-gray-90">
          {{ t("Announcement will be sent to") }}
        </p>
        <ul class="list-disc space-y-1 pl-6 text-sm text-gray-700">
          <li
            v-for="recipient in previewRecipients"
            :key="recipient"
          >
            {{ recipient }}
          </li>
        </ul>
      </div>

      <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 pt-4">
        <BaseButton
          icon="back"
          :label="t('Cancel')"
          :route="listRoute"
          type="plain"
        />
        <BaseButton
          icon="eye-on"
          :disabled="isSaving"
          :is-loading="isPreviewing"
          :label="t('Preview')"
          name="preview"
          type="secondary-text"
          @click="previewAnnouncement"
        />
        <BaseButton
          v-if="previewReady"
          icon="save"
          is-submit
          :is-loading="isSaving"
          :label="t('Save')"
          name="save"
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import CalendarRemindersEditor from "../../components/ccalendarevent/CalendarRemindersEditor.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import announcementService from "../../services/announcementService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isSaving = ref(false)
const isPreviewing = ref(false)
const formSubmitted = ref(false)
const loadErrorMessage = ref("")
const formErrorMessage = ref("")
const formWarningMessage = ref("")
const formErrorRef = ref(null)
const formWarningRef = ref(null)
const previewRecipients = ref([])
const previewReady = ref(false)
const previewPayload = ref(null)
const showAdvancedSettings = ref(false)
const selectedClassId = ref(null)
const attachmentFiles = ref([])
const attachmentInputRef = ref(null)
const fileComment = ref("")
const deletingAttachmentId = ref(0)

const form = ref({
  id: null,
  title: "",
  content: "",
  language: "",
  recipients: [],
  csrfToken: "",
  recipientOptions: [],
  classes: [],
  classLabel: "",
  languages: [],
  tags: [],
  sendByEmail: true,
  sendToUsersInSessions: false,
  sendToHrmUsers: false,
  sendCopyToSelf: true,
  emailAlreadySent: false,
  sendToSessionsAvailable: false,
  sendToHrmAvailable: false,
  emailCsrfToken: "",
  scheduleAvailable: false,
  scheduleByDate: false,
  scheduleDate: "",
  scheduleMinimumDate: "",
  calendarAvailable: false,
  addToCalendar: false,
  eventStartDate: null,
  eventEndDate: null,
  reminders: [],
  attachmentsEnabled: false,
  attachmentCsrfToken: "",
  attachments: [],
})

const editorConfig = {
  toolbar: "bold italic underline | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 320,
}

const listRoute = computed(() => ({
  name: "AnnouncementList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const recipientOptions = computed(() => {
  const selectedClass = form.value.classes.find((item) => Number(item.id) === Number(selectedClassId.value || 0))
  if (!selectedClass) {
    return form.value.recipientOptions
  }

  const allowedValues = new Set(Array.isArray(selectedClass.recipientValues) ? selectedClass.recipientValues : [])

  return form.value.recipientOptions.filter((option) => allowedValues.has(option.value))
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  for (const key of ["origin", "page", "isStudentView"]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

function getFormParams() {
  const params = getContextParams()
  const id = Number(route.params.id || 0)

  if (id > 0) {
    params.id = id
  }

  for (const key of ["remind_inactive", "remindallinactives", "since"]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

function updateRecipients(values) {
  let nextValues = Array.isArray(values) ? [...values] : []

  if (nextValues.includes("everyone") && nextValues.length > 1) {
    nextValues = form.value.recipients.includes("everyone")
      ? nextValues.filter((value) => value !== "everyone")
      : ["everyone"]
  }

  form.value.recipients = nextValues
}

function applyClassRecipients() {
  const selectedClass = form.value.classes.find((item) => Number(item.id) === Number(selectedClassId.value || 0))
  if (!selectedClass) {
    form.value.recipients = ["everyone"]

    return
  }

  form.value.recipients = Array.isArray(selectedClass.recipientValues) ? [...selectedClass.recipientValues] : []
}

function handleAttachmentFiles(event) {
  attachmentFiles.value = Array.from(event?.target?.files || [])
}

function resetPreview() {
  previewReady.value = false
  previewRecipients.value = []
  previewPayload.value = null
}

async function loadForm() {
  isLoading.value = true
  loadErrorMessage.value = ""
  formErrorMessage.value = ""
  formWarningMessage.value = ""

  try {
    const response = await announcementService.getForm(getFormParams())
    form.value = {
      id: response.id ?? null,
      title: response.title || "",
      content: response.content || "",
      language: response.language || "",
      recipients: Array.isArray(response.recipients) ? response.recipients : ["everyone"],
      csrfToken: response.csrfToken || "",
      recipientOptions: Array.isArray(response.recipientOptions) ? response.recipientOptions : [],
      classes: Array.isArray(response.classes) ? response.classes : [],
      classLabel: response.classLabel || "",
      languages: Array.isArray(response.languages) ? response.languages : [],
      tags: Array.isArray(response.tags) ? response.tags : [],
      sendByEmail: Boolean(response.sendByEmail),
      sendToUsersInSessions: Boolean(response.sendToUsersInSessions),
      sendToHrmUsers: Boolean(response.sendToHrmUsers),
      sendCopyToSelf: Boolean(response.sendCopyToSelf),
      emailAlreadySent: Boolean(response.emailAlreadySent),
      sendToSessionsAvailable: Boolean(response.sendToSessionsAvailable),
      sendToHrmAvailable: Boolean(response.sendToHrmAvailable),
      emailCsrfToken: response.emailCsrfToken || "",
      scheduleAvailable: Boolean(response.scheduleAvailable),
      scheduleByDate: Boolean(response.scheduleByDate),
      scheduleDate: response.scheduleDate || "",
      scheduleMinimumDate: response.scheduleMinimumDate || "",
      calendarAvailable: Boolean(response.calendarAvailable),
      addToCalendar: Boolean(response.addToCalendar),
      eventStartDate: response.eventStartDate ? new Date(response.eventStartDate) : null,
      eventEndDate: response.eventEndDate ? new Date(response.eventEndDate) : null,
      reminders: Array.isArray(response.reminders)
        ? response.reminders.map((reminder) => ({
            count: Number(reminder.count || 0),
            period: reminder.period || "i",
          }))
        : [],
      attachmentsEnabled: Boolean(response.attachmentsEnabled),
      attachmentCsrfToken: response.attachmentCsrfToken || "",
      attachments: Array.isArray(response.attachments) ? response.attachments : [],
    }
    attachmentFiles.value = []
    fileComment.value = ""
    showAdvancedSettings.value = Boolean(
      form.value.language ||
        form.value.attachments.length ||
        form.value.sendByEmail ||
        form.value.sendCopyToSelf ||
        form.value.sendToUsersInSessions ||
        form.value.sendToHrmUsers ||
        form.value.scheduleByDate ||
        form.value.addToCalendar,
    )
    resetPreview()
  } catch (error) {
    console.error("Error loading announcement form", error)
    loadErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function normalizeDateTime(value) {
  if (!value) {
    return null
  }

  const date = value instanceof Date ? value : new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

function serializeDateTime(value) {
  const date = normalizeDateTime(value)

  return date ? date.toISOString() : null
}

function getCalendarDateValidationMessage() {
  if (!form.value.addToCalendar) {
    return ""
  }

  const startDate = normalizeDateTime(form.value.eventStartDate)
  const endDate = normalizeDateTime(form.value.eventEndDate)

  if (!startDate || !endDate) {
    return t("Start date") + " / " + t("End date")
  }

  if (endDate <= startDate) {
    return t("The end date must be after the start date.")
  }

  return ""
}

function buildPayload() {
  return {
    title: form.value.title,
    content: form.value.content,
    language: form.value.language,
    recipients: form.value.recipients,
    sendByEmail: form.value.sendByEmail && !form.value.emailAlreadySent,
    sendToUsersInSessions: form.value.sendByEmail && form.value.sendToUsersInSessions,
    sendToHrmUsers: form.value.sendByEmail && form.value.sendToHrmUsers,
    sendCopyToSelf: form.value.sendCopyToSelf,
    scheduleByDate: form.value.scheduleAvailable && form.value.scheduleByDate,
    scheduleDate: form.value.scheduleByDate ? form.value.scheduleDate : "",
    addToCalendar: form.value.calendarAvailable && form.value.addToCalendar,
    eventStartDate: form.value.addToCalendar ? serializeDateTime(form.value.eventStartDate) : null,
    eventEndDate: form.value.addToCalendar ? serializeDateTime(form.value.eventEndDate) : null,
    reminders: form.value.addToCalendar
      ? form.value.reminders.map((reminder) => ({
          count: Number(reminder.count || 0),
          period: reminder.period || "i",
        }))
      : [],
    csrfToken: form.value.csrfToken,
  }
}

function buildEmailPayload() {
  const sendPrimaryNow =
    form.value.sendByEmail && !form.value.emailAlreadySent && !form.value.scheduleByDate

  return {
    sendByEmail: sendPrimaryNow,
    sendToUsersInSessions: sendPrimaryNow && form.value.sendToUsersInSessions,
    sendToHrmUsers: sendPrimaryNow && form.value.sendToHrmUsers,
    sendCopyToSelf: form.value.sendCopyToSelf,
    csrfToken: form.value.emailCsrfToken,
  }
}

function formatEmailDeliveryWarning(response, fallbackMessage) {
  const lines = [response?.message || fallbackMessage]
  const internalMessageCount = Number(response?.internalMessageCount || 0)
  const internalMessageCreatedCount = Number(response?.internalMessageCreatedCount || 0)
  const internalMessageFailedCount = Number(response?.internalMessageFailedCount || 0)
  const failedRecipients = Array.isArray(response?.failedRecipients) ? response.failedRecipients : []

  if (internalMessageCount > 0) {
    lines.push(`${t("Internal messages available")}: ${internalMessageCount}`)
  }

  if (internalMessageCreatedCount > 0) {
    lines.push(`${t("Internal messages created now")}: ${internalMessageCreatedCount}`)
  }

  if (internalMessageFailedCount > 0) {
    lines.push(`${t("Internal message failures")}: ${internalMessageFailedCount}`)
  }

  if (failedRecipients.length) {
    lines.push(`${t("Failed email recipients")}: ${failedRecipients.join(", ")}`)
  }

  return lines.join("\n")
}

function clearSelectedAttachments() {
  attachmentFiles.value = []
  fileComment.value = ""
  if (attachmentInputRef.value) {
    attachmentInputRef.value.value = ""
  }
}

function blurActiveElement() {
  if (typeof document !== "undefined" && document.activeElement instanceof HTMLElement) {
    document.activeElement.blur()
  }
}

async function showFormError(message) {
  formErrorMessage.value = message
  await nextTick()
  blurActiveElement()
  formErrorRef.value?.scrollIntoView({ behavior: "smooth", block: "center" })
  formErrorRef.value?.focus({ preventScroll: true })
}

async function showFormWarning(message) {
  formWarningMessage.value = message
  await nextTick()
  blurActiveElement()
  formWarningRef.value?.scrollIntoView({ behavior: "smooth", block: "center" })
  formWarningRef.value?.focus({ preventScroll: true })
}

async function previewAnnouncement() {
  formErrorMessage.value = ""
  formWarningMessage.value = ""

  if (selectedClassId.value && form.value.recipients.length === 0) {
    resetPreview()
    await showFormError(t("No available options"))

    return
  }

  const calendarValidationMessage = getCalendarDateValidationMessage()
  if (calendarValidationMessage) {
    resetPreview()
    await showFormError(calendarValidationMessage)

    return
  }

  isPreviewing.value = true

  try {
    const payload = buildPayload()
    const response = await announcementService.preview(payload, getContextParams())
    previewRecipients.value = Array.isArray(response.previewRecipients) ? response.previewRecipients : []
    previewReady.value = previewRecipients.value.length > 0

    if (response.csrfToken) {
      form.value.csrfToken = response.csrfToken
    }

    if (previewReady.value) {
      previewPayload.value = {
        ...payload,
        csrfToken: response.csrfToken || payload.csrfToken,
      }
    }
  } catch (error) {
    console.error("Error previewing announcement recipients", error)
    resetPreview()
    await showFormError(
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred"),
    )
  } finally {
    isPreviewing.value = false
  }
}

async function saveAnnouncement() {
  formSubmitted.value = true
  formErrorMessage.value = ""
  formWarningMessage.value = ""

  if (!form.value.title.trim() || !String(form.value.content || "").replace(/<[^>]*>/g, "").trim()) {
    await showFormError(t("Please fill all required fields"))

    return
  }

  if (form.value.scheduleByDate && !form.value.scheduleDate) {
    await showFormError(t("Date to send notification"))

    return
  }

  const calendarValidationMessage = getCalendarDateValidationMessage()
  if (calendarValidationMessage) {
    await showFormError(calendarValidationMessage)

    return
  }

  if (!previewReady.value || !previewPayload.value) {
    await showFormError(t("Preview"))

    return
  }

  isSaving.value = true

  try {
    const wasNew = !form.value.id
    const response = form.value.id
      ? await announcementService.update(form.value.id, previewPayload.value, getContextParams())
      : await announcementService.create(previewPayload.value, getContextParams())

    const announcementId = Number(response?.id || form.value.id || 0)
    form.value.id = announcementId

    if (wasNew && form.value.addToCalendar) {
      form.value.calendarAvailable = false
      form.value.addToCalendar = false
      form.value.eventStartDate = null
      form.value.eventEndDate = null
      form.value.reminders = []
    }

    if (form.value.attachmentsEnabled && attachmentFiles.value.length && announcementId > 0) {
      try {
        const uploadResponse = await announcementService.uploadAttachments(
          announcementId,
          attachmentFiles.value,
          fileComment.value,
          form.value.attachmentCsrfToken,
          getContextParams(),
        )
        const uploadedAttachments = Array.isArray(uploadResponse?.attachments) ? uploadResponse.attachments : []
        form.value.attachments = [...form.value.attachments, ...uploadedAttachments]
        clearSelectedAttachments()
      } catch (error) {
        console.error("Error uploading announcement attachments", error)
        await showFormWarning(
          error?.response?.data?.detail ||
            error?.response?.data?.["hydra:description"] ||
            t("The announcement was saved, but an attachment could not be uploaded."),
        )
        resetPreview()

        return
      }
    }

    const shouldSendEmail =
      (form.value.sendByEmail && !form.value.emailAlreadySent && !form.value.scheduleByDate) ||
      form.value.sendCopyToSelf

    if (shouldSendEmail && announcementId > 0) {
      try {
        const emailResponse = await announcementService.sendEmail(
          announcementId,
          buildEmailPayload(),
          getContextParams(),
        )

        form.value.emailAlreadySent = Boolean(emailResponse?.emailSent)
        if (form.value.emailAlreadySent) {
          form.value.sendByEmail = false
          form.value.sendToUsersInSessions = false
          form.value.sendToHrmUsers = false
        }
        if (emailResponse?.copySent) {
          form.value.sendCopyToSelf = false
        }

        if (!emailResponse?.success) {
          await showFormWarning(
            formatEmailDeliveryWarning(
              emailResponse,
              t("The announcement was saved, but no email could be delivered."),
            ),
          )
          resetPreview()

          return
        }
      } catch (error) {
        console.error("Error sending announcement email", error)
        await showFormWarning(
          error?.response?.data?.detail ||
            error?.response?.data?.["hydra:description"] ||
            t("The announcement was saved, but email delivery failed."),
        )
        resetPreview()

        return
      }
    }

    await router.push(listRoute.value)
  } catch (error) {
    console.error("Error saving announcement", error)
    await showFormError(
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred"),
    )
  } finally {
    isSaving.value = false
  }
}

function confirmDeleteAttachment(attachment) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteAttachment(attachment),
  })
}

async function deleteAttachment(attachment) {
  if (!form.value.id) return

  deletingAttachmentId.value = Number(attachment.id)
  formErrorMessage.value = ""

  try {
    await announcementService.deleteAttachment(
      form.value.id,
      attachment.id,
      form.value.attachmentCsrfToken,
      getContextParams(),
    )
    form.value.attachments = form.value.attachments.filter((item) => Number(item.id) !== Number(attachment.id))
  } catch (error) {
    console.error("Error deleting announcement attachment", error)
    await showFormError(
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred"),
    )
  } finally {
    deletingAttachmentId.value = 0
  }
}

watch(
  () => [
    form.value.title,
    form.value.content,
    form.value.language,
    form.value.recipients,
    form.value.sendByEmail,
    form.value.sendToUsersInSessions,
    form.value.sendToHrmUsers,
    form.value.sendCopyToSelf,
    form.value.scheduleByDate,
    form.value.scheduleDate,
    form.value.addToCalendar,
    form.value.eventStartDate,
    form.value.eventEndDate,
    form.value.reminders,
  ],
  resetPreview,
  { deep: true },
)

watch(
  () => form.value.sendByEmail,
  (sendByEmail) => {
    if (sendByEmail) return

    form.value.sendToUsersInSessions = false
    form.value.sendToHrmUsers = false
    form.value.scheduleByDate = false
  },
)

watch(
  () => form.value.scheduleByDate,
  (scheduleByDate) => {
    if (scheduleByDate) {
      form.value.sendToHrmUsers = false
    }
  },
)

watch(
  () => [route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadForm,
)

onMounted(loadForm)
</script>
