<template>

  <div v-if="!isLoading && !groupInfo.isMember" class="social-group-show">
    <div class="social-group-details-info">
      <p v-if="groupInfo.visibility === 1" class="text-center">
        {{ t('This is an open group') }}
      </p>
      <p v-else>
        {{ t('This is a closed group') }}
      </p>
    </div>
  </div>

  <div class="social-group-show group-info text-center">
    <div class="group-header">
      <h1 class="group-title">{{ groupInfo?.title || '...' }}</h1>
      <p class="group-description">{{ groupInfo?.description }}</p>
    </div>
  </div>

  <div v-if="!isLoading && (groupInfo.isMember || groupInfo.visibility === 1)" class="social-group-show">
    <div v-if="!groupInfo.isMember" class="text-center">
      <div v-if="![4, 3].includes(groupInfo.role)">
        <BaseButton
          :label="t('Join to group')"
          type="primary"
          class="mt-4"
          @click="joinGroup"
          icon="join-group"
        />
      </div>
      <div v-else-if="groupInfo.role === 3">
        <BaseButton
          :label="t('You have been invited to join this group')"
          type="primary"
          class="mt-4"
          @click="joinGroup"
          icon="email-unread"
        />
      </div>
    </div>
    <div v-if="groupInfo.isMember" class="text-center">
      <ul class="tabs">
        <li :class="{ active: activeTab === 'discussions' }" @click="activeTab = 'discussions'">{{ t('Discussions') }}</li>
        <li :class="{ active: activeTab === 'members' }" @click="activeTab = 'members'">{{ t('Members') }}</li>
      </ul>
      <div class="tab-content">
        <GroupDiscussions v-if="activeTab === 'discussions'" :group-id="groupInfo.id" />
        <GroupMembers v-if="activeTab === 'members'" :group-id="groupInfo.id" />
      </div>
    </div>
  </div>

  <div v-if="!isLoading && !(groupInfo.isMember || groupInfo.visibility === 1)" class="text-center">
    <div v-if="![4, 3].includes(groupInfo.role)">
      <BaseButton
        :label="t('Join to group')"
        type="primary"
        class="mt-4"
        @click="joinGroup"
        icon="join-group"
      />
    </div>
    <div v-else-if="groupInfo.role === 3">
      <BaseButton
        :label="t('You have been invited to join this group')"
        type="primary"
        class="mt-4"
        @click="joinGroup"
        icon="email-unread"
      />
    </div>
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
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()
const joinGroup = async () => {
  try {
    const response = await axios.post('/social-network/group-action', {
      userId: user.value.id,
      groupId: groupInfo.value.id,
      action: 'join'
    })
    if (response.data.success) {
      await loadGroup(groupInfo.value.id)
    }
  } catch (error) {
    console.error('Error joining the group:', error)
  }
}
onMounted(async () => {
  if (route.params.group_id) {
    await loadGroup(route.params.group_id)
  }
})
</script>
