<template>
  <div class="export-glossary">
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h2>Export Glossary</h2>
    <GlossaryExportForm />
  </div>
</template>

<script setup>
import GlossaryExportForm from "../../components/glossary/GlossaryExportForm.vue"
import { useRoute, useRouter } from "vue-router"

const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>

<style scoped>
.export-glossary {
  margin: 20px;
}
</style>
