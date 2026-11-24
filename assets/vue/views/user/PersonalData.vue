<template>
  <div class="flex flex-col gap-4">
    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Personal data: introduction") }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4" />
      <div>
        {{ t("We respect your privacy!") }}
      </div>
    </BaseCard>

    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Personal data we keep about you") }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4" />
      <div>
        <div
          v-for="(categoryData, categoryName) in personalData.data"
          :key="categoryName"
        >
          <button
            class="text-start w-full"
            @click="toggleCategory(categoryName)"
          >
            <span class="underline">{{ categoryName }}</span>
          </button>
          <ul v-if="expandedCategories.includes(categoryName)">
            <li
              v-for="(dataSet, dataSetIndex) in categoryData"
              :key="`dataset-${dataSetIndex}`"
            >
              <div
                v-for="(value, key) in dataSet"
                :key="`item-${dataSetIndex}-${key}`"
              >
                <span
                  ><strong>{{ key }}:</strong> {{ value }}</span
                >
              </div>
            </li>
          </ul>
        </div>
      </div>
    </BaseCard>

    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Download or send your personal data") }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4" />
      <div class="flex flex-col gap-3">
        <p>
          {{
            t(
              "You can download a JSON-LD or XML copy of the personal data we keep about you, or send it to another service. For your security, you will be asked to type your current password.",
            )
          }}
        </p>
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :label="t('Download as JSON-LD')"
            icon="file-export"
            type="black"
            @click="openExportDialog('download-json')"
          />
          <BaseButton
            :label="t('Download as XML')"
            icon="file-export"
            type="black"
            @click="openExportDialog('download-xml')"
          />
          <BaseButton
            v-if="exportOptions.allowExportToUrl"
            :label="t('Send to a URL')"
            icon="send"
            type="black"
            @click="openExportDialog('push')"
          />
        </div>
      </div>
    </BaseCard>

    <BaseCard
      v-if="showTermsAndConditionsCard"
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Terms and Conditions") }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4" />
      <div>
        <a
          href="#"
          @click.prevent="openTermsDialog"
        >
          {{ t("Read the Terms and Conditions") }}
        </a>
      </div>
    </BaseCard>

    <BaseCard
      v-if="showLegalAgreementCard"
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Permissions you gave us") }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4" />
      <div>
        <div v-if="legalStatus.isAccepted">
          <p>
            {{ t("Legal agreement accepted") }}
            <BaseIcon
              class="text-green-500"
              icon="check"
              size="small"
            />
          </p>
          <p>{{ t("Date") }}: {{ legalStatus.acceptDate }}</p>

          <div class="mt-3 p-3 rounded bg-blue-50 text-sm text-blue-800">
            <p>
              {{
                t("Your legal agreement remains active until any withdrawal request has been reviewed and processed.")
              }}
            </p>
          </div>

          <div
            v-if="legalWithdrawalRequestPending"
            class="mt-3 p-3 rounded bg-amber-50 text-sm text-amber-800"
          >
            <p>{{ t("Your legal consent withdrawal request is pending review.") }}</p>
          </div>

          <div
            v-if="accountDeletionRequestPending"
            class="mt-3 p-3 rounded bg-amber-50 text-sm text-amber-800"
          >
            <p>{{ t("Your account deletion request is pending review.") }}</p>
          </div>

          <div v-if="termsAndConditions?.items?.length">
            <div class="p-4">
              <p class="text-sm text-gray-600">
                {{ t("Why do you want to request the withdrawal of your legal consent?") }}
              </p>
            </div>

            <form
              class="flex flex-col gap-2 mt-6"
              @submit.prevent="submitDeleteTerm"
            >
              <BaseTextArea
                v-model="deleteTermExplanation"
                :label="
                  t(
                    'Please explain why you want to request the withdrawal of your legal consent, so we can process your request properly',
                  )
                "
                :disabled="legalWithdrawalRequestPending || isSubmittingDeleteLegalRequest"
                required
              />

              <div
                v-if="isSubmittingDeleteLegalRequest"
                class="text-sm text-blue-700"
              >
                {{ t("Submitting your legal consent withdrawal request...") }}
              </div>

              <LayoutFormButtons>
                <BaseButton
                  :label="
                    isSubmittingDeleteLegalRequest
                      ? t('Submitting request...')
                      : t('Request withdrawal of legal consent')
                  "
                  :disabled="legalWithdrawalRequestPending || isSubmittingDeleteLegalRequest"
                  icon="delete"
                  type="danger"
                  @click="submitDeleteTerm"
                />
              </LayoutFormButtons>
            </form>

            <form
              class="flex flex-col gap-2 mt-6"
              @submit.prevent="submitDeleteAccount"
            >
              <BaseTextArea
                v-model="deleteAccountExplanation"
                :label="t('Explain in this box why you want your account deleted')"
                :disabled="accountDeletionRequestPending || isSubmittingDeleteAccountRequest"
                required
              />

              <div
                v-if="isSubmittingDeleteAccountRequest"
                class="text-sm text-blue-700"
              >
                {{ t("Submitting your account deletion request...") }}
              </div>

              <LayoutFormButtons>
                <BaseButton
                  :label="isSubmittingDeleteAccountRequest ? t('Submitting request...') : t('Delete account')"
                  :disabled="accountDeletionRequestPending || isSubmittingDeleteAccountRequest"
                  icon="delete"
                  type="danger"
                  @click="submitDeleteAccount"
                />
              </LayoutFormButtons>
            </form>
          </div>
        </div>

        <div v-else-if="canAcceptTerms">
          <div
            v-if="isSubmittingAcceptTerms"
            class="mb-2 text-sm text-blue-700"
          >
            {{ t("Submitting your acceptance...") }}
          </div>

          <BaseButton
            :label="isSubmittingAcceptTerms ? t('Submitting request...') : t(legalStatus.message)"
            :disabled="isSubmittingAcceptTerms"
            icon="send"
            type="primary"
            @click="submitAcceptTerm"
          />
        </div>
      </div>
    </BaseCard>
  </div>

  <BaseDialog
    v-if="termsAndConditions?.items?.length"
    v-model:is-visible="termsAndConditionsDialogVisible"
    :style="{ width: '52rem', maxWidth: '95vw' }"
    :title="t('Read the Terms and Conditions')"
    :close-label="t('Close')"
  >
    <div class="overflow-y-auto max-h-[70vh] pe-2">
      <template
        v-for="(term, index) in termsAndConditions.items"
        :key="`term-${index}`"
      >
        <h3 class="text-lg font-semibold mb-2">{{ term.title }}</h3>

        <p
          v-if="term.subtitle"
          class="text-sm text-gray-500 mb-2"
        >
          <em>{{ term.subtitle }}</em>
        </p>

        <div
          v-html="term.content"
          class="mb-4"
          dir="auto"
        ></div>
        <hr class="my-4" />
      </template>

      <p
        v-if="termsAndConditions.date_text"
        class="text-sm text-gray-500 mt-4"
      >
        {{ termsAndConditions.date_text }}
      </p>
    </div>
  </BaseDialog>

  <BaseDialog
    v-model:is-visible="exportDialogVisible"
    :title="exportDialogTitle"
    header-icon="lock"
  >
    <form
      class="flex flex-col gap-3"
      @submit.prevent="submitExport"
    >
      <p>
        {{ t("For your security, please type your current password to continue.") }}
      </p>
      <BaseInputText
        id="personal-data-export-password"
        v-model="exportPassword"
        :label="t('Current password')"
        autocomplete="current-password"
        type="password"
        required
      />
      <BaseInputText
        v-if="exportAction === 'push'"
        id="personal-data-export-url"
        v-model="exportUrl"
        :label="t('Destination HTTPS URL')"
        :help-text="t('The address must use HTTPS. Private and internal addresses are not allowed.')"
        autocomplete="off"
        required
      />
      <LayoutFormButtons>
        <BaseButton
          :disabled="isExporting"
          :label="isExporting ? t('Please wait...') : exportSubmitLabel"
          :icon="exportAction === 'push' ? 'send' : 'file-export'"
          type="primary"
          @click="submitExport"
        />
      </LayoutFormButtons>
    </form>
  </BaseDialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"

import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import { useNotification } from "../../composables/notification"
import socialService from "../../services/socialService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()
const isSubmittingDeleteLegalRequest = ref(false)
const isSubmittingDeleteAccountRequest = ref(false)
const isSubmittingAcceptTerms = ref(false)
const { showSuccessNotification, showErrorNotification } = useNotification()

const personalData = reactive({
  data: {},
})

function createEmptyTermsAndConditions() {
  return {
    items: [],
    date_text: "",
    version: null,
    language_id: null,
    showing_accepted: false,
    content: [],
    available: false,
  }
}

function createEmptyLegalStatus() {
  return {
    isAccepted: false,
    acceptDate: null,
    message: "",
    available: false,
  }
}

const termsAndConditions = ref(createEmptyTermsAndConditions())
const legalStatus = reactive(createEmptyLegalStatus())

const termsAndConditionsDialogVisible = ref(false)
const expandedCategories = ref([])
const deleteTermExplanation = ref("")
const deleteAccountExplanation = ref("")
const legalWithdrawalRequestPending = ref(false)
const accountDeletionRequestPending = ref(false)

const exportDialogVisible = ref(false)
const exportAction = ref("download")
const exportPassword = ref("")
const exportUrl = ref("")
const isExporting = ref(false)
const exportOptions = reactive({
  allowExportToUrl: false,
  csrfToken: "",
})

const exportDialogTitle = computed(() => {
  if (exportAction.value === "push") {
    return t("Send your personal data to a URL")
  }
  if (exportAction.value === "download-xml") {
    return t("Download your personal data as XML")
  }

  return t("Download your personal data as JSON-LD")
})

const exportSubmitLabel = computed(() => {
  if (exportAction.value === "push") {
    return t("Send")
  }
  if (exportAction.value === "download-xml") {
    return t("Download as XML")
  }

  return t("Download as JSON-LD")
})

function resetExportForm() {
  exportPassword.value = ""
  exportUrl.value = ""
  isExporting.value = false
}

function openExportDialog(action) {
  exportAction.value = action
  resetExportForm()
  exportDialogVisible.value = true
}

async function fetchExportOptions() {
  try {
    const options = await socialService.fetchPersonalDataExportOptions()
    exportOptions.allowExportToUrl = Boolean(options?.allow_export_to_url)
    exportOptions.csrfToken = options?.csrf_token || ""
  } catch {
    exportOptions.allowExportToUrl = false
    exportOptions.csrfToken = ""
  }
}

async function submitExport() {
  if (isExporting.value) {
    return
  }
  if (exportPassword.value === "") {
    showErrorNotification(t("Please type your current password."))
    return
  }
  if (exportAction.value === "push" && exportUrl.value.trim() === "") {
    showErrorNotification(t("Please enter an HTTPS URL."))
    return
  }

  isExporting.value = true
  try {
    if (exportAction.value === "push") {
      const response = await socialService.sendPersonalDataToUrl({
        password: exportPassword.value,
        csrfToken: exportOptions.csrfToken,
        url: exportUrl.value.trim(),
      })
      showSuccessNotification(response.message || t("Your personal data was sent to the destination URL."))
    } else {
      await socialService.downloadPersonalData({
        password: exportPassword.value,
        csrfToken: exportOptions.csrfToken,
        format: exportAction.value === "download-xml" ? "xml" : "json",
      })
      showSuccessNotification(t("Your personal data file has been downloaded."))
    }
    exportDialogVisible.value = false
    resetExportForm()
    await fetchExportOptions()
  } catch (error) {
    showErrorNotification(error.message || t("The export could not be completed."))
  } finally {
    isExporting.value = false
    exportPassword.value = ""
  }
}

const showTermsAndConditionsCard = computed(() => {
  return termsAndConditions.value.available && termsAndConditions.value.items.length > 0
})

const showLegalAgreementCard = computed(() => {
  return showTermsAndConditionsCard.value
})

const canAcceptTerms = computed(() => {
  return showLegalAgreementCard.value && !legalStatus.isAccepted && Boolean(legalStatus.message)
})

function resetTermsAndConditions() {
  termsAndConditions.value = createEmptyTermsAndConditions()
  termsAndConditionsDialogVisible.value = false
}

function resetLegalStatus() {
  Object.assign(legalStatus, createEmptyLegalStatus())
}

const openTermsDialog = () => {
  if (!showTermsAndConditionsCard.value) {
    return
  }

  termsAndConditionsDialogVisible.value = true
}

const submitDeleteTerm = () => submitPrivacyRequest("delete_legal")
const submitDeleteAccount = () => submitPrivacyRequest("delete_account")

function toggleCategory(categoryName) {
  const index = expandedCategories.value.indexOf(categoryName)

  if (index === -1) {
    expandedCategories.value.push(categoryName)
  } else {
    expandedCategories.value.splice(index, 1)
  }
}

async function fetchPersonalData() {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (fetchPersonalData).")
    return
  }

  try {
    personalData.data = await socialService.fetchPersonalData(userId)
  } catch (error) {
    console.error("Error fetching personal data:", error)
    showErrorNotification("Error fetching personal data.")
  }
}

async function fetchTermsAndConditions() {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (fetchTermsAndConditions).")
    return
  }

  try {
    const res = await socialService.fetchTermsAndConditions(userId)

    if (!res?.items?.length) {
      resetTermsAndConditions()
      return
    }

    termsAndConditions.value = {
      ...createEmptyTermsAndConditions(),
      ...res,
      items: res?.items ?? [],
      content: res?.items ?? [],
      date_text: res?.date_text ?? "",
      available: true,
    }
  } catch (error) {
    console.error("Error fetching terms and conditions:", error)
    resetTermsAndConditions()
  }
}

async function submitPrivacyRequest(requestType) {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (submitPrivacyRequest).")
    showErrorNotification("User ID is not available.")
    return
  }

  const explanation = requestType === "delete_account" ? deleteAccountExplanation.value : deleteTermExplanation.value

  if (explanation.trim() === "") {
    showErrorNotification("Explanation is required.")
    return
  }

  if (requestType === "delete_legal" && (legalWithdrawalRequestPending.value || isSubmittingDeleteLegalRequest.value)) {
    return
  }

  if (
    requestType === "delete_account" &&
    (accountDeletionRequestPending.value || isSubmittingDeleteAccountRequest.value)
  ) {
    return
  }

  if (requestType === "delete_legal") {
    isSubmittingDeleteLegalRequest.value = true
  }

  if (requestType === "delete_account") {
    isSubmittingDeleteAccountRequest.value = true
  }

  try {
    const response = await socialService.submitPrivacyRequest({
      userId,
      explanation,
      requestType,
    })

    if (response.success) {
      showSuccessNotification(response.message)

      if ("delete_legal" === requestType) {
        legalWithdrawalRequestPending.value = true
        deleteTermExplanation.value = ""
      }

      if ("delete_account" === requestType) {
        accountDeletionRequestPending.value = true
        deleteAccountExplanation.value = ""
      }

      await updateUserData()
    } else {
      showErrorNotification(response.message)
    }
  } catch (error) {
    console.error("Error submitting privacy request:", error)
    showErrorNotification("Error submitting privacy request.")
  } finally {
    if (requestType === "delete_legal") {
      isSubmittingDeleteLegalRequest.value = false
    }

    if (requestType === "delete_account") {
      isSubmittingDeleteAccountRequest.value = false
    }
  }
}

async function submitAcceptTerm() {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (submitAcceptTerm).")
    showErrorNotification("User ID is not available.")
    return
  }

  if (!showTermsAndConditionsCard.value || isSubmittingAcceptTerms.value) {
    return
  }

  isSubmittingAcceptTerms.value = true

  try {
    const response = await socialService.submitAcceptTerm(userId)

    if (response.success) {
      showSuccessNotification(response.message)
      await updateUserData()
    } else {
      showErrorNotification(response.message)
    }
  } catch (error) {
    console.error("Error accepting the term:", error)
    showErrorNotification("Error accepting the term.")
  } finally {
    isSubmittingAcceptTerms.value = false
  }
}

async function fetchLegalStatus() {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (fetchLegalStatus).")
    return
  }

  try {
    const legalStatusData = await socialService.fetchLegalStatus(userId)

    Object.assign(legalStatus, {
      ...createEmptyLegalStatus(),
      ...legalStatusData,
      available: true,
    })
  } catch (error) {
    console.error("Error fetching legal status:", error)
    resetLegalStatus()
  }
}

async function updateUserData() {
  try {
    await Promise.all([fetchPersonalData(), fetchTermsAndConditions(), fetchLegalStatus(), fetchExportOptions()])
  } catch (error) {
    console.error("Error updating user data:", error)
    showErrorNotification("Error updating user data.")
  }
}

watch(
  () => securityStore.user?.id,
  async (id) => {
    if (!id) {
      return
    }

    await updateUserData()
  },
  { immediate: true },
)
</script>
