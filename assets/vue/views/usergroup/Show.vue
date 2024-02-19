<template>
  <div v-if="!isLoading && groupInfo.isMember" class="social-group-show">
    <div class="group-header">
      <h1 class="group-title">{{ groupInfo?.title || '...' }}</h1>
      <p class="group-description">{{ groupInfo?.description }}</p>
    </div>

    <ul class="tabs">
      <li :class="{ active: activeTab === 'discussions' }" @click="activeTab = 'discussions'">{{ t('Discussions') }}</li>
      <li :class="{ active: activeTab === 'members' }" @click="activeTab = 'members'">{{ t('Members') }}</li>
    </ul>

    <div class="tab-content">
      <GroupDiscussions v-if="activeTab === 'discussions'" :group-id="groupInfo.id" />
      <GroupMembers v-if="activeTab === 'members'" :group-id="groupInfo.id" />
    </div>
  </div>

  <div v-if="!isLoading && !groupInfo.isMember" class="text-center">
    <div class="group-header">
      <h1 class="group-title">{{ groupInfo?.title || '...' }}</h1>
      <p class="group-description">{{ groupInfo?.description }}</p>
    </div>
    <p v-if="groupInfo.visibility === 2">{{ t('This is a closed group.') }}</p>
    <p v-if="groupInfo.role === 3">{{ t('You already sent an invitation') }}</p>
    <p v-else>{{ t('Join this group to see the content.') }}</p>
    <BaseButton
      v-if="groupInfo.visibility === 1 && groupInfo.role !== 3"
      :label="t('Join to group')"
      type="primary"
      class="mt-4"
      @click="joinGroup"
      icon="mdi-account-multiple-plus"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import GroupDiscussions from "../../components/usergroup/GroupDiscussions.vue"
import GroupMembers from "../../components/usergroup/GroupMembers.vue"
import { useI18n } from "vue-i18n"
import { useSocialInfo } from "../../composables/useSocialInfo"
import axios from "axios"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()
const activeTab = ref('discussions')
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo();

const joinGroup = async () => {
  try {
    const response = await axios.post('/social-network/group-action', {
      userId: user.value.id,
      groupId: groupInfo.value.id,
      action: 'join'
    });

    if (response.data.success) {
      await loadGroup(groupInfo.value.id);
    }
  } catch (error) {
    console.error('Error joining the group:', error);
  }
};

onMounted(async () => {
  if (route.params.group_id) {
    await loadGroup(route.params.group_id);
  }
});
</script>
