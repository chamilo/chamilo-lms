<script setup>
import { ref } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useVisualTheme } from "../../composables/theme"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const platformConfigStore = usePlatformConfig()
const { getThemeAssetUrl } = useVisualTheme()

const siteName = platformConfigStore.getSetting("platform.site_name")

const sources = [getThemeAssetUrl("images/header-logo.svg"), getThemeAssetUrl("images/header-logo.png")]

const currentSrc = ref(sources[0])

const onError = () => {
  const currentIndex = sources.indexOf(currentSrc.value)

  if (currentIndex < sources.length - 1) {
    currentSrc.value = sources[currentIndex + 1]
  } else {
    console.error("All image sources failed to load.")
  }
}
</script>

<template>
  <div class="platform-logo">
    <BaseAppLink :to="{ name: 'Index' }">
      <img
        :alt="siteName"
        :src="currentSrc"
        :title="siteName"
        @error="onError"
      />
    </BaseAppLink>
  </div>
</template>
