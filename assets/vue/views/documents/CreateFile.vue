<template>
  <Toolbar
    :handle-back="handleBack"
    :handle-reset="resetForm"
  />

  <!-- Quota warning banner -->
  <div
    v-if="quotaWarningMessage"
    class="mb-4 rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-900"
    role="alert"
  >
    {{ quotaWarningMessage }}
  </div>

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
import { useNotification } from "../../composables/notification"
import { useCertificateTags } from "../../composables/useCertificateTags"
import { useDocumentTemplates } from "../../composables/useDocumentTemplates"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import Panel from "primevue/panel"
import { usePlatformConfig } from "../../store/platformConfig"
import documentsService from "../../services/documents"

const QUOTA_WARNING_THRESHOLD_PERCENT = 2

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const { showSuccessNotification } = useNotification()

const { isLoading, created, onSendFormData: dispatchCreate, resetForm: dispatchReset } = useDocumentCreate()

const raw = platformConfigStore.getSetting("search.search_enabled")
const searchEnabled = raw !== "false"

const createForm = ref(null)

const errors = ref({})
const quotaWarningMessage = ref("")

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
})

const { certificateTags, insertCertificateTag, copyAllCertificateTags } = useCertificateTags(item)
const { templates, fetchTemplates, addTemplateToEditor } = useDocumentTemplates(item, createForm)

watch(created, (val) => {
  if (!val) {
    return
  }

  redirectToDocumentsList()
})

item.value.parentResourceNodeId = route.params.node ?? route.params.id ?? null
item.value.resourceLinkList = JSON.stringify([
  {
    gid: route.query.gid,
    sid: route.query.sid,
    cid: route.query.cid,
    visibility: RESOURCE_LINK_PUBLISHED,
  },
])

onMounted(async () => {
  await fetchTemplates()
  await showQuotaWarningIfNeeded()
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

function toInt(value, fallback = 0) {
  const n = Number(value)

  return Number.isFinite(n) ? n : fallback
}

async function showQuotaWarningIfNeeded() {
  const courseId = toInt(route.query.cid, 0)

  if (!courseId) {
    return
  }

  const sid = toInt(route.query.sid, 0)
  const gid = toInt(route.query.gid, 0)

  try {
    const msg = await documentsService.fetchQuotaWarningMessage(t, courseId, {
      sid,
      gid,
      force: true,
      thresholdPercent: QUOTA_WARNING_THRESHOLD_PERCENT,
    })

    if (msg) {
      quotaWarningMessage.value = msg
      showSuccessNotification(msg)
    }
  } catch (e) {
    console.error("[DocumentsCreateFile] Failed to show quota warning:", e)
  }
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
