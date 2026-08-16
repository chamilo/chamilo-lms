<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="infoMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ infoMessage }}
    </div>

    <div class="flex w-fit flex-wrap items-center gap-1 rounded-xl border border-gray-20 bg-white px-2 py-1 shadow-sm">
      <BaseButton
        :label="t('Back')"
        :route="gradebookRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
      <BaseButton
        v-if="certificates?.canManage && certificates?.settings?.customCertificateFallback"
        :label="t('Generate')"
        :to-url="certificates.customCertificateFallbackUrl"
        icon="gradebook"
        only-icon
        size="normal"
        type="success"
      />
      <BaseButton
        v-else-if="certificates?.canManage"
        :disabled="isRunningAction"
        :is-loading="isRunningAction && currentAction === 'generate_all'"
        :label="t('Generate')"
        icon="gradebook"
        only-icon
        size="normal"
        type="success"
        @click="generateAll"
      />
      <BaseButton
        v-if="certificates?.canManage"
        :disabled="isRunningAction"
        :label="t('Delete')"
        icon="delete"
        only-icon
        size="normal"
        type="danger-text"
        @click="confirmDeleteAll"
      />
      <BaseButton
        v-if="certificates?.canManage && hasGeneratedCertificates"
        :disabled="isRunningAction"
        :label="t('Notify')"
        icon="send"
        only-icon
        size="normal"
        type="primary-text"
        @click="openNotificationDialog"
      />
      <BaseButton
        v-if="
          certificates?.canManage &&
          hasGeneratedCertificates &&
          !certificates?.settings?.hideExport &&
          !certificates?.settings?.customCertificateFallback
        "
        :label="t('Export to PDF')"
        :to-url="gradebookService.buildCertificateExportUrl(exportContextParams)"
        icon="file-pdf"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-90">
            {{ t("Certificate") }}
          </h1>
          <p
            v-if="categorySubtitle"
            class="mt-1 text-sm text-gray-600"
          >
            {{ categorySubtitle }}
          </p>
        </div>

        <div
          v-if="certificates?.canManage && certificates?.settings?.filterByOfficialCode"
          class="w-full md:w-72"
        >
          <BaseSelect
            id="gradebook-certificate-official-code"
            v-model="officialCode"
            :label="t('Code')"
            name="gradebook_certificate_official_code"
            :options="officialCodeOptions"
            option-label="label"
            option-value="value"
          />
        </div>
      </div>
    </div>

    <div
      v-if="certificates?.canManage && certificates?.category?.weightWarning"
      class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
      role="alert"
    >
      <span class="font-semibold">{{ t("Warning") }}:</span>
      {{ t("Weight") }} {{ formatNumber(certificates.category.resourceWeight) }} /
      {{ formatNumber(certificates.category.weight) }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <BaseTable
      v-else
      :is-loading="isLoading"
      :text-for-empty="t('No results found')"
      :total-items="learners.length"
      :values="learners"
      data-key="id"
    >
      <Column :header="t('Learner')">
        <template #body="{ data }">
          <div class="font-semibold text-gray-90">
            {{ data.user?.fullName || "-" }}
          </div>
          <div class="text-xs text-gray-500">
            {{ data.user?.username || "-" }}
          </div>
        </template>
      </Column>

      <Column :header="t('Score')">
        <template #body="{ data }">
          <span>{{ formatNumber(data.score) }}%</span>
        </template>
      </Column>

      <Column :header="t('Date')">
        <template #body="{ data }">
          {{ formatDate(data.certificate?.issuedAt) }}
        </template>
      </Column>

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex flex-wrap justify-end gap-1">
            <BaseButton
              v-if="data.certificate?.viewUrl"
              :label="t('View')"
              :to-url="data.certificate.viewUrl"
              icon="eye-on"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="data.certificate?.downloadUrl"
              :label="t('Download')"
              :to-url="data.certificate.downloadUrl"
              icon="download"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              v-if="certificates?.canManage && data.certificate"
              :disabled="isRunningAction"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDeleteOne(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="isNotificationDialogVisible"
      :title="t('Certificate notification')"
      header-icon="send"
    >
      <div class="flex flex-col gap-4">
        <BaseTextArea
          id="gradebook-certificate-notification-message"
          v-model="notificationMessage"
          :label="t('Message')"
          name="gradebook_certificate_notification_message"
          rows="8"
        />
        <div class="rounded-lg bg-gray-10 p-3 text-xs text-gray-600">
          <div class="mb-1 font-semibold">{{ t("Tags") }}</div>
          <div class="flex flex-wrap gap-x-3 gap-y-1">
            <code v-for="tag in notificationTags" :key="tag">{{ tag }}</code>
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton
          :disabled="isRunningAction || !notificationMessage.trim()"
          :is-loading="isRunningAction && currentAction === 'notify_all'"
          :label="t('Send message')"
          icon="send"
          type="success"
          @click="sendNotifications"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const certificates = ref(null)
const officialCode = ref("")
const isLoading = ref(false)
const isRunningAction = ref(false)
const currentAction = ref("")
const errorMessage = ref("")
const infoMessage = ref("")
const isNotificationDialogVisible = ref(false)
const notificationMessage = ref("((user_first_name)),")
const notificationTags = [
  "((course_title))",
  "((user_first_name))",
  "((user_last_name))",
  "((author_first_name))",
  "((author_last_name))",
  "((score))",
  "((portal_name))",
  "((certificate_link))",
]

const learners = computed(() =>
  (certificates.value?.learners || []).map((row) => ({
    ...row,
    id: Number(row.user?.id || 0),
  })),
)
const hasGeneratedCertificates = computed(() => learners.value.some((row) => Boolean(row.certificate)))
const categorySubtitle = computed(() => {
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId <= 0) {
    return ""
  }

  return String(certificates.value?.category?.title || "").trim()
})
const officialCodeOptions = computed(() => [
  { label: t("All"), value: "" },
  ...(certificates.value?.officialCodeOptions || []),
])

const gradebookRoute = computed(() => ({
  name: "GradebookList",
  params: { node: route.params.node },
  query: cleanQuery(route.query),
}))

const exportContextParams = computed(() => getContextParams())

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function cleanQuery(query) {
  const output = { ...query }
  delete output.officialCode

  return output
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }
  if (officialCode.value) {
    params.officialCode = officialCode.value
  }

  return params
}

async function loadCertificates() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    certificates.value = await gradebookService.getCertificates(getContextParams())
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

async function runAction(action, userId = null) {
  if (!certificates.value?.csrfToken) {
    return
  }

  isRunningAction.value = true
  currentAction.value = action
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.runCertificateAction(
      {
        action,
        categoryId: Number(certificates.value?.category?.id || 0),
        userId: userId ? Number(userId) : null,
        officialCode: officialCode.value || "",
        notificationMessage: action === "notify_all" ? notificationMessage.value : "",
        submittedCsrfToken: certificates.value.csrfToken,
      },
      getContextParams(),
    )
    infoMessage.value = t("Success")
    await loadCertificates()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isRunningAction.value = false
    currentAction.value = ""
  }
}

function openNotificationDialog() {
  notificationMessage.value = notificationMessage.value || "((user_first_name)),"
  isNotificationDialogVisible.value = true
}

async function sendNotifications() {
  if (!notificationMessage.value.trim()) {
    return
  }

  await runAction("notify_all")
  if (!errorMessage.value) {
    isNotificationDialogVisible.value = false
  }
}

function generateAll() {
  runAction("generate_all")
}

function confirmDeleteOne(row) {
  if (!row?.user?.id || !row.certificate) {
    return
  }

  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => runAction("delete", row.user.id),
  })
}

function confirmDeleteAll() {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => runAction("delete_all"),
  })
}

function formatNumber(value) {
  const number = Number(value)
  if (!Number.isFinite(number)) {
    return "0"
  }

  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(number)
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return "-"
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(date)
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.message ||
    error?.message ||
    t("An error occurred")
  )
}

watch(officialCode, async () => {
  if (!certificates.value?.canManage || !certificates.value?.settings?.filterByOfficialCode) {
    return
  }

  await loadCertificates()
})

onMounted(loadCertificates)
</script>
