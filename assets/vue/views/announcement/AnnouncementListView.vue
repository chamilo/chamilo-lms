<template>
  <section class="space-y-4">
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
          <div class="flex items-center gap-2">
            <BaseButton
              v-if="canManage"
              icon="announcement"
              :label="t('Add an announcement')"
              only-icon
              size="large"
              :route="addRoute"
              type="success"
            />

            <BaseButton
              :icon="isSearchVisible ? 'close' : 'search'"
              :label="isSearchVisible ? t('Cancel') : t('Search')"
              only-icon
              size="large"
              type="primary"
              @click="toggleSearch"
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
        data-key="id"
        :text-for-empty="t('There are no announcements.')"
        :total-items="filteredAnnouncements.length"
        :values="filteredAnnouncements"
      >
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

              <BaseIcon
                v-if="canManage && Number(data.visibility) !== 2"
                icon="eye-off"
                size="small"
                :tooltip="t('Invisible')"
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
            <div class="flex items-center gap-2">
              <BaseButton
                icon="eye-on"
                :label="t('View')"
                only-icon
                :route="getDetailRoute(data)"
                size="small"
                :tooltip="t('View')"
                type="primary-text"
              />
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
import announcementService from "../../services/announcementService"

const { t, locale } = useI18n()
const route = useRoute()

const announcements = ref([])
const authors = ref([])
const canManage = ref(false)
const isLoading = ref(false)
const errorMessage = ref("")
const isSearchVisible = ref(false)
const pendingTitleFilter = ref("")
const pendingAuthorFilter = ref("")
const titleFilter = ref("")
const authorFilter = ref("")

const hasActiveFilters = computed(() => titleFilter.value.trim() !== "" || Number(authorFilter.value || 0) > 0)

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

  for (const key of ["origin", "page", "isStudentView", ...extraKeys]) {
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

async function loadAnnouncements() {
  isLoading.value = true
  errorMessage.value = ""
  announcements.value = []
  authors.value = []
  canManage.value = false

  try {
    const response = await announcementService.getList(getContextParams())
    announcements.value = Array.isArray(response.items) ? response.items : []
    authors.value = Array.isArray(response.authors) ? response.authors : []
    canManage.value = Boolean(response.canManage)
  } catch (error) {
    console.error("Error loading announcements", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
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
