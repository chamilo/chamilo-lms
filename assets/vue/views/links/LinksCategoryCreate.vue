<template>
  <div>
    <h2 class="text-h3 font-small text-gray-800 mb-4">
      <BaseIcon icon="folder-plus"/>
      {{ t('Add a category') }}
    </h2>
    <hr class="m-0 mb-4"/>
    <LinkCategoryForm
      @back-pressed="goBack"
    />
  </div>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import LinkCategoryForm from "../../components/links/LinkCategoryForm.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import {useI18n} from "vue-i18n";

const {t} = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "LinksList",
    query: route.query,
  })
}
</script>
