<template>
  <SocialNetworkPost
    v-for="message in messageList"
    :key="message.id"
    :message="message"
  />

  <Loading :visible="isLoading" />
</template>

<script>
import {MESSAGE_TYPE_WALL} from "../message/constants";
import SocialNetworkPost from "./Post";
import {useStore} from "vuex";
import {inject, onMounted, ref, watch} from "vue";
import Loading from "../Loading";

export default {
  name: "SocialNetworkPostList",
  components: {Loading, SocialNetworkPost},
  setup() {
    const user = inject('social-user');
    const store = useStore();

    const messageList = ref([]);
    const isLoading = ref(false);

    async function listMessage() {
      if (!user.value['@id']) {
        return;
      }

      isLoading.value = true;

      store.state.message.resetList = true;

      try {
        await store.dispatch('message/fetchAll', {
          msgType: MESSAGE_TYPE_WALL,
          'receivers.receiver': user.value['@id'],
          'order[sendDate]': 'desc',
        });
        messageList.value = store.getters['message/list'];
      } catch (e) {
        messageList.value = [];
      }

      isLoading.value = false;
    }

    watch(() => user.value, listMessage)

    onMounted(listMessage)

    return {
      messageList,
      isLoading
    }
  }
}
</script>
