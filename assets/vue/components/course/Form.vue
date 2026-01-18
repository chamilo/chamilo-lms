<template>
  <form
    class="course-form-container"
    @submit.prevent="submitForm"
  >
    <div class="form-header">
      <BaseInputText
        id="course-name"
        v-model="courseName"
        :error-text="courseNameError"
        :help-text="t('Write a short and striking course name, for example: Innovation Management')"
        :is-invalid="isCourseNameInvalid"
        :label="t('Course name')"
        required
      />

      <BaseAdvancedSettingsButton v-model="showAdvancedSettings" />
    </div>
    <div
      v-if="showAdvancedSettings"
      class="advanced-settings"
    >
      <BaseMultiSelect
        id="category-multiselect"
        v-model="courseCategory"
        :label="t('Category')"
        :options="categoryOptions"
        input-id="multiselect-category"
      />
      <BaseInputText
        id="course-code"
        v-model="courseCode"
        :error-text="courseCodeError"
        :help-text="t('Only letters (a-z) and numbers (0-9)')"
        :is-invalid="isCodeInvalid"
        :label="t('Course code')"
        :maxlength="40"
        validation-message="Only letters (a-z) and numbers (0-9) are allowed."
      />
      <BaseSelect
        id="language-dropdowns"
        v-model="courseLanguage"
        :label="t('Language')"
        :options="languageOptions"
        name="language"
        option-label="name"
        option-value="id"
      />
    </div>
    <!-- Form Footer -->
    <div class="form-footer">
      <BaseButton
        :label="t('Back')"
        class="mr-4"
        icon="back"
        type="secondary"
        @click="goBack"
      />
      <BaseButton
        :label="t('Create this course')"
        icon="plus"
        type="primary"
        :is-submit="true"
      />
    </div>
  </form>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useRouter } from "vue-router"
import courseService from "../../services/courseService"
import languageService from "../../services/languageService"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import { useI18n } from "vue-i18n"

const props = defineProps({
  values: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: [Array, Object, null],
    default: null,
  },
})

const emit = defineEmits(["submit"])

const { t, locale } = useI18n()

const courseName = ref("")
const courseCategory = ref([])
const courseCode = ref("")
const courseLanguage = ref(null)
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

function normalizeLocale(value) {
  return String(value || "")
    .trim()
    .replace("-", "_")
    .toLowerCase()
}

function resolveDefaultLanguageId(options, desiredLocale) {
  // Options are { name, id } where id is language.isocode (e.g. "en", "fr", "es")
  const desired = normalizeLocale(desiredLocale)
  const base = desired.split("_")[0] // e.g. "en_US" -> "en"

  const byExact = options.find((opt) => normalizeLocale(opt.id) === desired)
  if (byExact) return byExact.id

  const byBase = options.find((opt) => normalizeLocale(opt.id) === base)
  if (byBase) return byBase.id

  return null
}

function applyDefaultLanguageIfEmpty() {
  if (courseLanguage.value) return
  if (!languageOptions.value || languageOptions.value.length === 0) return

  const desired = props.values?.language || locale.value
  const resolvedId = resolveDefaultLanguageId(languageOptions.value, desired)

  if (resolvedId) {
    courseLanguage.value = resolvedId
  }
}

const validateCourseCode = () => {
  const pattern = /^[a-zA-Z0-9]*$/
  if (!pattern.test(courseCode.value)) {
    isCodeInvalid.value = true
    courseCodeError.value = "Only letters (a-z) and numbers (0-9) are allowed."
    return false
  }

  isCodeInvalid.value = false
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

  isCourseNameInvalid.value = false
  courseNameError.value = ""

  if (!validateCourseCode()) {
    return
  }

  emit("submit", {
    name: courseName.value,
    category: courseCategory.value ? courseCategory.value : null,
    code: courseCode.value,
    language: courseLanguage.value,
    template: courseTemplate.value ? courseTemplate.value.value : null,
    fillDemoContent: false,
  })
}

const focusCourseNameField = async () => {
  // Focus the first meaningful field as soon as the component is mounted.
  // BaseInputText may render different DOM structures, so we try multiple selectors.
  await nextTick()

  const candidates = [
    "#course-name", // if id is applied to an <input>
    "#course-name input", // if id is on a wrapper and the input is inside
    'input[id="course-name"]',
    'input[name="course-name"]',
  ]

  for (const selector of candidates) {
    const el = document.querySelector(selector)
    if (el && typeof el.focus === "function") {
      el.focus()
      return
    }
  }
}

onMounted(async () => {
  await focusCourseNameField()

  try {
    const categoriesResponse = await courseService.getCategories("categories")
    categoryOptions.value = categoriesResponse.map((category) => ({
      name: category.name,
      id: category.id,
    }))

    const languagesResponse = await languageService.findAll()
    const data = await languagesResponse.json()
    languageOptions.value = data["hydra:member"].map((language) => ({
      name: language.originalName,
      id: language.isocode,
    }))
    // Apply default language after options are loaded
    applyDefaultLanguageIfEmpty()
  } catch (error) {
    // Keep messages in English
    console.error("Failed to load dropdown data", error)
  }
})
const goBack = () => {
  router.go(-1)
}
</script>
