<template>
  <a
    :data-type="getDataType"
    :href="resource.contentUrl"
    data-fancybox="gallery"
  >
    <DocumentEntryThumbnail
      :data="resource"
      :title="resource.title"
    />
    {{ resource.title }}
  </a>
</template>

<script>
import DocumentEntryThumbnail from "./DocumentEntryThumbnail.vue"

export default {
  name: "ResourceFileLink",
  components: {
    DocumentEntryThumbnail,
  },
  props: {
    resource: {
      type: Object,
      required: true,
    },
  },
  computed: {
    getDataType() {
      const node = this.resource && this.resource.resourceNode
      const file = node && node.firstResourceFile

      if (file && file.image) {
        return "image"
      }

      if (file && file.video) {
        return "video"
      }

      return "iframe"
    },
  },
}
</script>
