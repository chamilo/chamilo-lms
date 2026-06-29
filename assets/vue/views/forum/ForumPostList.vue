<template>
  <div>
    <SectionHeader :title="thread?.title || t('Posts')" />

    <BaseToolbar class="mb-4">
      <BaseButton
        :label="t('Back to threads')"
        :route="{ name: 'ForumThreadList', params: { node: parentId, forumId }, query: route.query }"
        icon="back"
        only-icon
        size="small"
        type="plain"
      />
      <BaseButton
        v-if="canReply"
        :label="t('Reply')"
        :route="{ name: 'ForumReply', params: { node: parentId, forumId, threadId }, query: route.query }"
        icon="send"
        only-icon
        size="small"
        type="success-text"
      />
      <BaseButton
        v-if="thread?.canSubscribe"
        :label="thread?.subscribed ? t('Stop notifying me') : t('Notify me')"
        :icon="thread?.subscribed ? 'email-unread' : 'email-plus'"
        only-icon
        size="small"
        type="primary-text"
        @click="toggleThreadNotification"
      />
      <BaseButton
        :label="t('Search')"
        :route="{ name: 'ForumSearch', params: { node: parentId }, query: route.query }"
        icon="search"
        only-icon
        size="small"
        type="primary-text"
      />
      <BaseButton
        v-if="lpId"
        :label="t('Back to learning path')"
        icon="back"
        only-icon
        size="small"
        type="plain"
        @click="goBackToLearningPath"
      />
      <label class="flex items-center gap-2 text-xs text-gray-600">
        <span>{{ t('View') }}</span>
        <select
          v-model="viewType"
          class="rounded border border-gray-30 bg-white px-2 py-1 text-xs"
          name="forum_view_type"
        >
          <option
            v-for="option in viewTypeOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </label>
      <BaseButton
        v-if="thread?.canToggleSticky"
        :label="thread?.threadSticky ? t('Remove sticky') : t('Make sticky')"
        icon="tag-outline"
        only-icon
        size="small"
        type="secondary-text"
        @click="toggleThreadSticky"
      />
      <BaseButton
        v-if="canToggleThreadVisibility"
        :label="isThreadVisible(thread) ? t('Hide') : t('Show')"
        :icon="isThreadVisible(thread) ? 'eye-on' : 'eye-off'"
        only-icon
        size="small"
        type="primary-text"
        @click="toggleThreadVisibility"
      />
      <BaseButton
        v-if="thread?.canToggleLock"
        :label="Number(thread?.locked || 0) ? t('Open thread') : t('Close thread')"
        :icon="Number(thread?.locked || 0) ? 'unlock' : 'lock'"
        only-icon
        size="small"
        type="secondary-text"
        @click="toggleThreadLock"
      />
      <BaseButton
        v-if="thread?.canDelete"
        :label="t('Delete thread')"
        icon="delete"
        only-icon
        size="small"
        type="danger-text"
        @click="confirmDeleteThread"
      />
    </BaseToolbar>

    <div
      v-if="thread && !isThreadVisible(thread)"
      class="mb-4 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
    >
      {{ t("This thread is hidden from learners.") }}
    </div>

    <div
      v-if="thread?.lockedByGradebook"
      class="mb-4 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
    >
      {{ t("This option is not available.") }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else-if="!posts.length"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
    >
      <BaseIcon
        class="mx-auto mb-2 text-gray-400"
        icon="comment"
        size="big"
      />
      {{ t("No posts found") }}
    </div>

    <div
      v-else
      class="flex flex-col gap-4"
    >
      <article
        v-for="post in displayedPosts"
        :id="`post-${post.iid}`"
        :key="post.iid"
        :class="['rounded-xl border border-gray-20 bg-white p-4 shadow-sm', getPostLevelClass(post)]"
      >
        <div class="grid gap-4 md:grid-cols-[10rem_minmax(0,1fr)]">
          <aside class="flex flex-row items-center gap-3 md:flex-col md:items-center md:border-r md:border-gray-20 md:pr-4 md:text-center">
            <div class="relative shrink-0">
              <BaseUserAvatar
                :alt="post.posterFullName || t('Unknown user')"
                :image-url="getPosterAvatarUrl(post)"
                size="large"
              />
              <span
                v-if="isTeacherRole(post)"
                :title="getRoleLabel(post)"
                class="absolute -bottom-1 -right-1 inline-flex h-6 w-6 items-center justify-center rounded-full border border-white bg-support-2 text-primary shadow-sm"
              >
                <i
                  class="mdi mdi-account-tie text-sm"
                  aria-hidden="true"
                ></i>
                <span class="sr-only">{{ getRoleLabel(post) }}</span>
              </span>
            </div>
            <div class="min-w-0">
              <div class="truncate text-sm font-semibold text-primary">
                {{ post.posterFullName || t("Unknown user") }}
              </div>
              <div
                v-if="getPostRelativeTime(post) || getPostDateValue(post)"
                class="mt-1 text-xs text-gray-500"
              >
                <span :title="formatDate(getPostDateValue(post)) || getPostRelativeTime(post)">{{ getPostRelativeTime(post) }}</span>
              </div>
            </div>
          </aside>

          <div class="min-w-0">
            <div class="mb-3 flex flex-col gap-3 border-b border-gray-20 pb-3 md:flex-row md:items-start md:justify-between">
              <div class="min-w-0">
                <h2 class="truncate text-base font-semibold text-gray-90">{{ post.title }}</h2>
                <div class="mt-1 flex flex-wrap gap-2 text-xs text-gray-500">
                  <span v-if="!isPostVisible(post)">{{ t("Hidden") }}</span>
                  <span
                    v-if="showModerationStatus(post)"
                    :class="getModerationBadgeClass(post)"
                  >
                    {{ t(post.statusLabel || getModerationStatusLabel(post)) }}
                  </span>
                  <span
                    v-if="getPostRelativeTime(post) || getPostDateValue(post)"
                    :title="formatDate(getPostDateValue(post)) || getPostRelativeTime(post)"
                  >
                    {{ getPostRelativeTime(post) }}
                  </span>
                  <span
                    v-if="post.revisionRequested"
                    class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
                  >
                    {{ t('Revision requested') }}
                  </span>
                  <span
                    v-if="post.revisionLanguage"
                    class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
                  >
                    {{ t('Revision') }}
                  </span>
                </div>
              </div>

              <div class="flex shrink-0 flex-wrap items-center justify-end gap-1">
                <BaseButton
                  v-if="post.canApprove"
                  :label="t('Approve post')"
                  icon="check"
                  only-icon
                  size="small"
                  type="success-text"
                  @click="approvePost(post)"
                />
                <BaseButton
                  v-if="post.canReject"
                  :label="t('Reject post')"
                  icon="close"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmRejectPost(post)"
                />
                <BaseButton
                  v-if="post.canToggleVisibility"
                  :label="isPostVisible(post) ? t('Hide') : t('Show')"
                  :icon="isPostVisible(post) ? 'eye-on' : 'eye-off'"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="togglePostVisibility(post)"
                />
                <BaseButton
                  v-if="canReply && post.canReplyToPost"
                  :label="t('Reply to this message')"
                  :route="getReplyToPostRoute(post)"
                  icon="send"
                  only-icon
                  size="small"
                  type="success-text"
                />
                <BaseButton
                  v-if="canReply && post.canQuote"
                  :label="t('Quote this message')"
                  :route="getQuotePostRoute(post)"
                  icon="comment"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  v-if="post.canAskRevision"
                  :label="post.revisionRequested ? t('Cancel revision request') : t('Ask for a revision')"
                  icon="refresh"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="askRevision(post)"
                />
                <BaseButton
                  v-if="post.canGiveRevision"
                  :label="t('Give revision')"
                  :route="getGiveRevisionRoute(post)"
                  icon="reply"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  v-if="post.canReport"
                  :label="t('Report')"
                  icon="alert"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmReportPost(post)"
                />
                <BaseButton
                  v-if="post.canMove"
                  :label="t('Move post')"
                  icon="arrows-left-right"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="openMovePost(post)"
                />
                <BaseButton
                  v-if="post.canEdit"
                  :label="t('Edit post')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary-text"
                  @click="openEditPost(post)"
                />
                <BaseButton
                  v-if="post.canDelete"
                  :label="t('Delete post')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmDeletePost(post)"
                />
              </div>
            </div>

            <div
              class="prose prose-sm max-w-none text-gray-800"
              v-html="sanitizePostText(post.postText)"
            />

            <div
              v-if="getAttachments(post).length"
              class="mt-4 rounded-lg border border-gray-20 bg-gray-10 p-3"
            >
              <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800">
                <BaseIcon
                  icon="attachment"
                  size="small"
                />
                {{ t('Attachments') }}
              </h3>
              <ul class="flex flex-col gap-2">
                <li
                  v-for="attachment in getAttachments(post)"
                  :key="attachment.iid || attachment.id || attachment.filename"
                  class="flex items-center justify-between gap-2 text-sm"
                >
                  <div class="flex min-w-0 items-center gap-2">
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
                      {{ attachment.filename || attachment.path || t('Attachment') }}
                    </a>
                    <span class="shrink-0 text-xs text-gray-500">{{ formatSize(attachment.size) }}</span>
                  </div>
                  <BaseButton
                    v-if="attachment.canDelete"
                    :label="t('Delete attachment')"
                    icon="delete"
                    only-icon
                    size="small"
                    type="danger-text"
                    @click="confirmDeleteAttachment(attachment)"
                  />
                </li>
              </ul>
            </div>
          </div>
        </div>
      </article>
    </div>

    <BaseDialog
      v-model:is-visible="editDialogVisible"
      :title="t('Edit post')"
      header-icon="edit"
    >
      <div class="flex flex-col gap-4">
        <BaseInputText
          id="forum-post-edit-title"
          v-model="editForm.title"
          :error-text="t('Title is required')"
          :form-submitted="editFormSubmitted"
          :is-invalid="editFormSubmitted && !editForm.title.trim()"
          :label="t('Title')"
          name="post_title"
          required
        />
        <BaseTinyEditor
          v-model="editForm.text"
          :help-text="editFormSubmitted && !hasEditMessage ? t('Message is required') : ''"
          :title="t('Message')"
          editor-id="forum-post-edit-message"
        />
      </div>

      <template #footer>
        <BaseButton
          :label="t('Save')"
          :is-loading="isSavingEdit"
          icon="save"
          type="success"
          @click="savePostEdit"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="moveDialogVisible"
      :title="t('Move post')"
      header-icon="arrows-left-right"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="savePostMove"
      >
        <BaseSelect
          id="forum-post-move-target"
          v-model="moveTargetThreadId"
          :is-loading="isLoadingMoveOptions"
          :is-invalid="moveFormSubmitted && null === moveTargetThreadId"
          :label="t('Move to thread')"
          :message-text="moveFormSubmitted && null === moveTargetThreadId ? t('Target thread is required') : null"
          :options="moveThreadOptions"
          name="target_thread_id"
        />
      </form>

      <template #footer>
        <BaseButton
          :label="t('Move post')"
          :disabled="isSavingMove"
          :is-loading="isSavingMove"
          icon="arrows-left-right"
          type="success"
          @click="savePostMove"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import forumService from "../../services/forumService"
import { useSecurityStore } from "../../store/securityStore"
import { sanitizeHtml } from "../../utils/sanitizeHtml"

const { t, d, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()
const securityStore = useSecurityStore()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isSavingEdit = ref(false)
const isSavingMove = ref(false)
const isLoadingMoveOptions = ref(false)
const forum = ref(null)
const thread = ref(null)
const posts = ref([])
const csrfToken = ref("")
const editDialogVisible = ref(false)
const moveDialogVisible = ref(false)
const editFormSubmitted = ref(false)
const moveFormSubmitted = ref(false)
const editPost = ref(null)
const movePost = ref(null)
const moveTargetThreadId = ref(null)
const moveThreadOptions = ref([])
const editForm = reactive({
  title: "",
  text: "",
})

const parentId = computed(() => Number(route.params.node || 0))
const forumId = computed(() => Number(route.params.forumId || 0))
const threadId = computed(() => Number(route.params.threadId || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const lpId = computed(() => Number(route.query.lp_id || 0))
const canReply = computed(() => Boolean(thread.value?.canReply))
const canToggleThreadVisibility = computed(() => Boolean(thread.value?.canToggleVisibility))

const baseQuery = computed(() => ({
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const actionPayload = computed(() => ({ csrfToken: csrfToken.value }))
const hasEditMessage = computed(() => stripTags(editForm.text).trim().length > 0)
const viewType = ref(["flat", "threaded", "nested"].includes(String(route.query.view || "")) ? String(route.query.view) : "flat")
const viewTypeOptions = computed(() => [
  { label: t("Flat"), value: "flat" },
  { label: t("Threaded"), value: "threaded" },
  { label: t("Nested"), value: "nested" },
])
const displayedPosts = computed(() => buildDisplayedPosts(posts.value, viewType.value))

function sanitizePostText(value) {
  return sanitizeHtml(value || "")
}

function stripTags(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function buildDisplayedPosts(items, mode) {
  if ("flat" === mode) {
    return items.map((post) => ({ ...post, level: 0 }))
  }

  const byParent = new Map()
  const byId = new Map()

  items.forEach((post) => {
    const postId = Number(post.iid || 0)
    const parentId = Number(post.postParentId || 0)
    byId.set(postId, post)

    if (!byParent.has(parentId)) {
      byParent.set(parentId, [])
    }

    byParent.get(parentId).push(post)
  })

  const result = []
  const visited = new Set()

  function appendChildren(parentId, level) {
    const children = byParent.get(parentId) || []
    children.forEach((child) => {
      const childId = Number(child.iid || 0)
      if (visited.has(childId)) {
        return
      }

      visited.add(childId)
      result.push({ ...child, level })
      appendChildren(childId, "nested" === mode ? level + 1 : 1)
    })
  }

  items.forEach((post) => {
    const postId = Number(post.iid || 0)
    const parentId = Number(post.postParentId || 0)
    if (parentId && byId.has(parentId)) {
      return
    }

    if (!visited.has(postId)) {
      visited.add(postId)
      result.push({ ...post, level: 0 })
      appendChildren(postId, 1)
    }
  })

  items.forEach((post) => {
    const postId = Number(post.iid || 0)
    if (!visited.has(postId)) {
      visited.add(postId)
      result.push({ ...post, level: 0 })
    }
  })

  return result
}

function getPostLevelClass(post) {
  const level = Math.min(Number(post.level || 0), 4)

  return ["", "ml-4", "ml-8", "ml-12", "ml-16"][level]
}

function getReplyToPostRoute(post) {
  return {
    name: "ForumReply",
    params: { node: parentId.value, forumId: forumId.value, threadId: threadId.value },
    query: { ...route.query, parentPostId: post.iid },
  }
}

function getQuotePostRoute(post) {
  return {
    name: "ForumReply",
    params: { node: parentId.value, forumId: forumId.value, threadId: threadId.value },
    query: { ...route.query, parentPostId: post.iid, quotePostId: post.iid, quote: 1 },
  }
}

function getGiveRevisionRoute(post) {
  return {
    name: "ForumReply",
    params: { node: parentId.value, forumId: forumId.value, threadId: threadId.value },
    query: { ...route.query, giveRevision: "1", parentPostId: post.iid },
  }
}

function isThreadVisible(value) {
  if (value?.threadVisible === undefined || value?.threadVisible === null) {
    return true
  }

  return true === value.threadVisible || 1 === value.threadVisible || "1" === String(value.threadVisible)
}

function isPostVisible(post) {
  if (post?.visible === undefined || post?.visible === null) {
    return true
  }

  return true === post.visible || 1 === post.visible || "1" === String(post.visible)
}


function showModerationStatus(post) {
  const status = getModerationStatus(post)

  return 1 !== status && (Boolean(forum.value?.moderated) || status > 0)
}

function getModerationStatus(post) {
  return Number(post?.status || 0)
}

function getModerationStatusLabel(post) {
  const status = getModerationStatus(post)

  if (1 === status) {
    return "Validated"
  }

  if (3 === status) {
    return "Rejected"
  }

  return "Waiting for moderation"
}

function getModerationBadgeClass(post) {
  const status = getModerationStatus(post)
  const baseClass = "rounded-full px-2 py-0.5"

  if (1 === status) {
    return `${baseClass} bg-green-100 text-green-700`
  }

  if (3 === status) {
    return `${baseClass} bg-red-100 text-red-700`
  }

  return `${baseClass} bg-yellow-100 text-yellow-700`
}

function getAttachments(post) {
  return Array.isArray(post?.attachments) ? post.attachments : []
}

function getAttachmentUrl(attachment) {
  return attachment.downloadUrl || attachment.contentUrl || attachment.url || "#"
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

function goBackToLearningPath() {
  const query = { ...route.query }
  delete query.action
  delete query.create
  delete query.content
  delete query.editThreadId
  delete query.lpItemId

  return router.push({
    name: "LpBuilder",
    params: {
      node: Number(route.query.node || route.params.node || 0),
      lpId: lpId.value,
    },
    query,
  })
}

async function ensureToken() {
  if (csrfToken.value) {
    return
  }

  const tokenResponse = await forumService.getActionToken()
  csrfToken.value = tokenResponse.token || ""
}

async function loadPosts() {
  isLoading.value = true

  try {
    const [data, tokenResponse] = await Promise.all([
      forumService.getThreadPosts(threadId.value, forumId.value, baseQuery.value),
      forumService.getActionToken(),
    ])

    forum.value = data.forum
    thread.value = { ...(data.thread || {}), canReply: Boolean(data.canReply) }
    posts.value = data.posts || []
    if (!route.query.view && forum.value?.defaultView) {
      viewType.value = ["flat", "threaded", "nested"].includes(forum.value.defaultView) ? forum.value.defaultView : "flat"
    }
    csrfToken.value = tokenResponse.token || ""
  } catch (error) {
    console.error("Error fetching forum posts:", error)
    notifications.showErrorNotification(t("Could not retrieve posts"))
  } finally {
    isLoading.value = false
  }
}


async function toggleThreadVisibility() {
  const wasVisible = isThreadVisible(thread.value)

  try {
    await ensureToken()
    const response = await forumService.toggleThreadVisibility(threadId.value, baseQuery.value, { ...actionPayload.value, visible: !wasVisible })
    if (thread.value) {
      thread.value.threadVisible = response.visible
    }
    notifications.showSuccessNotification(response.visible ? t("Thread shown") : t("Thread hidden"))
    await loadPosts()
  } catch (error) {
    console.error("Error toggling forum thread visibility:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}

async function toggleThreadLock() {
  try {
    await ensureToken()
    const response = await forumService.toggleThreadLock(threadId.value, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(Number(response.locked || 0) ? t("Thread closed") : t("Thread opened"))
    await loadPosts()
  } catch (error) {
    console.error("Error toggling forum thread lock:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}

async function toggleThreadSticky() {
  try {
    await ensureToken()
    const response = await forumService.toggleThreadSticky(threadId.value, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(response.threadSticky ? t("Thread marked as sticky") : t("Thread unmarked as sticky"))
    await loadPosts()
  } catch (error) {
    console.error("Error toggling forum thread sticky status:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}


async function toggleThreadNotification() {
  if (!thread.value) {
    return
  }

  try {
    await ensureToken()
    const response = await forumService.toggleThreadSubscription(threadId.value, baseQuery.value, {
      ...actionPayload.value,
      subscribed: !thread.value.subscribed,
    })

    thread.value.subscribed = response.subscribed
    notifications.showSuccessNotification(response.subscribed ? t("Thread notifications enabled") : t("Thread notifications disabled"))
    await loadPosts()
  } catch (error) {
    console.error("Error toggling forum thread notification:", error)
    notifications.showErrorNotification(t("Could not update thread notification"))
  }
}

function confirmDeleteThread() {
  requireConfirmation({
    message: t("Are you sure you want to delete this thread?"),
    accept: () => deleteThread(),
  })
}

async function deleteThread() {
  try {
    await ensureToken()
    await forumService.deleteThread(threadId.value, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(t("Thread deleted"))
    await router.push({ name: "ForumThreadList", params: { node: parentId.value, forumId: forumId.value }, query: route.query })
  } catch (error) {
    console.error("Error deleting forum thread:", error)
    notifications.showErrorNotification(t("Could not delete thread"))
  }
}


async function approvePost(post) {
  try {
    await ensureToken()
    const response = await forumService.approvePost(post.iid, baseQuery.value, actionPayload.value)
    post.visible = response.visible
    post.status = response.status
    notifications.showSuccessNotification(t("Post approved"))
    await loadPosts()
  } catch (error) {
    console.error("Error approving forum post:", error)
    notifications.showErrorNotification(t("Could not approve post"))
  }
}

function confirmRejectPost(post) {
  requireConfirmation({
    message: t("Are you sure you want to reject this post?"),
    accept: () => rejectPost(post),
  })
}

async function rejectPost(post) {
  try {
    await ensureToken()
    const response = await forumService.rejectPost(post.iid, baseQuery.value, actionPayload.value)
    post.visible = response.visible
    post.status = response.status
    notifications.showSuccessNotification(t("Post rejected"))
    await loadPosts()
  } catch (error) {
    console.error("Error rejecting forum post:", error)
    notifications.showErrorNotification(t("Could not reject post"))
  }
}

async function togglePostVisibility(post) {
  const wasVisible = isPostVisible(post)

  try {
    await ensureToken()
    const response = await forumService.togglePostVisibility(post.iid, baseQuery.value, { ...actionPayload.value, visible: !wasVisible })
    post.visible = response.visible
    notifications.showSuccessNotification(response.visible ? t("Post shown") : t("Post hidden"))
    await loadPosts()
  } catch (error) {
    console.error("Error toggling forum post visibility:", error)
    notifications.showErrorNotification(t("Could not update post"))
  }
}

function openEditPost(post) {
  editPost.value = post
  editForm.title = post.title || ""
  editForm.text = post.postText || ""
  editFormSubmitted.value = false
  editDialogVisible.value = true
}

async function savePostEdit() {
  editFormSubmitted.value = true

  if (!editPost.value || !editForm.title.trim() || !hasEditMessage.value) {
    return
  }

  isSavingEdit.value = true

  try {
    await ensureToken()
    await forumService.updatePost(editPost.value.iid, baseQuery.value, {
      ...actionPayload.value,
      title: editForm.title.trim(),
      text: editForm.text.trim(),
    })

    notifications.showSuccessNotification(t("Post updated"))
    editDialogVisible.value = false
    await loadPosts()
  } catch (error) {
    console.error("Error updating forum post:", error)
    notifications.showErrorNotification(t("Could not update post"))
  } finally {
    isSavingEdit.value = false
  }
}

function confirmDeletePost(post) {
  requireConfirmation({
    message: t("Are you sure you want to delete this post?"),
    accept: () => deletePost(post),
  })
}

async function deletePost(post) {
  try {
    await ensureToken()
    const response = await forumService.deletePost(post.iid, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(response.threadDeleted ? t("Thread deleted") : t("Post deleted"))

    if (response.threadDeleted) {
      await router.push({ name: "ForumThreadList", params: { node: parentId.value, forumId: forumId.value }, query: route.query })
      return
    }

    await loadPosts()
  } catch (error) {
    console.error("Error deleting forum post:", error)
    notifications.showErrorNotification(t("Could not delete post"))
  }
}

async function openMovePost(post) {
  movePost.value = post
  moveTargetThreadId.value = null
  moveFormSubmitted.value = false
  moveDialogVisible.value = true
  await loadMoveThreadOptions()
}

async function loadMoveThreadOptions() {
  isLoadingMoveOptions.value = true

  try {
    const threadItems = await forumService.getThreads(forumId.value, baseQuery.value)
    moveThreadOptions.value = [
      { label: t("A new thread"), value: 0 },
      ...threadItems
        .filter((item) => Number(item.iid || 0) !== threadId.value)
        .map((item) => ({ label: item.title || t("Untitled thread"), value: Number(item.iid || 0) })),
    ]
  } catch (error) {
    console.error("Error loading target threads:", error)
    notifications.showErrorNotification(t("Could not retrieve threads"))
  } finally {
    isLoadingMoveOptions.value = false
  }
}

async function savePostMove() {
  moveFormSubmitted.value = true

  if (!movePost.value || null === moveTargetThreadId.value) {
    return
  }

  isSavingMove.value = true

  try {
    await ensureToken()
    const response = await forumService.movePost(movePost.value.iid, baseQuery.value, {
      ...actionPayload.value,
      targetThreadId: Number(moveTargetThreadId.value),
    })

    notifications.showSuccessNotification(t("Post moved"))
    moveDialogVisible.value = false

    if (Number(response.targetThreadId || 0) !== threadId.value) {
      await router.push({
        name: "ForumPostList",
        params: { node: parentId.value, forumId: Number(response.targetForumId || forumId.value), threadId: Number(response.targetThreadId || threadId.value) },
        query: route.query,
      })
      return
    }

    await loadPosts()
  } catch (error) {
    console.error("Error moving forum post:", error)
    notifications.showErrorNotification(t("Could not move post"))
  } finally {
    isSavingMove.value = false
  }
}

async function askRevision(post) {
  try {
    await ensureToken()
    const response = await forumService.askPostRevision(post.iid, baseQuery.value, actionPayload.value)
    post.revisionRequested = response.revisionRequested
    notifications.showSuccessNotification(response.revisionRequested ? t("Revision requested") : t("Revision request removed"))
    await loadPosts()
  } catch (error) {
    console.error("Error asking forum post revision:", error)
    notifications.showErrorNotification(t("Could not update revision request"))
  }
}

function confirmReportPost(post) {
  requireConfirmation({
    message: t("Are you sure you want to report this post?"),
    accept: () => reportPost(post),
  })
}

async function reportPost(post) {
  try {
    await ensureToken()
    await forumService.reportPost(post.iid, baseQuery.value, actionPayload.value)
    notifications.showSuccessNotification(t("Reported"))
  } catch (error) {
    console.error("Error reporting forum post:", error)
    notifications.showErrorNotification(t("Could not report post"))
  }
}

function confirmDeleteAttachment(attachment) {
  requireConfirmation({
    message: t("Are you sure you want to delete this attachment?"),
    accept: () => deleteAttachment(attachment),
  })
}

async function deleteAttachment(attachment) {
  try {
    await ensureToken()
    await forumService.deleteAttachment(attachment.iid || attachment.id, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(t("Attachment deleted"))
    await loadPosts()
  } catch (error) {
    console.error("Error deleting forum attachment:", error)
    notifications.showErrorNotification(t("Could not delete attachment"))
  }
}

onMounted(loadPosts)
</script>
