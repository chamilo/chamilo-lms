<script setup>
import { computed, ref, watch } from "vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()

const siteName = computed(() => platformConfigStore.getSetting("platform.site_name") || "Chamilo")
const theme = computed(() => platformConfigStore.visualTheme || "chamilo")
const bust = ref(Date.now())
const showImg = ref(true)

const allowedLogoExtensions = ["svg", "png", "jpg", "jpeg", "gif", "webp"]

const configuredLogoUrl = computed(() => {
  const value = platformConfigStore.getSetting?.("platform.platform_logo_url")
  const url = String(value || "").trim()

  if (!isValidLogoUrl(url)) {
    return ""
  }

  return url
})

const logoUrl = computed(() => {
  if (configuredLogoUrl.value) {
    return configuredLogoUrl.value
  }

  return `/themes/${encodeURIComponent(theme.value)}/logo/header?t=${bust.value}`
})

watch(
  () => [platformConfigStore.visualTheme, configuredLogoUrl.value],
  () => {
    bust.value = Date.now()
    showImg.value = true
  },
)

function isValidLogoUrl(url) {
  if (!url) {
    return false
  }

  if (url.startsWith("/")) {
    return hasAllowedLogoExtension(url)
  }

  try {
    const parsedUrl = new URL(url)

    if (!["http:", "https:"].includes(parsedUrl.protocol)) {
      return false
    }

    return hasAllowedLogoExtension(parsedUrl.pathname)
  } catch {
    return false
  }
}

function hasAllowedLogoExtension(path) {
  const cleanPath = String(path || "")
    .split("?")[0]
    .split("#")[0]
  const extension = cleanPath.split(".").pop()?.toLowerCase()

  return allowedLogoExtensions.includes(extension)
}

function onError(e) {
  showImg.value = false
  if (e?.target) {
    e.target.style.display = "none"
  }
}
</script>
<template>
  <div class="platform-logo">
    <BaseAppLink :to="securityStore.user ? { name: 'Home' } : { name: 'Index' }">
      <img
        v-if="showImg"
        :alt="siteName"
        :src="logoUrl"
        :title="siteName"
        decoding="async"
        fetchpriority="high"
        @error="onError"
      />
      <span
        v-else
        class="font-semibold text-primary"
        aria-label="logo"
      >
        {{ siteName }}
      </span>
    </BaseAppLink>
  </div>
</template>
