<template>
  <div>
    <ButtonToolbar v-if="isAuthenticated && isCurrentTeacher">
      <BaseButton
        :label="t('Add new glossary term')"
        icon="plus"
        class="mb-2 mr-2"
        type="black"
        @click="addNewTerm"
      />
      <BaseButton
        :label="t('Import glossary')"
        icon="import"
        class="mb-2 mr-2"
        type="black"
        @click="importGlossary"
      />
      <BaseButton
        :label="t('Export glossary')"
        icon="file-export"
        class="mb-2 mr-2"
        type="black"
        @click="exportGlossary"
      />
      <BaseButton
        :label="view === 'table' ? t('List view') : t('Table view')"
        :icon="view === 'table' ? 'list' : 'table'"
        class="mb-2 mr-2"
        type="black"
        @click="changeView(view)"
      />
      <BaseButton
        :label="t('Export to Documents')"
        icon="export"
        class="mb-2 mr-2"
        type="black"
        @click="exportToDocuments"
      />
      <StudentViewButton
        class="mb-2 mr-2"
      />
    </ButtonToolbar>

    <BaseInputText
      v-model="searchTerm"
      class="mb-4"
      :label="t('Search term...')"
    />

    <div v-if="glossaries.length === 0 && searchTerm === ''">
      <!-- Render the image and create button -->
      <EmptyState
        icon="glossary"
        summary="Add your first term glossary to this course"
      >
        <BaseButton
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
        :search-term="searchTerm"
        @edit="editTerm($event)"
        @delete="confirmDeleteTerm($event)"
      />
      <GlossaryTermTable
        v-else
        :glossaries="glossaries"
        :search-term="searchTerm"
        @edit="editTerm($event)"
        @delete="confirmDeleteTerm($event)"
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
import ButtonToolbar from "../../components/basecomponents/ButtonToolbar.vue"
import { computed, onMounted, ref, watch } from "vue"
import { useStore } from "vuex"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import GlossaryTermList from "../../components/glossary/GlossaryTermList.vue"
import GlossaryTermTable from "../../components/glossary/GlossaryTermTable.vue"
import { useCidReq } from "../../composables/cidReq"
import glossaryService from "../../services/glossaryService"
import { useNotification } from "../../composables/notification"
import BaseDialogDelete from "../../components/basecomponents/BaseDialogDelete.vue"
import StudentViewButton from "../../components/StudentViewButton.vue";

const store = useStore()
const route = useRoute()
const router = useRouter()
const notifications = useNotification()

const { t } = useI18n()

const searchTerm = ref("")
const parentResourceNodeId = ref(Number(route.params.node))

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ])
)

const isAuthenticated = computed(() => store.getters["security/isAuthenticated"])
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])

const glossaries = ref([])
const view = ref("list")

const isDeleteItemDialogVisible = ref(false)
const termToDelete = ref(null)
const termToDeleteString = computed(() => {
  if (termToDelete.value === null) {
    return ''
  }
  return termToDelete.value.name
})

watch(searchTerm, () => {
  fetchGlossaries()
})

onMounted(() => {
  fetchGlossaries()
})

function addNewTerm() {
  router.push({
    name: "CreateTerm",
    query: route.query,
  })
}

function editTerm(term) {
  router.push({
    name: "UpdateTerm",
    params: { id: term.iid },
    query: route.query,
  })
}

async function confirmDeleteTerm(term) {
  termToDelete.value = term
  isDeleteItemDialogVisible.value = true
}

async function deleteTerm() {
  try {
    await glossaryService.deleteTerm(termToDelete.value.iid)
    notifications.showSuccessNotification(t("Term deleted"))
    termToDelete.value = null
    isDeleteItemDialogVisible.value = false
    await fetchGlossaries()
  } catch (error) {
    console.error("Error deleting term:", error)
    notifications.showErrorNotification(t("Could not delete term"))
  }
}

function importGlossary() {
  router.push({
    name: "ImportGlossary",
    query: route.query,
  })
}

function exportGlossary() {
  router.push({
    name: "ExportGlossary",
    query: route.query,
  })
}

function changeView(newView) {
  // Handle changing the view (e.g., table view)
  view.value = newView === "table" ? "list" : "table"
}

async function exportToDocuments() {
  const postData = {
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }

  try {
    await glossaryService.exportToDocuments(postData)
    notifications.showSuccessNotification(t("Exported to documents"))
  } catch (error) {
    console.error("Error fetching links:", error)
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
    console.error("Error fetching links:", error)
    notifications.showErrorNotification(t("Could not fetch glossary terms"))
  }
}
</script>
