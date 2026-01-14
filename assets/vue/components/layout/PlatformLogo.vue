<script setup>
import { computed, ref, watch } from "vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

const platformConfigStore = usePlatformConfig()
const securityStore = useSecurityStore()

const siteName = platformConfigStore.getSetting("platform.site_name")

const theme = computed(() => platformConfigStore.visualTheme || "chamilo")
const bust = ref(Date.now())

/**
 * It will always serve the best available logo (svg/png) and fallback to default theme.
 * This avoids strict=1 probing and prevents 404 noise in the console.
 */
const logoUrl = computed(() => {
  return `/themes/${encodeURIComponent(theme.value)}/logo/header?t=${bust.value}`
})

const showImg = ref(true)

watch(
  () => platformConfigStore.visualTheme,
  () => {
    bust.value = Date.now()
    showImg.value = true
  },
)

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
