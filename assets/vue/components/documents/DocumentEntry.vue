<template>
  <div v-if="isFile">
    <BaseAppLink
      v-if="isPreviewable"
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
    <BaseAppLink
      v-else
      :data-type="dataType"
      :url="data.contentUrl"
      class="flex align-center"
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
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const cidQuery = useCidReq()
const { isFile: utilsIsFile, isImage, isVideo, isAudio } = useFileUtils()

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
  return useFileUtils().isPreviewable(props.data)
})
</script>
