<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="backRoute"
        />
        <BaseButton
          v-if="result.canComment"
          icon="comment"
          :label="t('Add a new comment')"
          only-icon
          size="large"
          type="success"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="openCommentDialog()"
        />
      </template>

      <template #end>
        <BaseButton
          v-if="result.item.canEdit"
          icon="pencil"
          :label="t('Edit')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          :route="editRoute"
        />
        <BaseButton
          v-if="result.item.canChangeVisibility"
          icon="eye-on"
          :label="t('Visibility')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="openVisibilityDialog('item', result.item)"
        />
        <BaseButton
          v-if="result.item.canHighlight"
          icon="trophy"
          :label="t('Highlighted')"
          only-icon
          size="large"
          :type="result.item.isHighlighted ? 'primary' : 'primary-text'"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="performItemAction('toggle_highlight')"
        />
        <BaseButton
          v-if="result.item.canQualify"
          icon="gradebook"
          :label="t('Score')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="openScoreDialog('item', result.item)"
        />
        <BaseButton
          v-if="result.item.canCopyToOwn"
          icon="copy"
          :label="t('Copy to my portfolio')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="performItemAction('copy_to_own')"
        />
        <BaseButton
          v-if="result.item.canCopyToStudent"
          icon="assign-users"
          :label="t('Copy to students')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="openCopyDialog('item', result.item)"
        />
        <BaseButton
          v-if="result.item.canUseAsTemplate"
          icon="copy"
          :label="t('Template')"
          only-icon
          size="large"
          type="primary-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="performItemAction('toggle_template')"
        />
        <BaseButton
          v-if="result.item.canDelete"
          icon="delete"
          :label="t('Delete')"
          only-icon
          size="large"
          type="danger-text"
          class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
          @click="confirmDeleteItem"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <BaseCard>
        <template #title>
          <div class="flex min-w-0 items-start gap-3">
            <BaseUserAvatar
              :alt="result.item.author?.fullName || t('User')"
              :image-url="result.item.author?.imageUrl || ''"
              size="large"
            />
            <div class="min-w-0 flex-1">
              <h1 class="break-words text-xl font-semibold text-gray-90">
                {{ result.item.title }}
              </h1>
              <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                <span>{{ result.item.author?.fullName }}</span>
                <span>·</span>
                <span>{{ formatDate(result.item.createdAt) }}</span>
                <span
                  v-if="result.item.category"
                  class="rounded-full bg-gray-15 px-2 py-0.5 text-xs"
                >
                  {{ result.item.category.label }}
                </span>
                <BaseIcon
                  v-if="result.item.isHighlighted"
                  icon="trophy"
                  size="small"
                  :tooltip="t('Highlighted')"
                />
              </div>
            </div>
          </div>
        </template>

        <div class="break-words" v-html="result.item.content"></div>

        <div
          v-if="result.item.tags?.length"
          class="mt-4 flex flex-wrap gap-2"
        >
          <span
            v-for="tag in result.item.tags"
            :key="tag.id"
            class="inline-flex items-center gap-1 rounded-full bg-gray-15 px-2.5 py-1 text-xs text-gray-700"
          >
            <BaseIcon icon="tag-outline" size="small" />
            {{ tag.label }}
          </span>
        </div>

        <div
          v-if="result.item.attachments?.length"
          class="mt-4 border-t border-gray-20 pt-4"
        >
          <h2 class="mb-2 text-sm font-semibold text-gray-90">
            {{ t("Attachments") }}
          </h2>
          <div class="space-y-2">
            <div
              v-for="attachment in result.item.attachments"
              :key="attachment.id"
              class="flex items-center gap-2"
            >
              <a
                :href="attachment.downloadUrl"
                class="inline-flex min-w-0 items-center gap-1 truncate text-sm text-primary hover:underline"
              >
                <BaseIcon icon="attachment" size="small" />
                {{ attachment.filename }}
              </a>
              <BaseButton
                v-if="attachment.canDelete"
                icon="delete"
                :label="t('Delete')"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmDeleteItemAttachment(attachment)"
              />
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 border-t border-gray-20 pt-3 text-xs text-gray-500">
          <span v-if="result.item.updatedAt">
            {{ t("Updated at") }}: {{ formatDate(result.item.updatedAt) }}
          </span>
          <span>{{ result.item.commentsCount }} {{ t("Comments") }}</span>
          <span v-if="result.item.score !== null && result.item.score !== undefined">
            {{ t("Score") }}: {{ result.item.score }}<template v-if="result.maxScore > 0"> / {{ result.maxScore }}</template>
          </span>
          <span v-if="result.item.context?.courseTitle">
            {{ result.item.context.courseTitle }}
            <template v-if="result.item.context.sessionTitle"> · {{ result.item.context.sessionTitle }}</template>
          </span>
        </div>
      </BaseCard>

      <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Comments") }}
          </h2>
          <BaseButton
            v-if="result.canComment"
            icon="comment"
            :label="t('Add a new comment')"
            type="primary"
            @click="openCommentDialog()"
          />
        </div>

        <div
          v-if="result.comments.length === 0"
          class="rounded-xl border border-gray-20 bg-white px-6 py-8 text-center text-sm italic text-gray-500 shadow-sm"
        >
          {{ t("No data available") }}
        </div>

        <PortfolioCommentTree
          v-else
          :comments="result.comments"
          @action="handleCommentAction"
          @copy-students="openCopyDialog('comment', $event)"
          @delete="confirmDeleteComment"
          @delete-attachment="confirmDeleteCommentAttachment"
          @edit="openCommentDialog($event)"
          @reply="openCommentDialog(null, $event)"
          @score="openScoreDialog('comment', $event)"
          @visibility="openVisibilityDialog('comment', $event)"
        />
      </section>
    </template>

    <BaseDialog
      v-model:is-visible="commentDialogVisible"
      :title="commentDialogTitle"
      header-icon="comment"
    >
      <div class="space-y-4">
        <BaseSelect
          v-if="!commentForm.id && result.commentTemplates.length"
          id="portfolio_comment_template"
          v-model="selectedCommentTemplateId"
          :label="t('Template')"
          :options="commentTemplateOptions"
          allow-clear
          @change="applyCommentTemplate"
        />
        <BaseTinyEditor
          v-model="commentForm.content"
          editor-id="portfolio_comment_content"
          :editor-config="commentEditorConfig"
          :title="t('Comment')"
        />
        <BaseSelect
          v-if="result.mode === 'course' && result.advancedSharingEnabled"
          id="portfolio_comment_visibility"
          v-model="commentForm.visibility"
          :label="t('Visibility')"
          :options="commentVisibilityOptions"
        />
        <BaseMultiSelect
          v-if="result.mode === 'course' && result.advancedSharingEnabled && Number(commentForm.visibility) === 2"
          v-model="commentForm.recipientIds"
          input-id="portfolio_comment_recipients"
          :label="t('Choose recipients')"
          :options="result.recipientOptions"
          option-label="fullName"
          option-value="id"
        />
        <BaseFileUploadMultiple
          v-model="commentForm.attachments"
          :label="t('Add attachments')"
        />
        <BaseInputText
          v-for="(file, index) in commentForm.attachments"
          :id="`portfolio_comment_attachment_${index}`"
          :key="`${file.name}-${index}`"
          v-model="commentForm.attachmentDescriptions[index]"
          :label="`${t('Description')}: ${file.name}`"
          :name="`commentAttachmentDescription_${index}`"
        />
      </div>

      <template #footer>
        <BaseButton
          icon="save"
          :is-loading="isSavingComment"
          :label="t('Save')"
          type="success"
          @click="saveComment"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="scoreDialogVisible"
      :title="t('Score')"
      header-icon="gradebook"
    >
      <BaseInputNumber
        id="portfolio_score"
        v-model="scoreForm.score"
        :label="t('Score')"
        :min="0"
        :max="result.maxScore > 0 ? result.maxScore : undefined"
        :step="0.1"
      />

      <template #footer>
        <BaseButton
          icon="save"
          :is-loading="isSavingAction"
          :label="t('Save')"
          type="success"
          @click="saveScore"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="visibilityDialogVisible"
      :title="t('Visibility')"
      header-icon="eye-on"
    >
      <div class="space-y-4">
        <BaseSelect
          id="portfolio_action_visibility"
          v-model="visibilityForm.visibility"
          :label="t('Visibility')"
          :options="activeVisibilityOptions"
        />
        <BaseMultiSelect
          v-if="showVisibilityRecipients"
          v-model="visibilityForm.recipientIds"
          input-id="portfolio_action_recipients"
          :label="t('Choose recipients')"
          :options="result.recipientOptions"
          option-label="fullName"
          option-value="id"
        />
      </div>

      <template #footer>
        <BaseButton
          icon="save"
          :is-loading="isSavingAction"
          :label="t('Save')"
          type="success"
          @click="saveVisibility"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="copyDialogVisible"
      :title="t('Copy to students')"
      header-icon="assign-users"
    >
      <div class="space-y-4">
        <BaseMultiSelect
          v-model="copyForm.studentIds"
          input-id="portfolio_copy_students"
          :label="t('Students')"
          :options="result.recipientOptions"
          option-label="fullName"
          option-value="id"
        />
        <BaseInputText
          id="portfolio_copy_title"
          v-model="copyForm.title"
          :label="t('Title')"
          name="copyTitle"
        />
        <BaseTinyEditor
          v-model="copyForm.content"
          editor-id="portfolio_copy_content"
          :editor-config="commentEditorConfig"
          :title="t('Content')"
        />
      </div>

      <template #footer>
        <BaseButton
          icon="copy"
          :is-loading="isSavingAction"
          :label="t('Copy')"
          type="success"
          @click="saveCopy"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseFileUploadMultiple from "../../components/basecomponents/BaseFileUploadMultiple.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import PortfolioCommentTree from "../../components/portfolio/PortfolioCommentTree.vue"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import portfolioService from "../../services/portfolioService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { showSuccessNotification, showWarningNotification, showErrorNotification } = useNotification()

const isLoading = ref(false)
const isSavingComment = ref(false)
const isSavingAction = ref(false)
const errorMessage = ref("")
const result = reactive(emptyResult())

const commentDialogVisible = ref(false)
const scoreDialogVisible = ref(false)
const visibilityDialogVisible = ref(false)
const copyDialogVisible = ref(false)
const selectedCommentTemplateId = ref(null)
const commentForm = reactive(emptyCommentForm())
const scoreForm = reactive({ targetType: "item", target: null, score: 0 })
const visibilityForm = reactive({ targetType: "item", target: null, visibility: 1, recipientIds: [] })
const copyForm = reactive({ targetType: "item", target: null, studentIds: [], title: "", content: "" })

const commentEditorConfig = {
  toolbar:
    "undo redo | styles blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link unlink | removeformat",
  menubar: false,
  height: 260,
}

const prefix = computed(() => (result.mode === "course" ? "PortfolioCourse" : "PortfolioPersonal"))
const backRoute = computed(() => ({
  name: `${prefix.value}List`,
  params: result.mode === "course" ? { node: route.params.node } : {},
  query: contextParams(),
}))
const editRoute = computed(() => ({
  name: `${prefix.value}Edit`,
  params: {
    ...(result.mode === "course" ? { node: route.params.node } : {}),
    id: result.item.id,
  },
  query: contextParams(),
}))
const commentDialogTitle = computed(() => {
  if (commentForm.id) return t("Edit Comment")
  if (commentForm.parentId) return t("Reply")
  return t("Add a new comment")
})
const commentTemplateOptions = computed(() =>
  result.commentTemplates.map((template) => ({
    label: plainText(template.content).slice(0, 80) || `${t("Template")} #${template.id}`,
    value: template.id,
  })),
)
const itemVisibilityOptions = computed(() => {
  const options = [
    { label: t("Visible"), value: 1 },
    { label: t("Hidden"), value: 0 },
  ]
  if (result.mode === "course") {
    options.splice(1, 0, { label: t("Visible only to teachers"), value: 2 })
    if (result.advancedSharingEnabled) {
      options.push({ label: t("Choose recipients"), value: 3 })
    }
  }
  return options
})
const commentVisibilityOptions = computed(() => {
  const options = [{ label: t("Visible"), value: 1 }]
  if (result.mode === "course" && result.advancedSharingEnabled) {
    options.push(
      { label: t("Hidden"), value: 0 },
      { label: t("Choose recipients"), value: 2 },
    )
  }
  return options
})
const activeVisibilityOptions = computed(() =>
  visibilityForm.targetType === "comment" ? commentVisibilityOptions.value : itemVisibilityOptions.value,
)
const showVisibilityRecipients = computed(() => {
  if (result.mode !== "course" || !result.advancedSharingEnabled) return false
  return visibilityForm.targetType === "comment"
    ? Number(visibilityForm.visibility) === 2
    : Number(visibilityForm.visibility) === 3
})

function emptyResult() {
  return {
    mode: route.meta.portfolioMode || "personal",
    item: {},
    comments: [],
    csrfToken: "",
    maxScore: 0,
    canQualifyItems: false,
    canQualifyComments: false,
    commentTemplates: [],
    recipientOptions: [],
    canComment: false,
    advancedSharingEnabled: false,
  }
}

function emptyCommentForm() {
  return {
    id: null,
    parentId: null,
    content: "",
    visibility: 1,
    recipientIds: [],
    attachments: [],
    attachmentDescriptions: [],
  }
}

function firstQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function contextParams() {
  const params = {}
  const cid = Number(firstQueryValue(route.query.cid) || 0)
  const sid = Number(firstQueryValue(route.query.sid) || 0)
  const user = Number(firstQueryValue(route.query.user) || 0)
  if (cid > 0) params.cid = cid
  if (sid > 0) params.sid = sid
  if (user > 0) params.user = user
  return params
}

function plainText(value) {
  const element = document.createElement("div")
  element.innerHTML = String(value || "")
  return String(element.textContent || element.innerText || "").trim()
}

function formatDate(value) {
  const date = new Date(value)
  return Number.isNaN(date.getTime())
    ? String(value || "")
    : new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(date)
}

function errorDetail(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function loadItem() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const response = await portfolioService.getItem(Number(route.params.id), contextParams())
    Object.assign(result, emptyResult(), response)
  } catch (error) {
    console.error("Error loading Portfolio item", error)
    errorMessage.value = errorDetail(error)
  } finally {
    isLoading.value = false
  }
}

function openCommentDialog(comment = null, parent = null) {
  Object.assign(commentForm, emptyCommentForm())
  selectedCommentTemplateId.value = null
  if (comment) {
    commentForm.id = Number(comment.id)
    commentForm.content = comment.content || ""
    commentForm.recipientIds = [...(comment.recipientIds || [])]
    commentForm.visibility = Number(comment.visibility || 1) === 2 && commentForm.recipientIds.length === 0
      ? 0
      : Number(comment.visibility || 1)
  } else if (parent) {
    commentForm.parentId = Number(parent.id)
  }
  commentDialogVisible.value = true
}

function applyCommentTemplate() {
  const template = result.commentTemplates.find((row) => Number(row.id) === Number(selectedCommentTemplateId.value))
  if (template) commentForm.content = template.content || ""
}

async function saveComment() {
  if (!plainText(commentForm.content)) {
    showWarningNotification(t("Comment is required"))
    return
  }
  if (result.advancedSharingEnabled && Number(commentForm.visibility) === 2 && !commentForm.recipientIds.length) {
    showWarningNotification(t("Choose recipients"))
    return
  }

  isSavingComment.value = true
  try {
    const payload = {
      content: commentForm.content,
      parentId: commentForm.parentId,
      visibility: Number(commentForm.visibility) === 0 ? 2 : Number(commentForm.visibility),
      recipientIds: Number(commentForm.visibility) === 0 ? [] : commentForm.recipientIds,
      attachments: commentForm.attachments,
      attachmentDescriptions: commentForm.attachmentDescriptions,
      csrfToken: result.csrfToken,
    }
    if (commentForm.id) {
      await portfolioService.updateComment(commentForm.id, payload, contextParams())
      showSuccessNotification(t("Updated"))
    } else {
      await portfolioService.createComment(result.item.id, payload, contextParams())
      showSuccessNotification(t("Created"))
    }
    commentDialogVisible.value = false
    await loadItem()
  } catch (error) {
    console.error("Error saving Portfolio comment", error)
    showErrorNotification(error)
  } finally {
    isSavingComment.value = false
  }
}

async function performItemAction(action, payload = {}) {
  isSavingAction.value = true
  try {
    const response = await portfolioService.itemAction(
      result.item.id,
      { action, csrfToken: result.csrfToken, ...payload },
      contextParams(),
    )
    showSuccessNotification(t("Updated"))
    if (action === "copy_to_own" && response?.affectedIds?.[0]) {
      await router.push({
        name: `${prefix.value}Item`,
        params: { ...(result.mode === "course" ? { node: route.params.node } : {}), id: response.affectedIds[0] },
        query: contextParams(),
      })
      return true
    }
    await loadItem()
    return true
  } catch (error) {
    console.error("Error applying Portfolio item action", error)
    showErrorNotification(error)
    return false
  } finally {
    isSavingAction.value = false
  }
}

async function performCommentAction(comment, action, payload = {}) {
  isSavingAction.value = true
  try {
    const response = await portfolioService.commentAction(
      comment.id,
      { action, csrfToken: result.csrfToken, ...payload },
      contextParams(),
    )
    showSuccessNotification(t("Updated"))
    if (action === "copy_to_own" && response?.affectedIds?.[0]) {
      await router.push({
        name: `${prefix.value}Item`,
        params: { ...(result.mode === "course" ? { node: route.params.node } : {}), id: response.affectedIds[0] },
        query: contextParams(),
      })
      return true
    }
    await loadItem()
    return true
  } catch (error) {
    console.error("Error applying Portfolio comment action", error)
    showErrorNotification(error)
    return false
  } finally {
    isSavingAction.value = false
  }
}

function handleCommentAction({ comment, action }) {
  performCommentAction(comment, action)
}

function confirmDeleteItem() {
  requireConfirmation({
    title: t("Delete"),
    message: t("Please confirm your choice"),
    accept: async () => {
      isSavingAction.value = true
      try {
        await portfolioService.itemAction(
          result.item.id,
          { action: "delete", csrfToken: result.csrfToken },
          contextParams(),
        )
        showSuccessNotification(t("Deleted"))
        await router.push(backRoute.value)
      } catch (error) {
        console.error("Error deleting Portfolio item", error)
        showErrorNotification(error)
      } finally {
        isSavingAction.value = false
      }
    },
  })
}

function confirmDeleteComment(comment) {
  requireConfirmation({
    title: t("Delete"),
    message: t("Please confirm your choice"),
    accept: () => performCommentAction(comment, "delete"),
  })
}

function confirmDeleteItemAttachment(attachment) {
  requireConfirmation({
    title: t("Delete attachment"),
    message: t("Please confirm your choice"),
    accept: () => performItemAction("delete_attachment", { attachmentId: Number(attachment.id) }),
  })
}

function confirmDeleteCommentAttachment({ comment, attachment }) {
  requireConfirmation({
    title: t("Delete attachment"),
    message: t("Please confirm your choice"),
    accept: () => performCommentAction(comment, "delete_attachment", { attachmentId: Number(attachment.id) }),
  })
}

function openScoreDialog(targetType, target) {
  scoreForm.targetType = targetType
  scoreForm.target = target
  scoreForm.score = Number(target.score || 0)
  scoreDialogVisible.value = true
}

async function saveScore() {
  const saved =
    scoreForm.targetType === "comment"
      ? await performCommentAction(scoreForm.target, "score", { score: Number(scoreForm.score) })
      : await performItemAction("score", { score: Number(scoreForm.score) })
  if (saved) scoreDialogVisible.value = false
}

function openVisibilityDialog(targetType, target) {
  visibilityForm.targetType = targetType
  visibilityForm.target = target
  visibilityForm.recipientIds = [...(target.recipientIds || [])]
  visibilityForm.visibility = targetType === "comment"
    && Number(target.visibility ?? 1) === 2
    && visibilityForm.recipientIds.length === 0
    ? 0
    : Number(target.visibility ?? 1)
  visibilityDialogVisible.value = true
}

async function saveVisibility() {
  if (showVisibilityRecipients.value && !visibilityForm.recipientIds.length) {
    showWarningNotification(t("Choose recipients"))
    return
  }
  const payload = {
    visibility:
      visibilityForm.targetType === "comment" && Number(visibilityForm.visibility) === 0
        ? 2
        : Number(visibilityForm.visibility),
    recipientIds:
      visibilityForm.targetType === "comment" && Number(visibilityForm.visibility) === 0
        ? []
        : visibilityForm.recipientIds,
  }
  const saved =
    visibilityForm.targetType === "comment"
      ? await performCommentAction(visibilityForm.target, "set_visibility", payload)
      : await performItemAction("set_visibility", payload)
  if (saved) visibilityDialogVisible.value = false
}

function openCopyDialog(targetType, target) {
  copyForm.targetType = targetType
  copyForm.target = target
  copyForm.studentIds = []
  copyForm.title = targetType === "item" ? target.title || "" : `${t("Comment")} - ${target.author?.fullName || ""}`
  copyForm.content = target.content || ""
  copyDialogVisible.value = true
}

async function saveCopy() {
  if (!copyForm.studentIds.length) {
    showWarningNotification(t("Students"))
    return
  }
  if (!plainText(copyForm.title)) {
    showWarningNotification(t("Title is required"))
    return
  }
  const payload = {
    studentIds: copyForm.studentIds,
    title: copyForm.title,
    content: copyForm.content,
  }
  const saved =
    copyForm.targetType === "comment"
      ? await performCommentAction(copyForm.target, "copy_to_students", payload)
      : await performItemAction("copy_to_students", payload)
  if (saved) copyDialogVisible.value = false
}

watch(
  () => route.fullPath,
  () => loadItem(),
  { immediate: true },
)
</script>
