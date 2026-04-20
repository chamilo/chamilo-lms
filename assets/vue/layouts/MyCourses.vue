<template>
  <SectionHeader :title="t('My courses')"></SectionHeader>
  <router-view />
</template>

<script setup>
import { useI18n } from "vue-i18n"
import SectionHeader from "../components/layout/SectionHeader.vue"

const { t } = useI18n()
</script>
