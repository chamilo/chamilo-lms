<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Add a category<hr /></h1>
    <CategoryForm />
  </div>
</template>

<script>
import { useRouter, useRoute } from 'vue-router';
import CategoryForm from "../../components/links/CategoryForm.vue";

export default {
  components: {
    CategoryForm,
  },
  setup() {
    const router = useRouter();
    const route = useRoute();

    const goBack = () => {
      router.push({
        name: "LinksList",
        query: route.query,
      });
    };

    return {
      goBack,
    };
  },
};
</script>
