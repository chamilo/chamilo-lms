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
      icon: "pi pi-fw pi-home",
    },

    {
      label: t("Courses"),
      icon: "pi pi-fw pi-book",
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
      icon: "pi pi-fw pi-calendar",
      visible: securityStore.isAuthenticated,
    },
    {
      label: t("My progress"),
      url: "/main/auth/my_progress.php",
      icon: "pi pi-fw pi-chart-line",
      visible: securityStore.isAuthenticated,
    },
    {
      label: t("Social network"),
      to: { name: "SocialWall" },
      icon: "pi pi-fw pi-sitemap",
      visible: securityStore.isAuthenticated,
    },

    {
      label: t('Videoconference'),
      url: platformConfigStore.plugins.bbb.listingURL,
      icon: 'mdi mdi-video-box',
      visible: platformConfigStore.plugins?.bbb?.show_global_conference_link,
    },

    {
      label: t("Diagnosis"),
      icon: "pi pi-fw pi-search",
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
      icon: "pi pi-fw pi-table",
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
