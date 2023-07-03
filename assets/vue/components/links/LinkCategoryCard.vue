<template>
  <BaseCard plain>
    <slot name="header">
      <h5 class="mb-2">{{ t('General') }}</h5>
    </slot>
    <hr class="mt-0 mb-4">
    <slot></slot>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import {useI18n} from "vue-i18n"

const {t} = useI18n()
</script>
