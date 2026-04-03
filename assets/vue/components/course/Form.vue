<template>
  <form
    class="flex flex-col gap-5"
    @submit.prevent="submitForm"
  >
    <div class="flex flex-col gap-4">
      <div class="mb-1">
        <BaseInputText
          id="course-name"
          v-model="courseName"
          :error-text="courseNameError"
          :help-text="t('Write a short and striking course name, for example: Innovation Management')"
          :is-invalid="isCourseNameInvalid"
          :label="t('Course name')"
          required
        />
      </div>

      <div class="mt-1">
        <BaseAdvancedSettingsButton v-model="showAdvancedSettings" />
      </div>
    </div>

    <div
      v-if="showAdvancedSettings"
      class="flex flex-col gap-4"
    >
      <div class="flex flex-col gap-1">
        <CourseCategorySelect
          v-model="courseCategory"
          action="course-creation"
          option-value="id"
        />
        <small
          v-if="isCourseCategoryInvalid"
          class="p-error block mt-1 pl-0.5 text-sm leading-5"
        >
          {{ courseCategoryError }}
        </small>
      </div>

      <div
        v-if="!hideCourseCode"
        class="flex flex-col gap-1"
      >
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
      </div>

      <div class="flex flex-col gap-1">
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

      <div
        v-if="roomOptions.length > 0"
        class="flex flex-col gap-1"
      >
        <BaseSelect
          id="room-select"
          v-model="courseRoom"
          :label="t('Default room')"
          :options="roomOptions"
          name="room"
          option-label="name"
          option-value="id"
        />
      </div>
    </div>

    <div class="mt-3 flex flex-col-reverse items-stretch gap-3 md:flex-row md:justify-end md:items-center">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        @click="goBack"
      />
      <BaseButton
        :label="t('Create this course')"
        icon="plus"
        type="success"
        :is-submit="true"
      />
    </div>
  </form>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import languageService from "../../services/languageService"
import roomService from "../../services/roomService"
import baseService from "../../services/baseService"
import CourseCategorySelect from "../coursecategory/CourseCategorySelect.vue"
import { usePlatformConfig } from "../../store/platformConfig"

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
const router = useRouter()
const platformConfigStore = usePlatformConfig()

const courseName = ref("")
const courseCategory = ref([])
const courseCode = ref("")
const courseLanguage = ref(null)
const courseRoom = ref(null)
const roomOptions = ref([])
const courseTemplate = ref(null)
const showAdvancedSettings = ref(false)

const languageOptions = ref([])

const courseNameError = ref("")
const courseCategoryError = ref("")
const courseCodeError = ref("")
const isCodeInvalid = ref(false)
const isCourseNameInvalid = ref(false)
const isCourseCategoryInvalid = ref(false)

const formSubmitted = ref(false)

const hideCourseCode = computed(() => {
  return platformConfigStore.getSetting("course.course_creation_form_hide_course_code") === "true"
})

const isCourseCategoryMandatory = computed(() => {
  return platformConfigStore.getSetting("course.course_creation_form_set_course_category_mandatory") === "true"
})

const hasSelectedCourseCategory = computed(() => {
  if (Array.isArray(courseCategory.value)) {
    return courseCategory.value.length > 0
  }

  return !!courseCategory.value
})

function normalizeLocale(value) {
  return String(value || "")
    .trim()
    .replace("-", "_")
    .toLowerCase()
}

function resolveDefaultLanguageId(options, desiredLocale) {
  const desired = normalizeLocale(desiredLocale)
  const base = desired.split("_")[0]

  const byExact = options.find((opt) => normalizeLocale(opt.id) === desired)
  if (byExact) {
    return byExact.id
  }

  const byBase = options.find((opt) => normalizeLocale(opt.id) === base)
  if (byBase) {
    return byBase.id
  }

  return null
}

function applyDefaultLanguageIfEmpty() {
  if (courseLanguage.value) {
    return
  }

  if (!languageOptions.value || languageOptions.value.length === 0) {
    return
  }

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

const validateCourseCategory = () => {
  if (!isCourseCategoryMandatory.value) {
    isCourseCategoryInvalid.value = false
    courseCategoryError.value = ""
    return true
  }

  if (!hasSelectedCourseCategory.value) {
    isCourseCategoryInvalid.value = true
    courseCategoryError.value = t("This field is required")
    showAdvancedSettings.value = true
    return false
  }

  isCourseCategoryInvalid.value = false
  courseCategoryError.value = ""
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

  if (!validateCourseCategory()) {
    return
  }

  if (!hideCourseCode.value && !validateCourseCode()) {
    return
  }

  if (hideCourseCode.value) {
    isCodeInvalid.value = false
    courseCodeError.value = ""
    courseCode.value = ""
  }

  emit("submit", {
    name: courseName.value,
    category: hasSelectedCourseCategory.value ? courseCategory.value : null,
    code: hideCourseCode.value ? "" : courseCode.value,
    language: courseLanguage.value,
    template: courseTemplate.value ? courseTemplate.value.value : null,
    roomId: courseRoom.value || null,
    fillDemoContent: false,
  })
}

const focusCourseNameField = async () => {
  await nextTick()

  const candidates = ["#course-name", "#course-name input", 'input[id="course-name"]', 'input[name="course-name"]']

  for (const selector of candidates) {
    const el = document.querySelector(selector)
    if (el && typeof el.focus === "function") {
      el.focus()
      return
    }
  }
}

onMounted(async () => {
  if (isCourseCategoryMandatory.value) {
    showAdvancedSettings.value = true
  }

  await focusCourseNameField()

  try {
    const languagesResponse = await languageService.findAll()
    const data = await languagesResponse.json()
    languageOptions.value = data["hydra:member"].map((language) => ({
      name: language.originalName,
      id: language.isocode,
    }))

    applyDefaultLanguageIfEmpty()
  } catch (error) {
    console.error("Failed to load dropdown data", error)
  }

  try {
    const hasRooms = await roomService.exists()
    if (hasRooms) {
      const { items } = await baseService.getCollection("/api/rooms")
      roomOptions.value = items.map((r) => {
        const branch = r.branch
        const branchTitle = branch && typeof branch === "object" ? branch.title : null
        const label = branchTitle ? `${branchTitle} - ${r.title}` : r.title
        return { name: label, id: r["@id"] }
      })
    }
  } catch (error) {
    console.error("Failed to load rooms", error)
  }
})
const goBack = () => {
  router.go(-1)
}
</script>
