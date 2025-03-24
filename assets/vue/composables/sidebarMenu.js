import { useI18n } from "vue-i18n"
import { computed } from "vue"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import { useEnrolledStore } from "../store/enrolledStore"
import { useRoute } from "vue-router"
import { useSocialMenuItems } from "./useSocialMenuItems"

export function useSidebarMenu() {
  const { t } = useI18n()
  const route = useRoute()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()
  const enrolledStore = useEnrolledStore()
  const { items: socialItems } = useSocialMenuItems()
  const showTabsSetting = platformConfigStore.getSetting("platform.show_tabs")
  const showCatalogue = platformConfigStore.getSetting("platform.catalog_show_courses_sessions")

  const isActive = (item) => {
    if (item.route) {
      return route.path === item.route || (item.route.name && route.name === item.route.name)
    } else if (item.items) {
      return item.items.some((subItem) => isActive(subItem))
    }
    return false
  }

  const createMenuItem = (key, icon, label, routeName, subItems = null) => {
    if (showTabsSetting.indexOf(key) > -1) {
      const item = {
        icon: `mdi ${icon}`,
        label: t(label),
      }
      if (routeName) item.route = { name: routeName }
      if (subItems) item.items = subItems
      return item
    }
    return null
  }

  const menuItemsBeforeMyCourse = computed(() => {
    const items = []
    items.push(createMenuItem("campus_homepage", "mdi-home", "Home", "Home"))
    return items.filter(Boolean)
  })

  const menuItemMyCourse = computed(() => {
    const items = []

    if (securityStore.isAuthenticated && showTabsSetting.indexOf("my_courses") > -1) {
      const courseItems = []

      if (enrolledStore.isEnrolledInCourses) {
        courseItems.push({
          label: t("My courses"),
          route: { name: "MyCourses" },
        })
      }

      if (enrolledStore.isEnrolledInSessions) {
        courseItems.push({
          label: t("My sessions"),
          route: { name: "MySessions" },
        })
      }

      if (courseItems.length > 0) {
        items.push({
          icon: "mdi mdi-book-open-page-variant",
          label: courseItems.length > 1 ? t("Course") : courseItems[0].label,
          items: courseItems.length > 1 ? courseItems : undefined,
          route: 1 === courseItems.length ? courseItems[0].route : undefined,
          class: courseItems.length > 0 ? courseItems[0].class : "",
        })
      }
    }

    return items
  })

  const menuItemsAfterMyCourse = computed(() => {
    const items = []

    if (showTabsSetting.indexOf("catalogue") > -1) {
      if (showCatalogue == 0 || showCatalogue == 2) {
        items.push(createMenuItem("catalogue", "mdi-bookmark-multiple", "Explore more courses", "CatalogueCourses"))
      }
      if (showCatalogue > 0) {
        items.push(
          createMenuItem("catalogue", "mdi-bookmark-multiple-outline", "Sessions catalogue", "CatalogueSessions"),
        )
      }
    }

    items.push(createMenuItem("my_agenda", "mdi-calendar-text", "Events", "CCalendarEventList"))

    if (showTabsSetting.indexOf("reporting") > -1) {
      const subItems = []

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
      const styledSocialItems = socialItems.value.map((item) => {
        const newItem = {
          ...item,
          class: isActive(item) ? "p-focus" : "",
          icon: item.icon ? item.icon : undefined,
        }

        if (newItem.isLink && newItem.route) {
          newItem.url = newItem.route
        } else if (newItem.route) {
          // nothing to do
        } else if (newItem.link) {
          newItem.url = newItem.link
        }

        return newItem
      })

      items.push({
        icon: "mdi mdi-sitemap-outline",
        label: t("Social network"),
        items: styledSocialItems,
        expanded: isActive({ items: styledSocialItems }),
      })
    }

    if (
      showTabsSetting.includes("videoconference") > -1 &&
      platformConfigStore.plugins?.bbb?.show_global_conference_link &&
      platformConfigStore.plugins?.bbb?.listingURL
    ) {
      const conferenceItems = [
        {
          label: t("Conference Room"),
          url: platformConfigStore.plugins.bbb.listingURL,
        },
      ]

      if (conferenceItems.length > 0) {
        items.push(createMenuItem("videoconference", "mdi-video", "Videoconference", null, conferenceItems))
      }
    }

    if (showTabsSetting.indexOf("diagnostics") > -1) {
      items.push(
        createMenuItem("diagnostics", "mdi-text-box-search", "Diagnosis Management", null, [
          {
            label: t("Diagnosis Management"),
            url: "/main/search/load_search.php",
            visible: securityStore.isStudentBoss,
          },
          {
            label: t("Diagnostic Form"),
            url: "/main/search/search.php",
          },
        ]),
      )
    }

    if (showTabsSetting.indexOf("platform_administration") > -1) {
      if (securityStore.isAdmin || securityStore.isSessionAdmin) {
        const adminItems = [
          { label: t("Administration"), route: { name: "AdminIndex" } },
          ...(securityStore.isSessionAdmin &&
          "true" === platformConfigStore.getSetting("session.limit_session_admin_list_users")
            ? [{ label: t("Add user"), url: "/main/admin/user_add.php" }]
            : [{ label: t("Users"), url: "/main/admin/user_list.php" }]),
          { label: t("Courses"), url: "/main/admin/course_list.php" },
          { label: t("Sessions"), url: "/main/session/session_list.php" },
        ]

        items.push({
          icon: "mdi mdi-cog",
          items: adminItems,
          label: t("Administration"),
        })
      }
    }

    return items.filter(Boolean)
  })

  async function initialize() {
    await enrolledStore.initialize()
  }

  return {
    menuItemsBeforeMyCourse,
    menuItemMyCourse,
    menuItemsAfterMyCourse,
    initialize,
  }
}
