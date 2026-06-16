<template>
  <div>
    <SectionHeader :title="replyPageTitle" />

    <BaseToolbar class="mb-4">
      <BaseButton
        :label="t('Back to posts')"
        :route="{ name: 'ForumPostList', params: { node: parentId, forumId, threadId }, query: route.query }"
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
      @submit.prevent="submitReply"
    >
      <div class="flex flex-col gap-4">
        <BaseInputText
          id="forum-reply-title"
          v-model="form.title"
          :error-text="t('Title is required')"
          :form-submitted="formSubmitted"
          :is-invalid="formSubmitted && !form.title.trim()"
          :label="t('Title')"
          name="reply_title"
          required
        />

        <BaseTinyEditor
          v-model="form.text"
          :help-text="formSubmitted && !hasMessage ? t('Message is required') : ''"
          :title="t('Message')"
          editor-id="forum-reply-message"
        />

        <div
          v-if="isGivingRevision"
          class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700"
        >
          {{ t('This reply will be stored as a revision.') }}
        </div>

        <label
          v-if="showPostNotification"
          class="flex items-center gap-2 text-sm text-gray-700"
        >
          <input
            v-model="form.postNotification"
            class="h-4 w-4 rounded border-gray-300"
            name="post_notification"
            type="checkbox"
          />
          {{ t('Notify me by e-mail when somebody replies') }}
        </label>

        <BaseFileUploadMultiple
          v-if="allowAttachments"
          v-model="form.attachments"
          :label="t('Attach files')"
          name="reply_attachments"
          size="small"
        />

        <p
          v-else-if="forum"
          class="text-xs text-gray-500"
        >
          {{ t('Attachments are disabled for this forum') }}
        </p>

        <div class="flex flex-wrap justify-end gap-2">
          <BaseButton
            :label="t('Cancel')"
            icon="back"
            :route="{ name: 'ForumPostList', params: { node: parentId, forumId, threadId }, query: route.query }"
            type="plain"
          />
          <BaseButton
            :disabled="isSubmitting || !canSubmitReply"
            :is-loading="isSubmitting"
            :is-submit="true"
            :label="isSubmitting ? t('Saving') : t('Post reply')"
            icon="send"
            type="success"
          />
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUploadMultiple from "../../components/basecomponents/BaseFileUploadMultiple.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import forumService from "../../services/forumService"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()
const courseSettingsStore = useCourseSettings()
const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true, sessionCoach: true })

const csrfToken = ref("")
const isSubmitting = ref(false)
const formSubmitted = ref(false)
const forum = ref(null)
const showPostNotification = computed(() => !courseSettingsStore.isSettingEnabled("hide_forum_notifications"))
const thread = ref(null)
const parentPost = ref(null)
const quotedPost = ref(null)
const form = reactive({
  title: "",
  text: "",
  postNotification: false,
  attachments: [],
})

const parentId = computed(() => Number(route.params.node || 0))
const forumId = computed(() => Number(route.params.forumId || 0))
const threadId = computed(() => Number(route.params.threadId || 0))
const parentPostId = computed(() => Number(route.query.parentPostId || route.query.postParentId || 0))
const quotePostId = computed(() => Number(route.query.quotePostId || 0))
const shouldQuote = computed(() => "1" === String(route.query.quote || ""))
const isGivingRevision = computed(() => "1" === String(route.query.giveRevision || route.query.give_revision || ""))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const baseQuery = computed(() => ({
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const allowAttachments = computed(() => 1 === Number(forum.value?.allowAttachments || 0))
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

  if (!isAllowedToEdit.value && 0 !== Number(thread.value?.locked || 0)) {
    return t("The thread is locked.")
  }

  return ""
})
const canSubmitReply = computed(
  () =>
    isAllowedToEdit.value ||
    ("open" === forumAvailabilityStatus.value && 0 === Number(forum.value?.locked || 0) && 0 === Number(thread.value?.locked || 0)),
)
const replyPageTitle = computed(() => {
  if (isGivingRevision.value) {
    return t("Give revision")
  }

  return shouldQuote.value ? t("Quote this message") : parentPostId.value ? t("Reply to this message") : t("Reply to this thread")
})
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

function buildQuotedText(post) {
  if (!post) {
    return ""
  }

  const author = stripTags(post.posterFullName || t("Unknown user"))
  const quotedText = sanitizeHtml(post.postText || "")

  return `<div>&nbsp;</div><div style="margin: 5px;"><div style="font-size: 90%; font-style: italic;">${t("Quoting")} ${author}:</div><div style="color: #006600; font-size: 90%; font-style: italic; background-color: #FAFAFA; border: #D1D7DC 1px solid; padding: 3px;">${quotedText}</div></div><div>&nbsp;</div><div>&nbsp;</div>`
}

function isFormValid() {
  return Boolean(form.title.trim() && hasMessage.value)
}

async function loadInitialData() {
  const [threadPostsData, tokenResponse] = await Promise.all([
    forumService.getThreadPosts(threadId.value, forumId.value, baseQuery.value),
    forumService.getActionToken(),
  ])

  forum.value = threadPostsData.forum
  thread.value = threadPostsData.thread
  const posts = Array.isArray(threadPostsData.posts) ? threadPostsData.posts : []
  parentPost.value = parentPostId.value ? posts.find((post) => Number(post.iid || 0) === parentPostId.value) || null : null
  quotedPost.value = quotePostId.value ? posts.find((post) => Number(post.iid || 0) === quotePostId.value) || null : null

  if (!showPostNotification.value) {
    form.postNotification = false
  }

  const titleSource = parentPost.value || quotedPost.value || thread.value
  form.title = titleSource?.title ? `${t("Re:")} ${titleSource.title}` : `${t("Re:")}`

  if (shouldQuote.value && quotedPost.value) {
    form.text = buildQuotedText(quotedPost.value)
  }

  csrfToken.value = tokenResponse.token || ""
}

async function submitReply() {
  formSubmitted.value = true

  if (!isFormValid()) {
    return
  }

  if (!canSubmitReply.value) {
    notifications.showErrorNotification(forumAvailabilityMessage.value || t("The forum is closed."))

    return
  }

  isSubmitting.value = true

  try {
    const response = await forumService.createReply(baseQuery.value, {
      forumId: forumId.value,
      threadId: threadId.value,
      parentPostId: parentPostId.value || quotePostId.value || null,
      title: form.title.trim(),
      text: form.text.trim(),
      postNotification: showPostNotification.value && form.postNotification,
      giveRevision: isGivingRevision.value,
      revisionLanguage: "1",
      csrfToken: csrfToken.value,
      attachments: allowAttachments.value ? form.attachments : [],
    })

    if (response.requiresApproval) {
      notifications.showInfoNotification(t("Your message has to be approved before people can view it."))
    } else {
      notifications.showSuccessNotification(t("Reply added"))
    }

    await router.push({
      name: "ForumPostList",
      params: { node: parentId.value, forumId: forumId.value, threadId: threadId.value },
      query: route.query,
    })
  } catch (error) {
    console.error("Error replying to forum thread:", error)
    notifications.showErrorNotification(t("Could not add reply"))
    await loadInitialData()
  } finally {
    isSubmitting.value = false
  }
}

onMounted(loadInitialData)
</script>
