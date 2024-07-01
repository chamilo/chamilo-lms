<script setup>
import { usePlatformConfig } from "../../store/platformConfig"
import { useVisualTheme } from "../../composables/theme"

const platformConfigStore = usePlatformConfig()
const { getThemeAssetUrl } = useVisualTheme()

const siteName = platformConfigStore.getSetting("platform.site_name")
</script>

<template>
  <img
    :alt="siteName"
    :title="siteName"
    :src="getThemeAssetUrl('images/header-logo.png')"
  />
</template>
