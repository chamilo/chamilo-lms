<template>
  <section class="space-y-6">
    <div
      v-if="infoMessage"
      class="rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success"
      role="status"
    >
      {{ infoMessage }}
    </div>

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
      <div class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600 shadow-sm">
        {{ t("Publication consent allows an administrator to issue a signed certificate credential and optionally anchor its SHA-256 fingerprint on the Sepolia testnet. No name or personal data is sent on-chain. You can withdraw consent at any time.") }}
      </div>
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
          <Column :header="t('Publication consent')">
            <template #body="{ data }">
              <div v-if="data.certificate" class="flex items-center gap-2">
                <span class="text-xs text-gray-600">
                  {{ data.certificate.publicationApprovedAt ? t("Approved") : t("Not approved") }}
                  <span v-if="data.certificate.publicationApprovedAt" class="block">
                    {{ formatDate(data.certificate.publicationApprovedAt) }}
                  </span>
                </span>
                <BaseButton
                  :label="data.certificate.publicationApprovedAt ? t('Withdraw consent') : t('Approve publication')"
                  :icon="data.certificate.publicationApprovedAt ? 'delete' : 'pencil'"
                  only-icon
                  size="small"
                  :type="data.certificate.publicationApprovedAt ? 'danger-text' : 'secondary-text'"
                  :disabled="isUpdatingConsent"
                  :data-testid="`certificate-consent-${data.id}`"
                  @click="confirmPublicationConsent(data)"
                />
                <a
                  v-if="data.certificate.credentialVerificationUrl"
                  :href="data.certificate.credentialVerificationUrl"
                  class="text-xs underline text-primary"
                  :data-testid="`certificate-credential-${data.id}`"
                  target="_blank"
                  rel="noopener noreferrer"
                >{{ t("Verify") }}</a>
              </div>
            </template>
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
          <Column :header="t('Publication consent')">
            <template #body="{ data }">
              <div v-if="data.certificate" class="flex items-center gap-2">
                <span class="text-xs text-gray-600">
                  {{ data.certificate.publicationApprovedAt ? t("Approved") : t("Not approved") }}
                  <span v-if="data.certificate.publicationApprovedAt" class="block">
                    {{ formatDate(data.certificate.publicationApprovedAt) }}
                  </span>
                </span>
                <BaseButton
                  :label="data.certificate.publicationApprovedAt ? t('Withdraw consent') : t('Approve publication')"
                  :icon="data.certificate.publicationApprovedAt ? 'delete' : 'pencil'"
                  only-icon
                  size="small"
                  :type="data.certificate.publicationApprovedAt ? 'danger-text' : 'secondary-text'"
                  :disabled="isUpdatingConsent"
                  :data-testid="`certificate-consent-${data.id}`"
                  @click="confirmPublicationConsent(data)"
                />
                <a
                  v-if="data.certificate.credentialVerificationUrl"
                  :href="data.certificate.credentialVerificationUrl"
                  class="text-xs underline text-primary"
                  :data-testid="`certificate-credential-${data.id}`"
                  target="_blank"
                  rel="noopener noreferrer"
                >{{ t("Verify") }}</a>
              </div>
            </template>
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
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const certificates = ref(null)
const isLoading = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const isUpdatingConsent = ref(false)

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

function confirmPublicationConsent(row) {
  if (!row?.certificate || isUpdatingConsent.value) {
    return
  }

  const approve = !row.certificate.publicationApprovedAt
  requireConfirmation({
    message: approve
      ? t("I authorize a signed, publicly verifiable credential showing my name, course, score and issue date. An administrator may also publish only its SHA-256 fingerprint on the Sepolia testnet. The fingerprint is permanent, even if I withdraw later. Continue?")
      : t("Withdraw the publication approval for this certificate?"),
    accept: () => updatePublicationConsent(row.id, approve),
  })
}

async function updatePublicationConsent(certificateId, approve) {
  isUpdatingConsent.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await gradebookService.setCertificatePublicationConsent(certificateId, approve)
    await loadCertificates()
    infoMessage.value = approve ? t("Publication approval recorded") : t("Publication approval withdrawn")
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isUpdatingConsent.value = false
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
