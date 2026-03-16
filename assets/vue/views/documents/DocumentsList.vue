<template>
  <SectionHeader
    v-if="securityStore.isAuthenticated"
    :title="t('Documents')"
  >
    <BaseButton
      v-if="showNewCertificateButton"
      :label="t('Create certificate')"
      icon="file-add"
      only-icon
      type="black"
      @click="goToNewDocument"
    />
    <BaseButton
      v-if="showUploadCertificateButton"
      :label="t('Upload')"
      icon="file-upload"
      only-icon
      type="black"
      @click="goToUploadFile"
    />

    <BaseButton
      v-if="showBackButtonIfNotRootFolder"
      :label="t('Back')"
      icon="back"
      only-icon
      type="gray"
      @click="back"
    />
    <BaseButton
      v-if="showNewDocumentButton"
      :label="t('New document')"
      icon="file-add"
      only-icon
      type="success"
      @click="goToNewDocument"
    />
    <BaseButton
      v-if="showUploadButton"
      :label="t('Upload')"
      icon="file-upload"
      only-icon
      type="success"
      @click="goToUploadFile"
    />
    <BaseButton
      v-if="showNewFolderButton"
      :label="t('New folder')"
      icon="folder-plus"
      only-icon
      type="success"
      @click="openNew"
    />
    <BaseButton
      v-if="showNewDrawingButton"
      :label="t('New drawing')"
      icon="drawing"
      only-icon
      type="success"
    />
    <BaseButton
      v-if="showRecordAudioButton"
      :label="t('Record audio')"
      icon="record-add"
      only-icon
      type="success"
      @click="showRecordAudioDialog"
    />
    <BaseButton
      v-if="showNewCloudFileButton"
      :label="t('New cloud file')"
      icon="file-cloud-add"
      only-icon
      type="success"
    />
    <BaseButton
      v-if="showSlideshowButton"
      :disabled="!hasImageInDocumentEntries"
      :label="t('Slideshow')"
      icon="view-gallery"
      only-icon
      type="black"
      @click="showSlideShowWithFirstImage"
    />
    <BaseButton
      v-if="showUsageButton"
      :label="t('Usage')"
      icon="usage"
      only-icon
      type="black"
      @click="showUsageDialog"
    />
    <BaseButton
      v-if="showDownloadAllButton"
      :label="t('Download all')"
      icon="download"
      only-icon
      type="primary"
    />
    <BaseButton
      v-if="showGenerateMediaButton"
      :label="t('Generate media')"
      icon="robot"
      only-icon
      type="black"
      @click="goToGenerateMedia"
    />
  </SectionHeader>

  <BaseTable
    v-model:filters="filters"
    v-model:selected-items="selectedItems"
    :global-filter-fields="['resourceNode.title', 'resourceNode.updatedAt']"
    :is-loading="tableIsLoading"
    v-model:rows="options.itemsPerPage"
    :total-items="totalItems"
    :values="items"
    data-key="iid"
    lazy
    @page="onPage"
    @sort="sortingChanged"
  >
    <Column
      v-if="isCurrentTeacher"
      :exportable="false"
      selection-mode="multiple"
    />

    <Column
      :header="t('Title')"
      :sortable="true"
      field="resourceNode.title"
    >
      <template #body="slotProps">
        <div style="display: flex; align-items: center">
          <DocumentEntry
            v-if="slotProps.data"
            :data="slotProps.data"
          />

          <!-- AI badge at the end of the title -->
          <span
            v-if="slotProps.data?.ai_assisted"
            class="ml-2 inline-flex items-center gap-1 rounded-full border border-gray-300 bg-gray-10 px-2 py-[2px] text-xs text-gray-700"
            title="AI-assisted"
            aria-label="AI-assisted"
          >
            <span aria-hidden="true">🤖</span>
            <span class="font-semibold">AI</span>
          </span>

          <BaseIcon
            v-if="isAllowedToEdit && isSessionDocument(slotProps.data)"
            class="mr-8"
            icon="session-star"
          />
        </div>
      </template>
    </Column>

    <Column
      :header="t('Size')"
      :sortable="true"
      field="resourceNode.firstResourceFile.size"
    >
      <template #body="slotProps">
        {{
          slotProps.data.resourceNode && slotProps.data.resourceNode.firstResourceFile
            ? prettyBytes(slotProps.data.resourceNode.firstResourceFile.size)
            : ""
        }}
      </template>
    </Column>

    <Column
      :header="t('Modified')"
      :sortable="true"
      field="resourceNode.updatedAt"
    >
      <template #body="slotProps">
        {{ relativeDatetime(slotProps.data.resourceNode.updatedAt) }}
      </template>
    </Column>

    <Column :exportable="false">
      <template #body="slotProps">
        <div class="flex flex-row justify-end gap-2">
          <BaseButton
            v-if="canEdit(slotProps.data)"
            :title="t('Move')"
            icon="folder-move"
            size="small"
            type="secondary"
            @click="openMoveDialog(slotProps.data)"
          />
          <BaseButton
            v-if="canEdit(slotProps.data)"
            :disabled="!(slotProps.data.filetype === 'file' || slotProps.data.filetype === 'video')"
            :title="getReplaceButtonTitle(slotProps.data)"
            icon="file-swap"
            size="small"
            type="secondary"
            @click="
              (slotProps.data.filetype === 'file' || slotProps.data.filetype === 'video') &&
              openReplaceDialog(slotProps.data)
            "
          />
          <BaseButton
            :title="t('Information')"
            icon="information"
            size="small"
            type="primary"
            @click="btnShowInformationOnClick(slotProps.data)"
          />
          <BaseButton
            v-if="showOnlyofficeButton(slotProps.data)"
            :title="getOnlyofficeButtonTitle(slotProps.data)"
            icon="onlyoffice"
            size="small"
            type="secondary"
            @click="openWithOnlyoffice(slotProps.data)"
          />
          <BaseButton
            v-if="showAiFeedbackButton(slotProps.data)"
            :disabled="aiFeedbackLoading"
            :title="aiFeedbackLoading ? t('In progress') : t('Get AI feedback')"
            icon="robot"
            size="small"
            type="secondary"
            @click="openAiFeedback(slotProps.data)"
          />
          <BaseButton
            v-if="canEdit(slotProps.data)"
            :icon="getVisibilityIcon(slotProps.data)"
            :title="t('Visibility')"
            size="small"
            type="secondary"
            @click="btnChangeVisibilityOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data) && allowAccessUrlFiles && isFile(slotProps.data) && securityStore.isAdmin"
            icon="file-replace"
            size="small"
            type="secondary"
            :title="t('Add file variation')"
            @click="goToAddVariation(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data)"
            :title="t('Edit')"
            icon="edit"
            size="small"
            type="secondary"
            @click="btnEditOnClick(slotProps.data)"
          />

          <BaseButton
            v-if="canEdit(slotProps.data)"
            :title="t('Delete')"
            icon="delete"
            size="small"
            type="danger"
            @click="confirmDeleteItem(slotProps.data)"
          />
          <BaseButton
            v-if="isCertificateMode && canEdit(slotProps.data)"
            :class="{ selected: slotProps.data.iid === defaultCertificateId }"
            :icon="slotProps.data.iid === defaultCertificateId ? 'certificate-selected' : 'certificate-not-selected'"
            :title="t('Set as default certificate')"
            size="small"
            type="slotProps.data.iid === defaultCertificateId ? 'success' : 'black'"
            @click="selectAsDefaultCertificate(slotProps.data)"
          />
          <BaseButton
            v-if="
              securityStore.isAuthenticated && isCurrentTeacher && isHtmlFile(slotProps.data) && canEdit(slotProps.data)
            "
            :icon="getTemplateIcon(slotProps.data.iid)"
            :title="t('Template options')"
            size="small"
            type="secondary"
            @click="openTemplateForm(slotProps.data.iid)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>

  <BaseToolbar
    v-if="securityStore.isAuthenticated && isCurrentTeacher"
    show-top-border
  >
    <BaseButton
      :label="t('Select all')"
      icon="select-all"
      type="primary"
      @click="selectAll"
    />
    <BaseButton
      :label="t('Unselect all')"
      icon="unselect-all"
      type="primary"
      @click="unselectAll"
    />
    <BaseButton
      :disabled="!selectedItems || !selectedItems.length"
      :label="t('Delete selected')"
      icon="delete"
      type="danger"
      @click="showDeleteMultipleDialog"
    />
    <BaseButton
      :disabled="isDownloading || !selectedItems || !selectedItems.length"
      :label="isDownloading ? t('In progress') : t('Download selected items as ZIP')"
      icon="download"
      type="primary"
      @click="downloadSelectedItems"
    />
  </BaseToolbar>

  <BaseDialogConfirmCancel
    v-model:is-visible="isMoveDialogVisible"
    :title="t('Move document')"
    @confirm-clicked="moveDocument"
    @cancel-clicked="isMoveDialogVisible = false"
  >
    <p>{{ t("Select the destination folder") }}</p>
    <Dropdown
      v-model="selectedFolder"
      :options="folders"
      :placeholder="t('Select a folder')"
      optionLabel="label"
      optionValue="value"
    />
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isNewFolderDialogVisible"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('New folder')"
    @confirm-clicked="createNewFolder"
    @cancel-clicked="hideNewFolderDialog"
  >
    <div class="p-float-label">
      <InputText
        id="title"
        v-model.trim="item.title"
        :class="{ 'p-invalid': submitted && !item.title }"
        autocomplete="off"
        autofocus
        name="title"
        required="true"
      />
      <label
        v-text="t('Name')"
        for="title"
      />
    </div>
    <small
      v-if="submitted && !item.title"
      v-text="t('Title is required')"
      class="p-error"
    />
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteItemDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteSingleItem"
    @cancel-clicked="isDeleteItemDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      <span v-if="item">{{ t("Are you sure you want to delete {0}?", [item.title]) }}</span>
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteMultipleDialogVisible"
    :title="t('Confirm')"
    @confirm-clicked="deleteMultipleItems"
    @cancel-clicked="isDeleteMultipleDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      <span v-if="item">{{ t("Are you sure you want to delete the selected items?") }}</span>
    </div>
  </BaseDialogConfirmCancel>

  <BaseDialogConfirmCancel
    v-model:is-visible="isReplaceDialogVisible"
    :title="t('Replace file')"
    @confirm-clicked="replaceDocument"
    @cancel-clicked="isReplaceDialogVisible = false"
  >
    <BaseFileUpload
      id="replace-file"
      :label="t('Select replacement file')"
      accept="*/*"
      model-value="selectedReplaceFile"
      @file-selected="selectedReplaceFile = $event"
    />
  </BaseDialogConfirmCancel>

  <BaseDialog
    v-model:is-visible="isFileUsageDialogVisible"
    :style="{ width: '28rem' }"
    :title="t('Space available')"
  >
    <div
      v-if="usageQuotaSummary"
      class="mb-3 rounded border border-gray-200 bg-gray-10 p-3"
    >
      <div class="text-sm font-semibold">
        {{ usageQuotaSummary.limiterLabel }}
      </div>

      <div class="mt-1 text-xs opacity-80">
        {{ usageQuotaSummary.remainingLabel }}
      </div>

      <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
        <div>
          <span class="font-semibold">{{ t("Course") }}:</span>
          {{ usageQuotaSummary.courseLine }}
        </div>
        <div>
          <span class="font-semibold">{{ t("Documents") }}:</span>
          {{ usageQuotaSummary.documentsLine }}
        </div>
      </div>
    </div>

    <BaseChart :data="usageData" />
  </BaseDialog>

  <BaseDialog
    v-model:is-visible="isRecordAudioDialogVisible"
    :style="{ width: '28rem' }"
    :title="t('Record audio')"
    header-icon="record-add"
  >
    <DocumentAudioRecorder
      :parent-resource-node-id="route.params.node"
      @document-saved="recordedAudioSaved"
      @document-not-saved="recordedAudioNotSaved"
    />
  </BaseDialog>

  <!-- AI feedback dialog -->
  <BaseDialogConfirmCancel
    v-model:is-visible="isAiFeedbackDialogVisible"
    :title="t('Get AI feedback')"
    :confirm-label="aiFeedbackLoading ? t('In progress') : t('Get AI feedback')"
    :cancel-label="t('Close')"
    @confirm-clicked="runAiFeedback"
    @cancel-clicked="closeAiFeedbackDialog"
  >
    <div class="space-y-3">
      <div class="text-sm">
        <div class="font-semibold">
          {{ aiFeedbackDocTitle }}
        </div>
        <div class="opacity-70">
          {{ aiFeedbackCourseTitle }}
        </div>
      </div>

      <!-- Provider selector (this is what you were missing in UI) -->
      <div class="space-y-1">
        <div class="text-sm font-semibold">AI provider</div>

        <select
          v-model="aiFeedbackProvider"
          class="w-full rounded border border-gray-300 p-2 text-sm"
          :disabled="aiFeedbackLoading || aiFeedbackSaving || aiFeedbackProviderOptions.length === 0"
        >
          <option
            v-for="p in aiFeedbackProviderOptions"
            :key="p.key"
            :value="p.key"
          >
            {{ p.label }}
          </option>
        </select>

        <div
          v-if="aiFeedbackProviderOptions.length === 0"
          class="text-xs text-red-600"
        >
          No compatible AI providers available for this file type.
        </div>

        <div class="text-xs opacity-70">
          <span v-if="aiFeedbackMode === 'pdf'">Mode: PDF (document processing)</span>
          <span v-else-if="aiFeedbackMode === 'txt'">Mode: TXT (text processing)</span>
          <span v-else>Mode: unsupported</span>
        </div>
      </div>

      <div class="space-y-1">
        <div class="text-sm font-semibold">Prompt</div>
        <textarea
          v-model="aiFeedbackPrompt"
          class="w-full rounded border border-gray-300 p-2 text-sm"
          rows="4"
          placeholder="Write your question for the AI..."
          :disabled="aiFeedbackLoading || aiFeedbackSaving"
        />
      </div>

      <div class="flex flex-row gap-2">
        <BaseButton
          v-if="aiFeedbackAnswer"
          :disabled="aiFeedbackLoading || aiFeedbackSaving"
          :label="t('Copy answer to clipboard')"
          icon="copy"
          type="secondary"
          @click="copyAiFeedbackToClipboard"
        />
        <BaseButton
          v-if="aiFeedbackAnswer"
          :disabled="aiFeedbackSaving || aiFeedbackLoading"
          :label="aiFeedbackSaving ? t('In progress') : t('Save answer to my inbox')"
          icon="save"
          type="primary"
          @click="saveAiFeedbackToInbox"
        />
      </div>

      <div
        v-if="aiFeedbackAnswer"
        class="rounded border border-gray-200 bg-gray-10 p-3"
      >
        <div class="text-sm font-semibold mb-2">Answer</div>
        <div class="whitespace-pre-wrap text-sm">
          {{ aiFeedbackAnswer }}
        </div>
      </div>

      <div
        v-if="aiFeedbackError"
        class="text-sm text-red-600"
      >
        {{ aiFeedbackError }}
      </div>
    </div>
  </BaseDialogConfirmCancel>
  <BaseDialogConfirmCancel
    v-model:is-visible="showTemplateFormModal"
    :cancel-label="t('Cancel')"
    :confirm-label="t('Save')"
    :title="t('Add as a template')"
    @confirm-clicked="submitTemplateForm"
    @cancel-clicked="showTemplateFormModal = false"
  >
    <form @submit.prevent="submitTemplateForm">
      <div class="p-float-label">
        <InputText
          id="templateTitle"
          v-model.trim="templateFormData.title"
          class="form-control"
          required
        />
        <label
          v-text="t('Name')"
          for="templateTitle"
        />
      </div>
      <small
        v-if="submitted && !templateFormData.title"
        v-text="t('Title is required')"
        class="p-error"
      />
      <BaseFileUpload
        id="post-file"
        :label="t('File upload')"
        accept="image"
        model-value=""
        size="small"
        @file-selected="selectedFile = $event"
      />
    </form>
  </BaseDialogConfirmCancel>
  <BaseDialogConfirmCancel
    v-model:is-visible="isDeleteWarningLpDialogVisible"
    :title="t('Confirm deletion')"
    @confirm-clicked="forceDeleteItem"
    @cancel-clicked="isDeleteWarningLpDialogVisible = false"
  >
    <div class="confirmation-content">
      <BaseIcon
        class="mr-2"
        icon="alert"
        size="big"
      />
      <p class="mb-2">
        {{ t("The following documents are used in learning paths:") }}
      </p>
      <ul class="pl-4 mb-4">
        <li
          v-for="lp in lpListWarning"
          :key="lp.lpId + lp.documentTitle"
        >
          <b>{{ lp.documentTitle }}</b> → {{ lp.lpTitle }}
        </li>
      </ul>
      <p class="mt-4 font-semibold">
        {{ t("Do you still want to delete them?") }}
      </p>
    </div>
  </BaseDialogConfirmCancel>
</template>

<script setup>
import { useStore } from "vuex"
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { isEmpty } from "lodash"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { computed, nextTick, onMounted, ref, unref, watch } from "vue"
import { useCidReq } from "../../composables/cidReq"
import { useDatatableList } from "../../composables/datatableList"
import { useFormatDate } from "../../composables/formatDate"
import axios from "axios"
import baseService from "../../services/baseService"
import DocumentEntry from "../../components/documents/DocumentEntry.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import { useFileUtils } from "../../composables/fileUtils"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseChart from "../../components/basecomponents/BaseChart.vue"
import DocumentAudioRecorder from "../../components/documents/DocumentAudioRecorder.vue"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"
import prettyBytes from "pretty-bytes"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import { useDocumentActionButtons } from "../../composables/document/documentActionButtons"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { usePlatformConfig } from "../../store/platformConfig"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useCourseSettings } from "../../store/courseSettingStore"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const store = useStore()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()
const courseSettingsStore = useCourseSettings()
const platformConfigStore = usePlatformConfig()
const { t, locale } = useI18n()
const notification = useNotification()

const aiHelpersEnabled = computed(() => {
  return String(platformConfigStore.getSetting("ai_helpers.enable_ai_helpers")) === "true"
})

const imageGeneratorEnabled = computed(() => {
  return String(courseSettingsStore?.getSetting?.("image_generator")) === "true"
})

const videoGeneratorEnabled = computed(() => {
  return String(courseSettingsStore?.getSetting?.("video_generator")) === "true"
})

const contentAnalyzerEnabled = computed(() => {
  const v = courseSettingsStore?.getSetting?.("content_analyzer")
  if (v === null || v === undefined || v === "") return true
  return String(v) === "true"
})

const allowAccessUrlFiles = computed(
  () => "false" !== platformConfigStore.getSetting("document.access_url_specific_files"),
)

const { filters, options, onUpdateOptions, deleteItem } = useDatatableList("Documents")
const { cid, sid, gid } = useCidReq()
const { isImage, isHtml, isFile } = useFileUtils()
const { relativeDatetime } = useFormatDate()
const isAllowedToEdit = ref(false)
const folders = ref([])
const selectedFolder = ref(null)
const isDownloading = ref(false)
const isDeleteWarningLpDialogVisible = ref(false)
const lpListWarning = ref([])

const {
  showNewDocumentButton,
  showUploadButton,
  showNewFolderButton,
  showNewDrawingButton,
  showRecordAudioButton,
  showNewCloudFileButton,
  showSlideshowButton,
  showUsageButton,
  showDownloadAllButton,
  showNewCertificateButton,
  showUploadCertificateButton,
} = useDocumentActionButtons()

const item = ref({})
const usageData = ref({})

const isNewFolderDialogVisible = ref(false)
const isDeleteItemDialogVisible = ref(false)
const isDeleteMultipleDialogVisible = ref(false)
const isFileUsageDialogVisible = ref(false)
const isRecordAudioDialogVisible = ref(false)

const submitted = ref(false)
const isMoveDialogVisible = ref(false)

filters.value.loadNode = 1

const selectedItems = ref([])

/**
 * -----------------------------------------
 * Visibility helpers (safe access)
 * -----------------------------------------
 */
function getPrimaryResourceLink(doc) {
  const links = doc?.resourceLinkListFromEntity
  if (!Array.isArray(links) || links.length === 0) {
    return null
  }
  return links[0] || null
}

function getPrimaryVisibility(doc) {
  const rl = getPrimaryResourceLink(doc)
  if (!rl) return null
  const v = Number(rl.visibility)
  return Number.isFinite(v) ? v : null
}

function getVisibilityIcon(doc) {
  const v = getPrimaryVisibility(doc)
  if (v === RESOURCE_LINK_PUBLISHED) return "eye-on"
  if (v === RESOURCE_LINK_DRAFT) return "eye-off"
  return ""
}

const rawItems = computed(() => store.getters["documents/getRecents"] || [])

/**
 * Student view / learner visibility filter.
 * Note: We keep all existing behaviors and only filter the UI list for learners/student-view.
 */
function isVisibleForLearner(doc) {
  const links = doc?.resourceLinkListFromEntity
  if (!Array.isArray(links) || links.length === 0) return true
  return links.some((rl) => Number(rl?.visibility) === RESOURCE_LINK_PUBLISHED)
}

const items = computed(() => {
  const list = rawItems.value

  // In student view, behave like learner even if logged as teacher.
  if (platformConfigStore.isStudentViewActive) {
    return list.filter(isVisibleForLearner)
  }

  // For real learners, also keep only published.
  if (!securityStore.isCurrentTeacher) {
    return list.filter(isVisibleForLearner)
  }

  return list
})

const isLoading = computed(() => store.getters["documents/isLoading"])
const totalItems = computed(() => store.getters["documents/getTotalItems"])
const resourceNode = computed(() => store.getters["resourcenode/getResourceNode"])

const hasImageInDocumentEntries = computed(() => {
  return items.value.find((i) => isImage(i)) !== undefined
})

const isCertificateMode = computed(() => {
  return route.query.filetype === "certificate"
})

const defaultCertificateId = ref(null)

const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher && !platformConfigStore.isStudentViewActive)

const onlyofficePluginEnabled = computed(() => {
  return platformConfigStore.plugins?.onlyoffice?.enabled === true
})

const onlyofficeEditorPath = computed(() => {
  return String(platformConfigStore.plugins?.onlyoffice?.editorPath || "/plugin/Onlyoffice/editor.php")
})

const onlyofficeSupportedExtensions = new Set([
  "doc",
  "docx",
  "odt",
  "rtf",
  "txt",
  "xls",
  "xlsx",
  "ods",
  "csv",
  "ppt",
  "pptx",
  "odp",
  "pdf",
])

const onlyofficeViewOnlyExtensions = new Set(["pdf"])

function getOnlyofficeFileName(doc) {
  return String(doc?.resourceNode?.firstResourceFile?.originalName || doc?.title || "").trim()
}

function getOnlyofficeExtension(doc) {
  const fileName = getOnlyofficeFileName(doc).toLowerCase()
  const parts = fileName.split(".")
  if (parts.length < 2) {
    return ""
  }

  return String(parts.pop() || "").trim()
}

function isOnlyofficeSupportedDocument(doc) {
  if (!doc) {
    return false
  }

  const filetype = String(doc?.filetype || "")
    .trim()
    .toLowerCase()

  if (!["file", "certificate"].includes(filetype)) {
    return false
  }

  const resourceFileId = doc?.resourceNode?.firstResourceFile?.id
  if (!resourceFileId) {
    return false
  }

  const ext = getOnlyofficeExtension(doc)
  if (!ext) {
    return false
  }

  return onlyofficeSupportedExtensions.has(ext)
}

function isOnlyofficeViewOnly(doc) {
  const ext = getOnlyofficeExtension(doc)
  return onlyofficeViewOnlyExtensions.has(ext)
}

function showOnlyofficeButton(doc) {
  return securityStore.isAuthenticated && onlyofficePluginEnabled.value && isOnlyofficeSupportedDocument(doc)
}

function getOnlyofficeButtonTitle() {
  return t("Open with ONLYOFFICE")
}

function buildOnlyofficeUrl(doc) {
  const url = new URL(onlyofficeEditorPath.value, window.location.origin)

  url.searchParams.set("cid", String(unref(cid) || 0))
  url.searchParams.set("sid", String(unref(sid) || 0))
  const currentGroupId = Number(unref(gid) || 0)
  if (currentGroupId > 0) {
    url.searchParams.set("groupId", String(currentGroupId))
  }
  url.searchParams.set("docId", String(doc.iid))
  url.searchParams.set("returnUrl", window.location.href)

  if (isOnlyofficeViewOnly(doc) || !canEdit(doc)) {
    url.searchParams.set("readOnly", "1")
  }

  return url.toString()
}

function openWithOnlyoffice(doc) {
  const url = buildOnlyofficeUrl(doc)
  window.open(url, "_blank", "noopener,noreferrer")
}
/**
 * Local loading flag to show the table spinner immediately.
 * This prevents the "empty table" impression while the store is still preparing the request.
 */
const tableLoading = ref(true)
const hasRequestedList = ref(false)

const tableIsLoading = computed(() => {
  return tableLoading.value || isLoading.value
})

function triggerTableLoad() {
  // Make sure spinner appears immediately on any list refresh.
  hasRequestedList.value = true
  tableLoading.value = true
  onUpdateOptions(options.value)
}

// Keep local loading in sync with the store loading state.
watch(isLoading, (val) => {
  if (val) {
    tableLoading.value = true
    return
  }
  // If we already triggered at least one load and the store is not loading anymore, hide spinner.
  if (hasRequestedList.value) {
    tableLoading.value = false
  }
})

// If store loading toggles late (or not at all), stop local loading when data changes.
watch([items, totalItems], () => {
  if (!hasRequestedList.value) return
  if (!isLoading.value) {
    tableLoading.value = false
  }
})

function resolveDefaultRows(total = 0) {
  const raw = platformConfigStore.getSetting("display.table_default_row", 10)
  const def = Number(raw)
  if (def === 0) return total || Number.MAX_SAFE_INTEGER // “All”
  return Number.isFinite(def) && def > 0 ? def : 10
}

const canEdit = (item) => {
  const resourceLink = getPrimaryResourceLink(item)
  if (!resourceLink) {
    return false
  }
  const isSessionDoc = resourceLink.session && resourceLink.session["@id"] === `/api/sessions/${sid}`
  const isBaseCourse = !resourceLink.session
  return (isSessionDoc && isAllowedToEdit.value) || (isBaseCourse && !sid && isCurrentTeacher.value)
}

const isSessionDocument = (item) => {
  const resourceLink = getPrimaryResourceLink(item)
  return resourceLink?.session && resourceLink.session["@id"] === `/api/sessions/${sid}`
}

const isHtmlFile = (fileData) => isHtml(fileData)

const isReplaceDialogVisible = ref(false)
const selectedReplaceFile = ref(null)
const documentToReplace = ref(null)

onMounted(async () => {
  tableLoading.value = true
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
  filters.value.loadNode = 1
  filters.value.filetype = ["file", "folder", "video"]

  // Set resource node.
  let nodeId = route.params.node
  if (isEmpty(nodeId)) {
    nodeId = route.query.node
  }

  await store.dispatch("resourcenode/findResourceNode", { id: `/api/resource_nodes/${nodeId}` })

  options.value.itemsPerPage = resolveDefaultRows(0)
  triggerTableLoad()
  void loadDefaultCertificate()
  void loadAllFolders()

  void courseSettingsStore
    .loadCourseSettings(cid, sid)
    .catch((e) => console.error("[AI] loadCourseSettings failed:", e))
    .finally(() => {
      void loadAiCapabilities()
    })

  void loadAiCapabilities()
  consumeAiSavedToast()
})

watch(
  () => [
    unref(cid),
    unref(sid),
    unref(gid),
    aiHelpersEnabled.value,
    imageGeneratorEnabled.value,
    videoGeneratorEnabled.value,
    contentAnalyzerEnabled.value,
  ],
  () => loadAiCapabilities(),
  { immediate: true },
)

watch(totalItems, (n) => {
  const def = Number(platformConfigStore.getSetting("display.table_default_row", 10))
  if (def === 0 && n) {
    options.value.itemsPerPage = n
    tableLoading.value = true
    onUpdateOptions(options.value)
  }
})

watch(
  () => route.params,
  () => {
    const nodeId = route.params.node
    const finderParams = { id: `/api/resource_nodes/${nodeId}`, cid, sid, gid }

    store.dispatch("resourcenode/findResourceNode", finderParams)

    if ("DocumentsList" === route.name) {
      triggerTableLoad()
    }
  },
)

const showBackButtonIfNotRootFolder = computed(() => {
  if (!resourceNode.value) {
    return false
  }
  return resourceNode.value.resourceType.title !== "courses"
})

function goToAddVariation(item) {
  const firstFile = item.resourceNode?.firstResourceFile
  if (!firstFile) {
    console.warn("[Documents] Missing firstResourceFile for document:", item?.iid)
    return
  }

  const resourceFileId = firstFile.id

  router.push({
    name: "DocumentsAddVariation",
    params: { resourceFileId, node: route.params.node },
    query: { cid, sid, gid },
  })
}

function back() {
  if (!resourceNode.value) {
    return
  }
  let parent = resourceNode.value.parent
  if (parent) {
    let queryParams = { cid, sid, gid }
    router.push({ name: "DocumentsList", params: { node: parent.id }, query: queryParams })
  }
}

function openNew() {
  item.value = {}
  submitted.value = false
  isNewFolderDialogVisible.value = true
}

function hideNewFolderDialog() {
  isNewFolderDialogVisible.value = false
  submitted.value = false
}

function createNewFolder() {
  submitted.value = true

  if (item.value.title?.trim()) {
    if (!item.value.id) {
      item.value.filetype = "folder"
      item.value.parentResourceNodeId = route.params.node
      item.value.resourceLinkList = JSON.stringify([
        {
          gid,
          sid,
          cid,
          visibility: RESOURCE_LINK_PUBLISHED, // visible by default
        },
      ])

      tableLoading.value = true
      store.dispatch("documents/createWithFormData", item.value).then(() => {
        notification.showSuccessNotification(t("Saved"))
        triggerTableLoad()
      })
    }
    isNewFolderDialogVisible.value = false
    item.value = {}
  }
}

function selectAll() {
  selectedItems.value = items.value
}

function showDeleteMultipleDialog() {
  isDeleteMultipleDialogVisible.value = true
}

async function confirmDeleteItem(itemToDelete) {
  try {
    const response = await axios.get(`/api/documents/${itemToDelete.iid}/lp-usage`)
    if (response.data.usedInLp) {
      lpListWarning.value = response.data.lpList.map((lp) => ({
        ...lp,
        documentTitle: itemToDelete.title,
        documentId: itemToDelete.iid,
      }))
      item.value = itemToDelete
      isDeleteWarningLpDialogVisible.value = true
    } else {
      item.value = itemToDelete
      isDeleteItemDialogVisible.value = true
    }
  } catch (error) {
    console.error("[Documents] Error checking LP usage for individual item:", error)
  }
}

async function forceDeleteItem() {
  try {
    const docIdsToDelete = [...new Set(lpListWarning.value.map((lp) => lp.documentId))]

    tableLoading.value = true
    await Promise.all(docIdsToDelete.map((iid) => axios.delete(`/api/documents/${iid}`)))

    notification.showSuccessNotification(t("Documents deleted"))
    isDeleteWarningLpDialogVisible.value = false
    item.value = {}
    unselectAll()
    triggerTableLoad()
  } catch (error) {
    console.error("[Documents] Error deleting documents forcibly:", error)
    notification.showErrorNotification(t("Error deleting document(s)."))
  }
}

function getReplaceButtonTitle(item) {
  if (item.filetype === "file") {
    return t("Replace file")
  }
  if (item.filetype === "video") {
    return t("Replace video")
  }
  return t("Replace (files or videos only)")
}

async function downloadSelectedItems() {
  if (!selectedItems.value.length) {
    notification.showErrorNotification(t("No items selected."))
    return
  }

  isDownloading.value = true

  try {
    const response = await axios.post(
      "/api/documents/download-selected",
      { ids: selectedItems.value.map((item) => item.iid) },
      { responseType: "blob" },
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", "selected_documents.zip")
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    notification.showSuccessNotification(t("Download started"))
  } catch (error) {
    console.error("[Documents] Error downloading selected items:", error)
    notification.showErrorNotification(t("Error downloading selected items."))
  } finally {
    isDownloading.value = false
  }
}

async function deleteMultipleItems() {
  const itemsWithoutLp = []
  const documentsWithLpMap = {}

  tableLoading.value = true
  for (const item of selectedItems.value) {
    try {
      const response = await axios.get(`/api/documents/${item.iid}/lp-usage`)
      if (response.data.usedInLp) {
        if (!documentsWithLpMap[item.iid]) {
          documentsWithLpMap[item.iid] = {
            iid: item.iid,
            title: item.title,
            lpList: [],
          }
        }
        documentsWithLpMap[item.iid].lpList.push(...response.data.lpList)
      } else {
        itemsWithoutLp.push(item)
      }
    } catch (error) {
      console.error(`[Documents] Error checking LP usage for document ${item.iid}:`, error)
    }
  }

  const documentsWithLp = Object.values(documentsWithLpMap)

  if (itemsWithoutLp.length > 0) {
    try {
      await store.dispatch("documents/delMultiple", itemsWithoutLp)
    } catch (e) {
      console.error("[Documents] Error deleting documents without LP:", e)
    }
  }

  if (documentsWithLp.length > 0) {
    lpListWarning.value = documentsWithLp.flatMap((doc) =>
      doc.lpList.map((lp) => ({
        ...lp,
        documentTitle: doc.title,
        documentId: doc.iid,
      })),
    )

    item.value = {}
    isDeleteWarningLpDialogVisible.value = true
  } else {
    notification.showSuccessNotification(t("Documents deleted"))
    unselectAll()
  }

  isDeleteMultipleDialogVisible.value = false
  triggerTableLoad()
}

function unselectAll() {
  selectedItems.value = []
}

function deleteSingleItem() {
  tableLoading.value = true
  deleteItem(item)
  item.value = {}
  isDeleteItemDialogVisible.value = false
  triggerTableLoad()
}

function onPage(event) {
  options.value = {
    itemsPerPage: event.rows,
    page: event.page + 1,
    sortBy: event.sortField,
    sortDesc: event.sortOrder === -1,
  }

  triggerTableLoad()
}

function sortingChanged(event) {
  options.value.sortBy = event.sortField
  options.value.sortDesc = event.sortOrder === -1

  triggerTableLoad()
}

function goToNewDocument() {
  router.push({
    name: "DocumentsCreateFile",
    query: route.query,
  })
}

function goToUploadFile() {
  router.push({
    name: "DocumentsUploadFile",
    query: route.query,
  })
}

function btnShowInformationOnClick(item) {
  const folderParams = route.query

  if (item) {
    folderParams.id = item["@id"]
  }

  router.push({
    name: "DocumentsShow",
    params: folderParams,
    query: folderParams,
  })
}

function btnChangeVisibilityOnClick(item) {
  const folderParams = route.query
  folderParams.id = item["@id"]

  baseService.put(item["@id"] + `/toggle_visibility?cid=${cid}&sid=${sid}`, {}).then((data) => {
    item.resourceLinkListFromEntity = data.resourceLinkListFromEntity
  })
}

function btnEditOnClick(item) {
  const folderParams = route.query
  folderParams.id = item["@id"]

  if ("folder" === item.filetype || isEmpty(item.filetype)) {
    router.push({
      name: "DocumentsUpdate",
      params: { id: item["@id"] },
      query: folderParams,
    })
    return
  }

  if ("file" === item.filetype || "certificate" === item.filetype) {
    folderParams.getFile = true
    router.push({ name: "DocumentsUpdateFile", params: { id: item["@id"] }, query: folderParams })
  }
}

function showSlideShowWithFirstImage() {
  let item = items.value.find((i) => isImage(i))
  if (!item) return
  document.querySelector(`a[href='${item.contentUrl}']`)?.click()
  document.querySelector("button.fancybox-button--play")?.click()
}

async function showUsageDialog() {
  try {
    const response = await axios.get(`/api/documents/${cid}/usage`, {
      headers: { Accept: "application/json" },
      params: { sid, gid },
    })

    usageData.value = response.data
  } catch (error) {
    console.error("[Documents] Error fetching documents quota usage:", error)
    usageData.value = {
      datasets: [{ data: [100] }],
      labels: [t("Storage usage unavailable")],
    }
  }

  isFileUsageDialogVisible.value = true
}

function showRecordAudioDialog() {
  isRecordAudioDialogVisible.value = true
}

function recordedAudioSaved() {
  notification.showSuccessNotification(t("Saved"))
  isRecordAudioDialogVisible.value = false
  triggerTableLoad()
}

function recordedAudioNotSaved(error) {
  notification.showErrorNotification(t("Document not saved"))
  console.error(error)
}

/**
 * -----------------------------------------
 * MOVE: helpers + folders fetching
 * -----------------------------------------
 */
function normalizeResourceNodeId(value) {
  if (value == null) return null
  if (typeof value === "number") return value

  if (typeof value === "string") {
    // Accept IRI like "/api/resource_nodes/123"
    const iriMatch = value.match(/\/api\/resource_nodes\/(\d+)/)
    if (iriMatch) return Number(iriMatch[1])

    // Accept "123"
    if (/^\d+$/.test(value)) return Number(value)
  }

  return null
}

function getDocumentsRootNodeId() {
  // We want the top node of this documents tree (usually the course root node).
  let node = resourceNode.value

  const fallback = normalizeResourceNodeId(route.params.node || route.query.node)

  if (!node) {
    return fallback
  }

  // If current node is already the course root, use it.
  if (node?.resourceType?.title === "courses") {
    return normalizeResourceNodeId(node.id) || fallback
  }

  // Walk up until we reach the course root node.
  let safety = 0
  while (node?.parent && node?.resourceType?.title !== "courses" && safety < 30) {
    node = node.parent
    safety++
  }

  return normalizeResourceNodeId(node?.id) || fallback
}

async function fetchFolders(nodeId = null, parentPath = "") {
  const rootId = normalizeResourceNodeId(nodeId) ?? getDocumentsRootNodeId()

  const foldersList = [
    {
      label: t("Documents"),
      value: rootId, // REAL root node id
    },
  ]

  try {
    let nodesToFetch = [{ id: rootId, path: parentPath }]
    let depth = 0
    const maxDepth = 10

    while (nodesToFetch.length > 0 && depth < maxDepth) {
      const currentNode = nodesToFetch.shift()
      const currentNodeId = normalizeResourceNodeId(currentNode?.id)

      if (!currentNodeId) {
        depth++
        continue
      }

      const response = await axios.get("/api/documents", {
        params: {
          loadNode: 1,
          filetype: ["folder"],
          "resourceNode.parent": currentNodeId,
          cid: unref(cid),
          sid: unref(sid),
          gid: unref(gid),
          page: 1,
          itemsPerPage: 200,
        },
      })

      const members = response.data?.["hydra:member"] || []

      members.forEach((folder) => {
        const folderNodeId =
          normalizeResourceNodeId(folder?.resourceNode?.id) ?? normalizeResourceNodeId(folder?.resourceNodeId)

        if (!folderNodeId) {
          return
        }

        const fullPath = `${currentNode.path}/${folder.title}`.replace(/^\/+/, "")

        foldersList.push({
          label: fullPath,
          value: folderNodeId,
        })

        nodesToFetch.push({ id: folderNodeId, path: fullPath })
      })

      depth++
    }

    return foldersList
  } catch (error) {
    console.error("[Documents] Error fetching folders:", error?.message || error)
    return foldersList
  }
}

async function loadAllFolders() {
  const rootId = getDocumentsRootNodeId()
  folders.value = await fetchFolders(rootId)
}

async function openMoveDialog(document) {
  item.value = document
  selectedFolder.value = null
  await loadAllFolders()
  isMoveDialogVisible.value = true
}

async function moveDocument() {
  try {
    const parentId = normalizeResourceNodeId(selectedFolder.value)

    if (!parentId) {
      notification.showErrorNotification(t("Select a folder"))
      return
    }

    // Optional: avoid no-op moves
    const currentParentId =
      normalizeResourceNodeId(item.value?.resourceNode?.parent?.id) ??
      normalizeResourceNodeId(item.value?.resourceNode?.parent)

    if (currentParentId && String(currentParentId) === String(parentId)) {
      notification.showErrorNotification(t("The document is already in this folder"))
      return
    }

    await axios.put(
      `/api/documents/${item.value.iid}/move`,
      { parentResourceNodeId: parentId },
      {
        params: {
          cid: unref(cid),
          sid: unref(sid),
          gid: unref(gid),
        },
      },
    )

    notification.showSuccessNotification(t("Document moved successfully"))
    isMoveDialogVisible.value = false
    triggerTableLoad()
  } catch (error) {
    console.error("[Documents] Error moving document:", error.response || error)
    notification.showErrorNotification(t("Error moving the document"))
  }
}

/**
 * -----------------------------------------
 * REPLACE
 * -----------------------------------------
 */
function openReplaceDialog(document) {
  if (!canEdit(document)) {
    return
  }

  documentToReplace.value = document
  isReplaceDialogVisible.value = true
}

async function replaceDocument() {
  if (!selectedReplaceFile.value) {
    notification.showErrorNotification(t("No file selected."))
    return
  }

  if (!(documentToReplace.value.filetype === "file" || documentToReplace.value.filetype === "video")) {
    notification.showErrorNotification(t("Only files can be replaced."))
    return
  }

  const formData = new FormData()
  formData.append("file", selectedReplaceFile.value)

  try {
    await axios.post(`/api/documents/${documentToReplace.value.iid}/replace`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })

    notification.showSuccessNotification(t("File replaced"))
    isReplaceDialogVisible.value = false
    triggerTableLoad()
  } catch (error) {
    notification.showErrorNotification(t("Error replacing file."))
    console.error(error)
  }
}

/**
 * -----------------------------------------
 * CERTIFICATES
 * -----------------------------------------
 */
async function selectAsDefaultCertificate(certificate) {
  try {
    const response = await axios.patch(`/gradebook/set_default_certificate/${cid}/${certificate.iid}`)
    if (response.status === 200) {
      loadDefaultCertificate()
      triggerTableLoad()
      notification.showSuccessNotification(t("Certificate set as default successfully"))
    }
  } catch {
    notification.showErrorNotification(t("Error setting certificate as default"))
  }
}

async function loadDefaultCertificate() {
  try {
    const response = await axios.get(`/gradebook/default_certificate/${cid}`)
    defaultCertificateId.value = response.data.certificateId
  } catch (error) {
    if (error.response?.status === 404) {
      console.error("[Documents] Default certificate not found.")
      defaultCertificateId.value = null
    } else {
      console.error("[Documents] Error loading the certificate:", error)
    }
  }
}

/**
 * -----------------------------------------
 * TEMPLATE
 * -----------------------------------------
 */
const showTemplateFormModal = ref(false)
const selectedFile = ref(null)
const templateFormData = ref({
  title: "",
  thumbnail: null,
})

const currentDocumentId = ref(null)

const isDocumentTemplate = async (documentId) => {
  try {
    const response = await axios.get(`/template/document-templates/${documentId}/is-template`)
    return response.data.isTemplate
  } catch (error) {
    console.error("[Documents] Error verifying template status:", error)
    return false
  }
}

const deleteDocumentTemplate = async (documentId) => {
  try {
    await axios.post(`/template/document-templates/${documentId}/delete`)
    triggerTableLoad()
    notification.showSuccessNotification(t("Template successfully deleted."))
  } catch (error) {
    console.error("[Documents] Error deleting template:", error)
    notification.showErrorNotification(t("Error deleting the template."))
  }
}

const getTemplateIcon = (documentId) => {
  const document = items.value.find((doc) => doc.iid === documentId)
  return document && document.template ? "template-selected" : "template-not-selected"
}

const openTemplateForm = async (documentId) => {
  const isTemplate = await isDocumentTemplate(documentId)

  if (isTemplate) {
    await deleteDocumentTemplate(documentId)
    triggerTableLoad()
  } else {
    currentDocumentId.value = documentId
    showTemplateFormModal.value = true
  }
}

const submitTemplateForm = async () => {
  submitted.value = true

  if (!templateFormData.value.title || !selectedFile.value) {
    notification.showErrorNotification(t("The title and thumbnail are required."))
    return
  }

  try {
    const formData = new FormData()
    formData.append("title", templateFormData.value.title)
    formData.append("thumbnail", selectedFile.value)
    formData.append("refDoc", currentDocumentId.value)
    formData.append("cid", cid)

    const response = await axios.post("/template/document-templates/create", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })

    if (response.status === 200 || response.status === 201) {
      notification.showSuccessNotification(t("Template created successfully."))
      templateFormData.value.title = ""
      selectedFile.value = null
      showTemplateFormModal.value = false
      triggerTableLoad()
    } else {
      notification.showErrorNotification(t("Error creating the template."))
    }
  } catch (error) {
    console.error("[Documents] Error submitting template form:", error)
    notification.showErrorNotification(t("Error submitting the form."))
  }
}

/**
 * -----------------------------------------
 * AI: capabilities + provider lists (selector in modal)
 * -----------------------------------------
 */
const hasAiImage = ref(false)
const hasAiVideo = ref(false)
const hasAiDocumentProcess = ref(false)
const hasAiTextForAnalyzer = ref(false)

const aiTextProviders = ref([]) // [{ key, label }]
const aiDocProcessProviders = ref([]) // [{ key, label }]

function normalizeProviders(raw) {
  if (!raw) return []

  // Array: ["openai", ...] OR [{key,label}, ...]
  if (Array.isArray(raw)) {
    return raw
      .map((p) => {
        if (typeof p === "string") {
          const s = p.trim()
          return s ? { key: s, label: s } : null
        }
        if (p && typeof p === "object") {
          const key = String(p.key ?? p.name ?? "").trim()
          if (!key) return null
          const label = String(p.label ?? key).trim()
          return { key, label: label || key }
        }
        return null
      })
      .filter(Boolean)
  }

  // Map/object: { openai: "openai (gpt-4o)", ... }
  if (raw && typeof raw === "object") {
    return Object.entries(raw)
      .map(([k, v]) => {
        const key = String(k || "").trim()
        if (!key) return null
        const label = String(v ?? key).trim()
        return { key, label: label || key }
      })
      .filter(Boolean)
  }

  return []
}

const showGenerateMediaButton = computed(() => {
  if (!isCurrentTeacher.value) return false
  if (!aiHelpersEnabled.value) return false

  const canImage = imageGeneratorEnabled.value && hasAiImage.value
  const canVideo = !isCertificateMode.value && videoGeneratorEnabled.value && hasAiVideo.value

  return canImage || canVideo
})

async function loadAiCapabilities() {
  if (!aiHelpersEnabled.value) {
    hasAiImage.value = false
    hasAiVideo.value = false
    hasAiDocumentProcess.value = false
    hasAiTextForAnalyzer.value = false
    aiTextProviders.value = []
    aiDocProcessProviders.value = []
    return
  }

  // If nothing is enabled on frontend, skip backend call.
  if (!imageGeneratorEnabled.value && !videoGeneratorEnabled.value && !contentAnalyzerEnabled.value) {
    hasAiImage.value = false
    hasAiVideo.value = false
    hasAiDocumentProcess.value = false
    hasAiTextForAnalyzer.value = false
    aiTextProviders.value = []
    aiDocProcessProviders.value = []
    return
  }

  try {
    const { data } = await axios.get("/ai/capabilities", {
      params: { cid: unref(cid), sid: unref(sid), gid: unref(gid) },
      headers: { Accept: "application/json" },
    })

    console.warn("[AI] capabilities:", data)

    const backendHasText = !!(data?.has?.text ?? data?.text)
    const backendHasImage = !!(data?.has?.image ?? data?.image)
    const backendHasVideo = !!(data?.has?.video ?? data?.video)
    const backendHasDocProcess = !!(data?.has?.document_process ?? data?.document_process)

    hasAiImage.value = imageGeneratorEnabled.value && backendHasImage
    hasAiVideo.value = videoGeneratorEnabled.value && backendHasVideo

    // document_process providers come from capabilities.types.document_process (often a map)
    aiDocProcessProviders.value = normalizeProviders(data?.types?.document_process)

    // text providers: only teachers use the AI feedback/analyzer feature, skip for students
    aiTextProviders.value = []
    if (isCurrentTeacher.value) {
      try {
        const res = await axios.get("/ai/text_providers", { headers: { Accept: "application/json" } })
        aiTextProviders.value = normalizeProviders(res?.data?.providers)
      } catch (e) {
        console.warn("[AI][Documents] Failed to load /ai/text_providers, fallback to capabilities:", e?.response || e)
        aiTextProviders.value = normalizeProviders(data?.types?.text)
      }
    }

    // Analyzer support depends on file mode + provider list availability.
    hasAiDocumentProcess.value =
      contentAnalyzerEnabled.value && backendHasDocProcess && aiDocProcessProviders.value.length > 0
    hasAiTextForAnalyzer.value = contentAnalyzerEnabled.value && backendHasText && aiTextProviders.value.length > 0
  } catch (e) {
    console.error("[AI] Failed to load capabilities:", e?.response || e)
    hasAiImage.value = false
    hasAiVideo.value = false
    hasAiDocumentProcess.value = false
    hasAiTextForAnalyzer.value = false
    aiTextProviders.value = []
    aiDocProcessProviders.value = []
  }
}

function goToGenerateMedia() {
  router.push({
    name: "DocumentsGenerateMedia",
    params: { node: route.params.node },
    query: { ...route.query, cid, sid, gid },
  })
}

/**
 * Detect analyzer mode from file (PDF vs TXT).
 * This must match backend logic in /ai/document_feedback.
 */
function getAnalyzerMode(doc) {
  const rf = doc?.resourceNode?.firstResourceFile
  const mime = String(rf?.mimeType || "").toLowerCase()
  const name = String(rf?.originalName || doc?.title || "").toLowerCase()

  const isPdf = mime === "application/pdf" || name.endsWith(".pdf")
  if (isPdf) return "pdf"

  const isTxt = mime.startsWith("text/plain") || name.endsWith(".txt")
  if (isTxt) return "txt"

  return ""
}

function showAiFeedbackButton(doc) {
  if (!isCurrentTeacher.value) return false
  if (!aiHelpersEnabled.value) return false
  if (!contentAnalyzerEnabled.value) return false

  // Only analyze items that have a real file attached.
  const rfId = doc?.resourceNode?.firstResourceFile?.id
  if (!rfId) return false

  // Avoid folders.
  const ft = String(doc?.filetype || "")
  if (!["file", "video", "certificate"].includes(ft)) return false

  const mode = getAnalyzerMode(doc)
  if (!mode) return false

  // Mode-specific gating so the button appears only when providers exist.
  if (mode === "pdf") {
    return hasAiDocumentProcess.value && aiDocProcessProviders.value.length > 0
  }
  if (mode === "txt") {
    return hasAiTextForAnalyzer.value && aiTextProviders.value.length > 0
  }

  return false
}

/**
 * -----------------------------------------
 * AI feedback modal state + provider selector
 * -----------------------------------------
 */
const isAiFeedbackDialogVisible = ref(false)
const aiFeedbackLoading = ref(false)
const aiFeedbackSaving = ref(false)
const aiFeedbackError = ref("")
const aiFeedbackAnswer = ref("")
const aiFeedbackPrompt = ref("")
const aiFeedbackDoc = ref(null)

// Selected provider key (string). When null => backend default provider is used.
const aiFeedbackProvider = ref(null)

const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)

const aiFeedbackDocTitle = computed(() => {
  return String(aiFeedbackDoc.value?.title || aiFeedbackDoc.value?.resourceNode?.title || "").trim()
})

const aiFeedbackCourseTitle = computed(() => {
  return String(course.value?.title || "").trim()
})

const aiFeedbackMode = computed(() => getAnalyzerMode(aiFeedbackDoc.value))

const aiFeedbackProviderOptions = computed(() => {
  if (aiFeedbackMode.value === "pdf") return aiDocProcessProviders.value
  if (aiFeedbackMode.value === "txt") return aiTextProviders.value
  return []
})

function openAiFeedback(doc) {
  aiFeedbackDoc.value = doc
  aiFeedbackError.value = ""
  aiFeedbackAnswer.value = ""

  // Default prompt can be changed by the teacher.
  aiFeedbackPrompt.value =
    "Please provide feedback on clarity, structure, and improvement suggestions. If needed, propose a revised version."

  // Reset provider then set first available provider after DOM/computed updates.
  aiFeedbackProvider.value = null
  isAiFeedbackDialogVisible.value = true

  void nextTick(() => {
    const opts = aiFeedbackProviderOptions.value || []
    aiFeedbackProvider.value = opts?.[0]?.key ?? null
  })
}

// If providers are loaded after dialog opens, make sure we have a valid selection.
watch(
  () => [isAiFeedbackDialogVisible.value, aiFeedbackProviderOptions.value],
  () => {
    if (!isAiFeedbackDialogVisible.value) return
    const opts = aiFeedbackProviderOptions.value || []
    if (!opts.length) return

    const current = String(aiFeedbackProvider.value || "").trim()
    if (!current) {
      aiFeedbackProvider.value = opts[0].key
      return
    }

    const exists = opts.some((p) => p.key === current)
    if (!exists) {
      aiFeedbackProvider.value = opts[0].key
    }
  },
  { deep: true },
)

function closeAiFeedbackDialog() {
  isAiFeedbackDialogVisible.value = false
  aiFeedbackLoading.value = false
  aiFeedbackSaving.value = false
  aiFeedbackError.value = ""
  aiFeedbackAnswer.value = ""
  aiFeedbackPrompt.value = ""
  aiFeedbackProvider.value = null
  aiFeedbackDoc.value = null
}

async function runAiFeedback() {
  // Prevent double-click runs.
  if (aiFeedbackLoading.value) return

  aiFeedbackError.value = ""

  if (!aiFeedbackDoc.value) {
    aiFeedbackError.value = "Missing selected document."
    return
  }

  const mode = getAnalyzerMode(aiFeedbackDoc.value)
  if (!mode) {
    aiFeedbackError.value = "Unsupported file type. Only PDF and TXT are supported."
    return
  }

  if (!aiFeedbackPrompt.value.trim()) {
    aiFeedbackError.value = "Prompt is required."
    return
  }

  const resourceFileId = aiFeedbackDoc.value?.resourceNode?.firstResourceFile?.id
  if (!resourceFileId) {
    aiFeedbackError.value = "Missing resource file information for this document."
    return
  }

  // If we have options, require a valid selected provider.
  const opts = aiFeedbackProviderOptions.value || []
  if (opts.length > 0) {
    const selected = String(aiFeedbackProvider.value || "").trim()
    if (!selected) {
      aiFeedbackError.value = "AI provider is required."
      return
    }
    const exists = opts.some((p) => p.key === selected)
    if (!exists) {
      aiFeedbackError.value = "Selected AI provider is not available for this file type."
      return
    }
  }

  aiFeedbackLoading.value = true
  aiFeedbackAnswer.value = ""

  try {
    const payload = {
      cid: unref(cid),
      sid: unref(sid),
      gid: unref(gid),
      document_iid: aiFeedbackDoc.value?.iid,
      resource_file_id: resourceFileId,
      document_title: aiFeedbackDocTitle.value,
      prompt: aiFeedbackPrompt.value,
      language: String(locale?.value || "en"),
      // Provider key or null => backend default provider.
      ai_provider: aiFeedbackProvider.value,
    }

    const { data } = await axios.post("/ai/document_feedback", payload, {
      headers: { Accept: "application/json" },
    })

    if (!data?.success) {
      aiFeedbackError.value = String(data?.text || "AI feedback request failed.")
      return
    }

    aiFeedbackAnswer.value = String(data?.text || "").trim()
  } catch (e) {
    console.error("[AI] document_feedback failed:", e?.response || e)
    aiFeedbackError.value = "AI feedback request failed."
  } finally {
    aiFeedbackLoading.value = false
  }
}

async function copyAiFeedbackToClipboard() {
  try {
    await navigator.clipboard.writeText(String(aiFeedbackAnswer.value || ""))
    notification.showSuccessNotification(t("Copied"))
  } catch (e) {
    console.error("[AI] Failed to copy to clipboard:", e)
    notification.showErrorNotification(t("Error"))
  }
}

async function saveAiFeedbackToInbox() {
  if (!aiFeedbackDoc.value || !aiFeedbackAnswer.value) {
    aiFeedbackError.value = "Nothing to save."
    return
  }

  aiFeedbackSaving.value = true
  aiFeedbackError.value = ""

  try {
    const payload = {
      cid: unref(cid),
      sid: unref(sid),
      gid: unref(gid),
      document_iid: aiFeedbackDoc.value?.iid,
      document_title: aiFeedbackDocTitle.value,
      answer: aiFeedbackAnswer.value,
    }

    const { data } = await axios.post("/ai/document_feedback/save_to_inbox", payload, {
      headers: { Accept: "application/json" },
    })

    if (!data?.success) {
      aiFeedbackError.value = String(data?.text || "Failed to save the answer to inbox.")
      return
    }

    notification.showSuccessNotification(t("Saved"))
  } catch (e) {
    console.error("[AI] save_to_inbox failed:", e?.response || e)
    aiFeedbackError.value = "Failed to save the answer to inbox."
  } finally {
    aiFeedbackSaving.value = false
  }
}

const usageQuotaSummary = computed(() => {
  const q = usageData.value?.quota
  if (!q) return null

  const limiter = String(q.limiter || "unlimited")

  function fmtBytes(v) {
    if (v === null || v === undefined) return t("Unlimited")
    const n = Number(v)
    if (!Number.isFinite(n)) return t("Unlimited")
    return prettyBytes(Math.max(n, 0))
  }

  const courseQuota = fmtBytes(q.courseQuotaBytes)
  const docsQuota = fmtBytes(q.documentsQuotaBytes)

  const courseAvail = fmtBytes(q.availableCourseBytes)
  const docsAvail = fmtBytes(q.availableDocumentsBytes)

  const effectiveAvail = fmtBytes(q.availableBytes)
  const effectivePct = Number(q.availablePercent)
  const pctLabel = Number.isFinite(effectivePct) ? `${effectivePct}%` : ""

  let limiterLabel = ""
  if (limiter === "course") {
    limiterLabel = `${t("Limiting quota")}: ${t("Course")}`
  } else if (limiter === "documents") {
    limiterLabel = `${t("Limiting quota")}: ${t("Documents")}`
  } else {
    limiterLabel = `${t("Limiting quota")}: ${t("Unlimited")}`
  }

  const remainingLabel = `${t("Remaining space")}: ${effectiveAvail}${pctLabel ? ` (${pctLabel})` : ""}`

  return {
    limiterLabel,
    remainingLabel,
    courseLine: `${courseAvail} / ${courseQuota}`,
    documentsLine: `${docsAvail} / ${docsQuota}`,
  }
})

function consumeAiSavedToast() {
  // Show toast only once after redirect from AI generator.
  if (String(route.query.ai_saved || "") !== "1") {
    return
  }

  const path = String(route.query.ai_saved_path || "").trim()
  if (path) {
    notification.showSuccessNotification(`${t("Saved")}: ${path}`)
  } else {
    notification.showSuccessNotification(t("Saved"))
  }

  // Remove params so it doesn't show again on refresh/back.
  const newQuery = { ...route.query }
  delete newQuery.ai_saved
  delete newQuery.ai_saved_path
  delete newQuery.ai_saved_iri

  router.replace({
    name: route.name,
    params: route.params,
    query: newQuery,
  })
}
</script>
