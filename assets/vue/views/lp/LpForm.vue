<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader :title="isEdit ? t('Settings') : t('Create new learning path')" />

    <div
      v-if="loading"
      class="p-6 text-center"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <form
      v-else
      class="flex w-full flex-col gap-6"
      @submit.prevent="save"
    >
      <div
        v-if="!isEdit"
        class="rounded-lg border border-info/30 bg-info/10 p-4 text-sm text-info-dark"
        v-html="t(createIntroductionKey)"
      />

      <h2
        v-if="!isEdit"
        class="text-xl font-semibold text-gray-90"
      >
        {{ t("To start, give a title to your course") }}
      </h2>

      <div class="flex w-full flex-col gap-4">
        <BaseTinyEditor
          v-if="form.titleAsHtml"
          editor-id="lp-title-editor"
          v-model="form.title"
          :editor-config="titleEditorConfig"
          :title="t('Learning path name')"
          required
        />
        <BaseInputText
          v-else
          id="lp-title"
          v-model="form.title"
          name="title"
          :label="isEdit ? t('Title') : t('Learning path name')"
          required
        />

        <BaseSelect
          v-if="isEdit"
          id="lp-category"
          v-model="form.categoryId"
          name="categoryId"
          :label="t('Category')"
          :options="form.categoryOptions"
          option-label="label"
          option-value="value"
          allow-clear
        />
      </div>

      <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
        <div class="flex w-full flex-col gap-5">
          <BaseSelect
            v-if="!isEdit"
            id="lp-category-create"
            v-model="form.categoryId"
            name="categoryId"
            :label="t('Category')"
            :options="form.categoryOptions"
            option-label="label"
            option-value="value"
            allow-clear
          />

          <BaseSelect
            v-if="form.showLanguage"
            id="lp-language"
            v-model="form.language"
            name="language"
            :label="t('Language')"
            :options="form.languageOptions"
            option-label="label"
            option-value="value"
          />

          <div
            v-if="!isEdit"
            class="flex flex-col gap-1"
          >
            <BaseCheckbox
              id="lp-accumulate-scorm-time-create"
              v-model="form.accumulateScormTime"
              name="accumulateScormTime"
              :label="t('Accumulate SCORM session time')"
            />
            <small class="text-gray-50">
              {{
                t(
                  "When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.",
                )
              }}
            </small>
          </div>

          <template v-if="isEdit">
            <BaseCheckbox
              id="lp-hide-toc"
              v-model="form.hideTocFrame"
              name="hideTocFrame"
              :label="t('Hide table of contents frame')"
            />

            <BaseSelect
              id="lp-default-view-mode"
              v-model="form.defaultViewMode"
              name="defaultViewMode"
              :label="t('Default view type')"
              :options="viewModeOptions"
              option-label="label"
              option-value="value"
            />

            <BaseSelect
              v-if="form.showTheme"
              id="lp-theme"
              v-model="form.theme"
              name="theme"
              :label="t('Graphical theme')"
              :options="form.themeOptions"
              option-label="label"
              option-value="value"
              allow-clear
            />

            <BaseTinyEditor
              editor-id="lp-author"
              v-model="form.author"
              :editor-config="authorEditorConfig"
              :title="t('Author')"
            />

            <div class="flex flex-col gap-3">
              <div
                v-if="form.imageUrl"
                class="flex items-center gap-4"
              >
                <img
                  :src="form.imageUrl"
                  alt=""
                  class="h-20 w-20 rounded-xl border border-gray-25 object-cover"
                />
                <BaseCheckbox
                  id="lp-remove-picture"
                  v-model="form.removePicture"
                  name="removePicture"
                  :label="t('Remove picture')"
                />
              </div>
              <div class="flex flex-col gap-2">
                <span class="text-sm font-medium text-gray-90">
                  {{ form.imageUrl ? t("Update Image") : t("Add image") }}
                </span>
                <BaseFileUpload
                  accept="image/png,image/jpeg,image/gif"
                  :label="t('Browse')"
                  @file-selected="imageFile = $event"
                />
                <small class="text-gray-50">{{ t("Trainer picture will resize if needed") }}</small>
              </div>
            </div>

            <BaseCheckbox
              v-if="form.showSearchIndex"
              id="lp-search-index"
              v-model="form.searchIndexEnabled"
              name="searchIndexEnabled"
              :label="t('Include this learning path in the global search results')"
            />

            <BaseSelect
              id="lp-prerequisite"
              v-model="form.prerequisiteId"
              name="prerequisiteId"
              :label="t('Prerequisites')"
              :options="form.prerequisiteOptions"
              option-label="label"
              option-value="value"
            />
            <small class="text-gray-50">
              {{
                t(
                  "Selecting another learning path as a prerequisite will hide the current prerequisite until the one in prerequisite is fully completed (100%)",
                )
              }}
            </small>

            <BaseInputNumber
              v-if="form.showMinimumTime"
              id="lp-minimum-time"
              v-model="form.accumulateWorkTime"
              :help-text="
                t('Minimum time (in minutes) a student must remain in the learning path to get access to the next one.')
              "
              :label="t('Minimum time (minutes)')"
              :min="0"
            />

            <div
              v-if="form.showFlow"
              class="flex flex-col gap-2"
            >
              <BaseRadioButtons
                v-model="form.nextLpId"
                name="nextLpId"
                :options="form.nextLpOptions"
                :title="t('Next learning path')"
              />
              <small class="text-gray-50">
                {{
                  form.nextLpOptions.length > 1
                    ? t("Select the learning path that will be available after this one.")
                    : t("Create another learning path in this course to enable learning path flow.")
                }}
              </small>
            </div>
          </template>

          <BaseCheckbox
            id="lp-activate-start-date"
            v-model="form.activateStartDate"
            name="activateStartDate"
            :label="t('Enable start time')"
          />
          <BaseCalendar
            v-if="form.activateStartDate"
            id="lp-published-on"
            v-model="form.publishedOn"
            :label="t('Publication date')"
            show-time
          />

          <BaseCheckbox
            id="lp-activate-end-date"
            v-model="form.activateEndDate"
            name="activateEndDate"
            :label="t('Enable end time')"
          />
          <BaseCalendar
            v-if="form.activateEndDate"
            id="lp-expired-on"
            v-model="form.expiredOn"
            :label="t('Expiration date')"
            show-time
          />

          <BaseCheckbox
            v-if="form.showUseMaxScore"
            id="lp-use-max-score"
            v-model="form.useMaxScore"
            name="useMaxScore"
            :label="t('Use default maximum score of 100')"
          />

          <BaseCheckbox
            v-if="form.showSubscribeUsers"
            id="lp-subscribe-users"
            v-model="form.subscribeUsers"
            name="subscribeUsers"
            :label="t('Subscribe users to learning path')"
          />

          <div
            v-if="isEdit"
            class="flex flex-col gap-1"
          >
            <BaseCheckbox
              id="lp-accumulate-scorm-time"
              v-model="form.accumulateScormTime"
              name="accumulateScormTime"
              :label="t('Accumulate SCORM session time')"
            />
            <small class="text-gray-50">
              {{
                t(
                  "When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.",
                )
              }}
            </small>
          </div>

          <BaseCheckbox
            v-if="form.showScoreAsProgress"
            id="lp-score-as-progress"
            v-model="form.useScoreAsProgress"
            name="useScoreAsProgress"
            :label="t('Use score as progress')"
          />

          <BaseSelect
            v-if="form.showIcon"
            id="lp-icon"
            v-model="form.icon"
            name="icon"
            :label="t('Icon')"
            :options="form.iconOptions"
            option-label="label"
            option-value="value"
          />

          <LpExtraFields
            v-model="extraFieldValues"
            :fields="form.extraFields"
            @file-selected="onExtraFileSelected"
          />

          <div
            v-if="form.showSkills"
            class="field"
          >
            <FloatLabel variant="on">
              <MultiSelect
                v-model="form.skillIds"
                display="chip"
                fluid
                input-id="lp-skills"
                name="skills"
                :options="form.skillOptions"
                option-label="label"
                option-value="value"
              />
              <label for="lp-skills">{{ t("Skills") }}</label>
            </FloatLabel>
          </div>
        </div>
      </BaseAdvancedSettingsButton>

      <div class="flex justify-end gap-2">
        <BaseButton
          :label="t('Cancel')"
          type="plain"
          @click="cancel"
        />
        <BaseButton
          :disabled="saving"
          :label="isEdit ? t('Save course settings') : t('Continue')"
          icon="save"
          type="success"
          is-submit
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import FloatLabel from "primevue/floatlabel"
import MultiSelect from "primevue/multiselect"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import LpExtraFields from "../../components/lp/LpExtraFields.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"
import { useCidReqStore } from "../../store/cidReq"

const FIELD_CHECKBOX = 13
const FIELD_INTEGER = 15
const FIELD_FLOAT = 17
const FIELD_DURATION = 28
const FIELD_DATE = 6
const FIELD_DATETIME = 7
const FIELD_MULTI_SELECT = 5
const FIELD_TAG = 10

const createIntroductionKey =
  '<strong>Welcome</strong> to the Chamilo Course authoring tool.<br />Create your courses step-by-step. The table of contents will appear to the left.'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)
const { showSuccessNotification, showErrorNotification } = useNotification()

const loading = ref(true)
const loadError = ref(false)
const saving = ref(false)
const showAdvancedSettings = ref(false)
const imageFile = ref(null)
const extraFiles = reactive({})
const extraFieldValues = reactive({})
const lpId = computed(() => Number(route.params.lpId || 0))
const isEdit = computed(() => lpId.value > 0)

const form = reactive({
  id: null,
  title: "",
  categoryId: null,
  language: "",
  hideTocFrame: false,
  defaultViewMode: "embedded",
  theme: "",
  author: "",
  searchIndexEnabled: true,
  prerequisiteId: 0,
  accumulateWorkTime: 0,
  nextLpId: 0,
  activateStartDate: true,
  publishedOn: new Date(),
  activateEndDate: false,
  expiredOn: new Date(Date.now() + 86400000),
  useMaxScore: true,
  subscribeUsers: false,
  accumulateScormTime: false,
  useScoreAsProgress: false,
  icon: "",
  removePicture: false,
  titleAsHtml: false,
  showLanguage: false,
  showTheme: false,
  showSearchIndex: false,
  showMinimumTime: false,
  showFlow: false,
  showUseMaxScore: false,
  showSubscribeUsers: false,
  showScoreAsProgress: false,
  showIcon: false,
  showSkills: false,
  imageUrl: null,
  categoryOptions: [],
  languageOptions: [],
  themeOptions: [],
  prerequisiteOptions: [],
  nextLpOptions: [],
  iconOptions: [],
  extraFields: [],
  skillOptions: [],
  skillIds: [],
  csrfToken: "",
})

const context = computed(() => ({
  cid: Number(course.value?.id || route.query.cid || 0),
  sid: Number(session.value?.id ?? route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

const viewModeOptions = computed(() => [
  { label: t("Current view mode: fullscreen"), value: "fullscreen" },
  { label: t("Current view mode: embedded"), value: "embedded" },
  {
    label: t("Current view mode: external embed. Use only for embedding in external sites."),
    value: "embedframe",
  },
  { label: t("Current view mode: Impress"), value: "impress" },
])

const titleEditorConfig = {
  height: 120,
  menubar: false,
  toolbar: "bold italic underline subscript superscript removeformat",
}

const authorEditorConfig = {
  height: 240,
}

onMounted(loadConfiguration)

async function loadConfiguration() {
  loading.value = true
  loadError.value = false
  try {
    const data = await lpService.getConfiguration(lpId.value, context.value)
    Object.assign(form, data)
    form.publishedOn = toDate(data.publishedOn, new Date())
    form.expiredOn = toDate(data.expiredOn, new Date(Date.now() + 86400000))
    form.skillIds = Array.isArray(data.skillIds) ? data.skillIds.map(Number) : []
    initializeExtraFields(data.extraFields || [])
  } catch (error) {
    loadError.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function initializeExtraFields(fields) {
  Object.keys(extraFieldValues).forEach((key) => delete extraFieldValues[key])
  fields.forEach((field) => {
    const value = field.value
    if (field.valueType === FIELD_CHECKBOX) {
      extraFieldValues[field.id] = [true, 1, "1", "true"].includes(value)
      return
    }
    if ([FIELD_INTEGER, FIELD_FLOAT, FIELD_DURATION].includes(field.valueType)) {
      extraFieldValues[field.id] = Number(value || 0)
      return
    }
    if ([FIELD_DATE, FIELD_DATETIME].includes(field.valueType)) {
      extraFieldValues[field.id] = parseExtraFieldDate(field.valueType, value)
      return
    }
    if ([FIELD_MULTI_SELECT, FIELD_TAG].includes(field.valueType)) {
      extraFieldValues[field.id] = Array.isArray(value) ? value : String(value || "").split(";").filter(Boolean)
      return
    }
    extraFieldValues[field.id] = value ?? ""
  })
}

async function save() {
  if (!String(form.title || "").replace(/<[^>]*>/g, "").trim()) {
    showErrorNotification(t("Title is required"))
    return
  }

  saving.value = true
  try {
    const payload = buildPayload()
    const result = await lpService.saveConfiguration(lpId.value, context.value, payload, imageFile.value, extraFiles)
    showSuccessNotification(t("Saved"))
    const savedId = Number(result.id || lpId.value)
    await router.push({
      name: "LpBuilder",
      params: { lpId: savedId },
      query: route.query,
    })
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function buildPayload() {
  return {
    title: form.title,
    categoryId: form.categoryId || null,
    language: form.showLanguage ? form.language : "",
    hideTocFrame: form.hideTocFrame,
    defaultViewMode: form.defaultViewMode,
    theme: form.theme,
    author: form.author,
    searchIndexEnabled: form.searchIndexEnabled,
    prerequisiteId: Number(form.prerequisiteId || 0),
    accumulateWorkTime: Number(form.accumulateWorkTime || 0),
    nextLpId: Number(form.nextLpId || 0),
    activateStartDate: form.activateStartDate,
    publishedOn: form.activateStartDate ? toIso(form.publishedOn) : null,
    activateEndDate: form.activateEndDate,
    expiredOn: form.activateEndDate ? toIso(form.expiredOn) : null,
    useMaxScore: form.useMaxScore,
    subscribeUsers: form.subscribeUsers,
    accumulateScormTime: form.accumulateScormTime,
    useScoreAsProgress: form.useScoreAsProgress,
    icon: form.icon,
    removePicture: form.removePicture,
    extraFields: serializeExtraFields(),
    skillIds: form.skillIds.map(Number),
    csrfToken: form.csrfToken,
  }
}

function serializeExtraFields() {
  const result = {}
  form.extraFields.forEach((field) => {
    const value = extraFieldValues[field.id]
    if (value instanceof Date) {
      result[field.id] = field.valueType === FIELD_DATE ? toLocalDate(value) : toLocalDateTime(value)
      return
    }
    result[field.id] = value
  })
  return result
}

function onExtraFileSelected(fieldId, file) {
  extraFiles[fieldId] = file
}

function cancel() {
  router.push({ name: "LpList", query: route.query })
}

function parseExtraFieldDate(valueType, value) {
  if (!value) {
    return null
  }

  if (valueType === FIELD_DATE) {
    const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})$/)
    if (match) {
      return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
    }
  }

  const normalized = String(value).replace(" ", "T")
  const date = new Date(normalized)

  return Number.isNaN(date.getTime()) ? null : date
}

function toDate(value, fallback) {
  if (!value) {
    return fallback
  }
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? fallback : date
}

function toIso(value) {
  const date = value instanceof Date ? value : new Date(value)
  return date.toISOString()
}

function toLocalDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")
  return `${year}-${month}-${day}`
}

function toLocalDateTime(date) {
  const hours = String(date.getHours()).padStart(2, "0")
  const minutes = String(date.getMinutes()).padStart(2, "0")
  return `${toLocalDate(date)} ${hours}:${minutes}`
}
</script>
