<template>
  <div v-if="data && data.resourceNode && data.resourceNode.resourceFile">
    <a
      data-fancybox="gallery"
      class="flex align-center"
      :href="data.contentUrl"
      :data-type="dataType"
    >
      <ResourceIcon class="mr-2" :file-type="data.filetype" />
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
      <ResourceIcon class="mr-2" file-type="folder"/>
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
  if (props.data.resourceNode.resourceFile.image) {
    return 'image';
  }
  if (props.data.resourceNode.resourceFile.video) {
    return 'video';
  }

  return 'iframe';
});
</script>
