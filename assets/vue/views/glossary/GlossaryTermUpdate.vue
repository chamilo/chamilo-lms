<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1>
      Edit term
      <hr />
    </h1>
    <GlossaryForm :termId="termId" />
  </div>
</template>

<script setup>
import GlossaryForm from "../../components/glossary/GlossaryForm.vue"
import { useRoute, useRouter } from "vue-router"
import { computed } from "vue"

const router = useRouter()
const route = useRoute()

const termId = computed(() => route.params.id)

const goBack = () => {
  router.push({
    name: "GlossaryList",
    query: route.query,
  })
}
</script>
