<template>
  <div class="flex flex-col md:flex-row gap-4" id="social-group-container">
    <div class="md:basis-1/3 lg:basis-1/4 2xl:basis-1/6 flex flex-col">
      <UserProfileCard v-if="!isLoading && !isGroup" />
      <GroupInfoCard v-if="!isLoading && isGroup" />
      <SocialGroupMenu v-if="!isLoading && isGroup" />
      <BaseCard v-if="isCurrentUser" plain class="mt-4 invite-friends">
        <div class="flex flex-col items-center p-2 user-invite-card">
          <div class="w-full">
            <div class=" bg-gray-200 border-b border-gray-300 rounded-t-lg text-center">
              <h2 class="text-xl font-semibold">{{ t('Pending Group Invitations') }}</h2>
            </div>
            <div class="pbg-white">
              <div v-if="pendingInvitations.length > 0" class="space-y-4">
                <div v-for="invitation in pendingInvitations" :key="invitation.id" class="flex items-center border rounded-lg shadow-sm bg-white">
                  <div class="ml-4 flex-grow text-center">
                    <h4 class="text-lg font-semibold">
                      <a :href="'profile.php?u=' + invitation.itemId" class="text-blue-600 hover:underline">{{ invitation.itemName }}</a>
                    </h4>
                    <span class="text-sm text-gray-500">{{ invitation.date }}</span>
                  </div>
                  <div class="flex space-x-2">
                    <BaseButton
                      v-if="invitation.canAccept"
                      icon="mdi-check"
                      type="success"
                      size="small"
                      only-icon
                      @click="() => acceptGroupInvitation(invitation.itemId)"
                    />

                    <button
                      v-if="invitation.canDeny"
                      class="remove-btn"
                      @click="() => denyGroupInvitation(invitation.itemId)"
                    >
                      -
                    </button>

                  </div>
                </div>
              </div>
              <div v-else class="p-4 text-center text-gray-500">
                <p>{{ t("No invitations or records found") }}</p>
              </div>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
    <div class="md:basis-2/3 lg:basis-3/4 2xl:basis-5/6">
      <router-view></router-view>
    </div>
  </div>
</template>
<script setup>
import UserProfileCard from "../social/UserProfileCard.vue"
import { onMounted, provide, ref } from "vue"
import { useSocialInfo } from "../../composables/useSocialInfo"
import SocialGroupMenu from "../social/SocialGroupMenu.vue"
import GroupInfoCard from "../social/GroupInfoCard.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import socialService from "../../services/socialService"
import { useNotification } from "../../composables/notification"
import { useI18n } from "vue-i18n"
import BaseCard from "../../components/basecomponents/BaseCard.vue"

const { t } = useI18n()
const { user, isCurrentUser, groupInfo, isGroup, loadGroup, loadUser, isLoading } = useSocialInfo()
const notification = useNotification()

const pendingInvitations = ref([])

const fetchInvitations = async (userId) => {
  if (!userId) return
  try {
    const data = await socialService.fetchInvitations(userId)
    pendingInvitations.value = data.pendingGroupInvitations
  } catch (error) {
    console.error('Error fetching invitations:', error)
  }
}

const acceptGroupInvitation = async (groupId) => {
  try {
    await socialService.acceptGroupInvitation(user.value.id, groupId)
    console.log('Group invitation accepted successfully')
    await fetchInvitations(user.value.id)
  } catch (error) {
    console.error('Error accepting group invitation:', error)
  }
}

const denyGroupInvitation = async (groupId) => {
  try {
    await socialService.denyGroupInvitation(user.value.id, groupId)
    console.log('Group invitation denied successfully')
    await fetchInvitations(user.value.id)
  } catch (error) {
    console.error('Error denying group invitation:', error)
  }
}

provide("social-user", user)
provide("is-current-user", isCurrentUser)
provide("group-info", groupInfo)
provide("is-group", isGroup)

onMounted(async () => {
  await loadUser()
  if (user.value && user.value.id) {
    await fetchInvitations(user.value.id)
  }
})
</script>
