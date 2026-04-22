<template>
  <div v-if="isFile">
    <a
      v-if="isOnlyofficeSupported"
      :href="onlyofficeUrl"
      class="flex align-center"
      rel="noopener noreferrer"
      target="_blank"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ data.title }}
    </a>
    <BaseAppLink
      v-else-if="isPreviewable"
      :data-type="dataType"
      :url="data.contentUrl"
      class="flex align-center"
      data-fancybox="gallery"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ data.title }}
    </BaseAppLink>
    <div
      v-else-if="hideDownloadIcon"
      class="flex align-center"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ data.title }}
    </div>
    <BaseAppLink
      v-else
      :data-type="dataType"
      :url="data.contentUrl"
      class="flex align-center"
      download
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ data.title }}
    </BaseAppLink>
  </div>
  <div v-else>
    <BaseAppLink
      :to="{
        name: 'DocumentsList',
        params: { node: props.data.resourceNode.id },
        query: cidQuery,
      }"
      class="flex align-center"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      <b>{{ data.resourceNode.title }}</b>
    </BaseAppLink>
  </div>
</template>
<script setup>
import ResourceIcon from "./ResourceIcon.vue"
import { computed } from "vue"
import { useCidReq } from "../../composables/cidReq"
import { useFileUtils } from "../../composables/fileUtils"
import { usePlatformConfig } from "../../store/platformConfig"

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const cidQuery = useCidReq()
const platformConfigStore = usePlatformConfig()
const { isFile: utilsIsFile, isImage, isVideo, isAudio, isPreviewable: utilsIsPreviewable } = useFileUtils()

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

const hideDownloadIcon = computed(() => {
  return platformConfigStore.getSetting("document.documents_hide_download_icon") === "true"
})

const onlyofficePluginEnabled = computed(() => {
  return platformConfigStore.plugins?.onlyoffice?.enabled === true
})

const onlyofficeEditorPath = computed(() => {
  return String(platformConfigStore.plugins?.onlyoffice?.editorPath || "/plugin/Onlyoffice/editor.php")
})

const dataType = computed(() => {
  if (!utilsIsFile(props.data)) {
    return ""
  }

  if (isImage(props.data)) {
    return "image"
  }
  if (isVideo(props.data)) {
    return "video"
  }
  if (isAudio(props.data)) {
    return "audio"
  }

  return "iframe"
})

const isFile = computed(() => {
  return props.data && utilsIsFile(props.data)
})

const isPreviewable = computed(() => {
  return utilsIsPreviewable(props.data)
})

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

const isOnlyofficeSupported = computed(() => {
  if (!onlyofficePluginEnabled.value) {
    return false
  }

  if (!props.data || !utilsIsFile(props.data)) {
    return false
  }

  const filetype = String(props.data?.filetype || "")
    .trim()
    .toLowerCase()

  if (!["file", "certificate"].includes(filetype)) {
    return false
  }

  const resourceFileId = props.data?.resourceNode?.firstResourceFile?.id
  if (!resourceFileId) {
    return false
  }

  const ext = getOnlyofficeExtension(props.data)
  if (!ext) {
    return false
  }

  return onlyofficeSupportedExtensions.has(ext)
})

const onlyofficeUrl = computed(() => {
  const sp = new URLSearchParams({
    cid: String(cidQuery.cid || 0),
    sid: String(cidQuery.sid || 0),
    groupId: String(cidQuery.gid || 0),
    docId: String(props.data.iid),
    returnUrl: window.location.href,
  })

  return `${onlyofficeEditorPath.value}?${sp.toString()}`
})
</script>
