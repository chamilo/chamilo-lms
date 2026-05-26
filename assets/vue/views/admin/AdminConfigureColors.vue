<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import BaseDivider from "../../components/basecomponents/BaseDivider.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import ColorThemePreview from "../../components/admin/ColorThemePreview.vue"
import ColorThemeForm from "../../components/admin/ColorThemeForm.vue"
import BrandingSection from "../../components/admin/BrandingSection.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
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

<template>
  <div
    id="top"
    class="admin-colors"
  >
    <SectionHeader :title="t('Configure Chamilo colors')">
      <BaseButton
        :label="t('Go to branding (logos)')"
        icon="customize"
        @click="goBranding"
      />
    </SectionHeader>

    <div class="section-header section-header--h2">
      <div class="flex flex-wrap items-center gap-2">
        <span
          class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 text-gray-700 px-2.5 py-1 text-xs"
        >
          {{ t("Color theme") }}:
          <strong class="ml-1">{{ selectedColorThemeTitle || "—" }}</strong>
        </span>
        <span
          class="inline-flex items-center rounded-full bg-gray-50 border border-gray-200 text-gray-700 px-2.5 py-1 text-xs"
        >
          {{ t("Visual theme (assets)") }}:
          <code class="ml-1">{{ effectiveSlug }}</code>
        </span>
      </div>

      <div
        v-if="showDefaultWarning"
        class="rounded-md border border-amber-200 bg-amber-50 text-amber-900 text-xs p-2"
      >
        {{
          t(
            "You are modifying logos for the default visual theme “chamilo”. Consider creating/using a custom visual theme to avoid overriding defaults.",
          )
        }}
      </div>
    </div>

    <div
      id="colors"
      class="admin-colors__container"
    >
      <div class="admin-colors__form">
        <ColorThemeForm @selected="onSelectedColorTheme" />
      </div>

      <div class="admin-colors__preview">
        <BaseDivider layout="vertical" />
        <ColorThemePreview />
      </div>
    </div>

    <div
      id="branding"
      class="mt-10"
    >
      <SectionHeader
        :title="t('Branding (logos)')"
        size="6"
      />
      <p class="text-sm opacity-80 mb-4">
        {{ t("Logos will be applied to the current visual theme: ") }}
        <code class="px-2 py-1 bg-gray-100 rounded">{{ effectiveSlug }}</code
        >.
        {{ t("Switch the active theme if you want to upload logos for a different one.") }}
      </p>
      <BrandingSection :slug="effectiveSlug" />
      <div class="mt-4 text-right">
        <a
          class="text-sm underline opacity-70 hover:opacity-100"
          href="#top"
        >
          {{ t("Back to top") }}
        </a>
      </div>
    </div>
  </div>
</template>
