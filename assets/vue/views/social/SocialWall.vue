<template>
  <div id="social-wall-container">
    <div class="flex justify-center mb-6 space-x-4">
      <button
        :class="['tab', { 'tab-active': filterType === null }]"
        @click="filterMessages(null)"
      >
        {{ t('All Messages') }}
      </button>
      <button
        :class="['tab', { 'tab-active': filterType === 'promoted' }]"
        @click="filterMessages('promoted')"
      >
        {{ t('Promoted Messages') }}
      </button>
    </div>

    <SocialWallPostForm v-if="!hidePostForm && isCurrentUser" @post-created="refreshPosts" class="mb-6" />
    <SocialWallPostList ref="postListRef" class="mb-6" />
  </div>
</template>

<script setup>
import { inject, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import SocialWallPostForm from "../../components/social/SocialWallPostForm.vue";
import SocialWallPostList from "../../components/social/SocialWallPostList.vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const props = defineProps({
  hidePostForm: {
    type: Boolean,
    default: false
  }
});

const postListRef = ref(null);
const isCurrentUser = inject('is-current-user');
const route = useRoute();
const router = useRouter();
const filterType = ref(route.query.filterType || null);

watch(
  () => route.query.filterType,
  (newFilterType) => {
    filterType.value = newFilterType;
    refreshPosts();
  }
);

function refreshPosts() {
  if (postListRef.value) {
    postListRef.value.refreshPosts();
  }
}

function filterMessages(type) {
  router.push({ path: '/social', query: { filterType: type } });
}
</script>

<style scoped>

</style>
