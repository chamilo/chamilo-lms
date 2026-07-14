<template>
  <section class="space-y-4">
    <BaseToolbar>
      <template #start>
        <BaseButton
          icon="arrow-left"
          :label="t('Tickets')"
          only-icon
          :route="{ name: 'TicketList', query: listQuery }"
          size="normal"
          type="primary"
        />
      </template>
    </BaseToolbar>

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

    <div
      v-else-if="formData && !formData.canCreate"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{
        t(
          "You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.",
        )
      }}
    </div>

    <form
      v-else-if="formData"
      class="space-y-6 rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
      @submit.prevent="submitForm"
    >
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <BaseSelect
          id="ticket-project"
          v-model="form.projectId"
          :label="t('Project')"
          name="project_id"
          option-label="title"
          option-value="id"
          :options="formData.projects"
          @change="changeProject"
        />

        <BaseSelect
          id="ticket-category"
          v-model="form.categoryId"
          :is-invalid="formSubmitted && !form.categoryId"
          :label="t('Category')"
          :message-text="formSubmitted && !form.categoryId ? t('Required field') : categoryHelp"
          name="category_id"
          option-label="label"
          option-value="id"
          :options="formData.categories"
        />

        <BaseInputText
          id="ticket-subject"
          v-model="form.subject"
          :error-text="t('Required field')"
          :form-submitted="formSubmitted"
          :is-invalid="formSubmitted && !form.subject.trim()"
          :label="t('Subject')"
          name="subject"
          required
        />

        <BaseInputText
          id="ticket-personal-email"
          v-model="form.personalEmail"
          :label="t('Personal e-mail')"
          name="personal_email"
          type="email"
        />

        <BaseInputText
          id="ticket-phone"
          v-model="form.phone"
          :label="`${t('Phone')} (${t('Optional')})`"
          name="phone"
        />

        <BaseSelect
          id="ticket-session"
          v-model="form.sessionId"
          :allow-clear="true"
          :label="t('Session')"
          name="session_id"
          option-label="label"
          option-value="id"
          :options="formData.sessions"
          @change="changeSession"
        />

        <BaseSelect
          id="ticket-course"
          v-model="form.courseId"
          :allow-clear="true"
          :is-invalid="formSubmitted && courseIsRequired && !form.courseId"
          :label="t('Course')"
          :message-text="formSubmitted && courseIsRequired && !form.courseId ? t('Required field') : null"
          name="course_id"
          option-label="label"
          option-value="id"
          :options="formData.courses"
        />

        <template v-if="formData.isAdmin">
          <BaseSelect
            id="ticket-status"
            v-model="form.statusId"
            :label="t('Status')"
            name="status_id"
            option-label="label"
            option-value="id"
            :options="formData.statuses"
          />

          <BaseSelect
            id="ticket-priority"
            v-model="form.priorityId"
            :label="t('Priority')"
            name="priority_id"
            option-label="label"
            option-value="id"
            :options="formData.priorities"
          />

          <BaseSelect
            id="ticket-source"
            v-model="form.source"
            :label="t('Source')"
            name="source_id"
            option-label="label"
            option-value="id"
            :options="formData.sources"
          />

          <BaseAutocomplete
            id="ticket-assignee"
            v-model="selectedAssignee"
            class="w-full"
            :label="t('Assign')"
            name="assigned_user"
            option-label="label"
            :search="searchAssignees"
          />
        </template>
      </div>

      <BaseTinyEditor
        v-model="form.content"
        editor-id="ticket-content"
        :full-page="false"
        :help-text="formSubmitted && !hasMessageContent ? t('Required field') : ''"
        :title="t('Message')"
      />

      <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h2 class="text-base font-semibold text-gray-90">{{ t("Files attachments") }}</h2>
            <p class="text-sm text-gray-600">
              {{ t("Maximun file size: %s", [formatFileSize(formData.maxUploadSize)]) }}
            </p>
          </div>

          <BaseButton
            v-if="uploadSlots.length < formData.maxAttachments"
            icon="plus"
            :label="t('Add one more file')"
            size="small"
            type="primary-text"
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

      <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 pt-4">
        <BaseButton
          icon="close"
          :label="t('Cancel')"
          :route="{ name: 'TicketList', query: listQuery }"
          type="plain"
        />
        <BaseButton
          icon="send"
          :is-loading="isSubmitting"
          is-submit
          :label="t('Send message')"
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useNotification } from "../../composables/notification"
import ticketService from "../../services/ticketService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()

const formData = ref(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const formSubmitted = ref(false)
const errorMessage = ref("")
const selectedAssignee = ref(null)
const uploadSlots = ref([{ id: 1, file: null }])
let nextUploadId = 2

const form = reactive({
  projectId: Number(route.query.project_id || 0) || null,
  categoryId: null,
  subject: "",
  content: "",
  personalEmail: "",
  phone: "",
  sessionId: Number(route.query.session_id || 0) || null,
  courseId: Number(route.query.course_id || 0) || null,
  priorityId: null,
  statusId: null,
  source: "PLA",
})

const listQuery = computed(() => (form.projectId ? { project_id: String(form.projectId) } : {}))
const selectedCategory = computed(() =>
  formData.value?.categories?.find((category) => Number(category.id) === Number(form.categoryId)),
)
const courseIsRequired = computed(() => Boolean(selectedCategory.value?.courseRequired))
const categoryHelp = computed(() => selectedCategory.value?.description || null)
const hasMessageContent = computed(() => stripHtml(form.content).trim().length > 0)

onMounted(() => loadForm())

async function loadForm({ preserveValues = false } = {}) {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await ticketService.getForm({
      projectId: form.projectId || undefined,
      sessionId: form.sessionId || undefined,
    })
    formData.value = response

    if (!preserveValues) {
      form.projectId = Number(response.projectId || 0) || null
      form.categoryId = response.categories?.[0]?.id || null
      form.priorityId = Number(response.defaultPriorityId || 1)
      form.statusId = Number(response.defaultStatusId || 1)
      form.source = response.defaultSource || "PLA"
    }

    const availableCourseIds = new Set((response.courses || []).map((course) => Number(course.id)))
    if (form.courseId && !availableCourseIds.has(Number(form.courseId))) {
      form.courseId = null
    }
  } catch (error) {
    console.error("[TicketCreate] Failed to load form", error)
    errorMessage.value = t("An error occurred")
    formData.value = null
  } finally {
    isLoading.value = false
  }
}

async function changeProject() {
  form.categoryId = null
  await loadForm({ preserveValues: true })
  form.categoryId = formData.value?.categories?.[0]?.id || null
}

async function changeSession() {
  form.courseId = null
  await loadForm({ preserveValues: true })
}

async function searchAssignees(query) {
  try {
    const response = await ticketService.searchUsers(query || "")
    return Array.isArray(response.items) ? response.items : []
  } catch (error) {
    console.error("[TicketCreate] Failed to search assignees", error)
    return []
  }
}

function addUploadSlot() {
  if (!formData.value || uploadSlots.value.length >= formData.value.maxAttachments) {
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

async function submitForm() {
  formSubmitted.value = true
  if (
    !form.categoryId ||
    !form.subject.trim() ||
    !hasMessageContent.value ||
    (courseIsRequired.value && !form.courseId)
  ) {
    return
  }

  isSubmitting.value = true
  try {
    const payload = new FormData()
    payload.append("csrfToken", formData.value.csrfToken)
    payload.append("projectId", String(form.projectId || 0))
    payload.append("categoryId", String(form.categoryId || 0))
    payload.append("subject", form.subject.trim())
    payload.append("content", form.content)
    payload.append("personalEmail", form.personalEmail || "")
    payload.append("phone", form.phone || "")
    payload.append("sessionId", String(form.sessionId || 0))
    payload.append("courseId", String(form.courseId || 0))
    payload.append("priorityId", String(form.priorityId || 0))
    payload.append("statusId", String(form.statusId || 0))
    payload.append("source", form.source || "PLA")
    payload.append("assignedUserId", String(selectedAssignee.value?.id || 0))

    uploadSlots.value.forEach((slot) => {
      if (slot.file) {
        payload.append("attachments[]", slot.file)
      }
    })

    const response = await ticketService.create(payload)
    showSuccessNotification(response.message || t("Saved."))
    await router.push({ name: "TicketDetail", params: { id: response.id } })
  } catch (error) {
    console.error("[TicketCreate] Failed to create ticket", error)
    showErrorNotification(error?.response?.data?.detail || t("An error occurred"))
  } finally {
    isSubmitting.value = false
  }
}

function stripHtml(value) {
  return String(value || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/gi, " ")
}

function formatFileSize(bytes) {
  const value = Number(bytes || 0)
  if (value <= 0) {
    return "-"
  }

  const units = ["B", "KB", "MB", "GB"]
  const index = Math.min(Math.floor(Math.log(value) / Math.log(1024)), units.length - 1)
  return `${(value / 1024 ** index).toFixed(index === 0 ? 0 : 1)} ${units[index]}`
}
</script>
