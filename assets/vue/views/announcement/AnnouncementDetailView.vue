<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <BaseButton
          icon="back"
          :label="t('Back')"
          only-icon
          :route="listRoute"
          size="large"
          :tooltip="t('Back')"
          type="primary-text"
        />
        <BaseButton
          v-if="announcement?.canEdit"
          icon="edit"
          :label="t('Edit')"
          only-icon
          :route="editRoute"
          size="large"
          :tooltip="t('Edit')"
          type="secondary-text"
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

    <BaseCard v-else-if="announcement">
      <template #title>
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <h2 class="min-w-0 flex-1 break-words text-xl font-semibold text-gray-90">
            {{ announcement.title }}
          </h2>

          <BaseIcon
            v-if="announcement.emailSent"
            icon="email-unread"
            size="small"
            :tooltip="t('Announcement sent by e-mail')"
          />

          <span
            v-if="canManage && Number(announcement.visibility) !== 2"
            class="rounded-full bg-gray-20 px-2 py-0.5 text-xs font-medium text-gray-700"
          >
            {{ t("Invisible") }}
          </span>
        </div>
      </template>

      <template #subtitle>
        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-600">
          <span v-if="announcement.author">
            {{ t("Created by") }}:
            {{ announcement.author.fullName || announcement.author.username }}
          </span>
          <span>
            {{ t("Latest update") }}:
            {{ formatDate(announcement.updatedAt) }}
          </span>
        </div>
      </template>

      <div
        v-if="announcement.content"
        class="break-words"
        v-html="announcement.content"
      ></div>
      <p
        v-else
        class="text-sm italic text-gray-500"
      >
        {{ t("No content") }}
      </p>

      <div
        v-if="announcement.attachments?.length"
        class="mt-6 border-t border-gray-20 pt-4"
      >
        <h3 class="mb-3 text-base font-semibold text-gray-90">
          {{ t("Attachments") }}
        </h3>

        <ul class="space-y-2">
          <li
            v-for="attachment in announcement.attachments"
            :key="attachment.id"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-gray-20 bg-gray-10 px-3 py-2"
          >
            <BaseIcon
              icon="attachment"
              size="small"
            />
            <a
              class="font-medium text-primary hover:underline"
              :href="attachment.downloadUrl"
            >
              {{ attachment.filename }}
            </a>
            <span class="text-xs text-gray-500">
              {{ formatFileSize(attachment.size) }}
            </span>
            <span
              v-if="attachment.comment"
              class="w-full text-sm text-gray-600 md:w-auto"
            >
              {{ attachment.comment }}
            </span>
          </li>
        </ul>
      </div>

      <div
        v-if="canViewRecipients && announcement.recipients"
        class="mt-6 border-t border-gray-20 pt-4"
      >
        <h3 class="mb-3 text-base font-semibold text-gray-90">
          {{ t("Visible to") }}
        </h3>

        <p
          v-if="announcement.recipients.everyone"
          class="text-sm text-gray-700"
        >
          {{ t("All") }}
        </p>

        <div
          v-if="announcement.recipients.groups?.length"
          class="mb-3"
        >
          <p class="mb-1 text-sm font-medium text-gray-700">
            {{ t("Groups") }}
          </p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="group in announcement.recipients.groups"
              :key="group.id"
              class="rounded-full bg-gray-20 px-3 py-1 text-xs font-medium text-gray-700"
            >
              {{ group.title }}
            </span>
          </div>
        </div>

        <div v-if="announcement.recipients.users?.length">
          <p class="mb-1 text-sm font-medium text-gray-700">
            {{ t("Users") }}
          </p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="user in announcement.recipients.users"
              :key="user.id"
              class="rounded-full bg-gray-20 px-3 py-1 text-xs font-medium text-gray-700"
              :title="user.username"
            >
              {{ user.fullName || user.username }}
            </span>
          </div>
        </div>
      </div>
    </BaseCard>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import announcementService from "../../services/announcementService"

const { t, locale } = useI18n()
const route = useRoute()

const announcement = ref(null)
const canManage = ref(false)
const canViewRecipients = ref(false)
const isLoading = ref(false)
const errorMessage = ref("")

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
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

  for (const key of ["origin", "page", "isStudentView"]) {
    if (Object.prototype.hasOwnProperty.call(route.query, key)) {
      params[key] = getQueryValue(route.query[key])
    }
  }

  return params
}

const listRoute = computed(() => ({
  name: "AnnouncementList",
  params: { node: route.params.node },
  query: getContextParams(),
}))

const editRoute = computed(() => ({
  name: "AnnouncementEdit",
  params: {
    node: route.params.node,
    id: route.params.id,
  },
  query: getContextParams(),
}))

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(locale.value, {
    dateStyle: "long",
    timeStyle: "short",
  }).format(date)
}

function formatFileSize(value) {
  const size = Number(value || 0)
  if (size <= 0) {
    return "0 B"
  }

  const units = ["B", "KB", "MB", "GB"]
  const unitIndex = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1)
  const normalized = size / 1024 ** unitIndex

  return `${new Intl.NumberFormat(locale.value, { maximumFractionDigits: 1 }).format(normalized)} ${units[unitIndex]}`
}

async function loadAnnouncement() {
  const id = Number(route.params.id || 0)
  if (id <= 0) {
    errorMessage.value = t("An error occurred")
    announcement.value = null

    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await announcementService.getItem(id, getContextParams())
    announcement.value = response.item || null
    canManage.value = Boolean(response.canManage)
    canViewRecipients.value = Boolean(response.canViewRecipients)
  } catch (error) {
    console.error("Error loading announcement", error)
    announcement.value = null
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

onMounted(loadAnnouncement)

watch(
  () => [route.params.id, route.query.cid, route.query.sid, route.query.gid, route.query.isStudentView],
  loadAnnouncement,
)
</script>
