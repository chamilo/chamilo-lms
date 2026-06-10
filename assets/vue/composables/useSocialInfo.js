import { onMounted, readonly, ref } from "vue"
import { useStore } from "vuex"
import { useRoute } from "vue-router"
import socialService from "../services/socialService"
import userService from "../services/userService"
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
        const data = await socialService.getGroupDetails(groupId)
        groupInfo.value = {
          ...data,
          isMember: data.isMember,
          role: data.role,
        }
        isGroup.value = true
      } catch (error) {
        console.error("Error loading group:", error)
        groupInfo.value = {}
        isGroup.value = false
      }
    } else {
      isGroup.value = false
      groupInfo.value = {}
    }
    isLoading.value = false
  }
  const loadUser = async () => {
    try {
      const isSocialRoute = route.path.includes("/social")
      const uid = route.query.uid

      // Only allow uid usage inside /social
      if (isSocialRoute && uid) {
        const params = { ...route.query }
        delete params.id

        params.page_origin = "social"
        user.value = await userService.findById(uid, params)
        isCurrentUser.value = false
      } else {
        user.value = securityStore.user
        isCurrentUser.value = true
      }
    } catch (error) {
      console.error("Error loading user:", error)
      user.value = securityStore.user
      isCurrentUser.value = true
    }
  }
  onMounted(async () => {
    try {
      await loadUser()

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
