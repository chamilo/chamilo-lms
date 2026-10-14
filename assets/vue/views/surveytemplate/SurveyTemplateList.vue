<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import surveyTemplateService from "../../services/surveyTemplateService"

const { t } = useI18n()
const notification = useNotification()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const templates = ref([])
const assignDialogVisible = ref(false)
const assigningTemplate = ref(null)
const isAssigning = ref(false)
const isLoadingTargets = ref(false)
const courses = ref([])
const targetCourseId = ref(null)
const targetSessionId = ref(null)

const selectedCourse = computed(
  () => courses.value.find((course) => Number(course.id) === Number(targetCourseId.value)) || null,
)

const sessionOptions = computed(() => selectedCourse.value?.sessions || [])

async function loadTemplates() {
  isLoading.value = true

  try {
    const data = await surveyTemplateService.getCatalog()
    templates.value = Array.isArray(data.templates) ? data.templates : []
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function confirmDelete(template) {
  requireConfirmation({
    message: t("Are you sure you want to delete this survey template?"),
    accept: () => deleteTemplate(template),
  })
}

async function deleteTemplate(template) {
  try {
    await surveyTemplateService.deleteTemplate(template.id)
    notification.showSuccessNotification(t("The survey template has been deleted."))
    await loadTemplates()
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

async function openAssignDialog(template) {
  assigningTemplate.value = template
  targetCourseId.value = null
  targetSessionId.value = null
  assignDialogVisible.value = true

  if (courses.value.length > 0) {
    return
  }

  isLoadingTargets.value = true
  try {
    const data = await surveyTemplateService.getTargetCatalog()
    courses.value = Array.isArray(data.courses) ? data.courses : []
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isLoadingTargets.value = false
  }
}

function onCourseChange() {
  targetSessionId.value = null
}

async function assignTemplate() {
  if (!assigningTemplate.value || Number(targetCourseId.value || 0) <= 0) {
    notification.showErrorNotification(t("Please select a course."))
    return
  }

  if (selectedCourse.value && selectedCourse.value.canAssignBase === false && Number(targetSessionId.value || 0) <= 0) {
    notification.showErrorNotification(t("Please select a session for this course."))
    return
  }

  isAssigning.value = true
  try {
    await surveyTemplateService.instantiate(assigningTemplate.value.id, {
      targetCourseId: Number(targetCourseId.value),
      targetSessionId: Number(targetSessionId.value || 0) || null,
    })
    assignDialogVisible.value = false
    notification.showSuccessNotification(t("The survey has been created from the template."))
  } catch (error) {
    notification.showErrorNotification(error)
  } finally {
    isAssigning.value = false
  }
}

function formatDate(value) {
  if (!value) {
    return ""
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return new Intl.DateTimeFormat(undefined, {
    year: "numeric",
    month: "short",
    day: "2-digit",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date)
}

function editRoute(template) {
  return { name: "SurveyTemplateEdit", params: { id: template.id } }
}

function questionsRoute(template) {
  return { name: "SurveyTemplateQuestions", params: { id: template.id } }
}

onMounted(loadTemplates)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Survey templates')">
      <BaseButton
        :label="t('Create template')"
        :route="{ name: 'SurveyTemplateCreate' }"
        icon="plus"
        only-icon
        type="success"
      />
    </SectionHeader>

    <div class="rounded-xl border border-gray-20 bg-white p-4 text-sm text-gray-600 shadow-sm">
      {{ t("Create reusable survey templates for teachers and assign them directly to a course or session.") }}
    </div>

    <BaseTable
      :is-loading="isLoading"
      :lazy="false"
      :text-for-empty="t('No results found')"
      :total-items="templates.length"
      :values="templates"
      data-key="id"
    >
      <Column
        field="title"
        :header="t('Title')"
        sortable
      />
      <Column
        field="subtitle"
        :header="t('Subtitle')"
      />
      <Column
        field="questionCount"
        :header="t('Questions')"
        sortable
      />
      <Column :header="t('Visible')">
        <template #body="{ data }">
          {{ data.visible ? t("Yes") : t("No") }}
        </template>
      </Column>
      <Column :header="t('Updated at')">
        <template #body="{ data }">
          {{ formatDate(data.updatedAt) }}
        </template>
      </Column>
      <Column
        :header="t('Actions')"
        class="w-44"
      >
        <template #body="{ data }">
          <div class="flex flex-nowrap justify-end gap-1">
            <BaseButton
              :label="t('Edit')"
              :route="editRoute(data)"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :label="t('Edit questions')"
              :route="questionsRoute(data)"
              icon="list"
              only-icon
              size="small"
              type="secondary-text"
            />
            <BaseButton
              :label="t('Assign to course')"
              icon="courses"
              only-icon
              size="small"
              type="secondary-text"
              @click="openAssignDialog(data)"
            />
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="assignDialogVisible"
      :title="t('Assign template to course')"
      header-icon="courses"
    >
      <div class="space-y-5">
        <div class="rounded-lg border border-gray-20 bg-gray-10 p-3 text-sm text-gray-700">
          <strong>{{ t("Template") }}:</strong>
          {{ assigningTemplate?.title || "" }}
        </div>

        <BaseSelect
          id="survey_template_target_course"
          v-model="targetCourseId"
          :disabled="isLoadingTargets"
          :label="t('Course')"
          :options="courses"
          name="targetCourseId"
          option-label="label"
          option-value="id"
          @change="onCourseChange"
        />

        <BaseSelect
          id="survey_template_target_session"
          v-model="targetSessionId"
          :disabled="!targetCourseId"
          :label="t('Session')"
          :options="sessionOptions"
          allow-clear
          name="targetSessionId"
          option-label="title"
          option-value="id"
        />

        <p
          v-if="selectedCourse?.canAssignBase !== false"
          class="text-xs text-gray-500"
        >
          {{ t("Leave the session empty to create the survey directly in the course.") }}
        </p>
        <p
          v-else
          class="text-xs text-orange-700"
        >
          {{ t("This course is available through a session on this portal, so a session is required.") }}
        </p>
      </div>

      <template #footer>
        <BaseButton
          :disabled="Number(targetCourseId || 0) <= 0"
          :is-loading="isAssigning"
          :label="t('Assign to course')"
          icon="courses"
          type="success"
          @click="assignTemplate"
        />
      </template>
    </BaseDialog>
  </div>
</template>
