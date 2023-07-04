<template>
  <div>
    <h2 class="text-h3 font-small text-gray-800 mb-4">
      <BaseIcon icon="link-add"/>
      {{ t('Add a link') }}
    </h2>
    <hr class="m-0 mb-4"/>
    <LinkForm
       @back-pressed="goBack"
    />
  </div>
</template>

<script setup>
import LinkForm from "../../components/links/LinkForm.vue"
import { useRouter, useRoute } from "vue-router"
import {useI18n} from "vue-i18n";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

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
