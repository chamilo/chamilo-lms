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
import {computed, ref, watch} from "vue";
import Loading from "../Loading";

export default {
  name: "SocialNetworkPostList",
  components: {Loading, SocialNetworkPost},
  props: {
    user: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const store = useStore();

    let messageList = ref([]);

    async function listMessage(user) {
      store.state.message.resetList = true;

      await store.dispatch('message/fetchAll', {
        msgType: MESSAGE_TYPE_WALL,
        'receivers.receiver': user['@id'],
        'order[sendDate]': 'desc',
      });
      messageList.value = store.getters['message/list'];
    }

    watch(() => props.user, (current) => {listMessage(current)});

    listMessage(props.user);

    return {
      messageList,
      isLoading: computed(() => store.state.message.isLoading)
    }
  }
}
</script>
