import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"

export function useSidebarMenu() {
  const { t } = useI18n()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()

  const items = [
    {
      label: t("Home"),
      to: { name: "Home" },
      icon: "mdi mdi-home",
    },

    {
      label: t("Courses"),
      icon: "mdi mdi-book-open-page-variant",
      visible: securityStore.isAuthenticated,
      items: [
        {
          label: t("My courses"),
          to: { name: "MyCourses" },
        },
        {
          label: t("My sessions"),
          to: { name: "MySessions" },
        },
      ],
    },
    {
      label: t("Events"),
      to: { name: "CCalendarEventList" },
      icon: "mdi mdi-calendar-text",
      visible: securityStore.isAuthenticated,
    },
    {
      label: t("My progress"),
      url: "/main/auth/my_progress.php",
      icon: "mdi mdi-chart-box",
      visible: securityStore.isAuthenticated,
    },
    {
      label: t("Social network"),
      to: { name: "SocialWall" },
      icon: "mdi mdi-sitemap-outline",
      visible: securityStore.isAuthenticated,
    },

    {
      label: t('Videoconference'),
      url: platformConfigStore.plugins.bbb.listingURL,
      icon: 'mdi mdi-video',
      visible: platformConfigStore.plugins?.bbb?.show_global_conference_link,
    },

    {
      label: t("Diagnosis"),
      icon: "mdi mdi-text-box-search",
      visible: securityStore.isStudentBoss || securityStore.isStudent,
      items: [
        {
          label: t("Management"),
          url: "/main/search/load_search.php",
          visible: securityStore.isStudentBoss,
        },
        {
          label: t("Search"),
          url: "/main/search/search.php",
        },
      ],
    },

    {
      label: t("Administration"),
      icon: "mdi mdi-cog",
      visible: securityStore.isAdmin,
      items: [
        {
          label: t("Administration"),
          to: { name: "AdminIndex" },
        },
        {
          label: t("Users"),
          url: "/main/admin/user_list.php",
        },
        {
          label: t("Courses"),
          url: "/main/admin/course_list.php",
        },
        {
          label: t("Sessions"),
          url: "/main/session/session_list.php",
        },
        {
          label: t("Reporting"),
          url: "/main/my_space/index.php",
        },
      ],
    },
  ]

  return items
}
