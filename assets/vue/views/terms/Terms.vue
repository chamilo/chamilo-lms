<template>
  <div class="terms">
    <div v-if="!isLoading">
      <div v-for="(item, index) in term" :key="index">
        <h3>{{ item.title }}</h3>
        <div v-html="item.content"></div>
      </div>
      <p class="small mt-4 mb-4">{{ term.date_text }}</p>
      <div v-if="!accepted && !blockButton">
        <BaseButton
          :label="$t('Accept Terms and Conditions')"
          type="primary"
          icon="pi pi-check"
          @click="acceptTerms"
        />
      </div>
      <div v-else-if="accepted">
        <p>{{ t('You accepted these terms on') }} {{ acceptanceDate }}</p>
        <BaseButton
          :label="$t('Revoke Acceptance')"
          type="danger"
          icon="pi pi-times"
          @click="revokeAcceptance"
        />
      </div>
      <div v-if="blockButton" class="alert alert-warning" v-html="infoMessage"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useSecurityStore } from "../../store/securityStore"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import socialService from '../../services/socialService'

const { t } = useI18n()

const term = ref({})
const accepted = ref(false)
const acceptanceDate = ref(null)
const blockButton = ref(false)
const infoMessage = ref('')
const isLoading = ref(true)
const securityStore = useSecurityStore()

const fetchTerms = async () => {
  try {
    const userId = securityStore.user.id
    term.value = await socialService.fetchTermsAndConditions(userId)
  } catch (error) {
    console.error('Error fetching terms:', error)
  }
}

const checkAcceptance = async () => {
  try {
    const userId = securityStore.user.id
    const response = await socialService.fetchLegalStatus(userId)
    accepted.value = response.isAccepted
    acceptanceDate.value = response.acceptDate
  } catch (error) {
    console.error('Error checking acceptance:', error)
  }
}

const checkRestrictions = async () => {
  try {
    const userId = securityStore.user.id
    const response = await socialService.checkTermsRestrictions(userId)
    blockButton.value = response.blockButton
    infoMessage.value = response.infoMessage
  } catch (error) {
    console.error('Error checking restrictions:', error)
  }
}

const acceptTerms = async () => {
  try {
    const userId = securityStore.user.id
    await socialService.submitAcceptTerm(userId)
    accepted.value = true
    acceptanceDate.value = new Date().toLocaleDateString()
  } catch (error) {
    console.error('Error accepting terms:', error)
  }
}

const revokeAcceptance = async () => {
  try {
    const userId = securityStore.user.id
    await socialService.revokeAcceptTerm(userId)
    accepted.value = false
    acceptanceDate.value = null
  } catch (error) {
    console.error('Error revoking acceptance:', error)
  }
}

onMounted(async () => {
  await fetchTerms()
  await checkAcceptance()
  await checkRestrictions()
  isLoading.value = false
})
</script>
