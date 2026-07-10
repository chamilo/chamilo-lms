<template>
  <section class="space-y-6">
    <BaseToolbar class="mb-4 border-b border-gray-25 bg-white">
      <template #start>
        <div class="flex flex-wrap items-center gap-2">
          <BaseButton
            icon="back"
            :label="t('Back')"
            only-icon
            :route="listRoute"
            size="normal"
            :tooltip="t('Back')"
            type="primary-text"
          />

          <BaseButton
            v-if="announcement?.canEdit"
            icon="edit"
            :label="t('Edit')"
            only-icon
            :route="editRoute"
            size="normal"
            :tooltip="t('Edit')"
            type="secondary-text"
          />

          <BaseButton
            v-if="announcement?.canChangeVisibility"
            :icon="Number(announcement.visibility) === 2 ? 'eye-on' : 'eye-off'"
            :is-loading="isManaging"
            :label="Number(announcement.visibility) === 2 ? t('Visible') : t('Invisible')"
            only-icon
            size="normal"
            :tooltip="Number(announcement.visibility) === 2 ? t('Visible') : t('Invisible')"
            type="secondary-text"
            @click="changeVisibility"
          />

          <BaseButton
            v-if="announcement?.canDelete"
            icon="delete"
            :is-loading="isManaging"
            :label="t('Delete')"
            only-icon
            size="normal"
            :tooltip="t('Delete')"
            type="danger-text"
            @click="confirmDeleteAnnouncement"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

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
              class="min-w-0 flex-1 text-sm text-gray-600"
            >
              {{ attachment.comment }}
            </span>

            <BaseButton
              v-if="attachment.canDelete && attachmentsEnabled"
              icon="delete"
              :is-loading="deletingAttachmentId === Number(attachment.id)"
              :label="t('Delete')"
              only-icon
              size="small"
              :tooltip="t('Delete')"
              type="danger-text"
              @click="confirmDeleteAttachment(attachment)"
            />
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
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import announcementService from "../../services/announcementService"

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()

const announcement = ref(null)
const canManage = ref(false)
const canViewRecipients = ref(false)
const attachmentsEnabled = ref(false)
const csrfToken = ref("")
const attachmentCsrfToken = ref("")
const isLoading = ref(false)
const isManaging = ref(false)
const deletingAttachmentId = ref(0)
const errorMessage = ref("")
const successMessage = ref("")

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

  for (const key of ["origin", "page", "isStudentView", "lp_id", "lp_item_id", "lp_view_id", "returnToLp", "embedded"]) {
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

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
}

async function changeVisibility() {
  if (!announcement.value) return

  isManaging.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const visibility = Number(announcement.value.visibility) === 2 ? 0 : 2
    await announcementService.changeVisibility(
      announcement.value.id,
      visibility,
      csrfToken.value,
      getContextParams(),
    )
    successMessage.value = t("The visibility has been changed.")
    await loadAnnouncement()
  } catch (error) {
    console.error("Error changing announcement visibility", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isManaging.value = false
  }
}

function confirmDeleteAnnouncement() {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: deleteAnnouncement,
  })
}

async function deleteAnnouncement() {
  if (!announcement.value) return

  isManaging.value = true
  errorMessage.value = ""

  try {
    await announcementService.deleteOne(announcement.value.id, csrfToken.value, getContextParams())
    await router.push(listRoute.value)
  } catch (error) {
    console.error("Error deleting announcement", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    isManaging.value = false
  }
}

function confirmDeleteAttachment(attachment) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => deleteAttachment(attachment),
  })
}

async function deleteAttachment(attachment) {
  if (!announcement.value) return

  deletingAttachmentId.value = Number(attachment.id)
  errorMessage.value = ""
  successMessage.value = ""

  try {
    await announcementService.deleteAttachment(
      announcement.value.id,
      attachment.id,
      attachmentCsrfToken.value,
      getContextParams(),
    )
    successMessage.value = t("Attachment has been deleted")
    await loadAnnouncement()
  } catch (error) {
    console.error("Error deleting announcement attachment", error)
    errorMessage.value = getErrorMessage(error)
  } finally {
    deletingAttachmentId.value = 0
  }
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
    attachmentsEnabled.value = Boolean(response.attachmentsEnabled)
    csrfToken.value = response.csrfToken || ""
    attachmentCsrfToken.value = response.attachmentCsrfToken || ""
  } catch (error) {
    console.error("Error loading announcement", error)
    announcement.value = null
    errorMessage.value = getErrorMessage(error)
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
