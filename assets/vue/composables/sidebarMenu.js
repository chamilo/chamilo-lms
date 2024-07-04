import { useI18n } from "vue-i18n"
import { computed } from "vue"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import { useEnrolledStore } from "../store/enrolledStore"
import { useRoute, useRouter } from "vue-router"
import { useSocialMenuItems } from "./useSocialMenuItems"

export function useSidebarMenu() {
  const { t } = useI18n()
  const router = useRouter()
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

  const menuItemsBeforeMyCourse = computed(() => {
    const items = []

    if (showTabsSetting.indexOf("campus_homepage") > -1) {
      items.push({
        icon: "mdi mdi-home",
        label: t("Home"),
        url: router.resolve({ name: "Home" }).href,
      })
    }

    return items
  })

  const menuItemMyCourse = computed(() => {
    const items = []

    if (securityStore.isAuthenticated) {
      const courseItems = []

      if (enrolledStore.isEnrolledInCourses) {
        courseItems.push({
          label: t("My courses"),
          url: router.resolve({ name: "MyCourses" }).href,
        })
      }

      if (enrolledStore.isEnrolledInSessions) {
        courseItems.push({
          label: t("My sessions"),
          url: router.resolve({ name: "MySessions" }).href,
        })
      }

      if (courseItems.length > 0) {
        items.push({
          icon: "mdi mdi-book-open-page-variant",
          label: courseItems.length > 1 ? t("Course") : courseItems[0].label,
          items: courseItems.length > 1 ? courseItems : undefined,
          url: 1 === courseItems.length ? courseItems[0].url : undefined,
          class: courseItems.length > 0 ? courseItems[0].class : "",
        })
      }
    }

    return items
  })

  const menuItemsAfterMyCourse = computed(() => {
    const items = []

    if (showCatalogue > -1) {
      if (showCatalogue == 0 || showCatalogue == 2) {
        items.push({
          icon: "mdi mdi-bookmark-multiple",
          label: t("Courses catalogue"),
          url: router.resolve({ name: "CatalogueCourses" }).href,
        })
      }
      if (showCatalogue > 0) {
        items.push({
          icon: "mdi mdi-bookmark-multiple-outline",
          label: t("Sessions catalogue"),
          url: () => router.resolve({ name: "CatalogueSessions" }).href,
        })
      }
    }

    if (showTabsSetting.indexOf("my_agenda") > -1) {
      items.push({
        icon: "mdi mdi-calendar-text",
        label: t("Events"),
        url: () => router.resolve({ name: "CCalendarEventList" }).href,
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
      const styledSocialItems = socialItems.value.map((item) => {
        const newItem = {
          ...item,
          class: `sub-item-indent${isActive(item) ? " active" : ""}`,
        }

        if (newItem.isLink && newItem.route) {
          newItem.url = newItem.route
        } else if (newItem.route) {
          newItem.url = router.resolve(newItem.route).href
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
            label: t("Diagnosis Management"),
            url: "/main/search/load_search.php",
            visible: securityStore.isStudentBoss,
          },
          {
            label: t("Diagnostic Form"),
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
          url: router.resolve({ name: "AdminIndex" }).href,
        },
      ]

      if (
        securityStore.isSessionAdmin &&
        "true" === platformConfigStore.getSetting("session.limit_session_admin_list_users")
      ) {
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
