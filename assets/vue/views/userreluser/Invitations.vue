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
import { inject, onMounted, ref } from "vue"
import InvitationList from "../../components/userreluser/InvitationList.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useRouter } from "vue-router"
import socialService from "../../services/socialService"

const receivedInvitations = ref([])
const sentInvitations = ref([])
const pendingInvitations = ref([])
const router = useRouter()
const user = inject('social-user')

onMounted(() => {
  if (user.value && user.value.id) {
    fetchInvitations(user.value.id)
  }
})

function goToSearch() {
  router.push({ name: 'SocialSearch' })
}

const fetchInvitations = async (userId) => {
  if (!userId) return
  try {
    const data = await socialService.fetchInvitations(userId)
    console.log('Invitations :::', data)
    receivedInvitations.value = data.receivedInvitations
    sentInvitations.value = data.sentInvitations
    pendingInvitations.value = data.pendingGroupInvitations
  } catch (error) {
    console.error('Error fetching invitations:', error)
  }
}

const acceptInvitation = async (invitationId) => {
  try {
    await socialService.acceptInvitation(user.value.id, invitationId)
    console.log('Invitation accepted successfully')
    await fetchInvitations(user.value.id)
  } catch (error) {
    console.error('Error accepting invitation:', error)
  }
}

const denyInvitation = async (invitationId) => {
  try {
    await socialService.denyInvitation(user.value.id, invitationId)
    console.log('Invitation denied successfully')
    await fetchInvitations(user.value.id)
  } catch (error) {
    console.error('Error denying invitation:', error)
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
</script>
