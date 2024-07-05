<script setup>
import { usePlatformConfig } from "../../store/platformConfig"
import { useVisualTheme } from "../../composables/theme"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const platformConfigStore = usePlatformConfig()
const { getThemeAssetUrl } = useVisualTheme()

const siteName = platformConfigStore.getSetting("platform.site_name")
</script>

<template>
  <div class="platform-logo">
    <BaseAppLink :to="{ name: 'Index' }">
      <picture>
        <source
          :srcset="getThemeAssetUrl('images/header-logo.svg')"
          type="image/svg+xml"
        />
        <img
          :alt="siteName"
          :title="siteName"
          :src="getThemeAssetUrl('images/header-logo.png')"
        />
      </picture>
    </BaseAppLink>
  </div>
</template>
