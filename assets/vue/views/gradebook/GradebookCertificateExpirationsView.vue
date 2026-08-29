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
        :route="certificatesRoute"
        icon="back"
        only-icon
        size="normal"
        type="primary-text"
      />
    </div>

    <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-90">
            {{ t("Expiring certificates") }}
          </h1>
          <p
            v-if="expirations?.category?.title"
            class="mt-1 text-sm text-gray-600"
          >
            {{ expirations.category.title }}
          </p>
        </div>

        <div class="w-full md:w-56">
          <BaseInputNumber
            id="gradebook-certificate-expirations-days-ahead"
            v-model="daysAhead"
            :label="t('Days ahead')"
            :min="0"
            name="gradebook_certificate_expirations_days_ahead"
          />
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-4 text-sm">
        <span class="rounded-full bg-red-100 px-3 py-1 font-semibold text-red-700">
          {{ t("Expired: {0}", [expirations?.summary?.expired ?? 0]) }}
        </span>
        <span class="rounded-full bg-yellow-100 px-3 py-1 font-semibold text-yellow-800">
          {{ t("Expiring soon: {0}", [expirations?.summary?.expiring ?? 0]) }}
        </span>
      </div>
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
      v-model:selectedItems="selectedItems"
      :is-loading="isLoading"
      :text-for-empty="t('No results found')"
      :total-items="rows.length"
      :values="rows"
      data-key="id"
    >
      <Column
        header-style="width: 3rem"
        selection-mode="multiple"
      />

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

      <Column :header="t('Expiry date')">
        <template #body="{ data }">
          {{ formatDateOnly(data.expiryDate) }}
        </template>
      </Column>

      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            :class="
              'expired' === data.status
                ? 'rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700'
                : 'rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800'
            "
          >
            {{ "expired" === data.status ? t("Expired") : t("Expiring soon") }}
          </span>
        </template>
      </Column>

      <Column :header="t('Last reminder sent')">
        <template #body="{ data }">
          <div class="flex flex-col gap-1 text-xs">
            <span v-if="!data.lastReminder?.expired && !data.lastReminder?.expiring">
              {{ t("Never") }}
            </span>
            <span v-if="data.lastReminder?.expired">
              {{ t("Expired notice: {0}", [formatDate(data.lastReminder.expired.sentAt)]) }}
              <template v-if="data.lastReminder.expired.stale">({{ t("outdated") }})</template>
            </span>
            <span v-if="data.lastReminder?.expiring">
              {{ t("Expiring notice: {0}", [formatDate(data.lastReminder.expiring.sentAt)]) }}
              <template v-if="data.lastReminder.expiring.stale">({{ t("outdated") }})</template>
            </span>
          </div>
        </template>
      </Column>
    </BaseTable>

    <div class="flex items-center gap-4 rounded-xl border border-gray-20 bg-white px-4 py-3 shadow-sm">
      <span class="text-sm text-gray-600">{{ selectedItems.length }} {{ t("selected") }}</span>
      <BaseButton
        :disabled="0 === selectedItems.length || isSendingNotification"
        :is-loading="isSendingNotification"
        :label="t('Send notification')"
        icon="send"
        type="primary"
        @click="confirmSendNotifications"
      />
    </div>

    <BaseDialog
      v-model:is-visible="isSendConfirmDialogVisible"
      :title="t('Send notification')"
      header-icon="send"
    >
      <div class="space-y-4">
        <p>{{ t("Are you sure you want to send this notification?") }}</p>
        <p class="text-sm text-gray-600">{{ selectedItems.length }} {{ t("selected") }}</p>

        <div v-if="selectionIncludesExpiring">
          <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ t("Expiring notice") }}
          </div>
          <div
            class="max-h-64 overflow-y-auto rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm"
            v-html="messagePreviews.expiring"
          />
        </div>

        <div v-if="selectionIncludesExpired">
          <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ t("Expired notice") }}
          </div>
          <div
            class="max-h-64 overflow-y-auto rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm"
            v-html="messagePreviews.expired"
          />
        </div>
      </div>

      <template #footer>
        <BaseButton
          :disabled="isSendingNotification"
          :is-loading="isSendingNotification"
          :label="t('Send notification')"
          icon="send"
          type="primary"
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
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()

const expirations = ref(null)
const isLoading = ref(false)
const isSendingNotification = ref(false)
const isSendConfirmDialogVisible = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const daysAhead = ref(30)
const selectedItems = ref([])
const messagePreviews = ref({ expired: "", expiring: "" })

const selectionIncludesExpiring = computed(() => selectedItems.value.some((item) => "expiring" === item.status))
const selectionIncludesExpired = computed(() => selectedItems.value.some((item) => "expired" === item.status))

const rows = computed(() =>
  (expirations.value?.rows || []).map((row) => ({
    ...row,
    id: Number(row.user?.id || 0),
  })),
)

const certificatesRoute = computed(() => {
  const query = { ...route.query }
  delete query.daysAhead

  return {
    name: "GradebookCertificates",
    params: { node: route.params.node },
    query,
  }
})

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
    sid: getQueryValue(route.query.sid),
    gid: getQueryValue(route.query.gid),
    node: route.params.node,
    daysAhead: Number(daysAhead.value || 0),
  }
  const categoryId = Number(getQueryValue(route.query.categoryId) || 0)
  if (categoryId > 0) {
    params.categoryId = categoryId
  }

  return params
}

async function loadExpirations() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    expirations.value = await gradebookService.getCertificateExpirations(getContextParams())
    daysAhead.value = Number(expirations.value?.daysAhead ?? daysAhead.value)
    selectedItems.value = []
    await loadMessagePreviews()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

async function loadMessagePreviews() {
  if (!expirations.value?.csrfToken) {
    return
  }

  try {
    const result = await gradebookService.runCertificateAction(
      {
        action: "preview_expiry",
        categoryId: Number(expirations.value?.category?.id || 0),
        submittedCsrfToken: expirations.value.csrfToken,
      },
      getContextParams(),
    )
    messagePreviews.value = {
      expired: result?.previewExpired || "",
      expiring: result?.previewExpiring || "",
    }
  } catch (error) {
    // Non-fatal: the list itself already loaded. The confirmation dialog just won't
    // have a preview to show if this fails.
    errorMessage.value = getErrorMessage(error)
  }
}

function confirmSendNotifications() {
  if (0 === selectedItems.value.length) {
    return
  }

  isSendConfirmDialogVisible.value = true
}

async function sendNotifications() {
  if (!expirations.value?.csrfToken || 0 === selectedItems.value.length) {
    return
  }

  isSendingNotification.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.runCertificateAction(
      {
        action: "notify_expiry",
        categoryId: Number(expirations.value?.category?.id || 0),
        userIds: selectedItems.value.map((item) => Number(item.id)),
        submittedCsrfToken: expirations.value.csrfToken,
      },
      getContextParams(),
    )
    infoMessage.value = t("Success")
    isSendConfirmDialogVisible.value = false
    await loadExpirations()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isSendingNotification.value = false
  }
}

function formatDateOnly(value) {
  if (!value) {
    return "-"
  }

  const [year, month, day] = String(value)
    .split("-")
    .map((part) => Number(part))
  if (!year || !month || !day) {
    return "-"
  }

  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(new Date(year, month - 1, day))
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return "-"
  }

  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(date)
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("An error occurred")
}

let daysAheadDebounce = null
watch(daysAhead, () => {
  if (daysAheadDebounce) {
    clearTimeout(daysAheadDebounce)
  }
  daysAheadDebounce = setTimeout(loadExpirations, 400)
})

onMounted(loadExpirations)
</script>
