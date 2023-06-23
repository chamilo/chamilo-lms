<template>
  <div>
    <WallPostForm />

    <WallPostList />
  </div>
</template>

<script>
import WallPostForm from "../../components/social/PostForm";
import WallPostList from "../../components/social/WallPostList";

export default {
  name: "SocialNetworkWall",
  components: {WallPostList, WallPostForm},
}
</script>
