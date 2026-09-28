<template>
  <div>
    <SectionHeader :title="t('Glossary')" />

    <BaseToolbar v-if="securityStore.isAuthenticated">
      <template v-if="canEditGlossary">
        <BaseButton
          :label="t('Add new glossary term')"
          icon="plus"
          only-icon
          type="success"
          @click="addNewTerm"
        />
        <BaseButton
          :label="t('Manage categories')"
          icon="file-tree-outline"
          only-icon
          type="black"
          @click="manageCategories"
        />
        <BaseButton
          v-if="canUseAiGlossaryGenerator"
          :label="t('Generate glossary terms')"
          icon="robot"
          only-icon
          type="black"
          @click="generateGlossaryTerms"
        />
        <BaseButton
          :label="t('Import glossary')"
          icon="import"
          only-icon
          type="black"
          @click="importGlossary"
        />
        <BaseButton
          :label="t('Export glossary')"
          icon="file-export"
          only-icon
          type="black"
          @click="exportGlossary"
        />
        <BaseButton
          :icon="view === 'table' ? 'list' : 'table'"
          :label="view === 'table' ? t('List view') : t('Table view')"
          only-icon
          type="black"
          @click="changeView(view)"
        />
        <BaseButton
          :label="t('Export to documents')"
          icon="export"
          only-icon
          type="black"
          @click="exportToDocuments"
        />
      </template>
    </BaseToolbar>

    <div class="mb-4 grid gap-4 md:grid-cols-2">
      <BaseInputText
        v-model="searchTerm"
        :label="t('Search term')"
        @update:model-value="debouncedSearch"
      />
      <BaseSelect
        id="glossary-category-filter"
        v-model="selectedCategoryId"
        :label="t('Category')"
        :options="categoryFilterOptions"
        name="categoryId"
        @change="fetchGlossaries"
      />
    </div>

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

    <EmptyState
      v-else-if="glossaries.length === 0"
      icon="glossary"
      :summary="emptyStateSummary"
    >
      <BaseButton
        v-if="canEditGlossary && !hasActiveFilters"
        :label="t('Add new glossary term')"
        class="mt-4"
        icon="plus"
        type="success"
        @click="addNewTerm"
      />
    </EmptyState>

    <div v-else>
      <GlossaryTermList
        v-if="view === 'list'"
        :glossaries="glossaries"
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
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import GlossaryTermList from "../../components/glossary/GlossaryTermList.vue"
import GlossaryTermTable from "../../components/glossary/GlossaryTermTable.vue"
import { getCourseContext } from "../../utils/courseContext"
import glossaryService from "../../services/glossaryService"
import { useNotification } from "../../composables/notification"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import { debounce } from "lodash"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import Skeleton from "primevue/skeleton"
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useCidReqStore } from "../../store/cidReq"
import { storeToRefs } from "pinia"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"

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
const searchTerm = ref("")
const categories = ref([])
const selectedCategoryId = ref(Number(route.query.categoryId || 0))
const parentResourceNodeId = ref(Number(route.params.node))

// Course context derived server-side from the gated session course.
const resourceLinkList = ref(JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }]))

const glossaries = ref([])
const view = ref("list")

const isDeleteItemDialogVisible = ref(false)
const termToDelete = ref(null)

const termToDeleteString = computed(() => {
  if (termToDelete.value === null) return ""
  return termToDelete.value.title
})

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })

const canEditGlossary = computed(() => {
  const inSession = !!route.query.sid
  const basePermission = isAllowedToEdit.value || (securityStore.isCurrentTeacher && !inSession)
  return basePermission && !platform.isStudentViewActive
})

const categoryFilterOptions = computed(() => [
  { label: t("All categories"), value: 0 },
  ...categories.value.map((category) => ({
    label: category.title,
    value: Number(category.iid || category.id || 0),
  })),
])

const hasActiveFilters = computed(() => {
  return searchTerm.value.trim() !== "" || selectedCategoryId.value > 0
})

const emptyStateSummary = computed(() => {
  return hasActiveFilters.value ? t("No results found") : t("Add your first term glossary to this course")
})

onMounted(async () => {
  isLoading.value = true

  await fetchCategories()
  await fetchGlossaries()
})

watch(
  () => [course.value?.id, session.value?.id],
  async () => {
    selectedCategoryId.value = 0
    await fetchCategories()
    await fetchGlossaries()
  },
)

useStudentViewRefresh(fetchGlossaries)

const debouncedSearch = debounce(() => {
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

function manageCategories() {
  if (!canEditGlossary.value) return
  router.push({
    name: "GlossaryCategories",
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
    query: {
      ...route.query,
      ...(selectedCategoryId.value > 0 ? { categoryId: selectedCategoryId.value } : {}),
    },
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
    sid: route.query.sid,
    cid: route.query.cid,
    categoryId: Number(selectedCategoryId.value || 0),
  }

  try {
    await glossaryService.exportToDocuments(postData)
    notifications.showSuccessNotification(t("Exported to documents"))
  } catch (error) {
    console.error("[Glossary] Error exporting to documents:", error)
    notifications.showErrorNotification(t("Could not export to documents"))
  }
}

const { cid, sid } = getCourseContext()

async function fetchCategories() {
  try {
    categories.value = await glossaryService.getCategories({
      cid: cid || null,
      sid: sid || null,
    })
  } catch (error) {
    console.error("[Glossary] Error fetching glossary categories:", error)
    categories.value = []
  }
}

async function fetchGlossaries() {
  isLoading.value = true

  const params = {
    "resourceNode.parent": route.query.parent || null,
    cid: cid || null,
    sid: sid || null,
    q: searchTerm.value,
    categoryId: Number(selectedCategoryId.value || 0) || null,
  }

  try {
    glossaries.value = await glossaryService.getGlossaryTerms(params)
  } catch (error) {
    console.error("[Glossary] Error fetching glossary terms:", error)
    notifications.showErrorNotification(t("Could not fetch glossary terms"))
  } finally {
    isLoading.value = false
  }
}
</script>
