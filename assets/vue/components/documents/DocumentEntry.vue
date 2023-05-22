<template>
  <div v-if="data && data.resourceNode && data.resourceNode.resourceFile">
    <a
      data-fancybox="gallery"
      class="flex align-center"
      :href="data.contentUrl"
      :data-type="dataType"
    >
      <ResourceIcon class="mr-2" :resource-data="data" />
      {{ data.title }}
    </a>
  </div>
  <div v-else>
    <RouterLink
      class="flex align-center"
      :to="{
        name: 'DocumentsList',
        params: { node: data.resourceNode.id },
        query: folderParams,
      }"
    >
      <ResourceIcon class="mr-2" :resource-data="data"/>
      <b>{{ data.resourceNode.title }}</b>
    </RouterLink>
  </div>
</template>

<script setup>
import ResourceIcon from "./ResourceIcon.vue";
import {computed} from "vue";

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
});

const dataType = computed(() => {
  let resourceFile = props.data.resourceNode.resourceFile;
  if (resourceFile === null) {
    return '';
  }
  if (resourceFile.image) {
    return 'image';
  }
  if (resourceFile.video) {
    return 'video';
  }

  return 'iframe';
});
</script>
