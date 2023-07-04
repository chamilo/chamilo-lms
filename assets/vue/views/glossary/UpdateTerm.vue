<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1>Edit term  <hr /></h1>
    <GlossaryForm :termId="termId" />
  </div>
</template>

<script>
import GlossaryForm from "../../components/glossary/GlossaryForm.vue";
import {useRoute, useRouter} from "vue-router";

export default {
  components: {
    GlossaryForm,
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
  computed: {
    termId() {
      return this.$route.params.id;
    },
  },
};
</script>
