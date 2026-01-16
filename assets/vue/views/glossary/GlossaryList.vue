<template>
  <div>
    <SectionHeader :title="t('Glossary')">
      <template #end>
        <StudentViewButton
          v-if="securityStore.isAuthenticated"
          @change="onStudentViewChange"
        />
      </template>
    </SectionHeader>
    <BaseToolbar v-if="securityStore.isAuthenticated">
      <template v-if="canEditGlossary">
        <BaseButton
          :label="t('Add new glossary term')"
          icon="plus"
          type="black"
          @click="addNewTerm"
        />
        <BaseButton
          v-if="canUseAiGlossaryGenerator"
          :label="t('Generate glossary terms')"
          icon="robot"
          type="black"
          @click="generateGlossaryTerms"
        />
        <BaseButton
          :label="t('Import glossary')"
          icon="import"
          type="black"
          @click="importGlossary"
        />
        <BaseButton
          :label="t('Export glossary')"
          icon="file-export"
          type="black"
          @click="exportGlossary"
        />
        <BaseButton
          :icon="view === 'table' ? 'list' : 'table'"
          :label="view === 'table' ? t('List view') : t('Table view')"
          type="black"
          @click="changeView(view)"
        />
        <BaseButton
          :label="t('Export to documents')"
          icon="export"
          type="black"
          @click="exportToDocuments"
        />
      </template>
    </BaseToolbar>

    <BaseInputText
      v-model="searchTerm"
      :label="t('Search term')"
      class="mb-4"
      @update:model-value="debouncedSearch"
    />

    <div v-if="isLoading">
      <BaseCard
        v-for="i in 4"
        :key="i"
        class="mb-4 bg-white"
        plain
      >
        <template #header>
          <div class="-mb-2 bg-gray-15 px-4 py-2">
            <Skeleton class="my-2 h-6 w-52" />
          </div>
        </template>
        <Skeleton class="h-6 w-64" />
      </BaseCard>
    </div>

    <div v-if="glossaries.length === 0 && !searchBoxTouched && !isLoading">
      <EmptyState
        icon="glossary"
        summary="Add your first term glossary to this course"
      >
        <BaseButton
          v-if="canEditGlossary"
          :label="t('Add new glossary term')"
          class="mt-4"
          icon="plus"
          type="primary"
          @click="addNewTerm"
        />
      </EmptyState>
    </div>

    <div>
      <GlossaryTermList
        v-if="view === 'list'"
        :glossaries="glossaries"
        :is-loading="isLoading"
        :search-term="searchTerm"
        :can-edit-glossary="canEditGlossary"
        @delete="confirmDeleteTerm($event)"
        @edit="editTerm($event)"
      />
      <GlossaryTermTable
        v-else
        :glossaries="glossaries"
        :search-term="searchTerm"
        :can-edit-glossary="canEditGlossary"
        @delete="confirmDeleteTerm($event)"
        @edit="editTerm($event)"
      />
    </div>

    <BaseDialogDelete
      v-model:is-visible="isDeleteItemDialogVisible"
      :item-to-delete="termToDeleteString"
      @confirm-clicked="deleteTerm"
      @cancel-clicked="isDeleteItemDialogVisible = false"
    />
  </div>
</template>

<script setup>
import EmptyState from "../../components/EmptyState.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import GlossaryTermList from "../../components/glossary/GlossaryTermList.vue"
import GlossaryTermTable from "../../components/glossary/GlossaryTermTable.vue"
import { useCidReq } from "../../composables/cidReq"
import glossaryService from "../../services/glossaryService"
import { useNotification } from "../../composables/notification"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import { debounce } from "lodash"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import Skeleton from "primevue/skeleton"
import { useSecurityStore } from "../../store/securityStore"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"

const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const notifications = useNotification()
const platform = usePlatformConfig()
const courseSettingsStore = useCourseSettings()

const { t } = useI18n()

const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)

const isLoading = ref(true)
const isSearchLoading = ref(false)
const searchTerm = ref("")
const searchBoxTouched = ref(false)
const parentResourceNodeId = ref(Number(route.params.node))

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED,
    },
  ]),
)

const glossaries = ref([])
const view = ref("list")

const isDeleteItemDialogVisible = ref(false)
const termToDelete = ref(null)

const termToDeleteString = computed(() => {
  if (termToDelete.value === null) return ""
  return termToDelete.value.title
})

const isAllowedToEdit = ref(false)

const canEditGlossary = computed(() => {
  const inSession = !!route.query.sid
  const basePermission = isAllowedToEdit.value || (securityStore.isCurrentTeacher && !inSession)
  return basePermission && !platform.isStudentViewActive
})

async function loadCourseSettingsIfPossible() {
  const courseId = course.value?.id
  const sessionId = session.value?.id

  if (!courseId) {
    return
  }

  try {
    await courseSettingsStore.loadCourseSettings(courseId, sessionId)
  } catch (err) {
    console.error("[Glossary] loadCourseSettings FAILED:", err)
  }
}

onMounted(async () => {
  isLoading.value = true

  await loadCourseSettingsIfPossible()
  await fetchGlossaries()
  await onStudentViewChange()
})

watch(
  () => [course.value?.id, session.value?.id],
  async () => {
    await loadCourseSettingsIfPossible()
  },
)

watch(
  () => platform.isStudentViewActive,
  async () => {
    await onStudentViewChange()
    await fetchGlossaries()
    await onStudentViewChange()
  },
)

async function onStudentViewChange() {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
}

const debouncedSearch = debounce(() => {
  searchBoxTouched.value = true
  isSearchLoading.value = true
  fetchGlossaries()
}, 500)

const aiHelpersEnabled = computed(() => {
  const v = String(platform.getSetting("ai_helpers.enable_ai_helpers"))
  return v === "true"
})

const glossaryGeneratorEnabled = computed(() => {
  const v =
    courseSettingsStore?.getSetting?.("glossary_terms_generator") ??
    courseSettingsStore?.getSetting?.("glossary_terms_generators")

  return String(v) === "true"
})

const canUseAiGlossaryGenerator = computed(() => {
  return !!(canEditGlossary.value && aiHelpersEnabled.value && glossaryGeneratorEnabled.value)
})

function generateGlossaryTerms() {
  if (!canEditGlossary.value) return
  router.push({
    name: "GenerateGlossaryTerms",
    query: route.query,
  })
}

function addNewTerm() {
  if (!canEditGlossary.value) return
  router.push({
    name: "CreateTerm",
    query: route.query,
  })
}

function editTerm(term) {
  if (!canEditGlossary.value) return
  router.push({
    name: "UpdateTerm",
    params: { id: term.iid },
    query: route.query,
  })
}

async function confirmDeleteTerm(term) {
  if (!canEditGlossary.value) return
  termToDelete.value = term
  isDeleteItemDialogVisible.value = true
}

async function deleteTerm() {
  if (!canEditGlossary.value) return
  try {
    await glossaryService.deleteTerm(termToDelete.value.iid)
    notifications.showSuccessNotification(t("Term removed"))
    termToDelete.value = null
    isDeleteItemDialogVisible.value = false
    await fetchGlossaries()
  } catch (error) {
    console.error("[Glossary] Error deleting term:", error)
    notifications.showErrorNotification(t("Could not delete term"))
  }
}

function importGlossary() {
  if (!canEditGlossary.value) return
  router.push({
    name: "ImportGlossary",
    query: route.query,
  })
}

function exportGlossary() {
  if (!canEditGlossary.value) return
  router.push({
    name: "ExportGlossary",
    query: route.query,
  })
}

function changeView(newView) {
  view.value = newView === "table" ? "list" : "table"
}

async function exportToDocuments() {
  if (!canEditGlossary.value) return
  const postData = {
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }

  try {
    await glossaryService.exportToDocuments(postData)
    notifications.showSuccessNotification(t("Exported to documents"))
  } catch (error) {
    console.error("[Glossary] Error exporting to documents:", error)
    notifications.showErrorNotification(t("Could not export to documents"))
  }
}

const { cid, sid } = useCidReq()

async function fetchGlossaries() {
  const params = {
    "resourceNode.parent": route.query.parent || null,
    cid: cid || null,
    sid: sid || null,
    q: searchTerm.value,
  }

  try {
    glossaries.value = await glossaryService.getGlossaryTerms(params)
  } catch (error) {
    console.error("[Glossary] Error fetching glossary terms:", error)
    notifications.showErrorNotification(t("Could not fetch glossary terms"))
  } finally {
    isLoading.value = false
    isSearchLoading.value = false
  }
}
</script>
