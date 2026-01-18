<template>
  <BaseToolbar v-if="!embedded">
    <BaseButton
      :label="t('Back')"
      icon="back"
      type="black"
      @click="back"
    />
  </BaseToolbar>

  <div class="flex flex-col justify-center items-center">
    <div class="mb-4 w-full">
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
  </div>
</template>
<script setup>
import { ref, onBeforeUnmount } from "vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import Uppy from "@uppy/core"
import Webcam from "@uppy/webcam"
import { Dashboard } from "@uppy/vue"
import XHRUpload from "@uppy/xhr-upload"
import ImageEditor from "@uppy/image-editor"

import { useRouter } from "vue-router"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import { useUpload } from "../../composables/upload"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const props = defineProps({
  embedded: { type: Boolean, default: false },
  parentResourceNodeId: { type: [Number, String], default: 0 },
  filetype: { type: String, default: "file" },
})
const emit = defineEmits(["done", "cancel"])

const router = useRouter()
const { gid, sid, cid } = useCidReq()
const { onCreated } = useUpload()
const { t } = useI18n()

const LOG_PREFIX = "[FILEMANAGER UPLOAD]"
function log(...args) {
  // Keep logs in English for easier debugging
  console.log(LOG_PREFIX, ...args)
}

/**
 * Resolve parent node from session storage (pf_parent) or component props.
 * This mirrors the legacy file manager behavior.
 */
function resolveParentResourceNodeId() {
  try {
    const pfParentRaw = sessionStorage.getItem("pf_parent")
    const pfParent = Number(pfParentRaw || 0)
    if (pfParent) {
      return pfParent
    }
  } catch (e) {
    log("Failed to read pf_parent from sessionStorage, falling back to props", e)
  }

  const fromProps = Number(props.parentResourceNodeId || 0)
  return fromProps || 0
}

function buildEndpoint(parentId) {
  const pid = String(Number(parentId || 0))
  const qs = new URLSearchParams({
    "resourceNode.parent": pid,
    parentResourceNodeId: pid,
    parent: pid,
  }).toString()

  return `${ENTRYPOINT}personal_files?${qs}`
}

const parentResourceNodeId = ref(resolveParentResourceNodeId())
const uploadedItems = ref([])

const allowedFiletypes = ["file", "video", "certificate"]
const filetype = allowedFiletypes.includes(props.filetype) ? props.filetype : "file"

const resourceLinkList = JSON.stringify([
  {
    gid,
    sid,
    cid,
    visibility: RESOURCE_LINK_PUBLISHED,
  },
])

// Advanced options defaults – we do not expose UI here, just send sane defaults
const isUncompressZipEnabled = false
const fileExistsOption = "rename"

/**
 * Single shared Uppy instance (same pattern as DocumentUpload.vue).
 * NOTE: We keep it as a plain instance (not a ref) to avoid reactivity wrappers.
 */
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
    endpoint: buildEndpoint(parentResourceNodeId.value),
    formData: true,
    fieldName: "uploadFile",
    allowedMetaFields: [
      "filetype",
      "parentResourceNodeId",
      "parentResourceNode",
      "resourceNode.parent",
      "resourceLinkList",
      "isUncompressZipEnabled",
      "fileExistsOption",
    ],
  })
  .on("upload-success", (_file, response) => {
    log("Upload success", response)
    if (response?.body) {
      onCreated(response.body)
      uploadedItems.value.push(response.body)
    }
  })
  .on("complete", () => {
    const parentNodeId = parentResourceNodeId.value
    log("Upload complete, items:", uploadedItems.value, "parent:", parentNodeId)

    // Embedded mode (e.g. editor): notify parent and stay in Vue world
    if (props.embedded) {
      emit("done", {
        parentNodeId,
        items: uploadedItems.value,
      })
      uploadedItems.value = []
      return
    }

    // Standalone file manager: go back to listing
    router.push({
      name: "FileManagerList",
      params: { node: parentNodeId || 0 },
      query: { cid, sid, gid },
    })
  })
  .on("error", (err) => {
    // Avoid crashing the app on Uppy internal errors
    log("Uppy error", err)
  })
  .on("restriction-failed", (file, error) => {
    log("Uppy restriction failed", file?.name, error?.message || error)
  })

// Initial meta – mirrors DocumentUpload but targeting personal_files
uppy.setMeta({
  filetype,
  parentResourceNodeId: parentResourceNodeId.value,
  parentResourceNode: `/api/resource_nodes/${parentResourceNodeId.value}`,
  "resourceNode.parent": parentResourceNodeId.value,
  resourceLinkList,
  isUncompressZipEnabled,
  fileExistsOption,
})

// Per-filetype restrictions (same concept as DocumentUpload.vue)
if (filetype === "certificate") {
  uppy.setOptions({ restrictions: { allowedFileTypes: [".html"] } })
} else if (filetype === "video") {
  uppy.setOptions({ restrictions: { allowedFileTypes: ["video/*"] } })
} else {
  uppy.setOptions({ restrictions: { allowedFileTypes: null } })
}

function back() {
  // Embedded (editor): close popup and let parent decide
  if (props.embedded) {
    emit("cancel")
    return
  }

  const parentNodeId = parentResourceNodeId.value || 0
  router.push({
    name: "FileManagerList",
    params: { node: parentNodeId },
    query: { cid, sid, gid },
  })
}

/**
 * Safely destroy an Uppy instance across different Uppy versions and ref/non-ref cases.
 */
function destroyUppyInstance(instanceLike) {
  const instance = instanceLike?.value ?? instanceLike
  if (!instance) return

  // Uppy v2/v3 typically has close()
  if (typeof instance.close === "function") {
    try {
      instance.close({ reason: "unmount" })
    } catch (e) {
      // Some versions accept no args
      try {
        instance.close()
      } catch (e2) {
        log("Uppy close() failed", e2)
      }
    }
    return
  }

  // Some builds expose destroy()
  if (typeof instance.destroy === "function") {
    try {
      instance.destroy()
    } catch (e) {
      log("Uppy destroy() failed", e)
    }
    return
  }

  // Minimal fallback (avoid throwing during unmount)
  try {
    if (typeof instance.cancelAll === "function") instance.cancelAll()
    if (typeof instance.reset === "function") instance.reset()
  } catch (e) {
    log("Uppy fallback cleanup failed", e)
  }
}

onBeforeUnmount(() => {
  try {
    log("Destroying Uppy instance from Upload.vue")
    destroyUppyInstance(uppy)
  } catch (e) {
    log("Error while destroying Uppy", e)
  }
})
</script>
