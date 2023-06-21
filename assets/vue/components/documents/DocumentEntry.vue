<template>
  <div v-if="isFile">
    <a
      data-fancybox="gallery"
      class="flex align-center"
      :href="data.contentUrl"
      :data-type="dataType"
    >
      <ResourceIcon class="mr-2" :resource-data="data"/>
      {{ data.title }}
    </a>
  </div>
  <div v-else>
    <RouterLink
      class="flex align-center"
      :to="{
        name: 'DocumentsList',
        params: {node: props.data.resourceNode.id},
        query: cidQuery,
      }"
    >
      <ResourceIcon class="mr-2" :resource-data="data"/>
      <b>{{ data.resourceNode.title }}</b>
    </RouterLink>
  </div>
</template>

<script setup>
import ResourceIcon from "./ResourceIcon.vue"
import {computed} from "vue"
import {useCidReq} from '../../composables/cidReq'
import {useFileUtils} from "../../composables/fileUtils";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
})

const cidQuery = useCidReq()
const {isFile: utilsIsFile, isImage, isVideo, isAudio} = useFileUtils()

const dataType = computed(() => {
  if (!utilsIsFile(props.data)) {
    return ''
  }

  if (isImage(props.data)) {
    return 'image'
  }
  if (isVideo(props.data)) {
    return 'video'
  }
  if (isAudio(props.data)) {
    return 'video'
  }

  return 'iframe';
})

const isFile = computed(() => {
  return props.data && utilsIsFile(props.data)
})
</script>
