<template>
  <div>
    <div class="mb-4">
      <button class="btn btn--secondary" @click="goBack">Back</button>
    </div>
    <h1>Update Link <hr /></h1>
    <LinkForm :linkId="linkId" />
  </div>
</template>

<script>
import LinkForm from "../../components/links/LinkForm.vue";
import {useRoute, useRouter} from "vue-router";

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
  computed: {
    linkId() {
      return this.$route.params.id;
    },
  },
};
</script>
