<template>
  <div class="row q-col-gutter-md">
    <div class="col-8">
      <SocialNetworkHome :user="user" />
    </div>
    <div class="col-4">
      <q-card flat bordered>
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
import {ref, watch} from "vue";
import SocialNetworkHome from "./Home";

export default {
  name: "SocialNetworkLayout",
  components: {SocialNetworkHome},
  props: {
    uid: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const store = useStore();

    const user = ref({});

    const currentUser = store.getters['security/getUser'];

    async function setUser(uid) {
      if (uid) {
        store.dispatch('user/load', uid).then(data => {user.value = data});
      } else {
        user.value = currentUser;
      }
    }

    setUser(props.uid);

    watch(() => props.uid, (uid, old) => setUser(uid));

    return {
      user
    }
  }
}
</script>
