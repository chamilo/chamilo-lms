<template>
  <div class="terms-edit-view mb-8">
    <Message
      :closable="false"
      class="mt-5"
      icon="pi pi-send"
      severity="info"
    >
      {{
        t(
          "Display a Terms & Conditions statement on the registration page, require visitor to accept the T&C to register",
        )
      }}
    </Message>
    <Message
      :closable="false"
      class="mt-2"
      icon="pi pi-exclamation-triangle"
      severity="warn"
    >
      {{
        t(
          "Please remember that modifications to the terms related to GDPR require you to report those modifications to third parties to whom you have provided personal information about your users, as stated in Article 19 of GDPR.",
        )
      }}
    </Message>

    <BaseToolbar showTopBorder>
      <div class="flex justify-between w-full items-center">
        <BaseSelect
          v-model="selectedLanguage"
          :options="languages"
          class="w-96 mb-0"
          id="language-dropdown"
          label="Language"
          name="language"
          option-value="id"
          option-label="name"
          placeholder="Select a language"
        />
        <BaseButton
          :label="t('Load')"
          class="ml-4"
          icon="search"
          type="button"
          @click="loadTermsByLanguage"
        />
        <BaseButton
          :label="t('All versions')"
          class="ml-4"
          icon="back"
          type="secondary"
          @click="backToList"
        />
      </div>
    </BaseToolbar>

    <div v-if="termsLoaded">
      <form @submit.prevent="saveTerms">
        <BaseTinyEditor
          v-model="termData.content"
          :help-text="t('Why do we collect this data?')"
          :title="t('Personal data collection')"
          editor-id="item_content"
        />

        <BaseRadioButtons
          v-model="termData.type"
          :options="typeOptions"
          :title="t('Type of terms')"
          name="termsType"
        />

        <Dialog
          v-model:visible="dialogVisible"
          :header="t('Preview')"
          :modal="true"
          :style="{ width: '50vw' }"
        >
          <div v-html="previewContent" />
        </Dialog>

        <!-- Extra fields -->
        <div
          v-for="field in extraFields"
          :key="field.id"
          class="extra-field"
        >
          <component
            :is="getFieldComponent(field.type)"
            :help-text="field.type === 'editor' ? field.props.helpText : ''"
            v-bind="field.props"
            @update:model-value="field.props.modelValue = $event"
          >
          </component>
        </div>

        <BaseTextArea
          id="changes"
          v-model="termData.changes"
          label="Explain changes"
        />

        <div class="form-actions">
          <BaseButton
            class="mr-4"
            icon="back"
            label="Back"
            type="secondary"
            @click="backToList"
          />
          <BaseButton
            class="mr-4"
            icon="search"
            label="Preview"
            type="primary"
            @click="previewTerms"
          />
          <BaseButton
            class="mr-4"
            icon="save"
            isSubmit
            label="Save"
            type="success"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useRouter } from "vue-router"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import Message from "primevue/message"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import languageService from "../../services/languageService"
import legalService from "../../services/legalService"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"

const { t } = useI18n()

const router = useRouter()
const languages = ref([])
const selectedLanguage = ref(null)
const termsLoaded = ref(false)
const termData = ref({
  language: "",
  content: "",
  type: "0",
  changes: "",
})
const dialogVisible = ref(false)
const previewContent = ref("")
const typeOptions = ref([
  { label: "HTML", value: "0" },
  { label: "Page Link", value: "1" },
])
const loadTermsByLanguage = async () => {
  if (!selectedLanguage.value) return
  termsLoaded.value = false
  try {
    const response = await legalService.findAllByLanguage(selectedLanguage.value)
    if (response.ok) {
      const data = await response.json()
      const latestTerm = data["hydra:member"].length ? data["hydra:member"][0] : null
      termData.value = latestTerm
        ? {
            id: latestTerm.id,
            content: latestTerm.content,
            type: latestTerm.type.toString(),
            changes: latestTerm.changes,
          }
        : {
            content: "",
            type: "0",
            changes: "",
          }
      extraFields.value = await legalService.fetchExtraFields(latestTerm ? latestTerm.id : null)
    }
  } catch (error) {
    console.error("Error loading terms:", error)
  } finally {
    termsLoaded.value = true
  }
}
const saveTerms = async () => {
  const payload = {
    lang: selectedLanguage.value,
    content: termData.value.content,
    type: termData.value.type.toString(),
    changes: termData.value.changes,
    extraFields: {},
  }
  extraFields.value.forEach((field) => {
    payload.extraFields[field.id] = field.props.modelValue
  })
  try {
    const response = await legalService.saveOrUpdateLegal(payload)
    if (response.ok) {
      await router.push({ name: "TermsConditionsList" })
    } else {
      console.error("Error saving or updating legal terms:", response.statusText)
    }
  } catch (error) {
    console.error("Error when making request:", error)
  }
}
const previewTerms = () => {
  previewContent.value = termData.value.content
  dialogVisible.value = true
}
function backToList() {
  router.push({ name: "TermsConditionsList" })
}

const extraFields = ref([])

function getFieldComponent(type) {
  const componentMap = {
    text: BaseInputText,
    select: BaseSelect,
    editor: BaseTinyEditor,
    // Add more mappings as needed
  }
  return componentMap[type] || "div"
}

watch(selectedLanguage, () => {
  termsLoaded.value = false
})
onMounted(async () => {
  try {
    const response = await languageService.findAll()
    if (!response.ok) {
      throw new Error("Network response was not ok")
    }
    const data = await response.json()
    languages.value = data["hydra:member"].map((lang) => ({
      name: lang.englishName,
      id: lang.id,
    }))
  } catch (error) {
    console.error("Error loading languages:", error)
  }
})
</script>
