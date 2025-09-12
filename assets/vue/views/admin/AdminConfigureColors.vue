<template>
  <div class="admin-colors scroll-mt-24" id="top">
    <SectionHeader :title="t('Configure Chamilo colors')" />

    <div class="sticky top-0 z-10 bg-white/75 backdrop-blur-sm border-b border-gray-100 shadow-sm py-2 mb-6">
      <div class="flex flex-wrap items-center gap-2 px-3">
        <span
          class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 text-gray-700 px-2.5 py-1 text-xs"
        >
          {{ t('Color theme') }}:
          <strong class="ml-1">{{ selectedColorThemeTitle || '—' }}</strong>
        </span>
        <span
          class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 text-gray-700 px-2.5 py-1 text-xs"
        >
          {{ t('Visual theme (assets)') }}:
          <code class="ml-1">{{ effectiveSlug }}</code>
        </span>

        <button class="btn btn--primary btn--small ml-auto" @click="goBranding">
          {{ t('Go to branding (logos)') }}
        </button>
      </div>

      <div v-if="showDefaultWarning" class="px-3 mt-2">
        <div class="rounded-md border border-amber-200 bg-amber-50 text-amber-900 text-xs p-2">
          {{
            t(
              'You are modifying logos for the default visual theme “chamilo”. Consider creating/using a custom visual theme to avoid overriding defaults.'
            )
          }}
        </div>
      </div>
    </div>

    <div class="admin-colors__container" id="colors">
      <div class="admin-colors__form">
        <ColorThemeForm @selected="onSelectedColorTheme" />
      </div>

      <div class="admin-colors__preview">
        <BaseDivider layout="vertical" />
        <ColorThemePreview />
      </div>
    </div>

    <div class="mt-10 scroll-mt-24" id="branding">
      <SectionHeader :title="t('Branding (logos)')" size="6" />
      <p class="text-sm opacity-80 mb-4">
        {{ t('Logos will be applied to the current visual theme: ') }}
        <code class="px-2 py-1 bg-gray-100 rounded">{{ effectiveSlug }}</code>.
        {{ t('Switch the active theme if you want to upload logos for a different one.') }}
      </p>
      <BrandingSection :slug="effectiveSlug" />
      <div class="mt-4 text-right">
        <a href="#top" class="text-sm underline opacity-70 hover:opacity-100">
          {{ t('Back to top') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import BaseDivider from "../../components/basecomponents/BaseDivider.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import ColorThemePreview from "../../components/admin/ColorThemePreview.vue"
import ColorThemeForm from "../../components/admin/ColorThemeForm.vue"
import BrandingSection from "../../components/admin/BrandingSection.vue"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const { visualTheme } = storeToRefs(platformConfigStore)

const DEFAULT_THEME = "chamilo"
const effectiveSlug = computed(() => visualTheme.value || DEFAULT_THEME)
const showDefaultWarning = computed(() => effectiveSlug.value === DEFAULT_THEME)

const selectedColorThemeTitle = ref("")

function onSelectedColorTheme(theme) {
  selectedColorThemeTitle.value = theme?.title || ""
}

function goBranding() {
  document.getElementById("branding")?.scrollIntoView({ behavior: "smooth", block: "start" })
}
</script>

<style>
html {
  scroll-behavior: smooth;
}
.scroll-mt-24 {
  scroll-margin-top: 6rem;
}
</style>
