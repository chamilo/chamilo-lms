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

      <div
        v-if="shouldShowCourseCreationOptions"
        class="rounded-2xl border border-gray-25 bg-white p-4 shadow-sm"
      >
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Course type") }}
          </h2>
          <p class="mt-1 text-sm text-gray-50">
            {{ t("Choose whether this course uses the standard platform limits or benefits from a purchased service.") }}
          </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
          <button
            v-if="standardCourseOption"
            class="rounded-2xl border p-4 text-left transition"
            :class="getCourseOptionClasses(standardCourseOption)"
            type="button"
            :disabled="!standardCourseOption.available"
            @click="selectCourseOption('standard', null)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-base font-semibold text-gray-90">
                  {{ standardCourseOption.label || t("Standard course") }}
                </p>
                <p class="mt-1 text-sm text-gray-50">
                  {{ standardCourseOption.description || t("Use the standard platform limits.") }}
                </p>
              </div>
              <span
                v-if="selectedCourseOptionType === 'standard'"
                class="rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-white"
              >
                {{ t("Selected") }}
              </span>
            </div>

            <div class="mt-4 grid gap-2 text-sm text-gray-90">
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Courses") }}</span>
                <span class="font-semibold">{{ formatLimit(standardCourseOption.currentCourses, standardCourseOption.maxCourses) }}</span>
              </div>
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Hosting limit") }}</span>
                <span class="font-semibold">{{ formatUsersLimit(standardCourseOption.hostingLimit) }}</span>
              </div>
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Document quota") }}</span>
                <span class="font-semibold">{{ formatQuota(standardCourseOption.documentQuotaMb) }}</span>
              </div>
            </div>

            <p
              v-if="!standardCourseOption.available"
              class="mt-3 text-sm font-medium text-danger"
            >
              {{ t("The standard course limit has been reached.") }}
            </p>
          </button>

          <button
            v-for="serviceOption in serviceCourseOptions"
            :key="`service-${serviceOption.serviceId}`"
            class="rounded-2xl border p-4 text-left transition"
            :class="getCourseOptionClasses(serviceOption)"
            type="button"
            :disabled="!serviceOption.available"
            @click="selectCourseOption('service', serviceOption.serviceSaleId)"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-base font-semibold text-gray-90">
                  {{ serviceOption.label }}
                </p>
                <p class="mt-1 line-clamp-3 text-sm text-gray-50">
                  {{ serviceOption.description || t("Use the benefits granted by this service.") }}
                </p>
              </div>
              <span
                v-if="selectedCourseOptionType === 'service' && selectedBuyCoursesServiceSaleId === serviceOption.serviceSaleId"
                class="rounded-full bg-support-1 px-3 py-1 text-xs font-semibold text-white"
              >
                {{ t("Selected") }}
              </span>
            </div>

            <div class="mt-4 grid gap-2 text-sm text-gray-90">
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Courses with these benefits") }}</span>
                <span class="font-semibold">{{ formatLimit(serviceOption.usedCourses, serviceOption.maxCourses) }}</span>
              </div>
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Hosting limit") }}</span>
                <span class="font-semibold">{{ formatUsersLimit(serviceOption.hostingLimit) }}</span>
              </div>
              <div class="rounded-xl bg-gray-15 p-3">
                <span class="block text-xs font-semibold uppercase text-gray-50">{{ t("Document quota") }}</span>
                <span class="font-semibold">{{ formatQuota(serviceOption.documentQuotaMb) }}</span>
              </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
              <span
                v-if="serviceOption.serviceSaleId"
                class="rounded-full bg-success-light px-3 py-1 text-xs font-semibold text-success-dark"
              >
                {{ t("Active service") }}
              </span>
              <a
                v-else-if="serviceOption.buyUrl"
                class="font-semibold text-primary hover:underline"
                :href="serviceOption.buyUrl"
                @click.stop
              >
                {{ t("Buy service") }}
              </a>
              <a
                v-if="serviceOption.informationUrl"
                class="font-semibold text-primary hover:underline"
                :href="serviceOption.informationUrl"
                @click.stop
              >
                {{ t("More information") }}
              </a>
            </div>

            <p
              v-if="serviceOption.disabledReason"
              class="mt-3 text-sm font-medium text-danger"
            >
              {{ getDisabledReasonLabel(serviceOption.disabledReason) }}
            </p>
          </button>
        </div>

        <p
          v-if="shouldBlockSubmitByCourseOption"
          class="mt-4 rounded-xl bg-warning-light p-3 text-sm font-medium text-warning-dark"
        >
          {{ t("Select an available course type before creating the course.") }}
        </p>
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
        :disabled="shouldBlockSubmitByCourseOption"
        :is-submit="true"
      />
    </div>
  </form>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
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
  buyCoursesOptions: {
    type: [Object, null],
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
const selectedCourseOptionType = ref("standard")
const selectedBuyCoursesServiceSaleId = ref(null)

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

const shouldShowCourseCreationOptions = computed(() => {
  return !!props.buyCoursesOptions?.enabled && !!props.buyCoursesOptions?.hasServiceOptions
})

const standardCourseOption = computed(() => {
  return props.buyCoursesOptions?.standard || null
})

const serviceCourseOptions = computed(() => {
  return Array.isArray(props.buyCoursesOptions?.services) ? props.buyCoursesOptions.services : []
})

const selectedServiceOption = computed(() => {
  if (selectedCourseOptionType.value !== "service" || !selectedBuyCoursesServiceSaleId.value) {
    return null
  }

  return serviceCourseOptions.value.find((option) => option.serviceSaleId === selectedBuyCoursesServiceSaleId.value) || null
})

const selectedCourseOptionIsAvailable = computed(() => {
  if (!shouldShowCourseCreationOptions.value) {
    return true
  }

  if (selectedCourseOptionType.value === "standard") {
    return !!standardCourseOption.value?.available
  }

  return !!selectedServiceOption.value?.available
})

const shouldBlockSubmitByCourseOption = computed(() => {
  return shouldShowCourseCreationOptions.value && !selectedCourseOptionIsAvailable.value
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

function selectCourseOption(type, serviceSaleId = null) {
  selectedCourseOptionType.value = type
  selectedBuyCoursesServiceSaleId.value = type === "service" ? serviceSaleId : null
}

function getCourseOptionClasses(option) {
  const isSelected = option.type === "standard"
    ? selectedCourseOptionType.value === "standard"
    : selectedCourseOptionType.value === "service" && selectedBuyCoursesServiceSaleId.value === option.serviceSaleId

  if (!option.available) {
    return "cursor-not-allowed border-gray-25 bg-gray-15 opacity-60"
  }

  return isSelected
    ? "border-primary bg-primary/5 shadow-md"
    : "border-gray-25 bg-white hover:border-primary hover:shadow-md"
}

function formatLimit(current, max) {
  const currentValue = Number(current || 0)
  const maxValue = Number(max || 0)

  if (maxValue <= 0) {
    return `${currentValue} / ${t("Unlimited")}`
  }

  return `${currentValue} / ${maxValue}`
}

function formatUsersLimit(value) {
  const limit = Number(value || 0)

  if (limit <= 0) {
    return t("Unlimited")
  }

  return `${limit} ${t("users")}`
}

function formatQuota(value) {
  const quota = Number(value || 0)

  if (quota <= 0) {
    return t("Unlimited")
  }

  return `${quota} MB`
}

function getDisabledReasonLabel(reason) {
  if (reason === "service_not_purchased") {
    return t("You need to buy this service before using these benefits.")
  }

  if (reason === "service_course_limit_reached") {
    return t("You already used all courses available with this service.")
  }

  if (reason === "platform_limit_reached") {
    return t("The standard course limit has been reached.")
  }

  return t("This option is not available.")
}

function selectDefaultCourseOption() {
  if (!shouldShowCourseCreationOptions.value) {
    selectCourseOption("standard", null)
    return
  }

  if (standardCourseOption.value?.available) {
    selectCourseOption("standard", null)
    return
  }

  const firstAvailableService = serviceCourseOptions.value.find((option) => option.available)
  if (firstAvailableService) {
    selectCourseOption("service", firstAvailableService.serviceSaleId)
    return
  }

  selectCourseOption("standard", null)
}

watch(
  () => props.buyCoursesOptions,
  () => {
    selectDefaultCourseOption()
  },
  { immediate: true }
)

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

  if (shouldBlockSubmitByCourseOption.value) {
    return
  }

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
    buyCoursesServiceSaleId: selectedCourseOptionType.value === "service" ? selectedBuyCoursesServiceSaleId.value : null,
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
