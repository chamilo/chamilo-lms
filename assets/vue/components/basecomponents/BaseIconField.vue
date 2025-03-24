<script setup>
import { useI18n } from "vue-i18n"

import IconField from "primevue/iconfield"
import InputIcon from "primevue/inputicon"
import InputText from "primevue/inputtext"

import { chamiloIconToClass } from "./ChamiloIcons"

const modelValue = defineModel({
  type: String,
})

const { t } = useI18n()
</script>

<template>
  <IconField icon-position="left">
    <InputIcon :class="chamiloIconToClass['search']" />
    <InputText
      v-model="modelValue"
      :placeholder="t('Search')"
    />
  </IconField>
</template>
