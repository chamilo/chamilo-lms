<template>
  <div class="terms-list-view mt-4">
    <BaseToolbar showTopBorder>
        <BaseButton
          :label="t('Edit Terms and Conditions')"
          icon="edit"
          type="primary"
          @click="editTerms"
        />
    </BaseToolbar>

    <Message severity="warn" :closable="false">
      {{ t('You should create the Term and Conditions for all the available languages.') }}
    </Message>

    <DataTable :value="terms" :loading="isLoading">
      <Column field="version" header="Version"></Column>
      <Column field="language" header="Language"></Column>

      <Column header="Content">
        <template #body="slotProps">
          <div v-html="slotProps.data.content"></div>
        </template>
      </Column>

      <Column field="changes" header="Changes"></Column>
      <Column field="typeLabel" header="Type"></Column>
      <Column field="date"  header="Date">
        <template #body="slotProps">
          {{ formatDate(slotProps.data.date) }}
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import Message from "primevue/message"
import languageService from "../../services/languageService"
import legalService from "../../services/legalService"
import Dropdown from "primevue/dropdown"
import Button from "primevue/button"

const { t } = useI18n()
const router = useRouter()
const terms = ref([])
const isLoading = ref(false)
async function fetchLanguageName(languageId) {
  try {
    const response = await languageService.find("/api/languages/" + languageId)
    if (response.ok) {
      const languageData = await response.json()
      return languageData.originalName
    }
  } catch (error) {
    console.error("Error loading language details:", error)
  }
  return null
}

onMounted(async () => {
  isLoading.value = true
  try {
    const response = await legalService.findAll()
    if (response.ok) {
      const data = await response.json()
      terms.value = await Promise.all(data['hydra:member'].map(async (term) => {
        const languageName = await fetchLanguageName(term.languageId)
        return {
          ...term,
          language: languageName,
          typeLabel: getTypeLabel(term.type),
        }
      }))
    } else {
      console.error("The request to the API was not successful:", response.statusText)
    }
  } catch (error) {
    console.error("Error loading legal terms:", error)
  } finally {
    isLoading.value = false
  }
})
function getTypeLabel(typeValue) {
  const typeMap = {
    '0': t('HTML'),
    '1': t('Page Link'),
  }
  return typeMap[typeValue] || 'Unknown'
}

function formatDate(timestamp) {
  const date = new Date(timestamp * 1000)
  const day = date.getDate().toString().padStart(2, '0')
  const month = (date.getMonth() + 1).toString().padStart(2, '0')
  const year = date.getFullYear()
  const hours = date.getHours().toString().padStart(2, '0')
  const minutes = date.getMinutes().toString().padStart(2, '0')
  const seconds = date.getSeconds().toString().padStart(2, '0')
  return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`
}

function editTerms() {
  router.push({ name: 'TermsConditionsEdit' })
}
</script>
