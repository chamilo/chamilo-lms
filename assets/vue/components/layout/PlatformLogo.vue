<script setup>
import { computed, ref, watch } from "vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()

const siteName = platformConfigStore.getSetting("platform.site_name")

const theme = computed(() => platformConfigStore.visualTheme || "chamilo")
const DEFAULT_THEME = "chamilo"
const bust = ref(Date.now())

function themeUrl(name, path, { strict = false } = {}) {
  const base = `/themes/${encodeURIComponent(name)}/${path}`
  const qs = []
  if (strict) qs.push("strict=1")
  qs.push(`t=${bust.value}`)
  return `${base}?${qs.join("&")}`
}

const sources = computed(() => [
  themeUrl(theme.value, "images/header-logo.svg", { strict: true }),
  themeUrl(theme.value, "images/header-logo.png", { strict: true }),
  themeUrl(theme.value, "images/header-logo.svg"),
  themeUrl(theme.value, "images/header-logo.png"),
  themeUrl(DEFAULT_THEME, "images/header-logo.svg"),
  themeUrl(DEFAULT_THEME, "images/header-logo.png"),
])

const idx = ref(0)
const currentSrc = computed(() => sources.value[idx.value] || "")

watch(
  () => platformConfigStore.visualTheme,
  () => {
    idx.value = 0
    bust.value = Date.now()
  }
)

const onError = () => {
  if (idx.value < sources.value.length - 1) {
    idx.value++
  } else {
    idx.value = sources.value.length
  }
}
</script>
<template>
  <div class="platform-logo">
    <BaseAppLink :to="securityStore.user ? { name: 'Home' } : { name: 'Index' }">
      <img
        v-if="currentSrc"
        :alt="siteName"
        :src="currentSrc"
        :title="siteName"
        decoding="async"
        fetchpriority="high"
        @error="onError"
      />
      <span v-else class="font-semibold text-primary" aria-label="logo">
        {{ siteName }}
      </span>
    </BaseAppLink>
  </div>
</template>
