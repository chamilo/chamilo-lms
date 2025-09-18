<template>
  <BaseToolbar v-if="!embedded">
    <BaseButton :label="t('Back')" icon="back" type="black" @click="back" />
  </BaseToolbar>
  <div class="flex flex-col justify-center items-center">
    <div class="mb-4 w-full">
      <Dashboard
        v-if="uppy"
        :uppy="uppy"
        :proudlyDisplayPoweredByUppy="false"
        :width="'100%'"
        :height="'350px'"
      />
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"

const Webcam = require("@uppy/webcam").default
const XHRUpload = require("@uppy/xhr-upload").default
const ImageEditor = require("@uppy/image-editor").default

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

const LOG_PREFIX = "[UPLOAD DBG]"
function log(...args) { console.log(LOG_PREFIX, ...args) }

function resolveParentFromSessionThenProp() {
  try {
    const ssRaw = sessionStorage.getItem("pf_parent")
    const ss = Number(ssRaw || 0)
    if (ss) {
      return ss
    }
    const p = Number(props.parentResourceNodeId || 0)
    return p || 0
  } catch (e) {
    return Number(props.parentResourceNodeId || 0)
  }
}

const parentIdRef = ref(0)
const fileTypeRef = ref(String(props.filetype || "file"))

function buildEndpoint(parentId) {
  const pid = String(Number(parentId || 0))
  const qs = new URLSearchParams({
    "resourceNode.parent": pid,
    parentResourceNodeId: pid,
    parent: pid,
  }).toString()
  const ep = `${ENTRYPOINT}personal_files?${qs}`
  return ep
}

const uploadedItems = ref([])
const uppy = ref(null)

function applyCurrentParentToUppy(hook = "") {
  const freshParent = resolveParentFromSessionThenProp()
  parentIdRef.value = freshParent

  const plugin = uppy.value?.getPlugin?.("XHRUpload")

  if (plugin?.setOptions) {
    const newEndpoint = buildEndpoint(freshParent)
    plugin.setOptions({ endpoint: newEndpoint })
  }

  const beforeMeta = uppy.value?.getMeta?.() || {}
  uppy.value?.setMeta({
    filetype: fileTypeRef.value,
    parentResourceNodeId: String(freshParent),
    parentResourceNode: `/api/resource_nodes/${freshParent}`,
    "resourceNode.parent": String(freshParent),
    resourceLinkList: JSON.stringify([{ gid, sid, cid, visibility: RESOURCE_LINK_PUBLISHED }]),
    isUncompressZipEnabled: false,
    fileExistsOption: "rename",
  })
  const afterMeta = uppy.value?.getMeta?.() || {}
  const ep = plugin?.opts?.endpoint
}

onMounted(() => {
  parentIdRef.value = resolveParentFromSessionThenProp()
  uppy.value = new Uppy({
    autoProceed: true,
    debug: true,
    restrictions: { allowedFileTypes: fileTypeRef.value === "certificate" ? [".html"] : null },
  })
    .use(ImageEditor, {
      cropperOptions: { viewMode: 1, background: false, autoCropArea: 1, responsive: true },
      actions: {
        revert: true, rotate: true, granularRotate: true, flip: true,
        zoomIn: true, zoomOut: true, cropSquare: true, cropWidescreen: true, cropWidescreenVertical: true,
      },
    })
    .use(XHRUpload, {
      endpoint: buildEndpoint(parentIdRef.value),
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
    .on("file-added", (file) => {
      applyCurrentParentToUppy("file-added")
    })
    .on("upload", (data) => {
      applyCurrentParentToUppy("upload")
    })
    .on("upload-progress", (file, progress) => {
    })
    .on("upload-success", (file, response) => {
      onCreated(response?.body)
      if (response?.body) uploadedItems.value.push(response.body)
    })
    .on("complete", (result) => {
      if (props.embedded) {
        emit("done", { parentNodeId: parentIdRef.value, items: uploadedItems.value })
        uploadedItems.value = []
        return
      }
      router.push({ name: "FileManagerList", params: { node: parentIdRef.value } })
    })
    .on("error", (err) => {
    })
    .on("restriction-failed", (file, error) => {
    })

  if (fileTypeRef.value !== "certificate") {
    uppy.value.use(Webcam)
  }

  const initialMeta = {
    filetype: fileTypeRef.value,
    parentResourceNodeId: String(parentIdRef.value),
    parentResourceNode: `/api/resource_nodes/${parentIdRef.value}`,
    "resourceNode.parent": String(parentIdRef.value),
    resourceLinkList: JSON.stringify([{ gid, sid, cid, visibility: RESOURCE_LINK_PUBLISHED }]),
    isUncompressZipEnabled: false,
    fileExistsOption: "rename",
  }
  uppy.value.setMeta(initialMeta)

  const initialEndpoint = uppy.value.getPlugin("XHRUpload")?.opts?.endpoint
  window.__UPPY_DBG = () => {
    try {
      const plugin = uppy.value?.getPlugin?.("XHRUpload")
      const ep = plugin?.opts?.endpoint
    } catch (e) {
      log("__UPPY_DBG error:", e)
    }
  }
})

function back() {
  if (props.embedded) {
    emit("cancel")
    return
  }
  router.push({ name: "FileManagerList", params: { node: parentIdRef.value || 0 } })
}
</script>
