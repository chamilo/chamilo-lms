<template>
  <section class="space-y-6">
    <SectionHeader :title="t('Groups')" />

    <BaseToolbar>
      <template #start>
        <div
          v-if="showSubscriptionTabs"
          class="flex flex-wrap items-center gap-2"
        >
          <BaseButton
            :label="t('Learners')"
            :route="usersRoute(STUDENT)"
            icon="account"
            type="primary-text"
          />
          <BaseButton
            :label="t('Teachers')"
            :route="usersRoute(TEACHER)"
            icon="human-male-board"
            type="primary-text"
          />
          <BaseButton
            :label="t('Groups')"
            icon="join-group"
            type="primary"
          />
          <BaseButton
            v-if="showClasses"
            :label="t('Classes')"
            :route="classesRoute"
            icon="sessions"
            type="primary-text"
          />
        </div>
      </template>

      <template #end>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            v-if="canManageCourse"
            :label="t('Create new group(s)')"
            :route="childRoute('CourseGroupCreate')"
            icon="plus"
            only-icon
            :tooltip="t('Create new group(s)')"
            type="success"
          />
          <BaseButton
            v-if="canCreateCategory"
            :label="t('Add category')"
            :route="childRoute('CourseGroupCategoryCreate')"
            icon="folder-plus"
            only-icon
            :tooltip="t('Add category')"
            type="success"
          />
          <BaseButton
            v-if="defaultCategoryId > 0"
            :label="t('Edit settings')"
            :route="categoryRoute(defaultCategoryId)"
            icon="settings"
            only-icon
            :tooltip="t('Edit settings')"
            type="secondary"
          />
          <BaseButton
            v-if="canManageCourse"
            :label="t('Import')"
            :route="childRoute('CourseGroupImport')"
            icon="import"
            only-icon
            :tooltip="t('Import')"
            type="success"
          />
          <BaseButton
            v-if="canManageCourse && categories.length > 0"
            :label="t('Groups overview')"
            :route="childRoute('CourseGroupOverview')"
            icon="view-gallery"
            only-icon
            :tooltip="t('Groups overview')"
            type="primary"
          />
          <BaseButton
            v-if="canManageCourse"
            :label="t('CSV export')"
            icon="file-delimited-outline"
            only-icon
            :tooltip="t('CSV export')"
            type="primary"
            @click="download(csvExportUrl)"
          />
          <BaseButton
            v-if="canManageCourse"
            :label="t('Excel export')"
            icon="file-excel"
            only-icon
            :tooltip="t('Excel export')"
            type="primary"
            @click="download(xlsxExportUrl)"
          />
          <BaseButton
            v-if="canManageCourse"
            :label="t('Export to PDF')"
            icon="file-pdf"
            only-icon
            :tooltip="t('Export to PDF')"
            type="primary"
            @click="download(pdfExportUrl)"
          />
          <BaseButton
            :label="searchVisible ? t('Hide search') : t('Search')"
            :icon="searchVisible ? 'close' : 'search'"
            only-icon
            :tooltip="searchVisible ? t('Hide search') : t('Search')"
            type="primary"
            @click="searchVisible = !searchVisible"
          />
        </div>
      </template>
    </BaseToolbar>

    <form
      v-if="searchVisible"
      class="flex flex-col gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:flex-row md:items-end"
      @submit.prevent="applySearch"
    >
      <BaseInputText
        id="course-group-search"
        v-model="searchDraft"
        class="flex-1"
        :label="t('Search groups')"
        name="search"
      />
      <BaseButton
        :is-loading="loading"
        :label="t('Search')"
        icon="search"
        is-submit
        type="primary"
      />
      <BaseButton
        :label="t('Clear search')"
        icon="close"
        type="plain"
        @click="clearSearch"
      />
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="selectedGroupIds.length > 0 && canManageCourse"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
    >
      <strong>{{ t("{0} groups selected", [selectedGroupIds.length]) }}</strong>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="t('Fill selected groups')"
          icon="join-group"
          size="small"
          type="success"
          @click="confirmBulk('fill')"
        />
        <BaseButton
          :label="t('Empty selected groups')"
          icon="delete-multiple-user"
          size="small"
          type="secondary"
          @click="confirmBulk('empty')"
        />
        <BaseButton
          :label="t('Delete selected groups')"
          icon="delete"
          size="small"
          type="danger"
          @click="confirmBulk('delete')"
        />
      </div>
    </div>

    <div
      v-if="!loading && categories.length === 0"
      class="rounded-xl border border-gray-20 bg-white p-8 text-center text-gray-50"
    >
      {{ t("No groups found") }}
    </div>

    <article
      v-for="(category, categoryIndex) in categories"
      :key="category.id"
      class="overflow-hidden rounded-xl border border-gray-20 bg-white shadow-sm"
    >
      <header class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-20 bg-gray-10 p-4">
        <div>
          <h2 class="font-semibold text-gray-90">{{ t(category.title) }}</h2>
          <p
            v-if="category.description"
            class="mt-1 text-sm text-gray-50"
          >
            {{ category.description }}
          </p>
        </div>
        <div
          v-if="category.id > 0 && canManageCourse"
          class="flex items-center gap-1"
        >
          <BaseButton
            :label="t('Create new group(s)')"
            :route="createGroupInCategoryRoute(category.id)"
            icon="plus"
            only-icon
            size="small"
            type="success-text"
          />
          <BaseButton
            v-if="category.canEdit"
            :label="t('Edit')"
            :route="categoryRoute(category.id)"
            icon="pencil"
            only-icon
            size="small"
            type="secondary-text"
          />
          <BaseButton
            v-if="categoryIndex > 0 && categories[categoryIndex - 1].id > 0"
            :label="t('Move up')"
            icon="arrow-up"
            only-icon
            size="small"
            type="secondary-text"
            @click="moveCategory(category.id, categories[categoryIndex - 1].id)"
          />
          <BaseButton
            v-if="categoryIndex < categories.length - 1 && categories[categoryIndex + 1].id > 0"
            :label="t('Move down')"
            icon="arrow-down"
            only-icon
            size="small"
            type="secondary-text"
            @click="moveCategory(category.id, categories[categoryIndex + 1].id)"
          />
          <BaseButton
            v-if="category.canDelete"
            :label="t('Delete')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="confirmDeleteCategory(category)"
          />
        </div>
      </header>

      <BaseTable
        :is-loading="loading"
        :text-for-empty="t('No groups found')"
        :total-items="category.groups.length"
        :values="category.groups"
        data-key="id"
      >
        <Column
          v-if="canManageCourse"
          class="w-12"
        >
          <template #body="{ data }">
            <input
              :aria-label="t('Select group')"
              class="h-4 w-4 rounded border-gray-30"
              type="checkbox"
              :checked="selectedGroupIds.includes(data.id)"
              @change="toggleSelection(data.id, $event.target.checked)"
            />
          </template>
        </Column>
        <Column
          field="title"
          :header="t('Group name')"
        >
          <template #body="{ data }">
            <router-link
              v-if="data.canBrowse"
              class="font-medium text-primary hover:underline"
              :to="groupRoute(data.id)"
            >
              {{ data.title }}
            </router-link>
            <span
              v-else
              class="font-medium text-gray-90"
            >
              {{ data.title }}
            </span>
            <p
              v-if="data.description"
              class="mt-1 text-xs text-gray-50"
            >
              {{ data.description }}
            </p>
            <span
              v-if="data.linkedToClass"
              class="mt-1 inline-block rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-700"
            >
              {{ data.linkedClassTitle }}
            </span>
          </template>
        </Column>
        <Column
          field="membersLabel"
          :header="t('Members')"
        />
        <Column
          field="tutorsLabel"
          :header="t('Tutors')"
        >
          <template #body="{ data }">
            <span v-if="!data.tutors?.length">-</span>
            <template v-else>
              <span
                v-for="(tutor, index) in data.tutors"
                :key="`${data.id}-${tutor.username || index}`"
              >
                <a
                  v-if="tutor.email"
                  class="text-primary hover:underline"
                  :href="`mailto:${tutor.email}`"
                  :title="t('Login: {0}', [tutor.username])"
                >
                  {{ tutor.name }}
                </a>
                <span
                  v-else
                  :title="tutor.username ? t('Login: {0}', [tutor.username]) : ''"
                >
                  {{ tutor.name }}
                </span>
                <span v-if="index < data.tutors.length - 1">, </span>
              </span>
            </template>
          </template>
        </Column>
        <Column :header="t('Status')">
          <template #body="{ data }">
            <span
              class="rounded-full px-2 py-1 text-xs"
              :class="data.status ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
            >
              {{ data.status ? t("Visible") : t("Invisible") }}
            </span>
          </template>
        </Column>
        <Column :header="t('Detail')">
          <template #body="{ data }">
            <div class="flex flex-wrap items-center justify-center gap-1">
              <BaseButton
                :label="t('Group area')"
                :route="groupRoute(data.id)"
                icon="view-gallery"
                only-icon
                size="small"
                type="primary-text"
              />
              <BaseButton
                v-if="canManageCourse"
                :label="t('Excel export')"
                icon="file-excel"
                only-icon
                size="small"
                type="primary-text"
                @click="downloadGroupExport(data.id)"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Edit')"
                :route="editGroupRoute(data.id)"
                icon="pencil"
                only-icon
                size="small"
                type="secondary-text"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Members')"
                :route="membersRoute(data.id, 'members')"
                icon="join-group"
                only-icon
                size="small"
                type="primary-text"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Tutors')"
                :route="membersRoute(data.id, 'tutors')"
                icon="human-male-board"
                only-icon
                size="small"
                type="primary-text"
              />
              <BaseButton
                v-if="data.canManage"
                :label="data.status ? t('Make invisible') : t('Make visible')"
                :icon="data.status ? 'eye-off' : 'eye-on'"
                only-icon
                size="small"
                type="secondary-text"
                @click="toggleVisibility(data)"
              />
              <BaseButton
                v-if="data.canSelfRegister"
                :label="t('Add me to this group')"
                icon="user-add"
                only-icon
                size="small"
                type="success-text"
                @click="selfAction(data, 'self-register')"
              />
              <BaseButton
                v-if="data.canSelfUnregister"
                :label="t('Unsubscribe me from this group.')"
                icon="user-delete"
                only-icon
                size="small"
                type="danger-text"
                @click="selfAction(data, 'self-unregister')"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Fill group')"
                icon="join-group"
                only-icon
                size="small"
                type="success-text"
                @click="confirmSingle(data, 'fill')"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Empty group')"
                icon="delete-multiple-user"
                only-icon
                size="small"
                type="secondary-text"
                @click="confirmSingle(data, 'empty')"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Delete')"
                icon="delete"
                only-icon
                size="small"
                type="danger-text"
                @click="confirmSingle(data, 'delete')"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </article>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const STUDENT = 5
const TEACHER = 1
const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()
const { cid, sid, contextQuery } = useRouteCourseContext()

const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const canManageCourse = ref(false)
const canCreateCategory = ref(false)
const defaultCategoryId = ref(0)
const showSubscriptionTabs = ref(false)
const showClasses = ref(false)
const csvExportUrl = ref("")
const xlsxExportUrl = ref("")
const pdfExportUrl = ref("")
const searchVisible = ref(false)
const searchDraft = ref("")
const activeSearch = ref("")
const selectedGroupIds = ref([])

const classesRoute = computed(() => ({ name: "CourseUserClasses", params: route.params, query: contextQuery.value }))

function requestParams() {
  return { cid: cid.value, sid: sid.value, search: activeSearch.value }
}

function usersRoute(type) {
  return { name: "CourseUserList", params: route.params, query: { ...contextQuery.value, type } }
}

function childRoute(name, params = {}) {
  return { name, params: { ...route.params, ...params }, query: contextQuery.value }
}

function groupRoute(groupId) {
  return { ...childRoute("CourseGroupDetail", { groupId }), query: { ...contextQuery.value, gid: groupId } }
}

function editGroupRoute(groupId) {
  return { ...childRoute("CourseGroupEdit", { groupId }), query: { ...contextQuery.value, gid: groupId } }
}

function membersRoute(groupId, mode) {
  return {
    ...childRoute(mode === "tutors" ? "CourseGroupTutors" : "CourseGroupMembers", { groupId }),
    query: { ...contextQuery.value, gid: groupId },
  }
}

function categoryRoute(categoryId) {
  return childRoute("CourseGroupCategoryEdit", { categoryId })
}

function createGroupInCategoryRoute(categoryId) {
  return {
    ...childRoute("CourseGroupCreate"),
    query: { ...contextQuery.value, categoryId },
  }
}

async function loadGroups() {
  loading.value = true
  errorMessage.value = ""
  try {
    const response = await courseGroupService.getList(requestParams())
    categories.value = response.categories || []
    canManageCourse.value = Boolean(response.canManageCourse)
    canCreateCategory.value = Boolean(response.canCreateCategory)
    defaultCategoryId.value = Number(response.defaultCategoryId || 0)
    showSubscriptionTabs.value = Boolean(response.showSubscriptionTabs)
    showClasses.value = Boolean(response.showClasses)
    csvExportUrl.value = response.csvExportUrl || ""
    xlsxExportUrl.value = response.xlsxExportUrl || ""
    pdfExportUrl.value = response.pdfExportUrl || ""
    selectedGroupIds.value = []
  } catch (error) {
    console.error("[CourseGroup] Failed to load groups", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function runAction(name, payload = {}) {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""
  try {
    const response = await courseGroupService.action(name, payload, requestParams())
    successMessage.value = response.message ? t(response.message) : t("Updated")
    await loadGroups()
  } catch (error) {
    console.error(`[CourseGroup] Action ${name} failed`, error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

function toggleSelection(groupId, checked) {
  selectedGroupIds.value = checked
    ? [...new Set([...selectedGroupIds.value, groupId])]
    : selectedGroupIds.value.filter((id) => id !== groupId)
}

function confirmBulk(action) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => runAction(action, { groupIds: selectedGroupIds.value }),
  })
}

function confirmSingle(group, action) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => runAction(action, { groupIds: [group.id] }),
  })
}

function confirmDeleteCategory(category) {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () => runAction("delete-category", { categoryId: category.id }),
  })
}

function moveCategory(categoryId, otherCategoryId) {
  if (otherCategoryId <= 0) return
  runAction("move-category", { categoryId, otherCategoryId })
}

function toggleVisibility(group) {
  runAction("toggle-visibility", { groupId: group.id, visible: !group.status })
}

function selfAction(group, action) {
  runAction(action, { groupId: group.id })
}

function applySearch() {
  activeSearch.value = searchDraft.value.trim()
  loadGroups()
}

function clearSearch() {
  searchDraft.value = ""
  activeSearch.value = ""
  loadGroups()
}

function download(url) {
  if (url) window.location.assign(url)
}

function downloadGroupExport(groupId) {
  if (!xlsxExportUrl.value) return
  const separator = xlsxExportUrl.value.includes("?") ? "&" : "?"
  window.location.assign(`${xlsxExportUrl.value}${separator}groupId=${groupId}`)
}

onMounted(loadGroups)

useStudentViewRefresh(loadGroups)
</script>
