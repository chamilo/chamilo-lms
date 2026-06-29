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
      <div class="flex flex-col gap-5">
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
          {{ t("This reply will be stored as a revision.") }}
        </div>

        <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
          <div class="flex flex-col gap-4">
            <label
              v-if="showPostNotification"
              class="flex items-start gap-2 text-sm text-gray-700"
            >
              <input
                v-model="form.postNotification"
                class="mt-0.5 h-4 w-4 rounded border-gray-300"
                name="post_notification"
                type="checkbox"
              />
              <span>{{ t("Notify me by e-mail when somebody replies") }}</span>
            </label>

            <div
              v-if="allowAttachments"
              class="flex flex-col gap-2"
            >
              <div class="text-sm font-semibold text-gray-800">{{ t("Attachments") }}</div>
              <BaseFileUploadMultiple
                v-model="form.attachments"
                :label="t('Attach files')"
                name="reply_attachments"
                size="small"
              />
              <p class="text-xs text-gray-500">
                {{ t("You can attach one or more files to this reply.") }}
              </p>
            </div>

            <p
              v-else-if="forum"
              class="text-xs text-gray-500"
            >
              {{ t("Attachments are disabled for this forum") }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap justify-end gap-2 border-t border-gray-20 pt-4">
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

    <section
      v-if="posts.length"
      class="mt-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
    >
      <div class="mb-3 flex items-center gap-2 border-b border-gray-20 pb-3">
        <BaseIcon
          icon="comment"
          size="small"
        />
        <h2 class="text-base font-semibold text-gray-90">{{ t("Thread history") }}</h2>
      </div>

      <div class="flex max-h-[24rem] flex-col gap-3 overflow-y-auto pr-1">
        <article
          v-for="post in posts"
          :key="post.iid"
          :class="[
            'rounded-lg border border-gray-20 bg-gray-10 p-3',
            Number(post.iid || 0) === parentPostId || Number(post.iid || 0) === quotePostId ? 'border-primary' : '',
          ]"
        >
          <div class="mb-2 flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
            <div class="flex min-w-0 gap-3">
              <div class="relative shrink-0">
                <BaseUserAvatar
                  :alt="post.posterFullName || t('Unknown user')"
                  :image-url="getPosterAvatarUrl(post)"
                />
                <span
                  v-if="isTeacherRole(post)"
                  :title="getRoleLabel(post)"
                  class="absolute -bottom-1 -right-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-white bg-support-2 text-primary shadow-sm"
                >
                  <i
                    class="mdi mdi-account-tie text-xs"
                    aria-hidden="true"
                  ></i>
                  <span class="sr-only">{{ getRoleLabel(post) }}</span>
                </span>
              </div>
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-gray-90">{{ post.title }}</div>
                <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                  <span>{{ post.posterFullName || t("Unknown user") }}</span>
                  <span
                    v-if="getPostRelativeTime(post) || getPostDateValue(post)"
                    :title="formatDate(getPostDateValue(post)) || getPostRelativeTime(post)"
                  >
                    {{ getPostRelativeTime(post) }}
                  </span>
                  <span v-if="!isPostVisible(post)">{{ t("Hidden") }}</span>
                </div>
              </div>
            </div>
          </div>

          <div
            class="prose prose-sm max-w-none text-gray-800"
            v-html="sanitizePostText(post.postText)"
          />

          <div
            v-if="getAttachments(post).length"
            class="mt-3 rounded-lg border border-gray-20 bg-white p-3"
          >
            <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800">
              <BaseIcon
                icon="attachment"
                size="small"
              />
              {{ t("Attachments") }}
            </div>
            <ul class="flex flex-col gap-2">
              <li
                v-for="attachment in getAttachments(post)"
                :key="attachment.iid || attachment.id || attachment.filename"
                class="flex min-w-0 items-center gap-2 text-sm"
              >
                <BaseIcon
                  icon="file-generic"
                  size="small"
                />
                <a
                  :href="getAttachmentUrl(attachment)"
                  class="truncate text-primary hover:underline"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {{ attachment.filename || attachment.path || t("Attachment") }}
                </a>
                <span class="shrink-0 text-xs text-gray-500">{{ formatSize(attachment.size) }}</span>
              </li>
            </ul>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUploadMultiple from "../../components/basecomponents/BaseFileUploadMultiple.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useCourseSettings } from "../../store/courseSettingStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import forumService from "../../services/forumService"
import { useSecurityStore } from "../../store/securityStore"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

const { t, d, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()
const securityStore = useSecurityStore()
const courseSettingsStore = useCourseSettings()
const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true, sessionCoach: true })

const csrfToken = ref("")
const isSubmitting = ref(false)
const formSubmitted = ref(false)
const forum = ref(null)
const showPostNotification = computed(() => !courseSettingsStore.isSettingEnabled("hide_forum_notifications"))
const thread = ref(null)
const posts = ref([])
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

function sanitizePostText(value) {
  return sanitizeHtml(value || "")
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

function getAttachments(post) {
  return Array.isArray(post?.attachments) ? post.attachments : []
}

function getAttachmentUrl(attachment) {
  return attachment.downloadUrl || attachment.contentUrl || attachment.url || "#"
}

function isPostVisible(post) {
  if (post?.visible === undefined || post?.visible === null) {
    return true
  }

  return true === post.visible || 1 === post.visible || "1" === String(post.visible)
}

const relativeTimeFormatter = computed(() => {
  try {
    return new Intl.RelativeTimeFormat(locale.value || undefined, { numeric: "auto" })
  } catch (error) {
    console.error("Error creating relative time formatter:", error)

    return null
  }
})

function normalizeDateValue(value) {
  if (!value) {
    return ""
  }

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? "" : value
  }

  if (typeof value === "number") {
    const timestamp = value > 100000000000 ? value : value * 1000
    const date = new Date(timestamp)

    return Number.isNaN(date.getTime()) ? "" : date
  }

  if (typeof value === "object") {
    return normalizeDateValue(
      value.date || value.datetime || value.dateTime || value.value || value.timestamp || value.time || "",
    )
  }

  const rawValue = String(value).trim()
  if (!rawValue) {
    return ""
  }

  if (/^\d+$/.test(rawValue)) {
    return normalizeDateValue(Number(rawValue))
  }

  const normalizedValue = rawValue.includes("T") ? rawValue : rawValue.replace(" ", "T")
  const date = new Date(normalizedValue)

  return Number.isNaN(date.getTime()) ? "" : date
}

function resolveDateValue(...values) {
  for (const value of values) {
    const date = normalizeDateValue(value)
    if (date) {
      return date
    }
  }

  return ""
}

function formatDate(value) {
  const date = normalizeDateValue(value)
  if (!date) {
    return ""
  }

  return d(date, "long")
}

function isDefaultAvatarUrl(value) {
  const avatarUrl = String(value || "").trim()
  if (!avatarUrl) {
    return true
  }

  return ["/img/user_default.svg", "user_default.svg", "unknown.png", "anonymous"].some((marker) =>
    avatarUrl.includes(marker),
  )
}

function getCurrentUserAvatarUrl() {
  const user = securityStore.user || {}

  return String(user.illustrationUrl || user.avatarUrl || user.pictureUri || "").trim()
}

function isCurrentUserItem(item) {
  const posterUserId = Number(item?.posterUserId || 0)
  const currentUserId = Number(securityStore.user?.id || 0)

  return posterUserId > 0 && currentUserId > 0 && posterUserId === currentUserId
}

function getPosterAvatarUrl(item) {
  const avatarUrl = String(item?.posterAvatarUrl || item?.avatarUrl || "").trim()
  if (avatarUrl && !isDefaultAvatarUrl(avatarUrl)) {
    return avatarUrl
  }

  if (isCurrentUserItem(item)) {
    const currentAvatarUrl = getCurrentUserAvatarUrl()
    if (currentAvatarUrl && !isDefaultAvatarUrl(currentAvatarUrl)) {
      return currentAvatarUrl
    }
  }

  return ""
}

function getPostDateValue(post) {
  return resolveDateValue(
    post?.postDateIso,
    post?.createdAtIso,
    post?.sentAtIso,
    post?.postDate,
    post?.createdAt,
    post?.date,
    post?.sentAt,
    post?.postDateTimestamp,
    post?.createdAtTimestamp,
    post?.post_date,
    post?.created_at,
  )
}

function getPostRelativeTime(post) {
  return (
    post?.postRelativeTime ||
    post?.relativeTime ||
    post?.createdAtRelative ||
    (getPostDateValue(post) ? formatRelativeTime(getPostDateValue(post)) : "")
  )
}

function formatRelativeTime(value) {
  const date = normalizeDateValue(value)
  if (!date) {
    return ""
  }

  const diffInSeconds = Math.round((date.getTime() - Date.now()) / 1000)
  const units = [
    { unit: "year", seconds: 31536000 },
    { unit: "month", seconds: 2592000 },
    { unit: "week", seconds: 604800 },
    { unit: "day", seconds: 86400 },
    { unit: "hour", seconds: 3600 },
    { unit: "minute", seconds: 60 },
    { unit: "second", seconds: 1 },
  ]
  const selected = units.find((item) => Math.abs(diffInSeconds) >= item.seconds) || units[units.length - 1]
  const amount = Math.round(diffInSeconds / selected.seconds)

  if (relativeTimeFormatter.value) {
    return relativeTimeFormatter.value.format(amount, selected.unit)
  }

  return formatDate(value)
}

function isTeacherRole(item) {
  return Boolean(item?.posterIsTeacher || item?.posterRole === "teacher")
}

function getRoleLabel(item) {
  return item?.posterRoleLabel ? t(item.posterRoleLabel) : t("Teacher")
}

function formatSize(value) {
  const size = Number(value || 0)
  if (0 >= size) {
    return ""
  }

  if (1024 > size) {
    return `${size} B`
  }

  if (1024 * 1024 > size) {
    return `${Math.round(size / 1024)} KB`
  }

  return `${(size / 1024 / 1024).toFixed(1)} MB`
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
  posts.value = Array.isArray(threadPostsData.posts) ? threadPostsData.posts : []
  parentPost.value = parentPostId.value ? posts.value.find((post) => Number(post.iid || 0) === parentPostId.value) || null : null
  quotedPost.value = quotePostId.value ? posts.value.find((post) => Number(post.iid || 0) === quotePostId.value) || null : null

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
