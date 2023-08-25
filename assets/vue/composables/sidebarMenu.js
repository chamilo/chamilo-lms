import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"

export function useSidebarMenu() {
  const { t } = useI18n()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()

  const showTabsSetting = platformConfigStore.getSetting("platform.show_tabs")

  const items = []

  if (showTabsSetting.indexOf("campus_homepage") > -1) {
    items.push({
      icon: "mdi mdi-home",
      label: t("Home"),
      to: { name: "Home" },
    })
  }

  if (securityStore.isAuthenticated) {
    if (showTabsSetting.indexOf("campus_homepage") > -1) {
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
    }

    if (showTabsSetting.indexOf("my_agenda") > -1) {
      items.push({
        icon: "mdi mdi-calendar-text",
        label: t("Events"),
        to: { name: "CCalendarEventList" },
      })
    }

    if (showTabsSetting.indexOf("reporting") > -1) {
      let subItems = []

      if (securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin) {
        subItems.push({
          label: securityStore.isHRM ? t("Course sessions") : t("Reporting"),
          url: "/main/my_space/" + (securityStore.isHRM ? "session.php" : "index.php"),
        })
      } else if (securityStore.isStudentBoss) {
        subItems.push({
          label: t("Learners"),
          url: "/main/my_space/student.php",
        })
      } else {
        subItems.push({
          label: t("Progress"),
          url: "/main/auth/my_progress.php",
        })
      }

      items.push({
        icon: "mdi mdi-chart-box",
        label: t("Reporting"),
        items: subItems,
      })
    }

    if (showTabsSetting.indexOf("social") > -1) {
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

  if (securityStore.isAdmin || securityStore.isSessionAdmin) {
    const adminItems = [
      {
        label: t("Administration"),
        to: { name: "AdminIndex" },
      },
    ]

    if (securityStore.isSessionAdmin && 'true' === platformConfigStore.getSetting('session.limit_session_admin_list_users')) {
      adminItems.push({
        label: t("Add user"),
        url: "/main/admin/user_add.php",
      })
    } else {
      adminItems.push({
        label: t("Users"),
        url: "/main/admin/user_list.php",
      })
    }

    if (securityStore.isAdmin) {
      adminItems.push({
        label: t("Courses"),
        url: "/main/admin/course_list.php",
      })
    }

    adminItems.push({
      label: t("Sessions"),
      url: "/main/session/session_list.php",
    })

    items.push({
      icon: "mdi mdi-cog",
      items: adminItems,
      label: t("Administration"),
    })
  }

  return items
}
