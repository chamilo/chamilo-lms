<template>
  <div>
    <SectionHeader :title="forum?.title || t('Threads')" />

    <BaseToolbar class="mb-4">
      <BaseButton
        :label="t('Back to forums')"
        :route="{ name: 'ForumList', params: { node: parentId }, query: route.query }"
        icon="back"
        only-icon
        size="small"
        type="plain"
      />
      <BaseButton
        v-if="canCreateThread"
        :label="t('New thread')"
        :route="{ name: 'ForumCreateThread', params: { node: parentId, forumId }, query: route.query }"
        icon="add-topic"
        only-icon
        size="small"
        type="success-text"
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
    </BaseToolbar>

    <div
      v-if="forumAvailabilityMessage"
      class="mb-4 rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700"
    >
      {{ forumAvailabilityMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else-if="!threads.length"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
    >
      <BaseIcon
        class="mx-auto mb-2 text-gray-400"
        icon="add-topic"
        size="big"
      />
      {{ t("No threads found") }}
    </div>

    <div
      v-else
      class="flex flex-col gap-3"
    >
      <article
        v-for="thread in threads"
        :key="thread.iid"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div class="flex min-w-0 flex-1 gap-4">
            <div class="relative shrink-0">
              <BaseUserAvatar
                :alt="thread.posterFullName || t('Unknown user')"
                :image-url="getPosterAvatarUrl(thread)"
                size="large"
              />
              <span
                v-if="isTeacherRole(thread)"
                :title="getRoleLabel(thread)"
                class="absolute -bottom-1 -right-1 inline-flex h-6 w-6 items-center justify-center rounded-full border border-white bg-support-2 text-primary shadow-sm"
              >
                <i
                  class="mdi mdi-account-tie text-sm"
                  aria-hidden="true"
                ></i>
                <span class="sr-only">{{ getRoleLabel(thread) }}</span>
              </span>
            </div>

            <div class="min-w-0 flex-1">
              <div class="flex items-start gap-2">
                <BaseIcon
                  :icon="thread.locked ? 'lock' : 'add-topic'"
                  class="mt-0.5 shrink-0"
                  size="normal"
                />
                <router-link
                  :to="getThreadRoute(thread)"
                  class="min-w-0 truncate text-base font-semibold text-primary hover:underline"
                >
                  {{ thread.title }}
                </router-link>
              </div>

              <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                <span v-if="thread.posterFullName">{{ t("By") }} {{ thread.posterFullName }}</span>
                <span
                  v-if="getThreadRelativeTime(thread) || getThreadDateValue(thread)"
                  :title="formatAbsoluteDate(getThreadDateValue(thread)) || getThreadRelativeTime(thread)"
                >
                  {{ getThreadRelativeTime(thread) }}
                </span>
                <span>{{ t("Replies") }}: {{ thread.threadReplies || 0 }}</span>
                <span>{{ t("Views") }}: {{ thread.threadViews || 0 }}</span>
                <span v-if="thread.lastPostDate">{{ getThreadLastPostLabel(thread) }}</span>
              </div>

              <p
                v-if="getThreadPreview(thread)"
                class="mt-2 text-sm text-gray-700"
              >
                {{ getThreadPreview(thread) }}
              </p>

              <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
                <span
                  v-if="thread.threadSticky"
                  class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700"
                >
                  {{ t("Sticky") }}
                </span>
                <span
                  v-if="Number(thread.threadQualifyMax || 0) > 0"
                  class="rounded-full bg-green-100 px-2 py-0.5 text-green-700"
                >
                  {{ t("Graded") }}
                </span>
                <span
                  v-if="thread.lockedByGradebook && !thread.locked"
                  class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
                >
                  {{ t("Locked") }}
                </span>
                <span
                  v-if="thread.locked"
                  class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-700"
                >
                  {{ t("Locked") }}
                </span>
                <span
                  v-if="!isThreadVisible(thread)"
                  class="rounded-full bg-red-100 px-2 py-0.5 text-red-700"
                >
                  {{ t("Hidden") }}
                </span>
                <span
                  v-if="Number(thread.pendingPostCount || 0)"
                  class="rounded-full bg-yellow-100 px-2 py-0.5 text-yellow-700"
                >
                  {{ t("Posts pending moderation") }}: {{ thread.pendingPostCount }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex shrink-0 flex-wrap items-center justify-end gap-1">
            <BaseButton
              :label="t('View')"
              :route="{
                name: 'ForumPostList',
                params: { node: parentId, forumId: forumId, threadId: thread.iid },
                query: route.query,
              }"
              icon="comment"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="canOpenGrading(thread)"
              :label="canManage ? t('Grade thread') : t('Grade peers')"
              icon="check"
              only-icon
              size="small"
              type="secondary-text"
              @click="openGradingThread(thread)"
            />
            <BaseButton
              v-if="thread.canSubscribe"
              :label="thread.subscribed ? t('Stop notifying me') : t('Notify me')"
              :icon="thread.subscribed ? 'email-unread' : 'email-plus'"
              only-icon
              size="small"
              type="primary-text"
              @click="toggleThreadNotification(thread)"
            />
            <BaseButton
              v-if="thread.canToggleVisibility"
              :label="isThreadVisible(thread) ? t('Hide') : t('Show')"
              :icon="isThreadVisible(thread) ? 'eye-on' : 'eye-off'"
              only-icon
              size="small"
              type="primary-text"
              @click="toggleThreadVisibility(thread)"
            />
            <BaseButton
              v-if="thread.canEdit"
              :label="t('Move thread')"
              icon="arrows-left-right"
              only-icon
              size="small"
              type="secondary-text"
              @click="openMoveThread(thread)"
            />
            <BaseButton
              v-if="thread.canEdit"
              :label="t('Edit thread')"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEditThread(thread)"
            />
            <BaseButton
              v-if="thread.canToggleSticky"
              :label="thread.threadSticky ? t('Remove sticky') : t('Make sticky')"
              icon="tag-outline"
              only-icon
              size="small"
              type="secondary-text"
              @click="toggleThreadSticky(thread)"
            />
            <BaseButton
              v-if="thread.canToggleLock"
              :label="Number(thread.locked || 0) ? t('Open thread') : t('Close thread')"
              :icon="Number(thread.locked || 0) ? 'unlock' : 'lock'"
              only-icon
              size="small"
              type="secondary-text"
              @click="toggleThreadLock(thread)"
            />
            <BaseButton
              v-if="thread.canDelete"
              :label="t('Delete thread')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteThread(thread)"
            />
          </div>
        </div>
      </article>
    </div>

    <BaseDialog
      v-model:is-visible="moveDialogVisible"
      :title="t('Move thread')"
      header-icon="arrows-left-right"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveThreadMove"
      >
        <BaseSelect
          id="forum-thread-move-target"
          v-model="moveTargetForumId"
          :is-loading="isLoadingMoveOptions"
          :is-invalid="moveFormSubmitted && !moveTargetForumId"
          :label="t('Target forum')"
          :message-text="moveFormSubmitted && !moveTargetForumId ? t('Target forum is required') : null"
          :options="moveForumOptions"
          name="target_forum_id"
        />
      </form>

      <template #footer>
        <BaseButton
          :label="t('Move thread')"
          :disabled="isSavingMove"
          :is-loading="isSavingMove"
          icon="arrows-left-right"
          type="success"
          @click="saveThreadMove"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="gradingDialogVisible"
      :title="gradingDialogTitle"
      header-icon="check"
    >
      <div
        v-if="isLoadingGrading"
        class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-600"
      >
        {{ t("Loading") }}
      </div>

      <div
        v-else
        class="flex flex-col gap-4"
      >
        <label
          v-if="canManage"
          class="flex items-center gap-2 text-sm text-gray-700"
        >
          <input
            v-model="gradingForm.enabled"
            class="h-4 w-4 rounded border-gray-300"
            name="thread_qualify_gradebook"
            type="checkbox"
          />
          {{ t("Grade this thread") }}
        </label>

        <div
          v-if="canManage && gradingForm.enabled"
          class="grid gap-3 md:grid-cols-2"
        >
          <BaseSelect
            id="forum-thread-grading-category"
            v-model="gradingForm.categoryId"
            :is-invalid="gradingFormSubmitted && !gradingForm.categoryId"
            :label="t('Select assessment')"
            :message-text="gradingFormSubmitted && !gradingForm.categoryId ? t('Select assessment') : null"
            :options="gradingCategoryOptions"
            name="category_id"
          />

          <BaseInputText
            id="forum-thread-grading-title"
            v-model="gradingForm.title"
            :label="t('Column header in Competences Report')"
            name="calification_notebook_title"
          />

          <BaseInputText
            id="forum-thread-grading-max"
            v-model="gradingForm.maxScore"
            :is-invalid="gradingFormSubmitted && Number(gradingForm.maxScore) <= 0"
            :label="t('Maximum score')"
            name="numeric_calification"
            type="number"
          />

          <BaseInputText
            id="forum-thread-grading-weight"
            v-model="gradingForm.weight"
            :is-invalid="gradingFormSubmitted && Number(gradingForm.weight) <= 0"
            :label="t('Weight in Report')"
            name="weight_calification"
            type="number"
          />
        </div>

        <label
          v-if="canManage && gradingForm.enabled"
          class="flex items-center gap-2 text-sm text-gray-700"
        >
          <input
            v-model="gradingForm.peerQualify"
            class="h-4 w-4 rounded border-gray-300"
            name="thread_peer_qualify"
            type="checkbox"
          />
          {{ t("Thread scored by peers") }}
        </label>

        <div
          v-if="gradingForm.enabled && canUseGradingDialog"
          class="rounded-lg border border-gray-20"
        >
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-20 p-3">
            <strong class="text-sm text-gray-800">{{ t("Students") }}</strong>
            <BaseSelect
              id="forum-thread-grading-filter"
              v-model="gradingFilter"
              :options="gradingFilterOptions"
              name="grading_filter"
            />
          </div>

          <div
            v-if="!filteredGradingStudents.length"
            class="p-3 text-sm text-gray-500"
          >
            {{ canManage ? t("No students found") : t("No peers available to grade.") }}
          </div>

          <div
            v-for="student in filteredGradingStudents"
            :key="student.userId"
            class="flex flex-col gap-2 border-b border-gray-20 p-3 last:border-b-0 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <div class="text-sm font-medium text-gray-800">{{ student.fullName }}</div>
              <div class="text-xs text-gray-500">{{ student.username }}</div>
            </div>
            <div class="flex items-center gap-2">
              <BaseInputText
                :id="`forum-thread-score-${student.userId}`"
                v-model="student.scoreInput"
                :label="t('Score')"
                :name="`thread_score_${student.userId}`"
                type="number"
              />
              <BaseButton
                :disabled="student.isSaving"
                :is-loading="student.isSaving"
                :label="t('Save')"
                icon="save"
                size="small"
                type="success"
                @click="saveStudentScore(student)"
              />
            </div>

            <div
              v-if="canManage && student.history?.length"
              class="mt-3 w-full rounded-lg border border-gray-20 bg-gray-10 p-3 text-xs text-gray-600"
            >
              <div class="mb-2 font-semibold text-gray-800">{{ t("Score changes history") }}</div>
              <div
                v-for="(entry, index) in student.history"
                :key="`${student.userId}-history-${index}`"
                class="grid gap-2 border-t border-gray-20 py-2 first:border-t-0 md:grid-cols-3"
              >
                <span>{{ entry.qualifyUserName || t("Who changed") }}</span>
                <span>{{ entry.score }}</span>
                <span>{{ formatHistoryDate(entry.date) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <template
        v-if="canManage"
        #footer
      >
        <BaseButton
          :label="t('Save settings')"
          :disabled="isSavingGrading"
          :is-loading="isSavingGrading"
          icon="save"
          type="success"
          @click="saveThreadGradingSettings"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="editDialogVisible"
      :title="t('Edit thread')"
      header-icon="edit"
    >
      <div class="flex flex-col gap-4">
        <BaseInputText
          id="forum-thread-edit-title"
          v-model="editForm.title"
          :error-text="t('Title is required')"
          :form-submitted="editFormSubmitted"
          :is-invalid="editFormSubmitted && !editForm.title.trim()"
          :label="t('Thread title')"
          name="thread_title"
          required
        />
      </div>

      <template #footer>
        <BaseButton
          :label="t('Save')"
          :is-loading="isSavingEdit"
          icon="save"
          type="success"
          @click="saveThreadEdit"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import forumService from "../../services/forumService"
import { useSecurityStore } from "../../store/securityStore"

const { t, d, locale } = useI18n()
const route = useRoute()
const notifications = useNotification()
const securityStore = useSecurityStore()
const { requireConfirmation } = useConfirmation()
const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true, sessionCoach: true })

const isLoading = ref(false)
const isSavingEdit = ref(false)
const isSavingMove = ref(false)
const isLoadingMoveOptions = ref(false)
const forum = ref(null)
const threads = ref([])
const csrfToken = ref("")
const editDialogVisible = ref(false)
const moveDialogVisible = ref(false)
const editFormSubmitted = ref(false)
const moveFormSubmitted = ref(false)
const editThread = ref(null)
const moveThread = ref(null)
const moveTargetForumId = ref(null)
const moveForumOptions = ref([])
const gradingDialogVisible = ref(false)
const gradingThread = ref(null)
const gradingData = ref(null)
const isLoadingGrading = ref(false)
const isSavingGrading = ref(false)
const gradingFormSubmitted = ref(false)
const gradingFilter = ref("all")
const gradingForm = reactive({
  enabled: false,
  categoryId: null,
  title: "",
  maxScore: "",
  weight: "",
  peerQualify: false,
})
const editForm = reactive({
  title: "",
})

const parentId = computed(() => Number(route.params.node || 0))
const forumId = computed(() => Number(route.params.forumId || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const lpId = computed(() => Number(route.query.lp_id || 0))
const canManage = computed(() => isAllowedToEdit.value)
const forumAvailabilityStatus = computed(() => getForumAvailabilityStatus(forum.value))
const forumAvailabilityMessage = computed(() => {
  if ("not_started" === forumAvailabilityStatus.value) {
    return t("The forum is not open yet.")
  }

  if ("closed" === forumAvailabilityStatus.value) {
    return t("The forum is closed.")
  }

  return ""
})
const canCreateThread = computed(
  () =>
    forum.value &&
    0 === Number(forum.value.locked || 0) &&
    (isAllowedToEdit.value ||
      ("open" === forumAvailabilityStatus.value && 1 === Number(forum.value.allowNewThreads || 0))),
)
const gradingDialogTitle = computed(() => (canManage.value ? t("Grade thread") : t("Grade peers")))
const canUseGradingDialog = computed(() => Boolean(gradingData.value?.canManage || gradingData.value?.canPeerGrade))
const gradingCategoryOptions = computed(() =>
  (gradingData.value?.categories || []).map((category) => ({
    label: category.title,
    value: Number(category.id || 0),
  })),
)
const gradingFilterOptions = computed(() => [
  { label: t("All"), value: "all" },
  { label: t("Qualified"), value: "qualified" },
  { label: t("Not qualified"), value: "not_qualified" },
])
const filteredGradingStudents = computed(() => {
  const students = gradingData.value?.students || []
  if ("qualified" === gradingFilter.value) {
    return students.filter((student) => student.qualified)
  }

  if ("not_qualified" === gradingFilter.value) {
    return students.filter((student) => !student.qualified)
  }

  return students
})

const baseQuery = computed(() => ({
  "resourceNode.parent": parentId.value || null,
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const actionPayload = computed(() => ({ csrfToken: csrfToken.value }))

async function ensureToken() {
  if (csrfToken.value) {
    return
  }

  const tokenResponse = await forumService.getActionToken()
  csrfToken.value = tokenResponse.token || ""
}

function goBackToLearningPath() {
  const params = new URLSearchParams()
  params.set("cid", String(cid.value || ""))
  params.set("sid", String(sid.value || 0))
  params.set("gid", String(gid.value || 0))
  params.set("gradebook", "")
  params.set("action", "add_item")
  params.set("type", "step")
  params.set("lp_id", String(lpId.value))
  window.location.href = `/main/lp/lp_controller.php?${params.toString()}#resource_tab-5`
}

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

function isThreadVisible(thread) {
  if (thread?.threadVisible === undefined || thread?.threadVisible === null) {
    return true
  }

  return true === thread.threadVisible || 1 === thread.threadVisible || "1" === String(thread.threadVisible)
}

function getThreadRoute(thread) {
  return {
    name: "ForumPostList",
    params: { node: parentId.value, forumId: forumId.value, threadId: thread.iid },
    query: route.query,
  }
}

function stripTags(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function getThreadPreview(thread) {
  return stripTags(thread?.lastPostText || "").trim()
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

function formatAbsoluteDate(value) {
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

function getThreadDateValue(thread) {
  return resolveDateValue(
    thread?.threadDateIso,
    thread?.createdAtIso,
    thread?.threadDate,
    thread?.createdAt,
    thread?.date,
    thread?.threadDateTimestamp,
    thread?.thread_date,
    thread?.created_at,
  )
}

function getLastPostDateValue(thread) {
  return resolveDateValue(
    thread?.lastPostDateIso,
    thread?.lastPostCreatedAtIso,
    thread?.lastPostDate,
    thread?.lastPostCreatedAt,
    thread?.lastPostDateTimestamp,
    thread?.last_post_date,
    thread?.last_post_created_at,
  )
}

function getThreadRelativeTime(thread) {
  return (
    thread?.threadRelativeTime ||
    thread?.relativeTime ||
    thread?.createdAtRelative ||
    (getThreadDateValue(thread) ? formatRelativeTime(getThreadDateValue(thread)) : "")
  )
}

function getLastPostRelativeTime(thread) {
  return thread?.lastPostRelativeTime || (getLastPostDateValue(thread) ? formatRelativeTime(getLastPostDateValue(thread)) : "")
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

  return formatAbsoluteDate(value)
}

function getThreadLastPostLabel(thread) {
  const author = thread?.lastPosterFullName ? ` ${t("by")} ${thread.lastPosterFullName}` : ""
  const date = getLastPostRelativeTime(thread)

  return `${t("Last post")}${author}${date ? ` - ${date}` : ""}`
}

function isTeacherRole(item) {
  return Boolean(item?.posterIsTeacher || item?.posterRole === "teacher")
}

function getRoleLabel(item) {
  return item?.posterRoleLabel ? t(item.posterRoleLabel) : t("Teacher")
}

function getForumCategoryId(item) {
  if (!item?.forumCategory) {
    return 0
  }

  if (typeof item.forumCategory === "object") {
    return Number(item.forumCategory.iid || 0)
  }

  const parts = String(item.forumCategory).split("/")

  return Number(parts.pop() || 0)
}

async function loadMoveForumOptions() {
  isLoadingMoveOptions.value = true

  try {
    const [categoryItems, forumItems] = await Promise.all([
      forumService.getCategories(baseQuery.value),
      forumService.getForums(baseQuery.value),
    ])
    const categoryTitles = new Map(categoryItems.map((category) => [Number(category.iid || 0), category.title || ""]))

    moveForumOptions.value = forumItems
      .filter((item) => Number(item.iid || 0) !== forumId.value)
      .map((item) => {
        const categoryTitle = categoryTitles.get(getForumCategoryId(item)) || ""

        return {
          label: categoryTitle ? `${categoryTitle} / ${item.title}` : item.title,
          value: Number(item.iid || 0),
        }
      })
  } catch (error) {
    console.error("Error loading target forums:", error)
    notifications.showErrorNotification(t("Could not retrieve forums"))
  } finally {
    isLoadingMoveOptions.value = false
  }
}

async function loadThreads() {
  isLoading.value = true

  try {
    const [forumItem, threadItems, tokenResponse] = await Promise.all([
      forumService.getForum(forumId.value, baseQuery.value),
      forumService.getThreads(forumId.value, baseQuery.value),
      forumService.getActionToken(),
    ])

    forum.value = forumItem
    threads.value = threadItems
    csrfToken.value = tokenResponse.token || ""
  } catch (error) {
    console.error("Error fetching forum threads:", error)
    notifications.showErrorNotification(t("Could not retrieve threads"))
  } finally {
    isLoading.value = false
  }
}

function canPeerGradeThread(thread) {
  return !canManage.value && Boolean(thread?.threadPeerQualify) && Number(thread?.threadQualifyMax || 0) > 0
}

function canOpenGrading(thread) {
  if (thread?.lockedByGradebook) {
    return false
  }

  return canManage.value || canPeerGradeThread(thread)
}

function formatHistoryDate(value) {
  if (!value) {
    return ""
  }

  return new Date(value).toLocaleString()
}

async function openGradingThread(thread) {
  gradingThread.value = thread
  gradingFilter.value = "all"
  gradingFormSubmitted.value = false

  if (await loadThreadGrading(thread)) {
    gradingDialogVisible.value = true
  }
}

async function loadThreadGrading(thread) {
  isLoadingGrading.value = true

  try {
    await ensureToken()
    const data = await forumService.getThreadGrading(thread.iid, baseQuery.value)
    gradingData.value = {
      ...data,
      students: (data.students || []).map((student) => ({
        ...student,
        scoreInput: student.score ?? "",
        isSaving: false,
      })),
    }
    gradingForm.enabled = Boolean(data.enabled)
    gradingForm.categoryId = data.categoryId || gradingCategoryOptions.value[0]?.value || null
    gradingForm.title = data.title || thread.title || ""
    gradingForm.maxScore = data.maxScore || ""
    gradingForm.weight = data.weight || ""
    gradingForm.peerQualify = Boolean(data.peerQualify)

    return true
  } catch (error) {
    console.error("Error loading forum thread grading:", error)
    notifications.showErrorNotification(t("Could not retrieve thread grading"))

    return false
  } finally {
    isLoadingGrading.value = false
  }
}

async function saveThreadGradingSettings() {
  gradingFormSubmitted.value = true

  if (!gradingThread.value) {
    return
  }

  if (gradingForm.enabled && (!gradingForm.categoryId || Number(gradingForm.maxScore) <= 0 || Number(gradingForm.weight) <= 0)) {
    return
  }

  isSavingGrading.value = true

  try {
    await ensureToken()
    await forumService.updateThreadGrading(gradingThread.value.iid, baseQuery.value, {
      ...actionPayload.value,
      enabled: gradingForm.enabled,
      categoryId: gradingForm.enabled ? Number(gradingForm.categoryId || 0) : null,
      title: gradingForm.title.trim(),
      maxScore: gradingForm.enabled ? Number(gradingForm.maxScore || 0) : 0,
      weight: gradingForm.enabled ? Number(gradingForm.weight || 0) : 0,
      peerQualify: gradingForm.enabled && gradingForm.peerQualify,
    })

    notifications.showSuccessNotification(t("Thread grading updated"))
    await loadThreadGrading(gradingThread.value)
    await loadThreads()
  } catch (error) {
    console.error("Error saving forum thread grading:", error)
    notifications.showErrorNotification(t("Could not update thread grading"))
  } finally {
    isSavingGrading.value = false
  }
}

async function saveStudentScore(student) {
  if (!gradingThread.value) {
    return
  }

  const score = Number(student.scoreInput)
  if (Number.isNaN(score) || score < 0 || score > Number(gradingForm.maxScore || 0)) {
    notifications.showErrorNotification(t("Grade cannot exceed max score"))

    return
  }

  student.isSaving = true

  try {
    await ensureToken()
    const response = await forumService.saveThreadScore(gradingThread.value.iid, baseQuery.value, {
      ...actionPayload.value,
      userId: Number(student.userId),
      score,
    })

    student.score = response.score
    student.scoreInput = response.score
    student.qualified = true
    notifications.showSuccessNotification(t("Thread score saved"))
  } catch (error) {
    console.error("Error saving forum thread score:", error)
    notifications.showErrorNotification(t("Could not save thread score"))
  } finally {
    student.isSaving = false
  }
}

function openEditThread(thread) {
  editThread.value = thread
  editForm.title = thread.title || ""
  editFormSubmitted.value = false
  editDialogVisible.value = true
}

async function openMoveThread(thread) {
  moveThread.value = thread
  moveTargetForumId.value = null
  moveFormSubmitted.value = false
  moveDialogVisible.value = true
  await loadMoveForumOptions()
}

async function saveThreadMove() {
  moveFormSubmitted.value = true

  if (!moveThread.value || !moveTargetForumId.value) {
    return
  }

  isSavingMove.value = true

  try {
    await ensureToken()
    await forumService.moveThread(moveThread.value.iid, baseQuery.value, {
      ...actionPayload.value,
      targetForumId: Number(moveTargetForumId.value),
    })

    notifications.showSuccessNotification(t("Thread moved"))
    moveDialogVisible.value = false
    await loadThreads()
  } catch (error) {
    console.error("Error moving forum thread:", error)
    notifications.showErrorNotification(t("Could not move thread"))
  } finally {
    isSavingMove.value = false
  }
}

async function saveThreadEdit() {
  editFormSubmitted.value = true

  if (!editThread.value || !editForm.title.trim()) {
    return
  }

  isSavingEdit.value = true

  try {
    await ensureToken()
    await forumService.updateThread(editThread.value.iid, baseQuery.value, {
      ...actionPayload.value,
      title: editForm.title.trim(),
    })

    notifications.showSuccessNotification(t("Thread updated"))
    editDialogVisible.value = false
    await loadThreads()
  } catch (error) {
    console.error("Error updating forum thread:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  } finally {
    isSavingEdit.value = false
  }
}

async function toggleThreadLock(thread) {
  try {
    await ensureToken()
    const response = await forumService.toggleThreadLock(thread.iid, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(Number(response.locked || 0) ? t("Thread closed") : t("Thread opened"))
    await loadThreads()
  } catch (error) {
    console.error("Error toggling forum thread lock:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}

async function toggleThreadSticky(thread) {
  try {
    await ensureToken()
    const response = await forumService.toggleThreadSticky(thread.iid, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(response.threadSticky ? t("Thread marked as sticky") : t("Thread unmarked as sticky"))
    await loadThreads()
  } catch (error) {
    console.error("Error toggling forum thread sticky status:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}

async function toggleThreadVisibility(thread) {
  const wasVisible = isThreadVisible(thread)

  try {
    await ensureToken()
    const response = await forumService.toggleThreadVisibility(thread.iid, baseQuery.value, { ...actionPayload.value, visible: !wasVisible })
    thread.threadVisible = response.visible
    notifications.showSuccessNotification(response.visible ? t("Thread shown") : t("Thread hidden"))
    await loadThreads()
  } catch (error) {
    console.error("Error toggling forum thread visibility:", error)
    notifications.showErrorNotification(t("Could not update thread"))
  }
}

async function toggleThreadNotification(thread) {
  try {
    await ensureToken()
    const response = await forumService.toggleThreadSubscription(thread.iid, baseQuery.value, {
      ...actionPayload.value,
      subscribed: !thread.subscribed,
    })

    thread.subscribed = response.subscribed
    notifications.showSuccessNotification(response.subscribed ? t("Thread notifications enabled") : t("Thread notifications disabled"))
    await loadThreads()
  } catch (error) {
    console.error("Error toggling forum thread notification:", error)
    notifications.showErrorNotification(t("Could not update thread notification"))
  }
}

function confirmDeleteThread(thread) {
  requireConfirmation({
    message: t("Are you sure you want to delete this thread?"),
    accept: () => deleteThread(thread),
  })
}

async function deleteThread(thread) {
  try {
    await ensureToken()
    await forumService.deleteThread(thread.iid, baseQuery.value, actionPayload.value)

    notifications.showSuccessNotification(t("Thread deleted"))
    await loadThreads()
  } catch (error) {
    console.error("Error deleting forum thread:", error)
    notifications.showErrorNotification(t("Could not delete thread"))
  }
}

onMounted(loadThreads)
</script>
