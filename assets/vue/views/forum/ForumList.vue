<template>
  <div>
    <SectionHeader :title="t('Forums')" />

    <BaseToolbar class="mb-4">
      <BaseButton
        v-if="isAllowedToEdit"
        :label="t('Add category')"
        icon="folder-plus"
        only-icon
        size="small"
        type="success-text"
        @click="openCreateCategoryDialog"
      />
      <BaseButton
        v-if="isAllowedToEdit"
        :label="t('Add forum')"
        icon="add-topic"
        only-icon
        size="small"
        type="success-text"
        @click="openCreateForumDialog()"
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
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600"
    >
      {{ t("Loading") }}
    </div>

    <div
      v-else-if="!forums.length && !categories.length"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
    >
      <BaseIcon
        class="mx-auto mb-2 text-gray-400"
        icon="comment"
        size="big"
      />
      <p>{{ t("No forums found") }}</p>
      <p
        v-if="isAllowedToEdit"
        class="mt-1"
      >
        {{ t("Create your first forum category or forum to start discussions.") }}
      </p>
      <div
        v-if="isAllowedToEdit"
        class="mt-4 flex flex-wrap justify-center gap-2"
      >
        <BaseButton
          :label="t('Add category')"
          icon="folder-plus"
          type="success"
          @click="openCreateCategoryDialog"
        />
        <BaseButton
          :label="t('Add forum')"
          icon="add-topic"
          type="success"
          @click="openCreateForumDialog()"
        />
      </div>
    </div>

    <div
      v-else
      class="flex flex-col gap-4"
    >
      <div
        v-if="canFilterCategoryLanguage"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <BaseSelect
            id="forum-category-language-filter"
            v-model="categoryLanguageFilter"
            :label="t('Language')"
            :options="categoryLanguageFilterOptions"
            class="min-w-64"
            name="extra_language"
          />
          <BaseButton
            v-if="categoryLanguageFilter"
            :label="t('Reset')"
            size="small"
            type="plain"
            @click="resetCategoryLanguageFilter"
          />
        </div>
      </div>
      <section
        v-if="forumsWithoutCategory.length"
        class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
      >
        <div class="flex flex-col gap-3 border-b border-gray-20 bg-gray-15 p-4 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-2">
            <BaseIcon
              icon="folder-open"
              size="normal"
            />
            <h2 class="text-lg font-semibold text-gray-90">{{ t("General") }}</h2>
          </div>
          <BaseButton
            v-if="isAllowedToEdit"
            :label="t('Add forum')"
            icon="add-topic"
            only-icon
            size="small"
            type="success-text"
            @click="openCreateForumDialog()"
          />
        </div>
        <div class="p-4">
          <ForumCardList
            :can-manage="isAllowedToEdit"
            :forums="forumsWithoutCategory"
            @edit-forum="openEditForumDialog"
            @delete-forum="confirmDeleteForum"
            @toggle-forum-lock="toggleForumLock"
            @toggle-forum-visibility="toggleForumVisibility"
            @move-forum="moveForum"
            @toggle-forum-notification="toggleForumNotification"
          />
        </div>
      </section>

      <section
        v-for="(category, categoryIndex) in categoriesWithForums"
        :key="category.iid"
        class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
      >
        <div class="flex flex-col gap-3 border-b border-gray-20 bg-gray-15 p-4 md:flex-row md:items-center md:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <BaseIcon
                :icon="Number(category.locked || 0) ? 'lock' : 'folder-generic'"
                size="normal"
              />
              <h2 class="text-lg font-semibold text-gray-90">{{ category.title }}</h2>
              <span
                v-if="Number(category.locked || 0)"
                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
              >
                {{ t("Locked") }}
              </span>
              <span
                v-if="!isCategoryVisible(category)"
                class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700"
              >
                {{ t("Hidden") }}
              </span>
              <BaseButton
                v-if="canFoldCategories"
                :label="isCategoryFolded(category) ? t('Show') : t('Hide')"
                :icon="isCategoryFolded(category) ? 'unfold' : 'fold'"
                only-icon
                size="small"
                type="plain"
                @click="toggleCategoryFold(category)"
              />
            </div>
            <p
              v-if="category.catComment"
              class="mt-1 text-sm text-gray-600"
            >
              {{ category.catComment }}
            </p>
          </div>

          <div
            v-if="isAllowedToEdit"
            class="flex flex-wrap items-center justify-end gap-1"
          >
            <BaseButton
              :label="t('Add forum')"
              icon="add-topic"
              only-icon
              size="small"
              type="success-text"
              @click="openCreateForumDialog(category)"
            />
            <BaseButton
              :label="isCategoryVisible(category) ? t('Hide') : t('Show')"
              :icon="isCategoryVisible(category) ? 'eye-on' : 'eye-off'"
              only-icon
              size="small"
              type="primary-text"
              @click="toggleCategoryVisibility(category)"
            />
            <BaseButton
              :label="t('Edit')"
              icon="edit"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEditCategoryDialog(category)"
            />
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteCategory(category)"
            />
            <BaseButton
              :label="Number(category.locked || 0) ? t('Unlock') : t('Lock')"
              :icon="Number(category.locked || 0) ? 'unlock' : 'lock'"
              only-icon
              size="small"
              type="secondary-text"
              @click="toggleCategoryLock(category)"
            />
            <BaseButton
              :disabled="0 === categoryIndex"
              :label="t('Move up')"
              icon="arrow-up"
              only-icon
              size="small"
              type="secondary-text"
              @click="moveCategory(category, 'up')"
            />
            <BaseButton
              :disabled="categoryIndex >= categoriesWithForums.length - 1"
              :label="t('Move down')"
              icon="arrow-down"
              only-icon
              size="small"
              type="secondary-text"
              @click="moveCategory(category, 'down')"
            />
          </div>
        </div>

        <div
          v-show="!isCategoryFolded(category)"
          class="p-4"
        >
          <ForumCardList
            v-if="category.forums.length"
            :can-manage="isAllowedToEdit"
            :forums="category.forums"
            @edit-forum="openEditForumDialog"
            @delete-forum="confirmDeleteForum"
            @toggle-forum-lock="toggleForumLock"
            @toggle-forum-visibility="toggleForumVisibility"
            @move-forum="moveForum"
            @toggle-forum-notification="toggleForumNotification"
          />
          <div
            v-else
            class="rounded-lg border border-dashed border-gray-30 p-4 text-sm text-gray-600"
          >
            {{ t("No forums in this category") }}
          </div>
        </div>
      </section>
    </div>

    <BaseDialog
      v-model:is-visible="isCategoryDialogVisible"
      :title="categoryForm.id ? t('Edit category') : t('Add category')"
      header-icon="folder-plus"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveCategory"
      >
        <BaseInputText
          id="forum-category-title"
          v-model="categoryForm.title"
          :error-text="t('Title is required')"
          :form-submitted="categoryFormSubmitted"
          :is-invalid="categoryFormSubmitted && !categoryForm.title.trim()"
          :label="t('Title')"
          name="forum_category_title"
          required
        />
        <BaseTextArea
          id="forum-category-comment"
          v-model="categoryForm.comment"
          :label="t('Description')"
          name="forum_category_comment"
          rows="5"
        />
        <BaseSelect
          v-if="canEditCategoryLanguage"
          id="forum-category-language"
          v-model="categoryForm.language"
          :label="t('Language')"
          :options="categoryLanguageFilterOptions"
          name="extra_language"
        />
        <BaseCheckbox
          id="forum-category-locked"
          v-model="categoryForm.locked"
          :label="t('Locked')"
          name="forum_category_locked"
        />
      </form>
      <template #footer>
        <BaseButton
          :disabled="isSavingCategory"
          :is-loading="isSavingCategory"
          :label="categoryForm.id ? t('Save') : t('Create category')"
          icon="save"
          type="success"
          @click="saveCategory"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="isForumDialogVisible"
      :title="forumForm.id ? t('Edit forum') : t('Add forum')"
      header-icon="add-topic"
    >
      <form
        class="flex flex-col gap-4"
        @submit.prevent="saveForum"
      >
        <BaseInputText
          id="forum-title"
          v-model="forumForm.title"
          :error-text="t('Title is required')"
          :form-submitted="forumFormSubmitted"
          :is-invalid="forumFormSubmitted && !forumForm.title.trim()"
          :label="t('Title')"
          name="forum_title"
          required
        />
        <BaseTinyEditor
          v-model="forumForm.comment"
          :title="t('Description')"
          editor-id="forum-comment"
        />
        <input
          :value="forumForm.comment"
          name="forum_comment"
          type="hidden"
        />
        <BaseSelect
          id="forum-category"
          v-model="forumForm.categoryId"
          :label="t('Create in category')"
          :options="categoryOptions"
          name="forum_category"
        />
        <div class="grid gap-3 md:grid-cols-2">
          <BaseCheckbox
            id="forum-allow-new-threads"
            v-model="forumForm.allowNewThreads"
            :label="t('Allow users to start new threads')"
            name="allow_new_threads"
          />
          <BaseCheckbox
            id="forum-allow-attachments"
            v-model="forumForm.allowAttachments"
            :label="t('Allow attachments')"
            name="allow_attachments"
          />
          <BaseCheckbox
            id="forum-moderated"
            v-model="forumForm.moderated"
            :label="t('Moderated forum')"
            name="moderated"
          />
          <BaseCheckbox
            id="forum-requires-approval"
            v-model="forumForm.requiresApproval"
            :label="t('Posts require approval')"
            name="approval_direct"
          />
          <BaseCheckbox
            id="forum-students-can-edit"
            v-model="forumForm.studentsCanEdit"
            :label="t('Can learners edit their own posts?')"
            name="students_can_edit"
          />
          <BaseCheckbox
            id="forum-locked"
            v-model="forumForm.locked"
            :label="t('Locked')"
            name="forum_locked"
          />
        </div>
        <BaseSelect
          id="forum-default-view"
          v-model="forumForm.defaultView"
          :label="t('Default view type')"
          :options="defaultViewOptions"
          name="default_view_type"
        />
        <div
          v-if="currentGroupId"
          class="grid gap-3 md:grid-cols-2"
        >
          <BaseCheckbox
            id="forum-current-group-forum"
            v-model="forumForm.useCurrentGroup"
            :label="t('Forum for current group')"
            name="group_forum"
          />
          <BaseSelect
            v-if="forumForm.useCurrentGroup"
            id="forum-group-visibility"
            v-model="forumForm.groupVisibility"
            :label="t('Group forum visibility')"
            :options="groupVisibilityOptions"
            name="group_visibility"
          />
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <div class="flex flex-col gap-1">
            <BaseCalendar
              id="forum-start-time"
              v-model="forumForm.startTime"
              :label="t('Publication date')"
              :show-time="true"
            />
            <input
              :value="toApiDateTime(forumForm.startTime)"
              name="forum_start_time"
              type="hidden"
            />
            <span class="text-xs text-gray-500">{{ t('The forum will be visible starting from this date') }}</span>
          </div>
          <div class="flex flex-col gap-1">
            <BaseCalendar
              id="forum-end-time"
              v-model="forumForm.endTime"
              :label="t('Closing date')"
              :show-time="true"
            />
            <input
              :value="toApiDateTime(forumForm.endTime)"
              name="forum_end_time"
              type="hidden"
            />
            <span class="text-xs text-gray-500">{{ t('Once this date has passed, the forum will be closed') }}</span>
          </div>
        </div>

        <div class="rounded-lg border border-gray-20 bg-gray-10 p-4">
          <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <div class="flex h-24 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white">
              <img
                v-if="forumImagePreview"
                :alt="forumForm.title || t('Forum image')"
                :src="forumImagePreview"
                class="h-full w-full object-cover"
              />
              <BaseIcon
                v-else
                icon="comment"
                size="big"
              />
            </div>
            <div class="flex flex-1 flex-col gap-2">
              <div>
                <h3 class="text-sm font-semibold text-gray-90">{{ t('Forum image') }}</h3>
                <p class="text-xs text-gray-500">
                  {{ t('This image replaces the default forum icon shown next to the forum title.') }}
                </p>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <BaseFileUpload
                  :key="forumImageInputKey"
                  :label="t('Select image')"
                  accept="image/*"
                  size="small"
                  @fileSelected="selectForumImage"
                />
                <BaseButton
                  v-if="forumImagePreview"
                  :label="t('Remove image')"
                  icon="delete"
                  size="small"
                  type="danger-text"
                  @click="removeForumImage"
                />
              </div>
              <input
                :value="forumForm.imageFile?.name || ''"
                name="forum_image"
                type="hidden"
              />
              <p
                v-if="forumForm.imageFile"
                class="text-xs text-gray-500"
              >
                {{ t('Selected image') }}: {{ forumForm.imageFile.name }}
              </p>
            </div>
          </div>
        </div>
      </form>
      <template #footer>
        <BaseButton
          v-if="shouldReturnToLearningPathAfterForumSave"
          :disabled="isSavingForum"
          :label="t('Cancel')"
          icon="close"
          type="black"
          @click="goBackToLearningPath"
        />
        <BaseButton
          :disabled="isSavingForum"
          :is-loading="isSavingForum"
          :label="forumForm.id ? t('Save') : t('Create forum')"
          icon="save"
          type="success"
          @click="saveForum"
        />
      </template>
    </BaseDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import forumService from "../../services/forumService"
import ForumCardList from "./ForumCardList.vue"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()
const { requireConfirmation } = useConfirmation()
const { isAllowedToEdit } = useIsAllowedToEdit({ coach: true, sessionCoach: true })

const isLoading = ref(false)
const isSavingCategory = ref(false)
const isSavingForum = ref(false)
const categoryFormSubmitted = ref(false)
const forumFormSubmitted = ref(false)
const isCategoryDialogVisible = ref(false)
const isForumDialogVisible = ref(false)
const csrfToken = ref("")
const forumSettings = ref({
  defaultForumView: "flat",
  forumFoldCategories: false,
  allowForumPostRevisions: false,
  hideForumPostRevisionLanguage: false,
  categoryLanguageFilter: {
    enabled: false,
    options: [],
  },
})
const categories = ref([])
const forums = ref([])
const foldedCategoryIds = ref(new Set())
const categoryLanguageFilter = ref("")
const forumImageInputKey = ref(0)

const categoryForm = reactive({
  id: null,
  title: "",
  comment: "",
  locked: false,
  language: "",
})

const forumForm = reactive({
  id: null,
  title: "",
  comment: "",
  categoryId: 0,
  moderated: false,
  studentsCanEdit: false,
  requiresApproval: false,
  allowAttachments: true,
  allowNewThreads: true,
  defaultView: "flat",
  useCurrentGroup: false,
  groupVisibility: "public",
  startTime: null,
  endTime: null,
  locked: false,
  imageFile: null,
  imageUrl: "",
  imagePreviewUrl: "",
  removeImage: false,
})

const parentId = computed(() => Number(route.params.node || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const lpId = computed(() => Number(route.query.lp_id || 0))
const requestedForumEditId = computed(() => Number(route.query.editForumId || 0))
const currentGroupId = computed(() => gid.value)
const shouldOpenCreateForumDialogOnLoad = computed(() => {
  const create = String(route.query.create || route.query.content || "").toLowerCase()
  const action = String(route.query.action || "").toLowerCase()

  return isAllowedToEdit.value && ("forum" === create || ("add" === action && "forum" === create))
})
const shouldReturnToLearningPathAfterForumSave = computed(() => {
  const origin = String(route.query.origin || "").toLowerCase()
  const returnToLp = String(route.query.returnToLp || "")

  return Boolean(lpId.value) && "learnpath" === origin && "1" === returnToLp
})
const baseQuery = computed(() => ({
  "resourceNode.parent": parentId.value || null,
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const categoryQuery = computed(() => ({
  ...baseQuery.value,
  extra_language: categoryLanguageFilter.value || null,
  language_filter_applied: categoryLanguageFilter.value ? 1 : null,
}))
const defaultForumView = computed(() => {
  const value = String(forumSettings.value.defaultForumView || "flat")

  return ["flat", "threaded", "nested"].includes(value) ? value : "flat"
})
const canFoldCategories = computed(() => Boolean(forumSettings.value.forumFoldCategories))
const categoryLanguageOptions = computed(() => {
  const options = forumSettings.value.categoryLanguageFilter?.options

  return Array.isArray(options) ? options : []
})
const canFilterCategoryLanguage = computed(
  () => Boolean(forumSettings.value.categoryLanguageFilter?.enabled) && categoryLanguageOptions.value.length > 0,
)
const canEditCategoryLanguage = computed(() => canFilterCategoryLanguage.value)
const categoryLanguageFilterOptions = computed(() => [
  { label: t("Please select a language"), value: "" },
  ...categoryLanguageOptions.value,
])

const defaultViewOptions = computed(() => [
  { label: t("Flat"), value: "flat" },
  { label: t("Threaded"), value: "threaded" },
  { label: t("Nested"), value: "nested" },
])

const groupVisibilityOptions = computed(() => [
  { label: t("Public"), value: "public" },
  { label: t("Private"), value: "private" },
])

const categoryOptions = computed(() => [
  { label: t("No category"), value: 0 },
  ...categories.value.map((category) => ({ label: category.title, value: category.iid })),
])
const forumImagePreview = computed(() => {
  if (forumForm.removeImage) {
    return forumForm.imagePreviewUrl || ""
  }

  return forumForm.imagePreviewUrl || forumForm.imageUrl || ""
})

const categoryByIri = computed(() => {
  const byIri = new Map()

  for (const category of categories.value) {
    if (category["@id"]) {
      byIri.set(category["@id"], { ...category, forums: [] })
    }
  }

  return byIri
})

const forumsWithoutCategory = computed(() => forums.value.filter((forum) => !forum.forumCategory))
const categoriesWithForums = computed(() => {
  const byIri = categoryByIri.value

  for (const forum of forums.value) {
    const categoryIri = typeof forum.forumCategory === "string" ? forum.forumCategory : forum.forumCategory?.["@id"]

    if (categoryIri && byIri.has(categoryIri)) {
      byIri.get(categoryIri).forums.push(forum)
    }
  }

  return Array.from(byIri.values())
})

function goBackToLearningPath() {
  const query = { ...route.query }
  delete query.action
  delete query.create
  delete query.content
  delete query.editForumId
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

function isCategoryVisible(category) {
  if (category?.forumCategoryVisible === undefined || category?.forumCategoryVisible === null) {
    return true
  }

  return true === category.forumCategoryVisible || 1 === category.forumCategoryVisible || "1" === String(category.forumCategoryVisible)
}

function isForumVisible(forum) {
  if (forum?.forumVisible === undefined || forum?.forumVisible === null) {
    return true
  }

  return true === forum.forumVisible || 1 === forum.forumVisible || "1" === String(forum.forumVisible)
}

function isCategoryFolded(category) {
  return canFoldCategories.value && foldedCategoryIds.value.has(Number(category?.iid || 0))
}

function toggleCategoryFold(category) {
  const categoryId = Number(category?.iid || 0)
  if (!categoryId) {
    return
  }

  const nextValue = new Set(foldedCategoryIds.value)
  if (nextValue.has(categoryId)) {
    nextValue.delete(categoryId)
  } else {
    nextValue.add(categoryId)
  }

  foldedCategoryIds.value = nextValue
}

function getCategoryIdFromForum(forum) {
  if (!forum?.forumCategory) {
    return 0
  }

  if (typeof forum.forumCategory === "object") {
    return Number(forum.forumCategory.iid || 0)
  }

  const parts = String(forum.forumCategory).split("/")

  return Number(parts[parts.length - 1] || 0)
}

function resetCategoryForm() {
  categoryForm.id = null
  categoryForm.title = ""
  categoryForm.comment = ""
  categoryForm.locked = false
  categoryForm.language = ""
  categoryFormSubmitted.value = false
}

function resetForumForm(category = null) {
  revokeForumImagePreview()
  forumForm.id = null
  forumForm.title = ""
  forumForm.comment = ""
  forumForm.categoryId = category?.iid || 0
  forumForm.moderated = false
  forumForm.studentsCanEdit = false
  forumForm.requiresApproval = false
  forumForm.allowAttachments = true
  forumForm.allowNewThreads = true
  forumForm.defaultView = defaultForumView.value
  forumForm.useCurrentGroup = Boolean(currentGroupId.value)
  forumForm.groupVisibility = "public"
  forumForm.startTime = null
  forumForm.endTime = null
  forumForm.locked = false
  forumForm.imageFile = null
  forumForm.imageUrl = ""
  forumForm.imagePreviewUrl = ""
  forumForm.removeImage = false
  forumImageInputKey.value += 1
  forumFormSubmitted.value = false
}

function toDate(value) {
  if (!value) {
    return null
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

function toApiDateTime(value) {
  if (!value) {
    return ""
  }

  const date = value instanceof Date ? value : new Date(value)

  return Number.isNaN(date.getTime()) ? "" : date.toISOString()
}

function hasInvalidForumDates() {
  if (!forumForm.startTime || !forumForm.endTime) {
    return false
  }

  return forumForm.startTime.getTime() >= forumForm.endTime.getTime()
}

function revokeForumImagePreview() {
  if (forumForm.imagePreviewUrl) {
    URL.revokeObjectURL(forumForm.imagePreviewUrl)
  }
}

function selectForumImage(file) {
  if (!file) {
    return
  }

  const type = String(file.type || "").toLowerCase()
  if (!type.startsWith("image/") || ["image/svg", "image/svg+xml"].includes(type)) {
    notifications.showErrorNotification(t("Only image files are allowed."))

    return
  }

  revokeForumImagePreview()
  forumForm.imageFile = file
  forumForm.imagePreviewUrl = URL.createObjectURL(file)
  forumForm.removeImage = false
}

function removeForumImage() {
  const hadStoredImage = Boolean(forumForm.imageUrl)

  revokeForumImagePreview()
  forumForm.imageFile = null
  forumForm.imageUrl = ""
  forumForm.imagePreviewUrl = ""
  forumForm.removeImage = hadStoredImage
  forumImageInputKey.value += 1
}

function openCreateCategoryDialog() {
  resetCategoryForm()
  isCategoryDialogVisible.value = true
}

function openEditCategoryDialog(category) {
  categoryForm.id = category.iid
  categoryForm.title = category.title || ""
  categoryForm.comment = category.catComment || ""
  categoryForm.locked = Boolean(Number(category.locked || 0))
  categoryForm.language = category.language || ""
  categoryFormSubmitted.value = false
  isCategoryDialogVisible.value = true
}

function openCreateForumDialog(category = null) {
  resetForumForm(category)
  isForumDialogVisible.value = true
}

function openEditForumDialog(forum) {
  forumForm.id = forum.iid
  forumForm.title = forum.title || ""
  forumForm.comment = forum.forumComment || ""
  forumForm.categoryId = getCategoryIdFromForum(forum)
  forumForm.moderated = Boolean(forum.moderated)
  forumForm.studentsCanEdit = Boolean(Number(forum.allowEdit || 0))
  forumForm.requiresApproval = "1" === String(forum.approvalDirectPost || "0")
  forumForm.allowAttachments = Boolean(Number(forum.allowAttachments ?? 1))
  forumForm.allowNewThreads = Boolean(Number(forum.allowNewThreads ?? 1))
  forumForm.defaultView = forum.defaultView || "flat"
  forumForm.useCurrentGroup = Number(forum.forumOfGroup || 0) > 0
  forumForm.groupVisibility = forum.forumGroupPublicPrivate || "public"
  revokeForumImagePreview()
  forumForm.startTime = toDate(forum.startTime)
  forumForm.endTime = toDate(forum.endTime)
  forumForm.locked = Boolean(Number(forum.locked || 0))
  forumForm.imageFile = null
  forumForm.imageUrl = String(forum.forumImage || "")
  forumForm.imagePreviewUrl = ""
  forumForm.removeImage = false
  forumImageInputKey.value += 1
  forumFormSubmitted.value = false
  isForumDialogVisible.value = true
}

async function loadToken() {
  const response = await forumService.getActionToken()
  csrfToken.value = response.token || ""
  forumSettings.value = {
    ...forumSettings.value,
    ...(response.settings || {}),
  }
}

async function loadForums() {
  isLoading.value = true

  try {
    const [categoryItems, forumItems] = await Promise.all([
      forumService.getCategories(categoryQuery.value),
      forumService.getForums(baseQuery.value),
    ])

    categories.value = categoryItems
    forums.value = forumItems
  } catch (error) {
    console.error("Error fetching forums:", error)
    notifications.showErrorNotification(t("Could not retrieve forums"))
  } finally {
    isLoading.value = false
  }
}

async function saveCategory() {
  categoryFormSubmitted.value = true

  if (!categoryForm.title.trim()) {
    return
  }

  isSavingCategory.value = true

  const payload = {
    title: categoryForm.title.trim(),
    comment: categoryForm.comment.trim(),
    locked: categoryForm.locked,
    language: canEditCategoryLanguage.value ? categoryForm.language : "",
    parentResourceNodeId: parentId.value,
    csrfToken: csrfToken.value,
  }

  try {
    if (categoryForm.id) {
      await forumService.updateCategory(categoryForm.id, baseQuery.value, payload)
      notifications.showSuccessNotification(t("Forum category updated"))
    } else {
      await forumService.createCategory(baseQuery.value, payload)
      notifications.showSuccessNotification(t("Forum category created"))
    }

    isCategoryDialogVisible.value = false
    await loadForums()
  } catch (error) {
    console.error("Error saving forum category:", error)
    notifications.showErrorNotification(t("Could not save forum category"))
    await loadToken()
  } finally {
    isSavingCategory.value = false
  }
}

async function saveForum() {
  forumFormSubmitted.value = true

  if (!forumForm.title.trim()) {
    return
  }

  if (hasInvalidForumDates()) {
    notifications.showErrorNotification(t("Start date must be before the end date"))

    return
  }

  isSavingForum.value = true

  const payload = {
    title: forumForm.title.trim(),
    comment: forumForm.comment.trim(),
    categoryId: forumForm.categoryId || 0,
    moderated: forumForm.moderated,
    studentsCanEdit: forumForm.studentsCanEdit,
    requiresApproval: forumForm.requiresApproval,
    allowAttachments: forumForm.allowAttachments,
    allowNewThreads: forumForm.allowNewThreads,
    defaultView: forumForm.defaultView,
    groupForum: forumForm.useCurrentGroup ? currentGroupId.value : 0,
    groupVisibility: forumForm.useCurrentGroup ? forumForm.groupVisibility : "public",
    startTime: toApiDateTime(forumForm.startTime),
    endTime: toApiDateTime(forumForm.endTime),
    locked: forumForm.locked,
    parentResourceNodeId: parentId.value,
    lpId: lpId.value || 0,
    lpParentId: Number(route.query.parent || 0) || 0,
    csrfToken: csrfToken.value,
  }

  try {
    const isCreate = !forumForm.id
    let savedForum = null

    if (forumForm.id) {
      savedForum = await forumService.updateForum(forumForm.id, baseQuery.value, payload)
    } else {
      savedForum = await forumService.createForum(baseQuery.value, payload)
    }

    await saveForumImageIfNeeded(Number(savedForum?.iid || forumForm.id || 0))
    notifications.showSuccessNotification(isCreate ? t("Forum created") : t("Forum updated"))

    isForumDialogVisible.value = false

    if (shouldReturnToLearningPathAfterForumSave.value) {
      await goBackToLearningPath()

      return
    }

    await loadForums()
  } catch (error) {
    console.error("Error saving forum:", error)
    notifications.showErrorNotification(t("Could not save forum"))
    await loadToken()
  } finally {
    isSavingForum.value = false
  }
}

async function saveForumImageIfNeeded(forumId) {
  if (!forumId || (!forumForm.imageFile && !forumForm.removeImage)) {
    return
  }

  const response = await forumService.uploadForumImage(forumId, baseQuery.value, {
    csrfToken: csrfToken.value,
    image: forumForm.imageFile,
    removeImage: forumForm.removeImage,
  })

  revokeForumImagePreview()
  forumForm.imageUrl = response?.forumImage || ""
  forumForm.imageFile = null
  forumForm.imagePreviewUrl = ""
  forumForm.removeImage = false
  forumImageInputKey.value += 1
}

function confirmDeleteCategory(category) {
  requireConfirmation({
    message: t("Are you sure you want to delete this forum category?"),
    accept: () => deleteCategory(category),
  })
}

async function deleteCategory(category) {
  try {
    await forumService.deleteCategory(category.iid, baseQuery.value, { csrfToken: csrfToken.value })
    notifications.showSuccessNotification(t("Forum category deleted"))
    await loadForums()
  } catch (error) {
    console.error("Error deleting forum category:", error)
    notifications.showErrorNotification(t("Could not delete forum category"))
    await loadToken()
  }
}

function confirmDeleteForum(forum) {
  requireConfirmation({
    message: t("Are you sure you want to delete this forum?"),
    accept: () => deleteForum(forum),
  })
}

async function deleteForum(forum) {
  try {
    await forumService.deleteForum(forum.iid, baseQuery.value, { csrfToken: csrfToken.value })
    notifications.showSuccessNotification(t("Forum deleted"))
    await loadForums()
  } catch (error) {
    console.error("Error deleting forum:", error)
    notifications.showErrorNotification(t("Could not delete forum"))
    await loadToken()
  }
}

async function toggleCategoryLock(category) {
  try {
    await forumService.toggleCategoryLock(category.iid, baseQuery.value, { csrfToken: csrfToken.value })
    notifications.showSuccessNotification(Number(category.locked || 0) ? t("Forum category unlocked") : t("Forum category locked"))
    await loadForums()
  } catch (error) {
    console.error("Error toggling forum category lock:", error)
    notifications.showErrorNotification(t("Could not update forum category"))
    await loadToken()
  }
}

async function toggleCategoryVisibility(category) {
  const wasVisible = isCategoryVisible(category)

  try {
    const response = await forumService.toggleCategoryVisibility(category.iid, baseQuery.value, {
      visible: !wasVisible,
      csrfToken: csrfToken.value,
    })
    category.forumCategoryVisible = response.visible
    notifications.showSuccessNotification(response.visible ? t("Forum category shown") : t("Forum category hidden"))
    await loadForums()
  } catch (error) {
    console.error("Error toggling forum category visibility:", error)
    notifications.showErrorNotification(t("Could not update forum category"))
    await loadToken()
  }
}

async function toggleForumLock(forum) {
  try {
    await forumService.toggleForumLock(forum.iid, baseQuery.value, { csrfToken: csrfToken.value })
    notifications.showSuccessNotification(Number(forum.locked || 0) ? t("Forum unlocked") : t("Forum locked"))
    await loadForums()
  } catch (error) {
    console.error("Error toggling forum lock:", error)
    notifications.showErrorNotification(t("Could not update forum"))
    await loadToken()
  }
}

async function toggleForumVisibility(forum) {
  const wasVisible = isForumVisible(forum)

  try {
    const response = await forumService.toggleForumVisibility(forum.iid, baseQuery.value, {
      visible: !wasVisible,
      csrfToken: csrfToken.value,
    })
    forum.forumVisible = response.visible
    notifications.showSuccessNotification(response.visible ? t("Forum shown") : t("Forum hidden"))
    await loadForums()
  } catch (error) {
    console.error("Error toggling forum visibility:", error)
    notifications.showErrorNotification(t("Could not update forum"))
    await loadToken()
  }
}


async function toggleForumNotification(forum) {
  try {
    const response = await forumService.toggleForumSubscription(forum.iid, baseQuery.value, {
      csrfToken: csrfToken.value,
      subscribed: !forum.subscribed,
    })

    forum.subscribed = response.subscribed
    notifications.showSuccessNotification(response.subscribed ? t("Forum notifications enabled") : t("Forum notifications disabled"))
    await loadForums()
  } catch (error) {
    console.error("Error toggling forum notification:", error)
    notifications.showErrorNotification(t("Could not update forum notification"))
    await loadToken()
  }
}

async function moveCategory(category, direction) {
  try {
    await forumService.moveCategory(category.iid, baseQuery.value, { direction, csrfToken: csrfToken.value })
    notifications.showSuccessNotification(t("Forum category moved"))
    await loadForums()
  } catch (error) {
    console.error("Error moving forum category:", error)
    notifications.showErrorNotification(t("Could not move forum category"))
    await loadToken()
  }
}

async function moveForum(forum, direction) {
  try {
    await forumService.moveForum(forum.iid, baseQuery.value, { direction, csrfToken: csrfToken.value })
    notifications.showSuccessNotification(t("Forum moved"))
    await loadForums()
  } catch (error) {
    console.error("Error moving forum:", error)
    notifications.showErrorNotification(t("Could not move forum"))
    await loadToken()
  }
}

function resetCategoryLanguageFilter() {
  categoryLanguageFilter.value = ""
}

watch(categoryLanguageFilter, async () => {
  if (!canFilterCategoryLanguage.value) {
    return
  }

  await loadForums()
})

onMounted(async () => {
  await Promise.all([loadToken(), loadForums()])

  if (shouldOpenCreateForumDialogOnLoad.value) {
    openCreateForumDialog()

    return
  }

  if (isAllowedToEdit.value && requestedForumEditId.value > 0) {
    const forum = forums.value.find((item) => Number(item.iid || 0) === requestedForumEditId.value)
    if (forum) {
      openEditForumDialog(forum)
    }
  }
})
</script>
