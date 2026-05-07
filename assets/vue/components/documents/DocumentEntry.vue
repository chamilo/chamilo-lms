<template>
  <div v-if="isCloudLink">
    <a
      :href="cloudLinkUrl"
      class="flex align-center"
      target="_blank"
      rel="noopener noreferrer"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ documentTitle }}
    </a>
  </div>

  <div v-else-if="isFile">
    <a
      v-if="isOnlyofficeSupported"
      :href="onlyofficeUrl"
      class="flex align-center"
      target="_blank"
      rel="noopener noreferrer"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ documentTitle }}
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
      {{ documentTitle }}
    </BaseAppLink>

    <BaseAppLink
      v-else
      :data-type="dataType"
      :url="data.contentUrl"
      download
      class="flex align-center"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      {{ documentTitle }}
    </BaseAppLink>
  </div>
  <div v-else>
    <BaseAppLink
      :to="{
        name: 'DocumentsList',
        params: { node: data.resourceNode.id },
        query: cidQuery,
      }"
      class="flex align-center"
    >
      <ResourceIcon
        :resource-data="data"
        class="mr-2"
      />
      <b>{{ documentTitle }}</b>
    </BaseAppLink>
  </div>
</template>
<script setup>
import { computed } from "vue"
import ResourceIcon from "./ResourceIcon.vue"
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

const documentTitle = computed(() => {
  return String(props.data?.title || props.data?.resourceNode?.title || "")
})

const onlyofficePluginEnabled = computed(() => {
  return true === platformConfigStore.plugins?.onlyoffice?.enabled
})

const onlyofficeEditorPath = computed(() => {
  return String(platformConfigStore.plugins?.onlyoffice?.editorPath || "/plugin/Onlyoffice/editor.php")
})

const isCloudLink = computed(() => {
  return "link" === String(props.data?.filetype || "").toLowerCase()
})

const cloudLinkUrl = computed(() => {
  const rawUrl = String(props.data?.comment || "").trim()

  if (!rawUrl) {
    return "#"
  }

  try {
    const parsedUrl = new URL(rawUrl)

    if (!["http:", "https:"].includes(parsedUrl.protocol)) {
      return "#"
    }

    return parsedUrl.toString()
  } catch (error) {
    return "#"
  }
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
  return !!props.data && utilsIsFile(props.data)
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
  const url = new URL(onlyofficeEditorPath.value, window.location.origin)

  url.searchParams.set("cid", String(cidQuery.cid || 0))
  url.searchParams.set("sid", String(cidQuery.sid || 0))
  url.searchParams.set("groupId", String(cidQuery.gid || 0))
  url.searchParams.set("docId", String(props.data.iid))
  url.searchParams.set("returnUrl", window.location.href)

  return url.toString()
})
</script>
