<template>
  <div class="row q-col-gutter-md">
    <div class="col-8">
      <SocialNetworkWall />
    </div>
    <div class="col-4">
      <q-card bordered flat>
        <img
          :src="user.illustrationUrl"
        />

        <q-card-section class="text-center">
          <div class="text-h6">{{ user.fullName }}</div>
          <div class="text-subtitle2">{{ user.username }}</div>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<script>
import {useStore} from "vuex";
import {onMounted, provide, readonly, ref, watch} from "vue";
import SocialNetworkWall from "./Wall";
import {useRoute} from "vue-router";

export default {
  name: "SocialNetworkLayout",
  components: {SocialNetworkWall},
  setup() {
    const store = useStore();
    const route = useRoute();

    const user = ref({});

    provide('social-user', readonly(user));

    async function loadUser() {
      try {
        user.value = route.query.id
          ? await store.dispatch('user/load', route.query.id)
          : store.getters['security/getUser'];
      } catch (e) {
        user.value = {};
      }
    }

    onMounted(loadUser);

    watch(() => route.query, loadUser);

    return {
      user
    }
  }
}
</script>
