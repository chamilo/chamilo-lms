import { onMounted, readonly, ref } from "vue"
import { useStore } from "vuex"
import { useRoute } from "vue-router"
import axios from "axios"
import { useSecurityStore } from "../store/securityStore"

export function useSocialInfo() {
  const store = useStore()
  const securityStore = useSecurityStore()
  const route = useRoute()
  const user = ref({})
  const isCurrentUser = ref(true)
  const groupInfo = ref({
    isMember: false,
    title: "",
    description: "",
    role: "",
  })
  const isGroup = ref(false)
  const isLoading = ref(true)
  const loadGroup = async (groupId) => {
    isLoading.value = true
    if (groupId) {
      try {
        const response = await axios.get(`/social-network/group-details/${groupId}`)
        groupInfo.value = {
          ...response.data,
          isMember: response.data.isMember,
          role: response.data.role,
        }
        isGroup.value = true
      } catch (error) {
        console.error("Error loading group:", error)
        groupInfo.value = {}
        isGroup.value = false
      }
      isLoading.value = false
    } else {
      isGroup.value = false
      groupInfo.value = {}
    }
  }
  const loadUser = async () => {
    try {
      if (route.query.id) {
        const params = { ...route.query }
        if (route.path.includes("/social")) {
          params.page_origin = "social"
        }
        const response = await axios.get(`/api/users/${route.query.id}`, { params })
        user.value = response.data
        isCurrentUser.value = false
      } else {
        user.value = securityStore.user
        isCurrentUser.value = true
      }
    } catch (e) {
      user.value = {}
      isCurrentUser.value = true
    }
  }
  onMounted(async () => {
    try {
      //if (!route.params.group_id) {
      await loadUser()
      //}
      if (route.params.group_id) {
        await loadGroup(route.params.group_id)
      }
    } finally {
      isLoading.value = false
    }
  })
  return {
    user: readonly(user),
    isCurrentUser: readonly(isCurrentUser),
    groupInfo: readonly(groupInfo),
    isGroup: readonly(isGroup),
    loadGroup,
    loadUser,
    isLoading,
  }
}
