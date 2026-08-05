<template>
  <section class="space-y-4">
    <div
      v-if="successMessage"
      class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-else-if="isLoading"
      class="rounded-lg border border-gray-20 bg-white p-6 text-center text-sm text-gray-600"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="announcements.length === 0">
      <div
        v-if="canManage"
        class="flex flex-col items-center gap-4 rounded-lg border border-gray-20 bg-white p-8 text-center"
      >
        <BaseIcon
          icon="announcement"
          size="big"
        />

        <div>
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Announcements") }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ t("There are no announcements.") }}
          </p>
        </div>

        <BaseButton
          icon="announcement"
          :label="t('Add an announcement')"
          :route="addRoute"
          type="success"
        />
      </div>

      <div
        v-else
        class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
        role="status"
      >
        {{ t("There are no announcements.") }}
      </div>
    </template>

    <template v-else>
      <BaseToolbar>
        <template #start>
          <div class="flex flex-wrap items-center gap-2">
            <BaseButton
              v-if="canManage"
              icon="announcement"
              :label="t('Add an announcement')"
              only-icon
              size="normal"
              :route="addRoute"
              type="success"
            />

            <BaseButton
              :icon="isSearchVisible ? 'close' : 'search'"
              :label="isSearchVisible ? t('Cancel') : t('Search')"
              only-icon
              size="normal"
              type="primary"
              @click="toggleSearch"
            />

            <BaseButton
              v-if="selectedEditableIds.length"
              icon="delete"
              :disabled="hasRowActionInProgress"
              :is-loading="isManaging"
              :label="t('Delete')"
              only-icon
              size="normal"
              :tooltip="t('Delete')"
              type="danger"
              @click="confirmDeleteSelected"
            />

            <BaseButton
              v-else-if="canDeleteAll"
              icon="delete-forever"
              :disabled="hasRowActionInProgress"
              :is-loading="isManaging"
              :label="t('Delete all')"
              only-icon
              size="normal"
              :tooltip="t('Delete all')"
              type="danger"
              @click="confirmDeleteAll"
            />
          </div>
        </template>
      </BaseToolbar>

      <form
        v-if="isSearchVisible"
        class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
        @submit.prevent="applyFilters"
      >
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div class="min-w-0">
            <BaseInputText
              id="announcement-title-filter"
              v-model="pendingTitleFilter"
              class="w-full"
              :label="t('Title')"
              name="announcement_title_filter"
            />
          </div>

          <div class="min-w-0">
            <BaseSelect
              id="announcement-author-filter"
              v-model="pendingAuthorFilter"
              :allow-clear="true"
              class="w-full"
              :label="t('Users')"
              name="announcement_author_filter"
              option-label="label"
              option-value="id"
              :options="authors"
            />
          </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2">
          <BaseButton
            icon="search"
            is-submit
            :label="t('Search')"
            type="primary"
          />

          <BaseButton
            v-if="hasActiveFilters"
            icon="close"
            :label="t('Clear')"
            type="secondary"
            @click="clearFilters"
          />

          <BaseButton
            v-else
            icon="close"
            :label="t('Cancel')"
            type="plain"
            @click="toggleSearch"
          />
        </div>
      </form>

      <BaseTable
        v-model:selected-items="selectedAnnouncements"
        data-key="id"
        :text-for-empty="t('There are no announcements.')"
        :total-items="filteredAnnouncements.length"
        :values="filteredAnnouncements"
      >
        <Column
          v-if="canManage"
          selection-mode="multiple"
        />

        <Column
          field="title"
          :header="t('Title')"
        >
          <template #body="{ data }">
            <div class="flex min-w-0 items-center gap-2">
              <router-link
                class="min-w-0 break-words font-semibold text-primary hover:underline"
                :to="getDetailRoute(data)"
              >
                {{ data.title }}
              </router-link>

              <BaseIcon
                v-if="data.emailSent"
                icon="email-unread"
                size="small"
                :tooltip="t('Announcement sent by e-mail')"
              />

              <BaseIcon
                v-if="data.hasAttachments"
                icon="attachment"
                size="small"
                :tooltip="t('Attachments')"
              />

            </div>
          </template>
        </Column>

        <Column
          field="author.fullName"
          :header="t('By')"
        >
          <template #body="{ data }">
            <span :title="data.author?.username || ''">
              {{ data.author?.fullName || data.author?.username || "-" }}
            </span>
          </template>
        </Column>

        <Column
          field="updatedAt"
          :header="t('Latest update')"
        >
          <template #body="{ data }">
            {{ formatDate(data.updatedAt) }}
          </template>
        </Column>

        <Column :header="t('Actions')">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <BaseButton
                v-if="data.canEdit"
                icon="edit"
                :label="t('Edit')"
                only-icon
                :route="getEditRoute(data)"
                size="small"
                :tooltip="t('Edit')"
                type="secondary-text"
              />

              <BaseButton
                v-if="data.canChangeVisibility"
                :icon="Number(data.visibility) === 2 ? 'eye-on' : 'eye-off'"
                :disabled="isManaging || hasRowActionInProgress"
                :is-loading="isActionLoading(data.id)"
                :label="Number(data.visibility) === 2 ? t('Visible') : t('Invisible')"
                only-icon
                size="small"
                :tooltip="Number(data.visibility) === 2 ? t('Visible') : t('Invisible')"
                type="secondary-text"
                @click="changeVisibility(data)"
              />

              <BaseButton
                v-if="data.canMoveUp"
                icon="arrow-up"
                :disabled="isManaging || hasRowActionInProgress"
                :is-loading="isActionLoading(data.id)"
                :label="t('Move up')"
                only-icon
                size="small"
                :tooltip="t('Move up')"
                type="secondary-text"
                @click="moveAnnouncement(data, 'up')"
              />

              <BaseButton
                v-if="data.canMoveDown"
                icon="arrow-down"
                :disabled="isManaging || hasRowActionInProgress"
                :is-loading="isActionLoading(data.id)"
                :label="t('Move down')"
                only-icon
                size="small"
                :tooltip="t('Move down')"
                type="secondary-text"
                @click="moveAnnouncement(data, 'down')"
              />

              <BaseButton
                v-if="data.canDelete"
                icon="delete"
                :disabled="isManaging || hasRowActionInProgress"
                :is-loading="isActionLoading(data.id)"
                :label="t('Delete')"
                only-icon
                size="small"
                :tooltip="t('Delete')"
                type="danger-text"
                @click="confirmDeleteOne(data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import Column from "primevue/column"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import announcementService from "../../services/announcementService"

const { t, locale } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const announcements = ref([])
const authors = ref([])
const selectedAnnouncements = ref([])
const canManage = ref(false)
const canDeleteAll = ref(false)
const csrfToken = ref("")
const isLoading = ref(false)
const isManaging = ref(false)
const actionLoadingIds = ref(new Set())
const errorMessage = ref("")
const successMessage = ref("")
const isSearchVisible = ref(false)
const pendingTitleFilter = ref("")
const pendingAuthorFilter = ref("")
const titleFilter = ref("")
const authorFilter = ref("")

const hasActiveFilters = computed(() => titleFilter.value.trim() !== "" || Number(authorFilter.value || 0) > 0)
const hasRowActionInProgress = computed(() => actionLoadingIds.value.size > 0)

const filteredAnnouncements = computed(() => {
  const normalizedTitle = titleFilter.value.trim().toLocaleLowerCase()
  const selectedAuthorId = Number(authorFilter.value || 0)

  return announcements.value.filter((announcement) => {
    const matchesTitle =
      normalizedTitle === "" || String(announcement.title || "").toLocaleLowerCase().includes(normalizedTitle)
    const matchesAuthor =
      selectedAuthorId === 0 || Number(announcement.author?.id || 0) === selectedAuthorId

    return matchesTitle && matchesAuthor
  })
})

const selectedEditableIds = computed(() =>
  selectedAnnouncements.value.filter((item) => item?.canDelete).map((item) => Number(item.id)),
)

const addRoute = computed(() => ({
  name: "AnnouncementAdd",
  params: { node: route.params.node },
  query: getContextParams(["remind_inactive", "remindallinactives", "since"]),
}))

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams(extraKeys = []) {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  const gid = Number(getQueryValue(route.query.gid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  for (const key of ["origin", "page", "isStudentView", "lp_id", "lp_item_id", "lp_view_id", "returnToLp", "embedded", ...extraKeys]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

function getDetailRoute(announcement) {
  return {
    name: "AnnouncementDetail",
    params: {
      node: route.params.node,
      id: announcement.id,
    },
    query: getContextParams(),
  }
}

function getEditRoute(announcement) {
  return {
    name: "AnnouncementEdit",
    params: {
      node: route.params.node,
      id: announcement.id,
    },
    query: getContextParams(),
  }
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function toggleSearch() {
  isSearchVisible.value = !isSearchVisible.value
}

function applyFilters() {
  titleFilter.value = pendingTitleFilter.value
  authorFilter.value = pendingAuthorFilter.value
  isSearchVisible.value = false
}

function clearFilters() {
  pendingTitleFilter.value = ""
  pendingAuthorFilter.value = ""
  titleFilter.value = ""
  authorFilter.value = ""
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

function isActionLoading(id) {
  return actionLoadingIds.value.has(Number(id))
}

async function runRowAction(id, callback, message = "") {
  const next = new Set(actionLoadingIds.value)
  next.add(Number(id))
  actionLoadingIds.value = next
  errorMessage.value = ""
  successMessage.value = ""

  try {
    await callback()
    successMessage.value = message
    await loadAnnouncements()
  } catch (error) {
    console.error("Error managing announcement", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    const updated = new Set(actionLoadingIds.value)
    updated.delete(Number(id))
    actionLoadingIds.value = updated
  }
}

async function changeVisibility(announcement) {
  const visibility = Number(announcement.visibility) === 2 ? 0 : 2
  await runRowAction(
    announcement.id,
    () => announcementService.changeVisibility(announcement.id, visibility, csrfToken.value, getContextParams()),
    t("The visibility has been changed."),
  )
}

async function moveAnnouncement(announcement, direction) {
  await runRowAction(
    announcement.id,
    () => announcementService.move(announcement.id, direction, csrfToken.value, getContextParams()),
  )
}

function confirmDeleteOne(announcement) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () =>
      runRowAction(
        announcement.id,
        () => announcementService.deleteOne(announcement.id, csrfToken.value, getContextParams()),
        t("Announcement has been deleted"),
      ),
  })
}

function confirmDeleteSelected() {
  requireConfirmation({
    message: t("Are you sure you want to delete the selected items?"),
    accept: deleteSelected,
  })
}

async function deleteSelected() {
  isManaging.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    await announcementService.deleteSelected(selectedEditableIds.value, csrfToken.value, getContextParams())
    successMessage.value = t("Announcement has been deleted")
    await loadAnnouncements()
  } catch (error) {
    console.error("Error deleting selected announcements", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isManaging.value = false
  }
}

function confirmDeleteAll() {
  requireConfirmation({
    message: t("Are you sure you want to delete all items?"),
    accept: deleteAll,
  })
}

async function deleteAll() {
  isManaging.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    await announcementService.deleteAll(csrfToken.value, getContextParams())
    successMessage.value = t("Announcement has been deleted")
    await loadAnnouncements()
  } catch (error) {
    console.error("Error deleting all announcements", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isManaging.value = false
  }
}

async function loadAnnouncements() {
  isLoading.value = true
  errorMessage.value = ""
  announcements.value = []
  authors.value = []
  selectedAnnouncements.value = []
  canManage.value = false
  canDeleteAll.value = false
  csrfToken.value = ""

  try {
    const response = await announcementService.getList(getContextParams())
    announcements.value = Array.isArray(response.items) ? response.items : []
    authors.value = Array.isArray(response.authors) ? response.authors : []
    canManage.value = Boolean(response.canManage)
    canDeleteAll.value = Boolean(response.canDeleteAll)
    csrfToken.value = response.csrfToken || ""
  } catch (error) {
    console.error("Error loading announcements", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAnnouncements)

watch(
  () => [route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadAnnouncements,
)
</script>
