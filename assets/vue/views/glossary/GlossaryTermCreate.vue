<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Add new glossary term<hr /></h1>
    <GlossaryForm />
  </div>
</template>

<script setup>
import GlossaryForm from "../../components/glossary/GlossaryForm.vue"
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>
