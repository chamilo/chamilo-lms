<template>
  <div class="social-search p-2">
    {{ query.value }}
    <BaseCard class="mb-2">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ headerTitle }}</h2>
        </div>
      </template>
      <div class="flex flex-col items-end">
        <div class="w-full flex justify-between items-center mb-2">
          <label for="search-query" class="mr-2">{{ t('Users, Groups') }}</label>
          <BaseInputText
            id="search-query"
            v-model="query"
            class="flex-grow"
           label=""/>
        </div>
        <div class="w-full flex justify-between items-center mb-4">
          <label for="search-type" class="mr-2">{{ t('Type') }}</label>
          <BaseSelect
            id="search-type"
            v-model="searchType"
            :options="searchOptions"
            optionLabel="name"
            optionValue="code"
            class="flex-grow"
           label=""/>
        </div>
        <BaseButton
          label="Search"
          icon="search"
          @click="handleFormSearch"
          type="secondary"
          class="self-end"
        />
      </div>
    </BaseCard>


    <BaseCard v-if="users.length" class="mb-2">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Users') }}</h2>
        </div>
      </template>
      <ul>
        <li v-for="user in users" :key="user.id" class="flex items-center justify-between p-2 border-b-2">
          <div class="flex items-center">
            <img :src="user.avatar" class="w-16 h-16 rounded-full mr-4">
            <span>{{ user.name }}</span>
            <span v-if="user.status === 'online'" class="mdi mdi-circle circle-green mx-2" title="Online"></span>
            <span v-else class="mdi mdi-circle circle-gray mx-2" title="Offline"></span>
            <span :class="getRoleIcon(user.role)" class="mx-2"></span>
          </div>
          <div>
            <BaseButton
              v-if="user.showInvitationButton"
              @click="openInvitationModal(user)"
              label="Send invitation"
              icon="account"
              type="secondary"
              class="mr-2"
            />

            <BaseButton
              @click="openMessageModal(user)"
              label="Send message"
              icon="email"
              type="primary"
            />
          </div>
        </li>
      </ul>
    </BaseCard>

    <BaseCard v-if="groups.length" class="mb-2">
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t('Groups') }}</h2>
        </div>
      </template>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
        <div v-for="group in groups" :key="group.id" class="group-card">
          <div class="group-image flex justify-center">
            <img :src="group.image" class="rounded w-16 h-16"
          </div>
          <div class="group-info text-center">
            <h3>{{ group.name }}</h3>
            <p>{{ group.description }}</p>
            <a :href="group.url">
              <BaseButton label="See more" type="secondary" class="mt-2" icon=""/>
            </a>
          </div>
        </div>
      </div>
    </BaseCard>

    <!-- Invitation Modal -->
    <div v-if="showInvitationModal" class="invitation-modal-overlay" @click.self="closeInvitationModal">
      <div class="invitation-modal">
        <div class="invitation-modal-header">
          <h3>Send invitation</h3>
          <button class="close-button" @click="closeInvitationModal">✕</button>
        </div>
        <textarea class="invitation-modal-textarea" placeholder="Add a personal message" v-model="invitationMessage"></textarea>
        <button class="invitation-modal-send" @click="sendInvitation">Send message</button>
      </div>
    </div>

    <!-- Message Modal -->
    <div v-if="showMessageModal" class="message-modal-overlay" @click.self="closeMessageModal">
      <div class="message-modal">
        <div class="message-modal-header">
          <h3>{{ t('Send message') }}</h3>
          <button class="message-modal-close" @click="closeMessageModal">✕</button>
        </div>
        <div class="message-modal-body">
          <div class="message-user-info">
            <img :src="selectedUser.avatar" class="message-user-avatar" alt="User avatar">
            <span class="message-user-name">{{ selectedUser.name }}</span>
          </div>
          <input type="text" class="message-modal-input" :placeholder="t('Subject')" v-model="messageSubject">
          <textarea class="message-modal-textarea" :placeholder="t('Message')" v-model="messageContent"></textarea>
          <button class="message-modal-send" @click="sendMessage">{{ t('Send message') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, ref, computed, watch } from "vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import { useSocialInfo } from "../../composables/useSocialInfo"
import { useRoute } from "vue-router"

const route = useRoute()
const query = ref('')
const searchType = ref('user')
const searchPerformed = ref(false)
const { t } = useI18n()
const notification = useNotification()
const selectedUser = ref(null)
const showInvitationModal = ref(false)
const showMessageModal = ref(false)
const messageSubject = ref('')
const messageContent = ref('')
const invitationMessage = ref('')
const { user, groupInfo, isGroup, loadGroup, isLoading } = useSocialInfo()
const searchOptions = [
  { name: 'User', code: 'user' },
  { name: 'Group', code: 'group' }
]
const users = ref([])
const groups = ref([])
const getRoleIcon = (role) => {
  switch(role) {
    case 'student':
      return 'mdi mdi-school'
    case 'teacher':
      return 'mdi mdi-account-outline'
    case 'admin':
      return 'mdi mdi-briefcase-check'
    default:
      return 'mdi mdi-account'
  }
}
const headerTitle = computed(() => {
  return searchPerformed.value ? `${t('Results and feedback')} "${query.value}"` : t('Search')
})
const performSearch = async () => {
  try {
    if (query.value.trim() === '') {
      notification.showWarningNotification('Please enter a search term.')
      return
    }
    searchPerformed.value = true
    await nextTick()
    const response = await fetch(`/social-network/search?query=${query.value}&type=${searchType.value}`)
    const data = await response.json()
    if (!response.ok) {
      throw new Error(data.message || 'Server response error')
    }
    if (searchType.value === 'user') {

      users.value = data.results.map(item => ({
        ...item,
        showInvitationButton: (![3, 4, 10].includes(item.relationType) || item.id !== user.value.id) && !item.existingInvitations
      }))
      groups.value = []
    } else if (searchType.value === 'group') {
      groups.value = data.results
      users.value = []
    }
  } catch (error) {
    console.error('There has been a problem with your fetch operation:', error)
  }
}
const openMessageModal = (user) => {
  selectedUser.value = user
  showMessageModal.value = true
}
const closeMessageModal = () => {
  showMessageModal.value = false
  messageSubject.value = ''
  messageContent.value = ''
}
const openInvitationModal = (user) => {
  selectedUser.value = user
  showInvitationModal.value = true
}
const closeInvitationModal = () => {
  showInvitationModal.value = false
  invitationMessage.value = ''
}
const sendInvitation = async () => {
  if (!selectedUser.value) {
    notification.showErrorNotification('No user selected.')
    return
  }

  const invitationData = {
    userId: user.value.id,
    targetUserId: selectedUser.value.id,
    action: 'send_invitation',
    subject: '',
    content: invitationMessage.value
  }
  try {
    const response = await fetch('/social-network/user-action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(invitationData)
    })
    const result = await response.json()
    if (result.success) {
      notification.showSuccessNotification('Invitation sent successfully.')
      users.value = users.value.filter((user) => user.id !== selectedUser.value.id)
      selectedUser.value = null
    } else {
      notification.showErrorNotification(result.error)
    }
  } catch (error) {
    notification.showErrorNotification('An error occurred while sending the invitation.')
    console.error('Error sending invitation:', error)
  }

  showInvitationModal.value = false
  invitationMessage.value = ''
}
const sendMessage = async () => {
  if (!selectedUser.value) {
    notification.showErrorNotification('No user selected.')
    return
  }

  const messageData = {
    userId: user.value.id,
    targetUserId: selectedUser.value.id,
    action: 'send_message',
    subject: messageSubject.value,
    content: messageContent.value
  }
  try {
    const response = await fetch('/social-network/user-action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(messageData)
    })
    const result = await response.json()
    if (result.success) {
      notification.showSuccessNotification('Message sent successfully.')
    } else {
      notification.showErrorNotification('Failed to send message.')
    }
  } catch (error) {
    notification.showErrorNotification('An error occurred while sending the message.')
    console.error('Error sending message:', error)
  }

  closeMessageModal()
}

watch(route, (currentRoute) => {
  query.value = currentRoute.query.query || ''
  searchType.value = currentRoute.query.type || 'user'
  if (query.value) {
    performSearch()
  }
}, { immediate: true })
const handleFormSearch = async () => {
  if (!query.value.trim()) {
    notification.showWarningNotification('Please enter a search term.')
    return
  }

  await performSearch()
}
</script>
