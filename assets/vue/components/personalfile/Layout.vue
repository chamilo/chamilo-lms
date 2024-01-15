<template>
  <div class="flex flex-col md:flex-row gap-4">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard />
      <SocialSideMenu />
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
      <h2 v-t="'Friends'" class="mr-auto" />
      <hr />
      <router-view></router-view>
    </div>
  </div>
</template>
<script>
  import SocialSideMenu from "../social/SocialSideMenu.vue";
  import UserProfileCard from "../userreluser/UserProfileCard.vue";

  export default {
      name: 'PersonalFileLayout',
    components: {UserProfileCard, SocialSideMenu}
  }
</script>
