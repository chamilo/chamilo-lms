<template>
  <div v-if="!isLoading && item && canEditItem">
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
          ref="updateForm"
          :errors="violations"
          :search-enabled="isSearchEnabled"
          :values="item"
          @submit="onSendFormData"
        >
          <BaseCheckbox
            v-if="isCurrentTeacher"
            id="ai-assisted-flag"
            v-model="aiAssistedFlag"
            label="AI-assisted"
            name="ai_assited"
          />

          <EditLinks
            v-model="item"
            :show-share-with-user="false"
            links-type="users"
          />
        </DocumentsForm>

        <Panel
          v-if="filetype === 'certificate'"
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
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { useDocumentUpdate } from "../../composables/useDocumentUpdate"
import { useCertificateTags } from "../../composables/useCertificateTags"
import { useDocumentTemplates } from "../../composables/useDocumentTemplates"
import DocumentsForm from "../../components/documents/FormNewDocument.vue"
import Loading from "../../components/Loading.vue"
import Toolbar from "../../components/Toolbar.vue"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import TemplateList from "../../components/documents/TemplateList.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import Panel from "primevue/panel"

const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })
const {
  item,
  isLoading,
  violations,
  retrieve,
  onSendFormData: dispatchSendFormData,
  resetForm: dispatchResetForm,
} = useDocumentUpdate()

const updateForm = ref(null)
const aiAssistedFlag = ref(false)

const isSearchEnabled = computed(() => "false" !== platformConfigStore.getSetting("search.search_enabled"))

const allowedFiletypes = ["file", "certificate", "video"]
const filetype = allowedFiletypes.includes(route.query.filetype) ? route.query.filetype : "file"

const { certificateTags, insertCertificateTag, copyAllCertificateTags } = useCertificateTags(item)
const { templates, fetchTemplates, addTemplateToEditor } = useDocumentTemplates(item, updateForm)

const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher || isAllowedToEdit.value)

const canEditItem = computed(() => {
  const resourceLink = item.value?.resourceLinkListFromEntity?.[0]
  const sidFromResourceLink = resourceLink?.session?.["@id"]
  const sid = String(route.query.sid ?? "0")

  return (
    (sidFromResourceLink && sidFromResourceLink === `/api/sessions/${sid}` && isAllowedToEdit.value) ||
    isCurrentTeacher.value
  )
})

watch(
  item,
  (val) => {
    if (!val || typeof val !== "object") return
    const raw = val.ai_assisted_raw ?? val.ai_assisted
    aiAssistedFlag.value = raw === true || raw === 1 || raw === "1"
  },
  { immediate: true },
)

onMounted(() => {
  fetchTemplates()
  retrieve()

  if (item.value && typeof item.value === "object" && !item.value.searchFieldValues) {
    item.value.searchFieldValues = {}
  }
})

function handleBack() {
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
  const enabled = aiAssistedFlag.value || normalizeBoolean(currentRaw) || normalizeBoolean(current)

  item.value.ai_assisted = enabled ? 1 : 0
  item.value.ai_assisted_raw = enabled ? 1 : 0
  aiAssistedFlag.value = enabled
}

function onSendFormData() {
  normalizeAiAssistedState()
  dispatchSendFormData(updateForm.value)
}

function resetForm() {
  dispatchResetForm(updateForm.value)
}
</script>
