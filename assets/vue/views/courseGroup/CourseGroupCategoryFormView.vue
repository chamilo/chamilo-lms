<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Back to group list')"
          :route="listRoute"
          icon="back"
          only-icon
          type="plain"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <form
      v-if="!loading"
      class="space-y-6"
      @submit.prevent="saveCategory"
    >
      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">
          {{ !form.allowCategories ? t("Edit settings") : categoryId > 0 ? t("Edit category") : t("Add category") }}
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInputText
            id="group-category-title"
            v-model="form.title"
            :label="t('Title')"
            name="title"
            required
          />
          <BaseSelect
            id="group-category-groups-per-user"
            v-model="form.groupsPerUser"
            :label="t('Groups per user')"
            name="groupsPerUser"
            :options="groupsPerUserOptions"
          />
          <div class="space-y-3 rounded-lg border border-gray-20 bg-gray-10 p-4">
            <BaseRadioButtons
              v-model="memberLimitMode"
              name="max_member_no_limit"
              :options="memberLimitOptions"
              :title="t('Limit')"
            />
            <BaseInputNumber
              v-if="memberLimitMode === 'limited'"
              id="group-category-max-student"
              v-model="form.maxStudent"
              :label="t('Maximum number of members')"
              :min="1"
              name="maxStudent"
            />
          </div>
          <div class="flex flex-col gap-3 rounded-lg border border-gray-20 bg-gray-10 p-4">
            <BaseCheckbox
              id="category-self-registration"
              v-model="form.selfRegistrationAllowed"
              :label="t('Self-registration allowed')"
              name="selfRegistrationAllowed"
            />
            <BaseCheckbox
              id="category-self-unregistration"
              v-model="form.selfUnregistrationAllowed"
              :label="t('Self-unregistration allowed')"
              name="selfUnregistrationAllowed"
            />
          </div>
          <BaseTextArea
            id="group-category-description"
            v-model="form.description"
            class="min-h-28 md:col-span-2"
            :label="t('Description')"
            name="description"
          />
        </div>
      </article>

      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Default settings for new groups") }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect
            v-for="field in toolFields"
            :id="`category-${field.key}`"
            :key="field.key"
            v-model="form[field.key]"
            :label="t(field.label)"
            :name="field.key"
            :options="toolStateOptions(field.key)"
          />
          <BaseSelect
            v-if="form.allowDocumentAccess"
            id="category-document-access"
            v-model="form.documentAccess"
            :label="t('Document access')"
            name="documentAccess"
            :options="documentAccessOptions"
          />
        </div>
      </article>

      <div class="flex justify-end">
        <BaseButton
          :is-loading="saving"
          :label="categoryId > 0 ? t('Edit') : t('Add')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, contextQuery } = useRouteCourseContext()

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const memberLimitMode = ref("unlimited")
const form = reactive({
  title: "",
  description: "",
  groupsPerUser: 1,
  maxStudent: 0,
  selfRegistrationAllowed: false,
  selfUnregistrationAllowed: false,
  docState: 2,
  workState: 2,
  calendarState: 2,
  announcementsState: 2,
  forumState: 2,
  wikiState: 2,
  chatState: 2,
  documentAccess: 0,
  allowDocumentAccess: false,
  allowCategories: true,
})

const categoryId = computed(() => Number(route.params.categoryId || 0))
const requestParams = computed(() => ({ cid: cid.value, sid: sid.value }))
const listRoute = computed(() => ({
  name: "CourseUserGroups",
  params: parentParams(),
  query: { ...contextQuery.value, gid: 0 },
}))
const toolFields = [
  { key: "docState", label: "Documents" },
  { key: "workState", label: "Assignments" },
  { key: "calendarState", label: "Agenda" },
  { key: "announcementsState", label: "Announcements" },
  { key: "forumState", label: "Forum" },
  { key: "wikiState", label: "Wiki" },
  { key: "chatState", label: "Chat" },
]
function toolStateOptions(key) {
  const options = [
    { label: t("Not available"), value: 0 },
    { label: t("Public"), value: 1 },
    { label: t("Private"), value: 2 },
  ]

  if (key === "announcementsState") {
    options.push({ label: t("Private between users"), value: 3 })
  }

  return options
}
const groupsPerUserOptions = computed(() => [
  ...Array.from({ length: 10 }, (_, index) => ({ label: String(index + 1), value: index + 1 })),
  { label: t("All"), value: 0 },
])
const memberLimitOptions = computed(() => [
  { label: t("No limitation"), value: "unlimited" },
  { label: t("Maximum number of members"), value: "limited" },
])
const documentAccessOptions = computed(() => [
  { label: t("Share"), value: 0 },
  { label: t("Read only"), value: 1 },
  { label: t("Collaboration"), value: 2 },
])

function parentParams() {
  const params = { ...route.params }
  delete params.categoryId
  return params
}

async function loadCategory() {
  loading.value = true
  errorMessage.value = ""
  try {
    Object.assign(form, await courseGroupService.getCategoryForm(categoryId.value, requestParams.value))
    memberLimitMode.value = Number(form.maxStudent) > 0 ? "limited" : "unlimited"
  } catch (error) {
    console.error("[CourseGroup] Failed to load category form", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ""
  try {
    const payload = {
      categoryId: Number(form.categoryId || categoryId.value || 0),
      title: form.title,
      description: form.description,
      groupsPerUser: Number(form.groupsPerUser),
      maxStudent: memberLimitMode.value === "unlimited" ? 0 : Number(form.maxStudent || 0),
      selfRegistrationAllowed: Boolean(form.selfRegistrationAllowed),
      selfUnregistrationAllowed: Boolean(form.selfUnregistrationAllowed),
      docState: Number(form.docState),
      workState: Number(form.workState),
      calendarState: Number(form.calendarState),
      announcementsState: Number(form.announcementsState),
      forumState: Number(form.forumState),
      wikiState: Number(form.wikiState),
      chatState: Number(form.chatState),
      documentAccess: Number(form.documentAccess),
    }
    if (memberLimitMode.value === "limited" && payload.maxStudent <= 0) {
      errorMessage.value = t("Please enter a valid number for the maximum number of members.")
      return
    }
    await courseGroupService.saveCategoryForm(payload, categoryId.value, requestParams.value)
    await router.push(listRoute.value)
  } catch (error) {
    console.error("[CourseGroup] Failed to save category", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

onMounted(loadCategory)
</script>
