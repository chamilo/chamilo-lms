<template>
  <BaseCard plain class="bg-white">
    <template v-if="showHeader" #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <slot name="header"/>
      </div>
    </template>

    <hr v-if="showHeader" class="-mt-2 mb-4 -mx-4">

    <div>
      <slot/>
    </div>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue"
import {useI18n} from "vue-i18n"

const {t} = useI18n()
defineProps({
  showHeader: {
    type: Boolean,
    default: true,
  },
});
</script>
