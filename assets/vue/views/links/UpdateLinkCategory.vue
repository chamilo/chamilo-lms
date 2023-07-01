<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1>Update Link <hr /></h1>
    <CategoryForm :categoryId="categoryId" />
  </div>
</template>

<script>
import CategoryForm from "../../components/links/CategoryForm.vue";
import {useRoute, useRouter} from "vue-router";

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
  computed: {
    categoryId() {
      return this.$route.params.id;
    },
  },
};
</script>
