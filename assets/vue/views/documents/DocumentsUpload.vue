<template>
  <BaseToolbar>
    <BaseButton
      :label="t('Back')"
      icon="back"
      type="black"
      @click="back"
    />
  </BaseToolbar>
  <div class="flex flex-col justify-start">
    <div class="mb-4">
      <Dashboard
        :plugins="['Webcam', 'ImageEditor']"
        :props="{
          proudlyDisplayPoweredByUppy: false,
          width: '100%',
          height: '350px',
        }"
        :uppy="uppy"
      />
    </div>

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t("Options") }}:</label>
        <BaseCheckbox
          id="uncompress"
          v-model="isUncompressZipEnabled"
          :label="t('Uncompress zip')"
          name="uncompress"
        />
      </div>

      <div class="flex flex-row mb-2">
        <label class="font-semibold w-28">{{ t("If file exists") }}:</label>
        <BaseRadioButtons
          id="fileExistsOption"
          v-model="fileExistsOption"
          :initial-value="'rename'"
          :options="[
            { label: t('Do nothing'), value: 'nothing' },
            { label: t('Overwrite the existing file'), value: 'overwrite' },
            { label: t('Rename the uploaded file if it exists'), value: 'rename' },
          ]"
          name="fileExistsOption"
        />
      </div>

      <!-- Search / Xapian options -->
      <div
        v-if="isSearchEnabled"
        class="flex flex-row mb-2"
      >
        <label class="font-semibold w-28">{{ t("Search") }}:</label>
        <BaseCheckbox
          id="indexDocumentContent"
          v-model="indexDocumentContent"
          :label="t('Index document content?')"
          name="indexDocumentContent"
        />
      </div>

      <!-- Specific search fields -->
      <div
        v-if="isSearchEnabled && searchFields.length > 0"
        class="flex flex-col gap-2 mt-3"
      >
        <div
          v-for="field in searchFields"
          :key="field.id"
          class="flex flex-row items-center gap-3"
        >
          <label
            class="font-semibold w-28"
            :for="`upload_search_field_${field.code}`"
          >
            {{ field.title }}:
          </label>

          <input
            :id="`upload_search_field_${field.code}`"
            :name="`searchFieldValues[${field.code}]`"
            v-model="searchFieldValues[field.code]"
            type="text"
            class="w-full border border-gray-300 rounded px-3 py-2"
            :placeholder="field.title"
            autocomplete="off"
          />
        </div>
      </div>
    </BaseAdvancedSettingsButton>
  </div>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount, onMounted } from "vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"

import Uppy from "@uppy/core"
import Webcam from "@uppy/webcam"
import { Dashboard } from "@uppy/vue"
import XHRUpload from "@uppy/xhr-upload"
import ImageEditor from "@uppy/image-editor"
import { useRoute, useRouter } from "vue-router"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import { useUpload } from "../../composables/upload"
import { useI18n } from "vue-i18n"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { usePlatformConfig } from "../../store/platformConfig"

const route = useRoute()
const router = useRouter()
const { gid, sid, cid } = useCidReq()
const { onCreated } = useUpload()
const { t } = useI18n()
const platformConfigStore = usePlatformConfig()

const allowedFiletypes = ["file", "video", "certificate"]
const filetypeQuery = route.query.filetype
const filetype = allowedFiletypes.includes(filetypeQuery) ? filetypeQuery : "file"

const showAdvancedSettings = ref(false)
const isUncompressZipEnabled = ref(false)
const fileExistsOption = ref("rename")

const isSearchEnabled = computed(() => "false" !== platformConfigStore.getSetting("search.search_enabled"))
const indexDocumentContent = ref(isSearchEnabled.value)

// Search fields: [{id, code, title}]
const searchFields = ref([])
const searchFieldValues = ref({})

const parentResourceNodeId = ref(Number(route.query.parentResourceNodeId || route.params.node))
const resourceLinkList = ref(JSON.stringify([{ gid, sid, cid, visibility: RESOURCE_LINK_PUBLISHED }]))

function normalizeCode(code) {
  return String(code || "")
    .trim()
    .toLowerCase()
}

// Build meta keys: { "searchFieldValues[t]": "...", "searchFieldValues[d]": "..." }
function buildSearchFieldMeta(values, fields) {
  const meta = {}
  for (const f of fields || []) {
    const code = normalizeCode(f.code)
    if (!code) continue
    meta[`searchFieldValues[${code}]`] = String(values?.[code] ?? "")
  }
  return meta
}

const uppy = new Uppy({ autoProceed: false })
  .use(Webcam)
  .use(ImageEditor, {
    cropperOptions: {
      viewMode: 1,
      background: false,
      autoCropArea: 1,
      responsive: true,
    },
    actions: {
      revert: true,
      rotate: true,
      granularRotate: true,
      flip: true,
      zoomIn: true,
      zoomOut: true,
      cropSquare: true,
      cropWidescreen: true,
      cropWidescreenVertical: true,
    },
  })
  .use(XHRUpload, {
    endpoint: ENTRYPOINT + "documents",
    formData: true,
    fieldName: "uploadFile",
  })
  .on("upload-success", (_item, response) => {
    onCreated(response.body)
  })
  .on("complete", () => {
    const parentNodeId = parentResourceNodeId.value
    localStorage.setItem("isUploaded", "true")
    localStorage.setItem("uploadParentNodeId", parentNodeId)
    setTimeout(() => {
      if (route.query.returnTo) {
        router.push({
          name: route.query.returnTo,
          params: { node: parentNodeId },
          query: { ...route.query, parentResourceNodeId: parentNodeId },
        })
      } else {
        router.back()
      }
    }, 2000)
  })

// Initial meta (do not send searchFieldValues as an object)
uppy.setMeta({
  filetype,
  parentResourceNodeId: parentResourceNodeId.value,
  resourceLinkList: resourceLinkList.value,
  isUncompressZipEnabled: isUncompressZipEnabled.value,
  fileExistsOption: fileExistsOption.value,
  indexDocumentContent: indexDocumentContent.value,
})

if (filetype === "certificate") {
  uppy.setOptions({ restrictions: { allowedFileTypes: [".html"] } })
} else if (filetype === "video") {
  uppy.setOptions({ restrictions: { allowedFileTypes: ["video/*"] } })
} else {
  uppy.setOptions({ restrictions: { allowedFileTypes: null } })
}

onMounted(async () => {
  if (!isSearchEnabled.value) return

  try {
    const response = await fetch(ENTRYPOINT + "search_engine_fields", { credentials: "same-origin" })
    if (!response.ok) {
      console.error("[Search] Failed to load search engine fields:", response.status)
      return
    }

    const json = await response.json()
    const fields = Array.isArray(json) ? json : json["hydra:member"] || []
    if (!Array.isArray(fields)) {
      console.error("[Search] Unexpected search engine fields payload:", json)
      return
    }

    searchFields.value = fields
      .map((f) => ({
        id: f.id,
        code: normalizeCode(f.code),
        title: f.title,
      }))
      .filter((f) => f.code)

    // Ensure keys exist for v-model
    for (const f of searchFields.value) {
      if (undefined === searchFieldValues.value[f.code]) {
        searchFieldValues.value[f.code] = ""
      }
    }

    // Push meta keys: searchFieldValues[t], searchFieldValues[d], ...
    uppy.setMeta(buildSearchFieldMeta(searchFieldValues.value, searchFields.value))
  } catch (e) {
    console.error("[Search] Failed to fetch search engine fields:", e)
  }
})

watch(isUncompressZipEnabled, (value) => {
  uppy.setMeta({ isUncompressZipEnabled: value })
})

watch(fileExistsOption, (value) => {
  uppy.setMeta({ fileExistsOption: value })
})

watch(indexDocumentContent, (value) => {
  uppy.setMeta({ indexDocumentContent: value })
})

watch(
  searchFieldValues,
  () => {
    uppy.setMeta(buildSearchFieldMeta(searchFieldValues.value, searchFields.value))
  },
  { deep: true },
)

function back() {
  const queryParams = { cid, sid, gid, filetype, tab: route.query.tab }
  if (route.query.tab) {
    router.push({
      name: "FileManagerList",
      params: { node: parentResourceNodeId.value },
      query: queryParams,
    })
  } else {
    router.back()
  }
}

onBeforeUnmount(() => {
  try {
    uppy.close()
  } catch {
    // Ignore Uppy closing errors.
  }
})
</script>
