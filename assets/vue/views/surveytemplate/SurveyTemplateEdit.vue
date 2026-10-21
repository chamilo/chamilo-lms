<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import surveyTemplateService from "../../services/surveyTemplateService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notification = useNotification()

const isLoading = ref(false)
const isSaving = ref(false)
const formSubmitted = ref(false)
const form = ref(createEmptyForm())

const templateId = computed(() => Number(route.params.id || 0))
const isEditMode = computed(() => templateId.value > 0)
const isTitleInvalid = computed(() => formSubmitted.value && !stripHtml(form.value.title).trim())

const mediumEditorConfig = {
  height: 220,
  menubar: false,
}

function createEmptyForm() {
  return {
    title: "",
    subtitle: "",
    introduction: "",
    thanks: "",
    anonymous: false,
    shuffle: false,
    oneQuestionPerPage: false,
    displayQuestionNumber: true,
    mandatory: false,
    duration: 0,
    visible: false,
  }
}

function stripHtml(value) {
  const element = document.createElement("div")
  element.innerHTML = value || ""

  return element.textContent || element.innerText || ""
}

function normalizeForm(data) {
  form.value = {
    title: data.title || "",
    subtitle: data.subtitle || "",
    introduction: data.introduction || "",
    thanks: data.thanks || "",
    anonymous: true === data.anonymous,
    shuffle: true === data.shuffle,
    oneQuestionPerPage: true === data.oneQuestionPerPage,
    displayQuestionNumber: data.displayQuestionNumber !== false,
    mandatory: true === data.mandatory,
    duration: Number(data.duration || 0),
    visible: true === data.visible,
  }
}

async function loadConfiguration() {
  isLoading.value = true

  try {
    const data = await surveyTemplateService.getConfiguration(isEditMode.value ? templateId.value : null)
    normalizeForm(data)
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function buildPayload() {
  return {
    ...form.value,
    duration: Number(form.value.duration || 0) > 0 ? Number(form.value.duration) : null,
  }
}

async function save() {
  formSubmitted.value = true
  if (!stripHtml(form.value.title).trim()) {
    return
  }

  isSaving.value = true
  try {
    const saved = await surveyTemplateService.saveConfiguration(
      buildPayload(),
      isEditMode.value ? templateId.value : null,
    )

    notification.showSuccessNotification(t("The survey template has been saved."))

    if (!isEditMode.value && saved.templateId) {
      await router.push({ name: "SurveyTemplateQuestions", params: { id: saved.templateId } })
    }
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isSaving.value = false
  }
}

onMounted(loadConfiguration)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="isEditMode ? t('Edit survey template') : t('Create survey template')">
      <BaseButton
        :label="t('Back')"
        :route="{ name: 'SurveyTemplateList' }"
        icon="back"
        only-icon
        type="plain"
      />
      <BaseButton
        v-if="isEditMode"
        :label="t('Edit questions')"
        :route="{ name: 'SurveyTemplateQuestions', params: { id: templateId } }"
        icon="list"
        only-icon
        type="secondary"
      />
    </SectionHeader>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-sm text-gray-600 shadow-sm"
    >
      {{ t("Loading") }}...
    </div>

    <form
      v-else
      class="space-y-6"
      novalidate
      @submit.prevent="save"
    >
      <section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Basic information") }}</h2>

        <div class="grid gap-5 md:grid-cols-2">
          <BaseInputText
            id="survey_template_title"
            v-model="form.title"
            :error-text="t('The survey template title is required.')"
            :form-submitted="formSubmitted"
            :is-invalid="isTitleInvalid"
            :label="t('Title')"
            name="title"
            show-required-marker
          />
          <BaseInputText
            id="survey_template_subtitle"
            v-model="form.subtitle"
            :label="t('Subtitle')"
            name="subtitle"
          />
        </div>
      </section>

      <section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Survey behavior") }}</h2>

        <div class="grid gap-4 md:grid-cols-2">
          <BaseCheckbox
            id="survey_template_anonymous"
            v-model="form.anonymous"
            :label="t('Anonymous')"
            name="anonymous"
          />
          <BaseCheckbox
            id="survey_template_one_question_per_page"
            v-model="form.oneQuestionPerPage"
            :label="t('One question per page')"
            name="oneQuestionPerPage"
          />
          <BaseCheckbox
            id="survey_template_shuffle"
            v-model="form.shuffle"
            :label="t('Enable shuffle mode')"
            name="shuffle"
          />
          <BaseCheckbox
            id="survey_template_display_question_number"
            v-model="form.displayQuestionNumber"
            :label="t('Show question number')"
            name="displayQuestionNumber"
          />
          <BaseCheckbox
            id="survey_template_mandatory"
            v-model="form.mandatory"
            :label="t('Mandatory')"
            name="mandatory"
          />
          <BaseCheckbox
            id="survey_template_visible"
            v-model="form.visible"
            :label="t('Visible as template')"
            name="visible"
          />
        </div>

        <div class="mt-5 md:max-w-sm">
          <BaseInputNumber
            id="survey_template_duration"
            v-model="form.duration"
            :help-text="t('Use 0 for no time limit.')"
            :label="t('Duration')"
            :min="0"
            name="duration"
          />
        </div>
      </section>

      <section class="rounded-2xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Survey messages") }}</h2>

        <div class="space-y-5">
          <BaseTinyEditor
            editor-id="survey_template_introduction"
            v-model="form.introduction"
            :editor-config="mediumEditorConfig"
            :full-page="false"
            :title="t('Survey introduction')"
          />
          <BaseTinyEditor
            editor-id="survey_template_thanks"
            v-model="form.thanks"
            :editor-config="mediumEditorConfig"
            :full-page="false"
            :title="t('Final thanks')"
          />
        </div>
      </section>

      <div class="flex justify-end gap-2">
        <BaseButton
          :label="t('Cancel')"
          :route="{ name: 'SurveyTemplateList' }"
          icon="back"
          type="black"
        />
        <BaseButton
          :is-loading="isSaving"
          :label="t('Save')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>
  </div>
</template>
