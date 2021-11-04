<template>
  <div>
    <SocialNetworkForm />

    <SocialNetworkPostList />
  </div>
</template>

<script>
import SocialNetworkForm from "../../components/socialnetwork/WallForm";
import SocialNetworkPostList from "../../components/socialnetwork/PostList";

export default {
  name: "SocialNetworkWall",
  components: {SocialNetworkPostList, SocialNetworkForm},
}
</script>
