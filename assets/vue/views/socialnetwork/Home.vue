<template>
  <div>
    <SocialNetworkForm :user="user" />

    <SocialNetworkPostList :user="user" />
  </div>
</template>

<script>
import SocialNetworkForm from "../../components/socialnetwork/Form";
import SocialNetworkPostList from "../../components/socialnetwork/PostList";
import {useRoute} from "vue-router";

export default {
  name: "SocialNetworkHome",
  components: {SocialNetworkPostList, SocialNetworkForm},
  props: {
    user: {
      type: Object,
      required: true
    }
  }
}
</script>
