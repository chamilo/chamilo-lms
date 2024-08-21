import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useMessageRelUserStore } from "../store/messageRelUserStore"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import axios from 'axios'
import { useSocialInfo } from "./useSocialInfo"
import { storeToRefs } from "pinia"

export function useSocialMenuItems() {
  const { t } = useI18n();
  const messageRelUserStore = useMessageRelUserStore();
  const securityStore = useSecurityStore();
  const platformConfigStore = usePlatformConfig();
  const invitationsCount = ref(0);
  const groupLink = ref({ name: "UserGroupShow" });

  const { isCurrentUser } = useSocialInfo()
  const { user } = storeToRefs(securityStore)

  const unreadMessagesCount = computed(() => messageRelUserStore.countUnread)
  const globalForumsCourse = computed(() => platformConfigStore.getSetting("forum.global_forums_course_id"))
  const hideSocialGroupBlock = computed(() => platformConfigStore.getSetting("social.hide_social_groups_block") === "true")

  const isValidGlobalForumsCourse = computed(() => {
    const courseId = globalForumsCourse.value
    return courseId !== null && courseId !== undefined && courseId > 0
  });

  const fetchInvitationsCount = async (userId) => {
    if (!userId) return
    try {
      const { data } = await axios.get(`/social-network/invitations/count/${userId}`)
      invitationsCount.value = data.totalInvitationsCount
    } catch (error) {
      console.error("Error fetching invitations count:", error)
    }
  };

  const getGroupLink = async () => {
    try {
      const response = await axios.get("/social-network/get-forum-link")
      if (isValidGlobalForumsCourse.value) {
        groupLink.value = response.data.go_to
      } else {
        groupLink.value = { name: "UserGroupList" }
      }
    } catch (error) {
      console.error("Error fetching forum link:", error)
      groupLink.value = { name: "UserGroupList" }
    }
  };

  if (user.value && user.value.id) {
    fetchInvitationsCount(user.value.id)
    getGroupLink()
  }

  const items = computed(() => {
    const menuItems = [
      { icon: 'mdi mdi-home', label: t("Home"), route: '/social' },
      { icon: 'mdi mdi-email', label: t("Messages"), route: '/resources/messages', badgeCount: unreadMessagesCount.value },
      { icon: 'mdi mdi-handshake', label: t("My friends"), route: { name: 'UserRelUserList' } },
      { icon: 'mdi mdi-briefcase', label: t("My files"), route: { name: 'PersonalFileList', params: { node: securityStore.user.resourceNode.id } } },
      { icon: 'mdi mdi-account', label: t("Personal data"), route: '/resources/users/personal_data' },
    ]

    if (!hideSocialGroupBlock.value) {
      menuItems.splice(3, 0, { icon: 'mdi mdi-group', label: t("Social groups"), route: groupLink.value, isLink: isValidGlobalForumsCourse.value })
    }

    return isCurrentUser.value ? menuItems : [
      { icon: 'mdi mdi-home', label: t("Home"), route: '/social' },
      { icon: 'mdi mdi-email', label: t("Send message"), link: `/main/inc/ajax/user_manager.ajax.php?a=get_user_popup&user_id=${user.value.id}`, isExternal: true }
    ]
  })

  return { items }
}
