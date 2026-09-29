<template>
  <section class="space-y-6">
    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/30 bg-danger/10 p-4 text-sm text-danger"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
      <div>
        <h1 class="text-xl font-semibold text-gray-90">{{ t("My certificates") }}</h1>
      </div>

      <BaseButton
        v-if="certificates?.allowSearch && certificates?.searchUrl"
        :label="t('Search certificates')"
        :to-url="certificates.searchUrl"
        icon="search"
        type="primary"
      />
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else>
      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Courses") }}</h2>
        <BaseTable
          :text-for-empty="t('No results found')"
          :total-items="courseCertificates.length"
          :values="courseCertificates"
          data-key="id"
        >
          <Column :header="t('Course')">
            <template #body="{ data }">
              <div class="font-semibold text-gray-90">{{ data.course?.title || "-" }}</div>
              <div class="text-xs text-gray-500">{{ data.course?.code || "" }}</div>
            </template>
          </Column>
          <Column :header="t('Score')">
            <template #body="{ data }">{{ formatNumber(data.score) }}%</template>
          </Column>
          <Column :header="t('Date')">
            <template #body="{ data }">{{ formatDate(data.issuedAt) }}</template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex justify-end gap-1">
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
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Course sessions") }}</h2>
        <BaseTable
          :text-for-empty="t('No results found')"
          :total-items="sessionCertificates.length"
          :values="sessionCertificates"
          data-key="id"
        >
          <Column :header="t('Session')">
            <template #body="{ data }">{{ data.session?.title || "-" }}</template>
          </Column>
          <Column :header="t('Course')">
            <template #body="{ data }">
              <div class="font-semibold text-gray-90">{{ data.course?.title || "-" }}</div>
              <div class="text-xs text-gray-500">{{ data.course?.code || "" }}</div>
            </template>
          </Column>
          <Column :header="t('Score')">
            <template #body="{ data }">{{ formatNumber(data.score) }}%</template>
          </Column>
          <Column :header="t('Date')">
            <template #body="{ data }">{{ formatDate(data.issuedAt) }}</template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex justify-end gap-1">
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
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const certificates = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")

const courseCertificates = computed(() => certificates.value?.courseCertificates || [])
const sessionCertificates = computed(() => certificates.value?.sessionCertificates || [])

async function loadCertificates() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    certificates.value = await gradebookService.getMyCertificates()
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
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

  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date)
}

function getErrorMessage(error) {
  return error?.response?.data?.detail || error?.response?.data?.message || error?.message || t("Error")
}

onMounted(loadCertificates)
</script>
