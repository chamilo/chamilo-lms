<template>
  <div>
    <h2 class="text-h3 font-small text-gray-800 mb-4">
      {{ t('Upate link') }}
    </h2>
    <hr class="m-0 mb-4"/>
    <LinkForm
      :link-id="linkId"
      @back-pressed="goBack"
    />
  </div>
</template>

<script setup>
import LinkForm from "../../components/links/LinkForm.vue"
import {useRoute, useRouter} from "vue-router"
import {computed} from "vue";
import {useI18n} from "vue-i18n"

const {t} = useI18n()
const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "LinksList",
    query: route.query,
  })
}

const linkId = computed(() => {
  return route.params.id
})
</script>
