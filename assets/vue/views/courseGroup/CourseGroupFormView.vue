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
      <template #end>
        <BaseButton
          v-if="isEditing && form.linkedToClass && form.canRemoveClassLink"
          :label="t('Remove class link')"
          icon="link"
          type="danger"
          @click="confirmRemoveClassLink"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-8 text-center text-gray-50"
    >
      {{ t("Loading") }}
    </div>

    <form
      v-else-if="isEditing"
      class="space-y-6"
      @submit.prevent="saveGroup"
    >
      <div class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Group settings") }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInputText
            id="course-group-title"
            v-model="form.title"
            :label="t('Group name')"
            name="title"
            required
          />
          <BaseSelect
            v-if="form.allowCategories"
            id="course-group-category"
            v-model="form.categoryId"
            :label="t('Category')"
            name="categoryId"
            :options="categoryOptions"
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
              id="course-group-max-student"
              v-model="form.maxStudent"
              :label="t('Maximum number of members')"
              :min="1"
              name="maxStudent"
            />
          </div>
          <div class="flex flex-col gap-3 rounded-lg border border-gray-20 bg-gray-10 p-4">
            <BaseCheckbox
              id="group-self-registration"
              v-model="form.selfRegistrationAllowed"
              :label="t('Self-registration allowed')"
              name="selfRegistrationAllowed"
            />
            <BaseCheckbox
              id="group-self-unregistration"
              v-model="form.selfUnregistrationAllowed"
              :label="t('Self-unregistration allowed')"
              name="selfUnregistrationAllowed"
            />
          </div>
          <BaseTextArea
            id="course-group-description"
            v-model="form.description"
            class="min-h-28 md:col-span-2"
            :label="t('Description')"
            name="description"
          />
        </div>
      </div>

      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Tools") }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect
            v-for="field in toolFields"
            :id="`group-${field.key}`"
            :key="field.key"
            v-model="form[field.key]"
            :label="t(field.label)"
            :name="field.key"
            :options="toolStateOptions(field.key)"
          />
          <BaseSelect
            v-if="form.allowDocumentAccess"
            id="group-document-access"
            v-model="form.documentAccess"
            :label="t('Document access')"
            name="documentAccess"
            :options="documentAccessOptions"
          />
        </div>
      </article>

      <div
        v-if="form.linkedToClass"
        class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"
      >
        {{ t("This group is linked to the class") }}: <strong>{{ form.linkedClassTitle }}</strong>
      </div>

      <div class="flex justify-end">
        <BaseButton
          :is-loading="saving"
          :label="t('Save settings')"
          icon="save"
          is-submit
          type="success"
        />
      </div>
    </form>

    <div
      v-else-if="!loading"
      class="space-y-6"
    >
      <article class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-gray-90">{{ t("Create manual groups") }}</h2>
            <p class="text-sm text-gray-50">{{ t("Create one or more empty course groups") }}</p>
          </div>
          <BaseInputNumber
            id="number-of-groups"
            v-model="manualGroupCount"
            :label="t('Number of groups to create')"
            :min="1"
            :max="50"
          />
        </div>

        <div
          v-if="manualGroupCount > 1"
          class="mb-5 grid grid-cols-1 gap-3 rounded-lg border border-gray-20 bg-gray-10 p-4 md:grid-cols-2"
        >
          <BaseCheckbox
            v-if="form.allowCategories"
            id="same-category"
            v-model="sameCategory"
            :label="`${t('same for all')} - ${t('Group category')}`"
            name="sameCategory"
          />
          <BaseCheckbox
            id="same-places"
            v-model="samePlaces"
            :label="`${t('same for all')} - ${t('seats (optional)')}`"
            name="samePlaces"
          />
        </div>

        <div class="space-y-3">
          <div
            v-for="(group, index) in manualGroups"
            :key="index"
            class="grid grid-cols-1 gap-3 rounded-lg border border-gray-20 bg-gray-10 p-4 md:grid-cols-3"
          >
            <BaseInputText
              :id="`manual-group-${index}-name`"
              v-model="group.name"
              :label="t('Group name')"
              :name="`group_${index}_name`"
            />
            <BaseSelect
              v-if="form.allowCategories"
              :id="`manual-group-${index}-category`"
              v-model="group.categoryId"
              :label="t('Category')"
              :name="`group_${index}_category`"
              :disabled="sameCategory && index > 0"
              :options="categoryOptions"
            />
            <BaseInputNumber
              :id="`manual-group-${index}-places`"
              v-model="group.maxStudent"
              :label="t('Number of places')"
              :disabled="samePlaces && index > 0"
              :min="0"
              :name="`group_${index}_places`"
            />
          </div>
        </div>
        <div class="mt-5 flex justify-end">
          <BaseButton
            :is-loading="saving"
            :label="t('Create group(s)')"
            icon="plus"
            type="success"
            @click="createManualGroups"
          />
        </div>
      </article>

      <article
        v-if="form.baseGroups.length > 0"
        class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
      >
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Create subgroups") }}</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect
            id="base-group"
            v-model="baseGroupId"
            :label="t('Base group')"
            name="baseGroupId"
            :options="baseGroupOptions"
          />
          <BaseInputNumber
            id="subgroup-count"
            v-model="subgroupCount"
            :label="t('Number of groups to create')"
            :min="1"
          />
        </div>
        <div class="mt-5 flex justify-end">
          <BaseButton
            :is-loading="saving"
            :label="t('Create subgroups')"
            icon="folder-multiple-plus"
            type="success"
            @click="createSubgroups"
          />
        </div>
      </article>

      <article
        v-if="form.classes.length > 0"
        class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm"
      >
        <h2 class="mb-5 text-lg font-semibold text-gray-90">{{ t("Create groups from subscribed classes") }}</h2>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div
            v-for="item in form.classes"
            :key="item.id"
            class="rounded-lg border border-gray-20 bg-gray-10 p-4"
          >
            <BaseCheckbox
              :id="`class-${item.id}`"
              v-model="selectedClassIds"
              :label="`${item.label} (${item.users} ${t('Users')})`"
              :name="`class_${item.id}`"
              :value="item.id"
            />
          </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseSelect
            v-if="form.allowCategories"
            id="class-groups-category"
            v-model="classCategoryId"
            :label="t('Group category')"
            name="classCategoryId"
            :options="categoryOptions"
          />
          <div class="rounded-lg border border-gray-20 bg-gray-10 p-4 text-sm">
            <BaseCheckbox
              id="consistent-class-link"
              v-model="consistentLink"
              :label="t('Keep created groups linked to their source classes')"
              name="consistentLink"
            />
          </div>
        </div>
        <div class="mt-5 flex justify-end">
          <BaseButton
            :disabled="selectedClassIds.length === 0"
            :is-loading="saving"
            :label="t('Create groups from selected classes')"
            icon="sessions"
            type="success"
            @click="createClassGroups"
          />
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue"
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
import { useConfirmation } from "../../composables/useConfirmation"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { requireConfirmation } = useConfirmation()
const { cid, sid, contextQuery } = useRouteCourseContext()

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const manualGroupCount = ref(1)
const sameCategory = ref(false)
const samePlaces = ref(false)
const manualGroups = ref([])
const baseGroupId = ref(0)
const subgroupCount = ref(2)
const selectedClassIds = ref([])
const classCategoryId = ref(0)
const consistentLink = ref(false)
const memberLimitMode = ref("unlimited")
const form = reactive({
  groupId: 0,
  title: "",
  description: "",
  categoryId: 0,
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
  categories: [],
  allowCategories: false,
  allowDocumentAccess: false,
  baseGroups: [],
  classes: [],
  linkedToClass: false,
  linkedClassTitle: "",
  canRemoveClassLink: false,
})

const groupId = computed(() => Number(route.params.groupId || 0))
const isEditing = computed(() => groupId.value > 0)
const requestParams = computed(() => ({
  cid: cid.value,
  sid: sid.value,
  gid: groupId.value || 0,
  categoryId: Number(route.query.categoryId || 0),
}))
const listRoute = computed(() => ({
  name: "CourseUserGroups",
  params: parentParams(),
  query: { ...contextQuery.value, gid: 0 },
}))
const categoryOptions = computed(() => form.categories.map((item) => ({ label: item.label, value: item.id })))
const baseGroupOptions = computed(() =>
  form.baseGroups.map((item) => ({ label: `${item.label} (${item.members})`, value: item.id })),
)

const toolFields = [
  { key: "docState", label: "Documents" },
  { key: "workState", label: "Assignments" },
  { key: "calendarState", label: "Agenda" },
  { key: "announcementsState", label: "Announcements" },
  { key: "forumState", label: "Forum" },
  { key: "wikiState", label: "Wiki" },
  { key: "chatState", label: "Chat" },
]
const memberLimitOptions = computed(() => [
  { label: t("No limitation"), value: "unlimited" },
  { label: t("Maximum number of members"), value: "limited" },
])
const documentAccessOptions = computed(() => [
  { label: t("Share"), value: 0 },
  { label: t("Read only"), value: 1 },
  { label: t("Collaboration"), value: 2 },
])

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

function parentParams() {
  const params = { ...route.params }
  delete params.groupId
  delete params.categoryId
  return params
}

function syncManualGroups() {
  const count = Math.max(1, Math.min(50, Number(manualGroupCount.value || 1)))
  manualGroups.value = Array.from({ length: count }, (_, index) => {
    if (manualGroups.value[index]) return manualGroups.value[index]

    const number = Number(form.nextGroupNumber || 1) + index
    const formattedNumber = count < 100 && number < 10 ? `0${number}` : String(number)

    return {
      name: `${t("Group")} ${formattedNumber}`,
      categoryId: Number(form.categoryId || 0),
      maxStudent: 0,
    }
  })
  applySharedValues()
}

function applySharedValues() {
  const firstGroup = manualGroups.value[0]
  if (!firstGroup) return

  manualGroups.value.forEach((group, index) => {
    if (index === 0) return
    if (sameCategory.value) group.categoryId = firstGroup.categoryId
    if (samePlaces.value) group.maxStudent = firstGroup.maxStudent
  })
}

async function loadForm() {
  loading.value = true
  errorMessage.value = ""
  try {
    const response = await courseGroupService.getForm(groupId.value, requestParams.value)
    Object.assign(form, response)
    memberLimitMode.value = Number(form.maxStudent) > 0 ? "limited" : "unlimited"
    classCategoryId.value = Number(form.categoryId || form.categories[0]?.id || 0)
    syncManualGroups()
  } catch (error) {
    console.error("[CourseGroup] Failed to load group form", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function execute(callback, successText) {
  if (saving.value) return
  saving.value = true
  errorMessage.value = ""
  successMessage.value = ""
  try {
    await callback()
    successMessage.value = t(successText)
    await router.push(listRoute.value)
  } catch (error) {
    console.error("[CourseGroup] Save failed", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    saving.value = false
  }
}

function saveGroup() {
  const payload = {
    groupId: Number(form.groupId || groupId.value || 0),
    title: form.title,
    description: form.description,
    categoryId: Number(form.categoryId || 0),
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
  execute(() => courseGroupService.saveForm(payload, groupId.value, requestParams.value), "Saved")
}

function createManualGroups() {
  const groups = manualGroups.value.filter((item) => item.name.trim() !== "")
  if (groups.length === 0) {
    errorMessage.value = t("The group title is required.")
    return
  }
  execute(() => courseGroupService.action("create-groups", { groups }, requestParams.value), "Groups created")
}

function createSubgroups() {
  execute(
    () =>
      courseGroupService.action(
        "create-subgroups",
        { baseGroupId: baseGroupId.value, numberOfGroups: subgroupCount.value },
        requestParams.value,
      ),
    "Groups created",
  )
}

function createClassGroups() {
  execute(
    () =>
      courseGroupService.action(
        "create-class-groups",
        {
          categoryId: classCategoryId.value,
          classIds: selectedClassIds.value,
          consistentLink: consistentLink.value,
        },
        requestParams.value,
      ),
    "Groups created",
  )
}

function confirmRemoveClassLink() {
  requireConfirmation({
    message: t("Please confirm your choice"),
    accept: () =>
      execute(
        () => courseGroupService.action("remove-class-link", { groupId: groupId.value }, requestParams.value),
        "The class link was removed",
      ),
  })
}

watch(manualGroupCount, syncManualGroups)
watch([sameCategory, samePlaces], applySharedValues)
watch(() => [manualGroups.value[0]?.categoryId, manualGroups.value[0]?.maxStudent], applySharedValues)
onMounted(loadForm)
</script>
