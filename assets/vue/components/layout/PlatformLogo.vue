<script setup>
import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg"
import { usePlatformConfig } from "../../store/platformConfig"

const platformConfigStore = usePlatformConfig()

const siteName = platformConfigStore.getSetting("platform.site_name")
</script>

<template>
  <img
    :alt="siteName"
    :src="headerLogoPath"
    :title="siteName"
  />
</template>
