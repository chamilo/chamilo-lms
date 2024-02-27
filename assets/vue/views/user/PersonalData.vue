<template>
  <div class="flex flex-col gap-4">
    <BaseCard plain class="bg-white">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Personal Data: Introduction') }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4">
      <div>
        {{ t('We respect your personal data! This page...') }}
      </div>
    </BaseCard>

    <BaseCard plain class="bg-white">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Personal data we keep about you') }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4">
      <div>
        <div v-for="(categoryData, categoryName) in personalData.data" :key="categoryName">
          <button @click="toggleCategory(categoryName)" class="text-left w-full">
            <span class="underline">{{ categoryName }}</span>
          </button>
          <ul v-if="expandedCategories.includes(categoryName)">
            <li v-for="(dataSet, dataSetIndex) in categoryData" :key="`dataset-${dataSetIndex}`">
              <div v-for="(value, key) in dataSet" :key="`item-${dataSetIndex}-${key}`">
                <span><strong>{{ key }}:</strong> {{ value }}</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </BaseCard>

    <BaseCard plain class="bg-white">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Terms and Conditions') }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4">
      <div>
        <a href="#" @click="openTermsDialog">{{ t('Read the Terms and Conditions') }}</a>
      </div>
    </BaseCard>

    <BaseCard plain class="bg-white">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Permissions you gave us') }}</h2>
        </div>
      </template>
      <hr class="-mt-2 mb-4 -mx-4">
      <div>
        <div v-if="legalStatus.isAccepted">
          <p>{{ t('Legal agreement accepted') }}
            <BaseIcon
              icon="check"
              size="small"
              class="text-green-500"
            />
          </p>
          <p>{{ t('Date') }}: {{ legalStatus.acceptDate }}</p>

          <div v-if="termsAndConditions">
            <div class="p-4">
              <p class="text-sm text-gray-600">
                {{ t('Why you want to delete your Legal Agreement') }}
              </p>
            </div>
            <form class="flex flex-col gap-2 mt-6">
              <BaseTextArea
                v-model="deleteTermExplanation"
                :label="t('Please tell us why you want to withdraw the rights you previously gave us, to let us make it in the smoothest way possible')"
                required
              />
              <LayoutFormButtons>
                <BaseButton
                  :label="t('Delete legal agreement')"
                  type="danger"
                  icon="delete"
                  @click="submitDeleteTerm"
                />
              </LayoutFormButtons>
            </form>

            <form @submit.prevent="submitDeleteAccount" class="flex flex-col gap-2 mt-6">
              <BaseTextArea
                v-model="deleteAccountExplanation"
                :label="t('Explain in this box why you want your account deleted')"
                required
              />
              <LayoutFormButtons>
                <BaseButton
                  :label="t('Delete Account')"
                  type="danger"
                  icon="delete"
                  @click="submitDeleteAccount"
                />
              </LayoutFormButtons>
            </form>
          </div>

        </div>
        <div v-else>
          <p>{{ legalStatus.message }}</p>
        </div>
      </div>
    </BaseCard>
  </div>

  <BaseDialog
    v-if="termsAndConditions.length > 0"
    v-model:is-visible="termsAndConditionsDialogVisible"
    :style="{ width: '28rem' }"
    :title="t('Read the Terms and Conditions')"
  >
    <template v-for="(term, index) in termsAndConditions" :key="`term-${index}`">
      <h3>{{ term.title }}</h3>
      <div v-html="term.content"></div>
      <p>{{ term.date_text }}</p>
    </template>
  </BaseDialog>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, computed } from "vue"
import { useI18n } from 'vue-i18n';
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import { useStore } from "vuex"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const store = useStore()

const { showSuccessNotification, showErrorNotification } = useNotification()

const user = computed(() => store.getters["security/getUser"])

const personalData = reactive({
  data: {},
})

const termsAndConditions = ref({
  content: [],
  date_text: '',
})

const legalStatus = reactive({
  isAccepted: false,
  acceptDate: null,
  message: ''
})

const termsAndConditionsDialogVisible = ref(false)
const expandedCategories = ref([])
const deleteTermExplanation = ref('')
const deleteAccountExplanation = ref('')

const openTermsDialog = () => {
  termsAndConditionsDialogVisible.value = true
}

const submitPrivacyRequest = async (requestType) => {
  const explanation = requestType === 'delete_account' ? deleteAccountExplanation.value : deleteTermExplanation.value

  if (explanation.trim() === '') {
    showErrorNotification('Explanation is required')
    return
  }

  try {
    const response = await axios.post('/social-network/handle-privacy-request', {
      explanation,
      userId: user.value["@id"].split('/').pop(),
      requestType
    })

    if (response.data.success) {
      showSuccessNotification(response.data.message)
    } else {
      showErrorNotification(response.data.message)
    }
  } catch (error) {
    showErrorNotification('Error submitting privacy request:', error)
  }
}

const submitDeleteTerm = () => submitPrivacyRequest('delete_legal')
const submitDeleteAccount = () => submitPrivacyRequest('delete_account')

function toggleCategory(categoryName) {
  const index = expandedCategories.value.indexOf(categoryName)
  if (index === -1) {
    expandedCategories.value.push(categoryName)
  } else {
    expandedCategories.value.splice(index, 1)
  }
}

async function fetchPersonalData() {
  if (!user.value) {
    console.error("User ID is not available.")
    return
  }
  try {
    const userId = user.value["@id"].split('/').pop()
    const response = await axios.get(`/social-network/personal-data/${userId}`)
    personalData.data = response.data.personalData
  } catch (error) {
    console.error('Error fetching personal data:', error)
  }
}

async function fetchTermsAndConditions() {
  if (!user.value) {
    console.error("User ID is not available.")
    return
  }
  try {
    const userId = user.value["@id"].split('/').pop()
    const response = await axios.get(`/social-network/terms-and-conditions/${userId}`)
    termsAndConditions.value = response.data.terms
  } catch (error) {
    console.error('Error fetching terms and conditions:', error)
  }
}

async function fetchLegalStatus() {
  if (!user.value) {
    console.error("User ID is not available.")
    return
  }
  try {
    const userId = user.value["@id"].split('/').pop()
    const response = await axios.get(`/social-network/legal-status/${userId}`)
    Object.assign(legalStatus, response.data)
  } catch (error) {
    console.error('Error fetching legal status:', error)
  }
}

onMounted(async () => {
  await fetchPersonalData()
  await fetchTermsAndConditions()
  await fetchLegalStatus()
})
</script>
