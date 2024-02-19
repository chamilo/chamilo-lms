<template>
  <BaseButton
    label="Try and find some friends"
    icon="search"
    type="success"
    size="normal"
    @click="goToSearch"
  />
  <div>
    <InvitationList
      :invitations="receivedInvitations"
      title="Invitations Received"
      @accept="acceptInvitation"
      @deny="denyInvitation"
    />
    <InvitationList
      :invitations="sentInvitations"
      title="Invitations Sent"
    />
    <InvitationList
      :invitations="pendingInvitations"
      title="Pending Group Invitations"
      @accept="acceptGroupInvitation"
      @deny="denyGroupInvitation"
    />
  </div>
</template>

<script setup>
import axios from 'axios'
import { inject, onMounted, ref, watchEffect } from "vue"
import InvitationList from "../../components/userreluser/InvitationList.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useRouter } from "vue-router"

const receivedInvitations = ref([])
const sentInvitations = ref([])
const pendingInvitations = ref([])
const router = useRouter()

const user = inject('social-user')
const isCurrentUser = inject('is-current-user')


watchEffect(() => {
  if (user.value && user.value.id) {
    fetchInvitations(user.value.id)
  }
})
const fetchInvitations = async (userId) => {
  if (!userId) return
  try {
    const response = await axios.get(`/social-network/invitations/${userId}`)
    console.log('Invitations :::', response.data)
    receivedInvitations.value = response.data.receivedInvitations
    sentInvitations.value = response.data.sentInvitations
    pendingInvitations.value = response.data.pendingGroupInvitations
  } catch (error) {
    console.error('Error fetching invitations:', error)
  }
}
function goToSearch() {
  router.push({ name: 'SocialSearch' })
}

const acceptInvitation = async (invitationId) => {
  const invitation = receivedInvitations.value.find(invite => invite.id === invitationId)
  if (!invitation) return
  console.log('Invitation object:', invitation)
  const data = {
    userId: user.value.id,
    targetUserId: invitation.itemId,
    action: 'add_friend',
    is_my_friend: true,
  }
  try {
    const response = await axios.post('/social-network/user-action', data)
    if (response.data.success) {
      console.log('Invitation accepted successfully')
      fetchInvitations(user.value.id)
    } else {
      console.error('Failed to accept invitation')
    }
  } catch (error) {
    console.error('Error accepting invitation:', error)
  }
}
const denyInvitation = async (invitationId) => {
  const invitation = receivedInvitations.value.find(invite => invite.id === invitationId)
  if (!invitation) return
  const data = {
    userId: user.value.id,
    targetUserId: invitation.itemId,
    action: 'deny_friend',
  }
  try {
    const response = await axios.post('/social-network/user-action', data)
    if (response.data.success) {
      console.log('Invitation denied successfully')
      await fetchInvitations(user.value.id)
    } else {
      console.error('Failed to deny invitation')
    }
  } catch (error) {
    console.error('Error denying invitation:', error)
  }
}
const acceptGroupInvitation = (groupId) => {
  console.log(`Accepted group invitation with ID: ${groupId}`)
}
const denyGroupInvitation = (groupId) => {
  console.log(`Denied group invitation with ID: ${groupId}`)
}
</script>
