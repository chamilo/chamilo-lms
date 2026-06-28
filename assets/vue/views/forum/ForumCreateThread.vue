<template>
  <div>
    <SectionHeader :title="t('Create thread')" />

    <BaseToolbar class="mb-4">
      <BaseButton
        :label="t('Back to threads')"
        :route="{ name: 'ForumThreadList', params: { node: parentId, forumId }, query: route.query }"
        icon="back"
        only-icon
        size="small"
        type="plain"
      />
    </BaseToolbar>

    <div
      v-if="forumAvailabilityMessage"
      class="mb-4 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
    >
      {{ forumAvailabilityMessage }}
    </div>

    <form
      class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      novalidate
      @submit.prevent="submitThread"
    >
      <div class="flex flex-col gap-4">
        <BaseInputText
          id="forum-thread-title"
          v-model="form.title"
          :error-text="t('Title is required')"
          :form-submitted="formSubmitted"
          :is-invalid="formSubmitted && !form.title.trim()"
          :label="t('Thread title')"
          name="thread_title"
          required
        />

        <BaseTinyEditor
          v-model="form.text"
          :help-text="formSubmitted && !hasMessage ? t('Message is required') : ''"
          :title="t('Message')"
          editor-id="forum-thread-message"
        />

        <BaseAdvancedSettingsButton v-model="advancedSettingsVisible">
          <div class="space-y-4">
            <div
              v-if="isAllowedToEdit"
              class="rounded-lg border border-gray-20 bg-white p-4"
            >
              <label class="flex items-center gap-3 text-sm font-medium text-gray-800">
                <input
                  v-model="form.threadSticky"
                  class="h-4 w-4 rounded border-gray-300"
                  name="thread_sticky"
                  type="checkbox"
                />
                <span>{{ t('Sticky thread') }}</span>
              </label>
            </div>

            <div
              v-if="isAllowedToEdit"
              class="rounded-lg border border-gray-20 bg-white p-4"
            >
              <label class="flex items-center gap-3 text-sm font-medium text-gray-800">
                <input
                  v-model="form.gradebookEnabled"
                  class="h-4 w-4 rounded border-gray-300"
                  name="thread_qualify_gradebook"
                  type="checkbox"
                />
                <span>{{ t("Grade this thread") }}</span>
              </label>

              <div
                v-if="form.gradebookEnabled"
                class="mt-4 space-y-4"
              >
                <div class="grid gap-4 md:grid-cols-2">
                  <BaseSelect
                    id="forum-thread-gradebook-category"
                    v-model="form.gradebookCategoryId"
                    :is-invalid="formSubmitted && form.gradebookEnabled && !form.gradebookCategoryId"
                    :label="t('Select assessment')"
                    :message-text="formSubmitted && form.gradebookEnabled && !form.gradebookCategoryId ? t('Select assessment') : null"
                    :options="gradebookCategoryOptions"
                    name="category_id"
                  />

                  <BaseInputText
                    id="forum-thread-grade-title"
                    v-model="form.threadTitleQualify"
                    :label="t('Column header in Competences Report')"
                    name="calification_notebook_title"
                  />

                  <BaseInputText
                    id="forum-thread-grade-max"
                    v-model="form.threadQualifyMax"
                    :is-invalid="formSubmitted && form.gradebookEnabled && Number(form.threadQualifyMax) <= 0"
                    :label="t('Maximum score')"
                    name="numeric_calification"
                    type="number"
                  />

                  <BaseInputText
                    id="forum-thread-grade-weight"
                    v-model="form.threadWeight"
                    :is-invalid="formSubmitted && form.gradebookEnabled && Number(form.threadWeight) <= 0"
                    :label="t('Weight in Report')"
                    name="weight_calification"
                    type="number"
                  />
                </div>

                <label class="flex items-center gap-3 rounded-md bg-gray-10 p-3 text-sm text-gray-700">
                  <input
                    v-model="form.threadPeerQualify"
                    class="h-4 w-4 rounded border-gray-300"
                    name="thread_peer_qualify"
                    type="checkbox"
                  />
                  <span>{{ t("Thread scored by peers") }}</span>
                </label>
              </div>
            </div>

            <div class="rounded-lg border border-gray-20 bg-white p-4">
              <label
                v-if="showPostNotification"
                class="flex items-center gap-3 text-sm font-medium text-gray-800"
              >
                <input
                  v-model="form.postNotification"
                  class="h-4 w-4 rounded border-gray-300"
                  name="post_notification"
                  type="checkbox"
                />
                <span>{{ t('Notify me by e-mail when somebody replies') }}</span>
              </label>

              <div
                v-if="allowAttachments"
                class="mt-4 border-t border-gray-20 pt-4"
              >
                <BaseFileUploadMultiple
                  v-model="form.attachments"
                  :label="t('Attach files')"
                  name="thread_attachments"
                  size="small"
                />
              </div>

              <p
                v-else-if="forum"
                class="mt-4 border-t border-gray-20 pt-4 text-xs text-gray-500"
              >
                {{ t('Attachments are disabled for this forum') }}
              </p>
            </div>
          </div>
        </BaseAdvancedSettingsButton>

        <div class="flex flex-wrap justify-end gap-2">
          <BaseButton
            :label="t('Cancel')"
            icon="back"
            :route="{ name: 'ForumThreadList', params: { node: parentId, forumId }, query: route.query }"
            type="plain"
          />
          <BaseButton
            :disabled="isSubmitting || !canSubmitThread"
            :is-loading="isSubmitting"
            :is-submit="true"
            :label="isSubmitting ? t('Saving') : t('Create thread')"
            icon="add-topic"
            type="success"
          />
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUploadMultiple from "../../components/basecomponents/BaseFileUploadMultiple.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import forumService from "../../services/forumService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()
const courseSettingsStore = useCourseSettings()
const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true, sessionCoach: true })

const csrfToken = ref("")
const forum = ref(null)
const gradebookCategories = ref([])
const showPostNotification = computed(() => !courseSettingsStore.isSettingEnabled("hide_forum_notifications"))
const isSubmitting = ref(false)
const formSubmitted = ref(false)
const advancedSettingsVisible = ref(false)
const form = reactive({
  title: "",
  text: "",
  threadSticky: false,
  postNotification: false,
  attachments: [],
  gradebookEnabled: false,
  gradebookCategoryId: null,
  threadTitleQualify: "",
  threadQualifyMax: "",
  threadWeight: "",
  threadPeerQualify: false,
})

const parentId = computed(() => Number(route.params.node || 0))
const forumId = computed(() => Number(route.params.forumId || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const baseQuery = computed(() => ({
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const allowAttachments = computed(() => 1 === Number(forum.value?.allowAttachments || 0))
const gradebookCategoryOptions = computed(() =>
  gradebookCategories.value.map((category) => ({
    label: category.title,
    value: Number(category.id || 0),
  })),
)
const forumAvailabilityStatus = computed(() => getForumAvailabilityStatus(forum.value))
const forumAvailabilityMessage = computed(() => {
  if ("not_started" === forumAvailabilityStatus.value) {
    return t("The forum is not open yet.")
  }

  if ("closed" === forumAvailabilityStatus.value) {
    return t("The forum is closed.")
  }

  if (!isAllowedToEdit.value && 0 !== Number(forum.value?.locked || 0)) {
    return t("The forum is locked.")
  }

  if (!isAllowedToEdit.value && 1 !== Number(forum.value?.allowNewThreads || 0)) {
    return t("New threads are not allowed in this forum.")
  }

  return ""
})
const canSubmitThread = computed(
  () =>
    isAllowedToEdit.value ||
    ("open" === forumAvailabilityStatus.value &&
      0 === Number(forum.value?.locked || 0) &&
      1 === Number(forum.value?.allowNewThreads || 0)),
)
const hasMessage = computed(() => stripTags(form.text).trim().length > 0)

function getForumAvailabilityStatus(item) {
  if (!item) {
    return "open"
  }

  if (["open", "closed", "not_started"].includes(String(item.availabilityStatus || ""))) {
    return item.availabilityStatus
  }

  const now = Date.now()
  const startTime = item.startTime ? new Date(item.startTime).getTime() : 0
  if (startTime && startTime > now) {
    return "not_started"
  }

  const endTime = item.endTime ? new Date(item.endTime).getTime() : 0
  if (endTime && endTime < now) {
    return "closed"
  }

  return "open"
}

function stripTags(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function isFormValid() {
  if (!form.title.trim() || !hasMessage.value) {
    return false
  }

  if (!form.gradebookEnabled) {
    return true
  }

  return Boolean(form.gradebookCategoryId && Number(form.threadQualifyMax) > 0 && Number(form.threadWeight) > 0)
}

async function loadGradingOptions() {
  if (!isAllowedToEdit.value) {
    gradebookCategories.value = []

    return
  }

  try {
    const gradingOptions = await forumService.getGradingOptions(baseQuery.value)
    gradebookCategories.value = gradingOptions.categories || []
    if (!form.gradebookCategoryId && gradebookCategoryOptions.value.length) {
      form.gradebookCategoryId = gradebookCategoryOptions.value[0].value
    }
  } catch (error) {
    console.error("Error loading forum grading options:", error)
    gradebookCategories.value = []
  }
}

async function loadInitialData() {
  const [forumItem, tokenResponse] = await Promise.all([
    forumService.getForum(forumId.value, baseQuery.value),
    forumService.getActionToken(),
  ])

  forum.value = forumItem
  if (!showPostNotification.value) {
    form.postNotification = false
  }
  csrfToken.value = tokenResponse.token || ""
  await loadGradingOptions()
}

async function submitThread() {
  formSubmitted.value = true

  if (!isFormValid()) {
    return
  }

  if (!canSubmitThread.value) {
    notifications.showErrorNotification(forumAvailabilityMessage.value || t("New threads are not allowed in this forum."))

    return
  }

  isSubmitting.value = true

  try {
    const response = await forumService.createThread(baseQuery.value, {
      forumId: forumId.value,
      title: form.title.trim(),
      text: form.text.trim(),
      threadSticky: isAllowedToEdit.value && form.threadSticky,
      postNotification: showPostNotification.value && form.postNotification,
      csrfToken: csrfToken.value,
      attachments: allowAttachments.value ? form.attachments : [],
      gradebookEnabled: isAllowedToEdit.value && form.gradebookEnabled,
      gradebookCategoryId: form.gradebookEnabled ? Number(form.gradebookCategoryId || 0) : null,
      threadTitleQualify: form.threadTitleQualify.trim(),
      threadQualifyMax: form.gradebookEnabled ? Number(form.threadQualifyMax || 0) : 0,
      threadWeight: form.gradebookEnabled ? Number(form.threadWeight || 0) : 0,
      threadPeerQualify: form.gradebookEnabled && form.threadPeerQualify,
    })

    if (response.requiresApproval) {
      notifications.showInfoNotification(t("Your message has to be approved before people can view it."))
      await router.push({ name: "ForumThreadList", params: { node: parentId.value, forumId: forumId.value }, query: route.query })

      return
    }

    notifications.showSuccessNotification(t("Thread created"))
    await router.push({
      name: "ForumPostList",
      params: { node: parentId.value, forumId: forumId.value, threadId: response.threadId },
      query: route.query,
    })
  } catch (error) {
    console.error("Error creating forum thread:", error)
    notifications.showErrorNotification(t("Could not create thread"))
    await loadInitialData()
  } finally {
    isSubmitting.value = false
  }
}

watch(
  isAllowedToEdit,
  async (allowed) => {
    if (allowed && 0 === gradebookCategories.value.length) {
      await loadGradingOptions()
    }
  },
)

onMounted(loadInitialData)
</script>
