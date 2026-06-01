<template>
  <Toolbar
    :handle-back="handleBack"
    :handle-reset="resetForm"
  />

  <div class="documents-layout">
    <div class="template-list-container">
      <TemplateList
        :templates="templates"
        @template-selected="addTemplateToEditor"
      />
    </div>

    <div class="documents-form-container">
      <DocumentsForm
        ref="createForm"
        :errors="errors"
        :search-enabled="searchEnabled"
        :values="item"
        @submit="onSendFormData"
      />

      <Panel
        v-if="item.filetype === 'certificate'"
        :header="$t('Certificate tags')"
        class="mt-4"
      >
        <div class="flex items-start justify-between gap-3 mb-3">
          <p class="text-sm text-gray-600">
            {{
              $t(
                "Click a tag to insert it into the editor. These placeholders will be replaced when generating the certificate.",
              )
            }}
          </p>

          <button
            class="shrink-0 px-3 py-2 rounded-lg border border-gray-25 hover:bg-gray-10 text-sm font-medium"
            type="button"
            @click="copyAllCertificateTags"
          >
            {{ $t("Copy all") }}
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <button
            v-for="tag in certificateTags"
            :key="tag"
            :title="$t('Click to insert')"
            class="text-left px-3 py-2 rounded-lg border border-gray-25 hover:border-gray-20 hover:bg-gray-10"
            type="button"
            @click="insertCertificateTag(tag)"
          >
            <code class="text-sm">{{ tag }}</code>
          </button>
        </div>
      </Panel>
    </div>
  </div>

  <Loading :visible="isLoading" />
</template>

<script setup>
import { onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import TemplateList from "../../components/documents/TemplateList.vue"
import { useDocumentCreate } from "../../composables/useDocumentCreate"
import { useCertificateTags } from "../../composables/useCertificateTags"
import { useDocumentTemplates } from "../../composables/useDocumentTemplates"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import Panel from "primevue/panel"
import { usePlatformConfig } from "../../store/platformConfig"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const platformConfigStore = usePlatformConfig()

const { isLoading, created, onSendFormData: dispatchCreate, resetForm: dispatchReset } = useDocumentCreate()

const raw = platformConfigStore.getSetting("search.search_enabled")
const searchEnabled = raw !== "false"

const createForm = ref(null)

const errors = ref({})

const allowedFiletypes = ["file", "video", "certificate"]
const filetype = allowedFiletypes.includes(route.query.filetype) ? route.query.filetype : "file"

const item = ref({
  title: "",
  contentFile: "",
  newDocument: true,
  filetype,
  parentResourceNodeId: null,
  resourceLinkList: null,
  indexDocumentContent: searchEnabled,
  searchFieldValues: {},
  ai_assisted: 0,
  ai_assisted_raw: 0,
  language: "",
})

const { certificateTags, insertCertificateTag, copyAllCertificateTags } = useCertificateTags(item)
const { templates, fetchTemplates, addTemplateToEditor } = useDocumentTemplates(item, createForm)

watch(created, (val) => {
  if (!val) {
    return
  }

  createForm.value?.clearEditorDrafts?.()
  redirectToDocumentsList()
})

item.value.parentResourceNodeId = route.params.node ?? route.params.id ?? null
// Course context (cid/sid/gid) is derived server-side from the gated session
// course; only the visibility needs to travel in the body.
item.value.resourceLinkList = JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }])

onMounted(async () => {
  await fetchTemplates()
})

function getRouteNodeId() {
  return route.params.node ?? route.params.id ?? null
}

function getDocumentsListRouteName() {
  const candidates = ["DocumentsList", "FileManagerList"]

  for (const name of candidates) {
    if (typeof router.hasRoute === "function" && router.hasRoute(name)) {
      return name
    }
  }

  return null
}

async function redirectToDocumentsList() {
  const routeName = getDocumentsListRouteName()
  const nodeId = getRouteNodeId()

  if (routeName) {
    const params = { ...route.params }

    if (params.node !== undefined) {
      params.node = nodeId
    } else if (params.id !== undefined) {
      params.id = nodeId
    } else {
      params.node = nodeId
    }

    await router.push({
      name: routeName,
      params,
      query: {
        ...route.query,
        loadNode: 1,
      },
    })

    return
  }

  router.back()
}

function normalizeBoolean(value) {
  const v = String(value ?? "")
    .trim()
    .toLowerCase()

  return ["1", "true", "yes", "on"].includes(v)
}

function normalizeAiAssistedState() {
  const currentRaw = item.value?.ai_assisted_raw
  const current = item.value?.ai_assisted
  const enabled = normalizeBoolean(currentRaw) || normalizeBoolean(current)

  item.value.ai_assisted = enabled ? 1 : 0
  item.value.ai_assisted_raw = enabled ? 1 : 0
}


function handleBack() {
  router.back()
}

function resetForm() {
  dispatchReset(createForm.value, item)
}

async function onSendFormData() {
  normalizeAiAssistedState()
  await dispatchCreate(createForm.value)
}
</script>
