<template>
  <div class="admin-colors">
    <SectionHeader :title="t('Configure Chamilo colors')" />

    <div class="admin-colors__container">
      <div class="admin-colors__form">
        <ColorThemeForm />
      </div>

      <div class="admin-colors__preview">
        <BaseDivider layout="vertical" />

        <ColorThemePreview />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import BaseDivider from "../../components/basecomponents/BaseDivider.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import ColorThemePreview from "../../components/admin/ColorThemePreview.vue"
import ColorThemeForm from "../../components/admin/ColorThemeForm.vue"

const { t } = useI18n()
</script>
