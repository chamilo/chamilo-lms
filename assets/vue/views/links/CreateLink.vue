<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Add a link<hr /></h1>
    <LinkForm />
  </div>
</template>

<script>
import LinkForm from "../../components/links/LinkForm.vue";
import { useRouter, useRoute } from 'vue-router';

export default {
  components: {
    LinkForm,
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
