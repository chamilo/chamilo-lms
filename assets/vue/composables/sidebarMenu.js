import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"

export function useSidebarMenu() {
  const { t } = useI18n()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()

  const items = [
    {
      icon: "mdi mdi-home",
      label: t("Home"),
      to: { name: "Home" },
    },
  ]

  if (securityStore.isAuthenticated) {
    items.push({
      icon: "mdi mdi-book-open-page-variant",
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
      label: t("Courses"),
    })

    items.push({
      icon: "mdi mdi-calendar-text",
      label: t("Events"),
      to: { name: "CCalendarEventList" },
    })
  }

  if (securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin) {
    let url = "/main/my_space/" + (securityStore.isHRM ? "session.php" : "index.php")

    items.push({
      icon: "mdi mdi-chart-box",
      label: t("Reporting"),
      url,
    })
  } else {
    if (securityStore.isStudentBoss) {
      items.push({
        icon: "mdi mdi-chart-box",
        label: t("Reporting"),
        url: "/main/my_space/student.php",
      })
    } else {
      items.push({
        icon: "mdi mdi-chart-box",
        label: t("My progress"),
        url: "/main/auth/my_progress.php",
      })
    }
  }

  if (securityStore.isAuthenticated) {
    items.push({
      icon: "mdi mdi-sitemap-outline",
      label: t("Social network"),
      to: { name: "SocialWall" },
    })
  }

  if (platformConfigStore.plugins?.bbb?.show_global_conference_link) {
    items.push({
      icon: "mdi mdi-video",
      label: t("Videoconference"),
      url: platformConfigStore.plugins.bbb.listingURL,
    })
  }

  if (securityStore.isStudentBoss || securityStore.isStudent) {
    items.push({
      icon: "mdi mdi-text-box-search",
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
      label: t("Diagnosis"),
    })
  }

  if (securityStore.isAdmin) {
    items.push({
      icon: "mdi mdi-cog",
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
      ],
      label: t("Administration"),
    })
  }

  return items
}
