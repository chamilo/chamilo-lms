<template>
  <BaseToolbar>
    <BaseButton
      :label="t('Back')"
      icon="back"
      type="black"
      @click="back"
    />
  </BaseToolbar>
  <div class="flex flex-col justify-center items-center">
    <div class="mb-4">
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
import { ref, watch, onMounted } from "vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import "@uppy/webcam/dist/style.css"
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"

const Webcam = require("@uppy/webcam").default
const XHRUpload = require("@uppy/xhr-upload").default
const ImageEditor = require("@uppy/image-editor").default

import { useRoute, useRouter } from "vue-router"
import { ENTRYPOINT } from "../../config/entrypoint"
import { useCidReq } from "../../composables/cidReq"
import { useUpload } from "../../composables/upload"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const route = useRoute()
const router = useRouter()
const { gid, sid, cid } = useCidReq()
const { onCreated } = useUpload()
const { t } = useI18n()
const filetype = route.query.filetype === "certificate" ? "certificate" : "file"
const isUncompressZipEnabled = ref(false)
const fileExistsOption = ref("rename")
const parentResourceNodeId = ref(Number(route.query.parentResourceNodeId || route.params.node))
const resourceLinkList = ref(
  JSON.stringify([
    {
      gid,
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED,
    },
  ]),
)
const uppy = ref(null)

onMounted(() => {
  uppy.value = new Uppy({
    autoProceed: true,
    restrictions: {
      allowedFileTypes: filetype === "certificate" ? [".html"] : null,
    },
  })
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
      endpoint: `${ENTRYPOINT}personal_files`,
      formData: true,
      fieldName: "uploadFile",
    })
    .on("upload-success", (item, response) => {
      onCreated(response.body)
    })
    .on("complete", () => {
      console.log("Upload complete, sending message...")
      const parentNodeId = parentResourceNodeId.value
      localStorage.setItem("isUploaded", "true")
      localStorage.setItem("uploadParentNodeId", parentNodeId)
      setTimeout(() => {
        router.push({
          name: route.query.returnTo || "FileManagerList",
          params: { node: parentNodeId },
          query: { ...route.query, parentResourceNodeId: parentNodeId },
        })
      }, 2000)
    })

  if (filetype !== "certificate") {
    uppy.value.use(Webcam)
  }

  uppy.value.setMeta({
    filetype,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
    isUncompressZipEnabled: isUncompressZipEnabled.value,
    fileExistsOption: fileExistsOption.value,
  })
})

watch(isUncompressZipEnabled, () => {
  if (uppy.value) {
    uppy.value.setOptions({
      meta: { isUncompressZipEnabled: isUncompressZipEnabled.value },
    })
  }
})

watch(fileExistsOption, () => {
  if (uppy.value) {
    uppy.value.setOptions({
      meta: { fileExistsOption: fileExistsOption.value },
    })
  }
})

function back() {
  let queryParams = { cid, sid, gid, filetype, tab: route.query.tab }
  router.push({
    name: "FileManagerList",
    params: { node: route.query.tab ? parentResourceNodeId.value : 0 },
    query: queryParams,
  })
}
</script>
