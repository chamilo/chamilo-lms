<template>
  <SocialNetworkPost
    v-for="message in messageList"
    :key="message.id"
    :message="message"
  />
</template>

<script>
import {MESSAGE_TYPE_WALL} from "../message/msgType";
import SocialNetworkPost from "./Post";
import {useStore} from "vuex";
import {ref, watch} from "vue";

export default {
  name: "SocialNetworkPostList",
  components: {SocialNetworkPost},
  props: {
    user: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    console.log(props.user,  '<<<<<<');
    const store = useStore();

    let messageList = ref([]);

    function listMessage(user) {
      store.state.message.resetList = true;

      store.dispatch('message/fetchAll', {
        msgType: MESSAGE_TYPE_WALL,
        'receivers.receiver': user['@id'],
        'order[sendDate]': 'desc',
      }).then(() => {
        messageList.value = store.getters['message/list'];
      });
    }

    watch(() => props.user, (current) => {listMessage(current)});

    listMessage(props.user);

    return {
      messageList
    }
  }
}
</script>
