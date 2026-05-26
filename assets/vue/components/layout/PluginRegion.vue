<template>
  <div
    v-if="blocks.length"
    :data-region="region"
    class="plugin-region"
  >
    <div
      v-for="block in blocks"
      :key="block.pluginName"
      :class="`plugin-region plugin-region--${block.pluginName}`"
      v-html="block.html"
    />
  </div>
</template>

<script setup>
import { usePluginRegion } from "../../composables/pluginRegion"

const props = defineProps({
  region: {
    required: true,
    type: String,
  },
})

const { blocks } = usePluginRegion(props.region)
</script>
