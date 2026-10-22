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

    <div
      class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
    >
      <h1 class="text-xl font-semibold text-gray-90">
        {{ isOwnTranscript ? t("My transcript") : t("Transcript of {0}", [overview?.user?.fullName || ""]) }}
      </h1>

      <div
        v-if="overview"
        class="flex flex-wrap gap-2"
      >
        <BaseButton
          :label="t('Download unofficial transcript')"
          :to-url="overview.unofficialPdfUrl"
          icon="file-pdf"
          type="secondary"
        />
        <BaseButton
          v-if="overview.canIssue"
          :is-loading="isIssuing"
          :label="t('Issue official transcript')"
          icon="shield-check"
          type="primary"
          @click="confirmIssue"
        />
      </div>
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <template v-else-if="transcript">
      <dl
        class="grid gap-4 rounded-xl border border-gray-20 bg-white p-4 text-sm shadow-sm sm:grid-cols-2 lg:grid-cols-4"
      >
        <div>
          <dt class="text-xs font-semibold uppercase text-gray-50">{{ t("Student name") }}</dt>
          <dd class="font-semibold text-gray-90">{{ transcript.student.fullName }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase text-gray-50">{{ t("Student number") }}</dt>
          <dd class="font-semibold text-gray-90">{{ transcript.student.studentNumber || "-" }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase text-gray-50">{{ t("Address") }}</dt>
          <dd class="font-semibold text-gray-90">{{ transcript.student.address.join(", ") || "-" }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase text-gray-50">{{ t("Program title") }}</dt>
          <dd class="font-semibold text-gray-90">{{ transcript.programTitle || "-" }}</dd>
        </div>
      </dl>

      <section class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Courses") }}</h2>
        <BaseTable
          :text-for-empty="t('No completed course yet.')"
          :total-items="transcript.courses.length"
          :values="transcript.courses"
          data-key="certificateId"
        >
          <Column
            :header="t('Course')"
            field="code"
          />
          <Column :header="t('Description')">
            <template #body="{ data }">
              <div class="font-semibold text-gray-90">{{ data.title }}</div>
              <div
                v-if="data.sessionTitle"
                class="text-xs text-gray-50"
              >
                {{ data.sessionTitle }}
              </div>
            </template>
          </Column>
          <Column :header="t('Enrollment')">
            <template #body="{ data }">{{ formatDate(data.enrolledAt) }}</template>
          </Column>
          <Column :header="t('Required hours')">
            <template #body="{ data }">{{ formatNumber(data.requiredHours) }}</template>
          </Column>
          <Column :header="t('Completion date')">
            <template #body="{ data }">{{ formatDate(data.completedAt) }}</template>
          </Column>
          <Column :header="t('Hours completed')">
            <template #body="{ data }">{{ formatNumber(data.completedHours) }}</template>
          </Column>
          <Column :header="t('Final grade')">
            <template #body="{ data }">{{ formatNumber(data.score, 0) }}%</template>
          </Column>
          <Column
            :header="t('Grade')"
            field="letter"
          />
        </BaseTable>
      </section>

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-50">{{ t("Final grade") }}</div>
          <div class="text-2xl font-semibold text-gray-90">
            {{ null === transcript.finalGrade ? "-" : `${transcript.finalGrade}%` }}
          </div>
        </div>
        <div class="rounded-xl border border-gray-20 bg-white p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-50">GPA</div>
          <div class="text-2xl font-semibold text-gray-90">
            {{ null === transcript.gpa ? "-" : formatNumber(transcript.gpa, 2, 2) }}
          </div>
          <p
            v-if="transcript.gpaNote"
            class="mt-2 text-xs text-gray-50"
          >
            {{ transcript.gpaNote }}
          </p>
        </div>
        <div class="rounded-xl border border-gray-20 bg-white p-4 text-sm shadow-sm">
          <div class="mb-2 text-xs font-semibold uppercase text-gray-50">{{ t("Grading scale") }}</div>
          <ul class="space-y-1">
            <li
              v-for="grade in transcript.gradingScale"
              :key="grade.letter"
              class="flex justify-between"
            >
              <span>{{ grade.min }}% → {{ grade.letter }}</span>
              <span>{{ formatNumber(grade.points, 1, 1) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <section
        v-if="officialTranscripts.length || overview.canIssue"
        class="space-y-3"
      >
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Official transcripts") }}</h2>
        <BaseTable
          :text-for-empty="t('No official transcript has been issued yet.')"
          :total-items="officialTranscripts.length"
          :values="officialTranscripts"
          data-key="id"
        >
          <Column :header="t('Issued on')">
            <template #body="{ data }">{{ formatDate(data.issuedAt) }}</template>
          </Column>
          <Column :header="t('Issued by')">
            <template #body="{ data }">{{ data.issuedBy || "-" }}</template>
          </Column>
          <Column :header="t('Status')">
            <template #body="{ data }">
              <span :class="'valid' === data.status ? 'text-success' : 'text-danger'">
                {{ statusLabel(data.status) }}
              </span>
            </template>
          </Column>
          <Column :header="t('Actions')">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1">
                <BaseButton
                  :label="t('Download')"
                  :to-url="data.downloadUrl"
                  icon="download"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  :label="t('Verify')"
                  :to-url="data.verificationUrl"
                  icon="shield-check"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  v-if="data.canRevoke"
                  :label="t('Revoke')"
                  icon="close"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmRevoke(data)"
                />
              </div>
            </template>
          </Column>
        </BaseTable>
      </section>

      <section
        v-if="overview.canManageSignature"
        class="space-y-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm"
      >
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Signature of official transcripts") }}</h2>
        <p class="text-sm text-gray-50">
          {{
            t(
              "This image is printed on the official transcripts of this portal, above the signatory name and title set in the certificate settings.",
            )
          }}
        </p>
        <img
          v-if="overview.signatureImage"
          :alt="t('Signature of official transcripts')"
          :src="overview.signatureImage"
          class="max-h-24 rounded border border-gray-20 bg-white p-2"
        />
        <div class="flex flex-wrap items-center gap-2">
          <input
            ref="signatureInput"
            accept="image/png,image/jpeg"
            class="text-sm"
            type="file"
          />
          <BaseButton
            :is-loading="isSavingSignature"
            :label="t('Upload')"
            icon="upload"
            type="primary"
            @click="uploadSignature"
          />
          <BaseButton
            v-if="overview.signatureImage"
            :label="t('Delete')"
            icon="delete"
            type="danger"
            @click="deleteSignature"
          />
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import Column from "primevue/column"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import transcriptService from "../../services/transcriptService"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const overview = ref(null)
const isLoading = ref(false)
const isIssuing = ref(false)
const isSavingSignature = ref(false)
const errorMessage = ref("")
const infoMessage = ref("")
const signatureInput = ref(null)

const targetUserId = computed(() => Number(route.query.userId) || null)
const isOwnTranscript = computed(() => !targetUserId.value)
const transcript = computed(() => overview.value?.transcript || null)
const officialTranscripts = computed(() => overview.value?.officialTranscripts || [])

async function loadOverview() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    overview.value = await transcriptService.getOverview(targetUserId.value)
  } catch (error) {
    overview.value = null
    errorMessage.value = getErrorMessage(error)
  } finally {
    isLoading.value = false
  }
}

function confirmIssue() {
  requireConfirmation({
    message: t(
      "Issue a signed official transcript with the courses and grades listed on this page? It can be verified online until it is revoked.",
    ),
    accept: issue,
  })
}

async function issue() {
  isIssuing.value = true
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await transcriptService.issue(overview.value.user.id)
    await loadOverview()
    infoMessage.value = t("Official transcript issued")
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isIssuing.value = false
  }
}

function confirmRevoke(officialTranscript) {
  requireConfirmation({
    message: t("Revoke this official transcript? Its verification page will then show it as revoked."),
    accept: () => revoke(officialTranscript.id),
  })
}

async function revoke(transcriptId) {
  errorMessage.value = ""
  infoMessage.value = ""

  try {
    await transcriptService.revoke(transcriptId)
    await loadOverview()
    infoMessage.value = t("Official transcript revoked")
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  }
}

async function uploadSignature() {
  const file = signatureInput.value?.files?.[0]
  if (!file) {
    errorMessage.value = t("Select a PNG or JPEG image first.")

    return
  }

  isSavingSignature.value = true
  errorMessage.value = ""

  try {
    const { signatureImage } = await transcriptService.uploadSignatureImage(file)
    overview.value.signatureImage = signatureImage
    signatureInput.value.value = ""
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  } finally {
    isSavingSignature.value = false
  }
}

async function deleteSignature() {
  errorMessage.value = ""

  try {
    await transcriptService.deleteSignatureImage()
    overview.value.signatureImage = null
  } catch (error) {
    errorMessage.value = getErrorMessage(error)
  }
}

function statusLabel(status) {
  return {
    valid: t("Valid"),
    revoked: t("Revoked"),
    invalid_signature: t("Invalid signature"),
  }[status]
}

function formatNumber(value, maximumFractionDigits = 2, minimumFractionDigits = 0) {
  if (null === value || undefined === value || "" === value) {
    return "-"
  }

  return new Intl.NumberFormat(undefined, { maximumFractionDigits, minimumFractionDigits }).format(Number(value))
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

watch(targetUserId, loadOverview)
onMounted(loadOverview)
</script>
