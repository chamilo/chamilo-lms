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
            class="text-left w-full"
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

          <div v-if="termsAndConditions?.items?.length">
            <div class="p-4">
              <p class="text-sm text-gray-600">
                {{ t("Why you want to delete your Legal Agreement") }}
              </p>
            </div>
            <form class="flex flex-col gap-2 mt-6">
              <BaseTextArea
                v-model="deleteTermExplanation"
                :label="
                  t(
                    'Please tell us why you want to withdraw the rights you previously gave us, to let us make it in the smoothest way possible',
                  )
                "
                required
              />
              <LayoutFormButtons>
                <BaseButton
                  :label="t('Delete legal agreement')"
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
                required
              />
              <LayoutFormButtons>
                <BaseButton
                  :label="t('Delete account')"
                  icon="delete"
                  type="danger"
                  @click="submitDeleteAccount"
                />
              </LayoutFormButtons>
            </form>
          </div>
        </div>
        <div v-else>
          <BaseButton
            :label="t(legalStatus.message)"
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
  >
    <div class="overflow-y-auto max-h-[70vh] pr-2">
      <template
        v-for="(term, index) in termsAndConditions.items"
        :key="`term-${index}`"
      >
        <h3 class="text-lg font-semibold mb-2">{{ term.title }}</h3>

        <!-- Optional subtitle if backend returns it -->
        <p
          v-if="term.subtitle"
          class="text-sm text-gray-500 mb-2"
        >
          <em>{{ term.subtitle }}</em>
        </p>

        <div
          v-html="term.content"
          class="mb-4"
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
</template>

<script setup>
import { onMounted, reactive, ref, watch } from "vue"
import { useI18n } from "vue-i18n"

import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useNotification } from "../../composables/notification"
import socialService from "../../services/socialService"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const securityStore = useSecurityStore()

const { showSuccessNotification, showErrorNotification } = useNotification()

const personalData = reactive({
  data: {},
})

const termsAndConditions = ref({
  items: [],
  date_text: "",
  version: null,
  language_id: null,
  showing_accepted: false,

  content: [],
})

const legalStatus = reactive({
  isAccepted: false,
  acceptDate: null,
  message: "",
})

const termsAndConditionsDialogVisible = ref(false)
const expandedCategories = ref([])
const deleteTermExplanation = ref("")
const deleteAccountExplanation = ref("")

const openTermsDialog = () => {
  if (!termsAndConditions.value?.items?.length) {
    console.warn("No terms available to display in dialog.")
    showErrorNotification("No terms and conditions available.")
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

    // Normalize + keep backward compatibility
    termsAndConditions.value = {
      ...termsAndConditions.value,
      ...res,
      items: res?.items ?? [],
      content: res?.items ?? [],
      date_text: res?.date_text ?? "",
    }
  } catch (error) {
    console.error("Error fetching terms and conditions:", error)
    showErrorNotification("Error fetching terms and conditions.")

    termsAndConditions.value = {
      items: [],
      content: [],
      date_text: "",
      version: null,
      language_id: null,
      showing_accepted: false,
    }
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

  try {
    const response = await socialService.submitPrivacyRequest({
      userId,
      explanation,
      requestType,
    })

    if (response.success) {
      showSuccessNotification(response.message)
      deleteTermExplanation.value = ""
      deleteAccountExplanation.value = ""
      await updateUserData()
    } else {
      showErrorNotification(response.message)
    }
  } catch (error) {
    console.error("Error submitting privacy request:", error)
    showErrorNotification("Error submitting privacy request.")
  }
}

async function submitAcceptTerm() {
  const userId = securityStore.user?.id
  if (!userId) {
    console.warn("User ID is not available yet (submitAcceptTerm).")
    showErrorNotification("User ID is not available.")
    return
  }

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
    Object.assign(legalStatus, legalStatusData)
  } catch (error) {
    console.error("Error fetching legal status:", error)
    showErrorNotification("Error fetching legal status.")
  }
}

async function updateUserData() {
  try {
    await Promise.all([fetchPersonalData(), fetchTermsAndConditions(), fetchLegalStatus()])
  } catch (error) {
    console.error("Error updating user data:", error)
    showErrorNotification("Error updating user data.")
  }
}

watch(
  () => securityStore.user?.id,
  async (id) => {
    if (!id) return
    await updateUserData()
  },
  { immediate: true },
)

onMounted(async () => {
  if (!securityStore.user?.id) {
    console.warn("User is not available onMounted. Waiting for watcher to trigger.")
    return
  }
  await updateUserData()
})
</script>
