<template>
  <div>
    <div class="flex items-center justify-between">
      <BaseIcon
        icon="back"
        size="big"
        @click="goBack"
        :title="t('Back')"
      />
    </div>
    <hr />
    <h1 class="text-2xl font-bold">{{ t("Add documents") }} - {{ publicationTitle }}</h1>

    <div class="m-4">
      <h2 class="text-xl font-semibold mb-2">{{ t("Documents added") }}</h2>
      <div v-if="addedDocuments.length">
        <div
          v-for="doc in addedDocuments"
          :key="doc.document.iid"
          class="flex items-center justify-between bg-gray-100 p-2 rounded mb-2"
        >
          <span>{{ doc.document.title }}</span>
          <BaseButton
            type="danger"
            size="small"
            :label="t('Remove')"
            @click="removeDocument(doc.iid)"
            icon=""
          />
        </div>
      </div>
      <p
        v-else
        class="text-gray-500"
      >
        {{ t("No documents added yet.") }}
      </p>
    </div>

    <div>
      <h2 class="text-xl font-semibold mb-2">{{ t("Available documents") }}</h2>
      <div v-if="availableDocuments.length">
        <div
          v-for="doc in availableDocuments"
          :key="doc.iid"
          class="flex items-center justify-between bg-white border p-2 rounded mb-2"
        >
          <span>{{ doc.title }}</span>
          <BaseButton
            type="primary"
            size="small"
            :label="t('Add')"
            @click="addDocument(doc.iid)"
            icon=""
          />
        </div>
      </div>
      <p
        v-else
        class="text-gray-500"
      >
        {{ t("No available documents.") }}
      </p>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import axios from "axios"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useRoute, useRouter } from "vue-router"
import { useNotification } from "../../composables/notification"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const notification = useNotification()
const { cid, sid, gid } = useCidReq()

const publicationId = parseInt(route.params.id, 10)
const publicationTitle = ref("")
const addedDocuments = ref([])
const availableDocuments = ref([])
const parentResourceNodeId = ref(null)

function buildCidParams() {
  return {
    cid,
    ...(sid && { sid }),
    ...(gid && { gid }),
  }
}

onMounted(() => {
  loadPublicationMetadata()
  loadAddedDocuments()
})

function extractIdFromIri(iri) {
  return parseInt(iri?.split("/").pop(), 10)
}

async function loadPublicationMetadata() {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publications/${publicationId}`, {
      params: buildCidParams(),
    })
    const data = response.data
    publicationTitle.value = data.title

    parentResourceNodeId.value = extractIdFromIri(data.resourceLinkListFromEntity?.[0]?.course?.resourceNode?.["@id"])

    if (parentResourceNodeId.value) {
      await loadAvailableDocuments()
    }
  } catch (e) {
    console.error("Error loading publication metadata", e)
  }
}

async function loadAddedDocuments() {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publication_rel_documents`, {
      params: {
        ...buildCidParams(),
        publication: `/api/c_student_publications/${publicationId}`,
      },
    })
    addedDocuments.value = response.data["hydra:member"]
  } catch (e) {
    console.error("Error loading added documents", e)
  }
}

async function loadAvailableDocuments() {
  try {
    const response = await axios.get(`${ENTRYPOINT}documents`, {
      params: {
        "resourceNode.parent": parentResourceNodeId.value,
        "filetype[]": ["file"],
        loadNode: 1,
        ...buildCidParams(),
      },
    })
    availableDocuments.value = response.data["hydra:member"]
  } catch (e) {
    console.error("Error loading available documents", e)
  }
}

async function addDocument(documentId) {
  try {
    await axios.post(
      `${ENTRYPOINT}c_student_publication_rel_documents`,
      {
        publication: `/api/c_student_publications/${publicationId}`,
        document: `/api/documents/${documentId}`,
      },
      {
        params: buildCidParams(),
      },
    )
    notification.showSuccessNotification(t("Document added"))
    await loadAddedDocuments()
  } catch (e) {
    notification.showErrorNotification(t("Error adding document"))
  }
}

async function removeDocument(relId) {
  try {
    await axios.delete(`${ENTRYPOINT}c_student_publication_rel_documents/${relId}`, {
      params: buildCidParams(),
    })
    await loadAddedDocuments()
    notification.showSuccessNotification(t("Document removed"))
  } catch (e) {
    notification.showErrorNotification(t("Error removing document"))
  }
}

function goBack() {
  router.push({
    name: "AssignmentDetail",
    params: { id: publicationId },
    query: route.query,
  })
}
</script>
