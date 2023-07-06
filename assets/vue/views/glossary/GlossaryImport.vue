<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Import glossary<hr /></h1>
    <GlossaryImportForm />
  </div>
</template>

<script setup>
import GlossaryImportForm from "../../components/glossary/GlossaryImportForm.vue"
import {useRoute, useRouter} from "vue-router"

const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>
