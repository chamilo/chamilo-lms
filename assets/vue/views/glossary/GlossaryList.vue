<template>
  <div>
    <input type="text" v-model="searchTerm" placeholder="Search term..." />
    <ButtonToolbar v-if="isAuthenticated && isCurrentTeacher">
      <BaseButton
        label="Add new glossary term"
        icon="new_glossary_term"
        class="mb-2 mr-2"
        type="black"
        @click="addNewTerm"
      />
      <BaseButton label="Import glossary" icon="import" class="mb-2 mr-2" type="black" @click="importGlossary" />
      <BaseButton label="Export" icon="save" class="mb-2 mr-2" type="black" @click="exportGlossary" />
      <BaseButton
        :label="view === 'table' ? 'List view' : 'Table view'"
        icon="view_text"
        class="mb-2 mr-2"
        type="black"
        @click="changeView(view)"
      />
      <BaseButton
        label="Export to Documents"
        icon="export_to_documents"
        class="mb-2 mr-2"
        type="black"
        @click="exportToDocuments"
      />
    </ButtonToolbar>

    <div v-if="glossaries.length === 0">
      <!-- Render the image and create button -->
      <EmptyState icon="mdi mdi-alphabetical" summary="Add your first term glossary to this course">
        <BaseButton label="Add Glossary" class="mt-4" icon="plus" type="primary" @click="addNewTerm" />
      </EmptyState>
    </div>

    <div v-if="glossaries">
      <div v-if="view === 'list'">
        <ul>
          <li v-for="glossary in glossaries" :key="glossary.id">
            <span>{{ glossary.name }} - {{ glossary.description }}</span>
            <BaseButton label="Edit" class="mr-2" icon="edit" type="black" @click="editTerm(glossary.iid)" />
            <BaseButton label="Delete" class="mr-2" icon="delete" type="black" @click="deleteTerm(glossary.iid)" />
          </li>
        </ul>
      </div>
      <table v-else>
        <thead>
          <tr>
            <th>Title</th>
            <th>Description</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="glossary in glossaries" :key="glossary.id">
            <td>{{ glossary.name }}</td>
            <td>{{ glossary.description }}</td>
            <td>
              <BaseButton label="Edit" class="mr-2" icon="edit" type="black" @click="editTerm(glossary.iid)" />
              <BaseButton label="Delete" class="mr-2" icon="delete" type="black" @click="deleteTerm(glossary.iid)" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
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
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"
import { RESOURCE_LINK_PUBLISHED } from "../../components/resource_links/visibility"

const store = useStore()
const route = useRoute()
const router = useRouter()

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

function addNewTerm() {
  router.push({
    name: "CreateTerm",
    query: route.query,
  })
}

function editTerm(termId) {
  console.log("termId ", termId)

  router.push({
    name: "UpdateTerm",
    params: { id: termId },
    query: route.query,
  })
}

function deleteTerm(termId) {
  if (confirm("¿Delete?")) {
    axios
      .delete(`${ENTRYPOINT}glossaries/${termId}`)
      .then((response) => {
        console.log("Term deleted:", response.data)
        fetchGlossaries()
      })
      .catch((error) => {
        console.error("Error deleting term:", error)
      })
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

function exportToDocuments() {
  const postData = {
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }

  const endpoint = `${ENTRYPOINT}glossaries/export_to_documents`

  axios
    .post(endpoint, postData)
    .then((response) => {
      console.log(response.data)
    })
    .catch((error) => {
      console.error(error)
    })
}

function fetchGlossaries() {
  const params = {
    "resourceNode.parent": route.query.parent || null,
    cid: route.query.cid || null,
    sid: route.query.sid || null,
    q: searchTerm.value,
  }

  axios
    .get(ENTRYPOINT + "glossaries", { params })
    .then((response) => {
      console.log("responsedata:", response.data)
      glossaries.value = response.data

      console.log("en fetch glossaries.value", glossaries.value)
    })
    .catch((error) => {
      console.error("Error fetching links:", error)
    })
}

watch(searchTerm, () => {
  fetchGlossaries()
})

onMounted(() => {
  fetchGlossaries()
})
</script>
