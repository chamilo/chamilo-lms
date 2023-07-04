<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1 class="text-h3 font-small text-gray-800 mb-4">Import glossary<hr /></h1>
    <GlossaryImportForm />
  </div>
</template>

<script>
import GlossaryImportForm from "../../components/glossary/GlossaryImportForm.vue";
import {useRoute, useRouter} from "vue-router";

export default {
  components: {
    GlossaryImportForm,
  },
  setup() {
    const router = useRouter();
    const route = useRoute();

    const goBack = () => {
      router.push({
        name: "GlossaryList",
        query: route.query,
      });
    };

    return {
      goBack,
    };
  },
};
</script>
