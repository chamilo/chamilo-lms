<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Add a link</h1>
    <hr/>
    <LinkForm />
  </div>
</template>

<script setup>
import LinkForm from "../../components/links/LinkForm.vue"
import { useRouter, useRoute } from "vue-router"

const router = useRouter()
const route = useRoute()

const goBack = () => {
  router.push({
    name: "LinksList",
    query: route.query,
  })
}
</script>
