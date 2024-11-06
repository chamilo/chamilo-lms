<template>
  <div class="course-form-container">
    <div class="form-header">
      <BaseInputText
        id="course-name"
        :label="t('Course name')"
        :help-text="t('Write a short and striking course name, For example: Innovation Management')"
        v-model="courseName"
        :error-text="courseNameError"
        :is-invalid="isCourseNameInvalid"
        required
      />
      <BaseAdvancedSettingsButton v-model="showAdvancedSettings"></BaseAdvancedSettingsButton>
    </div>
    <div
      v-if="showAdvancedSettings"
      class="advanced-settings"
    >
      <BaseMultiSelect
        id="category-multiselect"
        v-model="courseCategory"
        :options="categoryOptions"
        :label="t('Category')"
        input-id="multiselect-category"
      />
      <BaseInputText
        id="course-code"
        :label="t('Course code')"
        :help-text="t('Only letters (a-z) and numbers (0-9)')"
        v-model="courseCode"
        :maxlength="40"
        :error-text="courseCodeError"
        :is-invalid="isCodeInvalid"
        validation-message="Only letters (a-z) and numbers (0-9) are allowed."
      />
      <BaseDropdown
        name="language"
        v-model="courseLanguage"
        :options="languageOptions"
        :placeholder="t('Select Language')"
        input-id="language-dropdown"
        :label="t('Language')"
        option-label="name"
      />
      <BaseCheckbox
        id="demo-content"
        :label="t('Fill with demo content')"
        v-model="fillDemoContent"
        name=""
      />
      <!--BaseAutocomplete
        id="template"
        v-model="courseTemplate"
        :label="t('Select Template')"
        :search="searchTemplates"
      /-->
    </div>
    <!-- Form Footer -->
    <div class="form-footer">
      <BaseButton
        label="Back"
        icon="back"
        type="secondary"
        @click="goBack"
        class="mr-4"
      />
      <BaseButton
        :label="t('Create this course')"
        icon="plus"
        type="primary"
        @click="submitForm"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseDropdown from "../basecomponents/BaseDropdown.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useRouter } from "vue-router"
import courseService from "../../services/courseService"
import languageService from "../../services/languageService"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
const courseName = ref("")
const courseCategory = ref([])
const courseCode = ref("")
const courseLanguage = ref(null)
const fillDemoContent = ref(false)
const courseTemplate = ref(null)
const showAdvancedSettings = ref(false)
const router = useRouter()

const categoryOptions = ref([])
const languageOptions = ref([])

const courseNameError = ref("")
const courseCodeError = ref("")
const isCodeInvalid = ref(false)
const isCourseNameInvalid = ref(false)

const formSubmitted = ref(false)

const emit = defineEmits(["submit"])

const validateCourseCode = () => {
  const pattern = /^[a-zA-Z0-9]*$/
  if (!pattern.test(courseCode.value)) {
    isCodeInvalid.value = true
    courseCodeError.value = "Only letters (a-z) and numbers (0-9) are allowed."
    return false
  }
  courseCodeError.value = ""
  return true
}

const submitForm = () => {
  formSubmitted.value = true
  if (!courseName.value) {
    isCourseNameInvalid.value = true
    courseNameError.value = "This field is required"
    return
  }

  if (!validateCourseCode()) {
    return
  }

  emit("submit", {
    name: courseName.value,
    category: courseCategory.value ? courseCategory.value : null,
    code: courseCode.value,
    language: courseLanguage.value,
    template: courseTemplate.value ? courseTemplate.value.value : null,
    fillDemoContent: fillDemoContent.value,
  })
}

onMounted(async () => {
  try {
    const categoriesResponse = await courseService.getCategories("categories")
    categoryOptions.value = categoriesResponse.map((category) => ({
      name: category.name,
      id: category.id,
    }))

    const languagesResponse = await languageService.findAll()
    const data = await languagesResponse.json()
    languageOptions.value = data["hydra:member"].map((language) => ({
      name: language.englishName,
      id: language.isocode,
    }))
  } catch (error) {
    console.error("Failed to load dropdown data", error)
  }
})

const searchTemplates = async (query) => {
  if (query && query.length >= 3) {
    return courseService.searchTemplates(query)
  } else {
    return []
  }
}

const goBack = () => {
  router.go(-1)
}
</script>
