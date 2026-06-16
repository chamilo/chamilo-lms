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
        <BaseTextArea
          id="forum-comment"
          v-model="forumForm.comment"
          :label="t('Description')"
          name="forum_comment"
          rows="5"
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
          <label class="flex flex-col gap-1 text-sm text-gray-700">
            <span>{{ t('Publication date') }}</span>
            <input
              id="forum-start-time"
              v-model="forumForm.startTime"
              class="rounded border border-gray-30 px-3 py-2 text-sm"
              name="forum_start_time"
              type="datetime-local"
            />
            <span class="text-xs text-gray-500">{{ t('The forum will be visible starting from this date') }}</span>
          </label>
          <label class="flex flex-col gap-1 text-sm text-gray-700">
            <span>{{ t('Closing date') }}</span>
            <input
              id="forum-end-time"
              v-model="forumForm.endTime"
              class="rounded border border-gray-30 px-3 py-2 text-sm"
              name="forum_end_time"
              type="datetime-local"
            />
            <span class="text-xs text-gray-500">{{ t('Once this date has passed, the forum will be closed') }}</span>
          </label>
        </div>
      </form>
      <template #footer>
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
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
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
})
const categories = ref([])
const forums = ref([])
const foldedCategoryIds = ref(new Set())

const categoryForm = reactive({
  id: null,
  title: "",
  comment: "",
  locked: false,
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
  startTime: "",
  endTime: "",
  locked: false,
})

const parentId = computed(() => Number(route.params.node || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const gid = computed(() => Number(route.query.gid || 0))
const lpId = computed(() => Number(route.query.lp_id || 0))
const currentGroupId = computed(() => gid.value)
const baseQuery = computed(() => ({
  "resourceNode.parent": parentId.value || null,
  cid: cid.value || null,
  sid: sid.value || null,
  gid: gid.value || null,
}))
const defaultForumView = computed(() => {
  const value = String(forumSettings.value.defaultForumView || "flat")

  return ["flat", "threaded", "nested"].includes(value) ? value : "flat"
})
const canFoldCategories = computed(() => Boolean(forumSettings.value.forumFoldCategories))

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
  categoryFormSubmitted.value = false
}

function resetForumForm(category = null) {
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
  forumForm.startTime = ""
  forumForm.endTime = ""
  forumForm.locked = false
  forumFormSubmitted.value = false
}

function toDateTimeLocal(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return ""
  }

  const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000)

  return localDate.toISOString().slice(0, 16)
}

function toApiDateTime(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? "" : date.toISOString()
}

function hasInvalidForumDates() {
  if (!forumForm.startTime || !forumForm.endTime) {
    return false
  }

  return new Date(forumForm.startTime).getTime() >= new Date(forumForm.endTime).getTime()
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
  forumForm.startTime = toDateTimeLocal(forum.startTime)
  forumForm.endTime = toDateTimeLocal(forum.endTime)
  forumForm.locked = Boolean(Number(forum.locked || 0))
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
      forumService.getCategories(baseQuery.value),
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
    csrfToken: csrfToken.value,
  }

  try {
    if (forumForm.id) {
      await forumService.updateForum(forumForm.id, baseQuery.value, payload)
      notifications.showSuccessNotification(t("Forum updated"))
    } else {
      await forumService.createForum(baseQuery.value, payload)
      notifications.showSuccessNotification(t("Forum created"))
    }

    isForumDialogVisible.value = false
    await loadForums()
  } catch (error) {
    console.error("Error saving forum:", error)
    notifications.showErrorNotification(t("Could not save forum"))
    await loadToken()
  } finally {
    isSavingForum.value = false
  }
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

onMounted(async () => {
  await Promise.all([loadToken(), loadForums()])
})
</script>
