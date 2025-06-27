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
  const showTabs = computed(() => {
    const defaultTabs = platformConfigStore.getSetting("platform.show_tabs") || []
    const tabsPerRoleJson = platformConfigStore.getSetting("platform.show_tabs_per_role") || ""

    let tabsPerRole = {}
    try {
      tabsPerRole = JSON.parse(tabsPerRoleJson)
    } catch (e) {
      console.warn("[Sidebar] Invalid JSON in platform.show_tabs_per_role", e)
    }

    const roleMap = {
      ROLE_ADMIN: "ADMIN",
      ROLE_SESSION_MANAGER: "SESSIONADMIN",
      ROLE_TEACHER: "COURSEMANAGER",
      ROLE_STUDENT_BOSS: "STUDENT_BOSS",
      ROLE_DRH: "DRH",
      ROLE_INVITEE: "INVITEE",
      ROLE_STUDENT: "STUDENT",
    }

    const roles = securityStore.user?.roles || []
    console.debug("[Sidebar] User roles:", roles)
    console.debug("[Sidebar] tabsPerRole:", tabsPerRole)

    for (const role of roles) {
      const mappedRole = roleMap[role] || role
      if (tabsPerRole[mappedRole]) {
        console.debug("[Sidebar] Matched role:", mappedRole, "tabs:", tabsPerRole[mappedRole])
        return tabsPerRole[mappedRole]
      }
    }

    console.debug("[Sidebar] No matched role, using defaultTabs:", defaultTabs)
    return defaultTabs
  })

  const rawShowCatalogue = platformConfigStore.getSetting("platform.catalog_show_courses_sessions")
  const showCatalogue = Number(rawShowCatalogue)
  const isAnonymous = !securityStore.isAuthenticated
  const isPrivilegedUser =
    securityStore.isAdmin || securityStore.isTeacher || securityStore.isHRM || securityStore.isSessionAdmin
  const allowStudentCatalogue = computed(() => {
    if (isAnonymous) {
      return platformConfigStore.getSetting("course.course_catalog_published") !== "false"
    }

    if (isPrivilegedUser) {
      return true
    }

    if (securityStore.isStudent) {
      return platformConfigStore.getSetting("display.allow_students_to_browse_courses") !== "false"
    }

    return false
  })

  const isActive = (item) => {
    if (item.route) {
      return route.path === item.route || (item.route.name && route.name === item.route.name)
    } else if (item.items) {
      return item.items.some((subItem) => isActive(subItem))
    }
    return false
  }

  const createMenuItem = (key, icon, label, opts = null) => {
    if (showTabs.value.indexOf(key) > -1) {
      const item = {
        icon: `mdi ${icon}`,
        label: t(label),
      }

      if (typeof opts === "string") {
        item.route = { name: opts }
      } else if (opts) {
        if (opts.url) item.url = opts.url
        if (opts.routeName) item.route = { name: opts.routeName }
        if (opts.subItems) item.items = opts.subItems
      }

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

    if (securityStore.isAuthenticated && showTabs.value.indexOf("my_courses") > -1) {
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

    if (allowStudentCatalogue.value && showTabs.value.indexOf("catalogue") > -1) {
      if (showCatalogue === 0 || showCatalogue === 2) {
        items.push(createMenuItem("catalogue", "mdi-bookmark-multiple", "Explore more courses", "CatalogueCourses"))
      }
      if (showCatalogue > 0) {
        items.push(
          createMenuItem("catalogue", "mdi-bookmark-multiple-outline", "Sessions catalogue", "CatalogueSessions"),
        )
      }
    }

    items.push(
      createMenuItem("my_agenda", "mdi-calendar-text", "Events", {
        url: "/resources/ccalendarevent",
      }),
    )

    if (showTabs.value.indexOf("reporting") > -1) {
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

    if (showTabs.value.indexOf("social") > -1) {
      const styledSocialItems = socialItems.value.map((item) => {
        const newItem = {
          ...item,
          class: `${isActive(item) ? "p-focus" : ""} pl-4`,
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
      showTabs.value.includes("videoconference") > -1 &&
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
        items.push(
          createMenuItem("videoconference", "mdi-video", "Videoconference", {
            subItems: conferenceItems,
          }),
        )
      }
    }

    if (showTabs.value.indexOf("diagnostics") > -1) {
      const subItems = [
        {
          label: t("Diagnosis Management"),
          url: "/main/search/load_search.php",
          visible: securityStore.isStudentBoss,
        },
        {
          label: t("Diagnostic Form"),
          url: "/main/search/search.php",
        },
      ].filter((item) => item.visible !== false)

      if (subItems.length > 0) {
        items.push(
          createMenuItem("diagnostics", "mdi-text-box-search", "Diagnosis Management", {
            subItems,
          }),
        )
      }
    }

    if (showTabs.value.includes("session_admin") && (securityStore.isAdmin || securityStore.isSessionAdmin)) {
      const sessionAdminItems = [
        {
          label: t("Dashboard"),
          route: { name: "AdminDashboard" },
          icon: "mdi mdi-view-dashboard-outline",
          class: "pl-4",
        },
        {
          label: t("Favorite courses"),
          route: { name: "AdminFavoritesCourses" },
          icon: "mdi mdi-star-outline",
          class: "pl-4",
        },
        {
          label: t("Completed courses"),
          route: { name: "AdminCompletedCourses" },
          icon: "mdi mdi-check-circle-outline",
          class: "pl-4",
        },
        {
          label: t("Incomplete courses"),
          route: { name: "AdminIncompleteCourses" },
          icon: "mdi mdi-progress-close",
          class: "pl-4",
        },
        {
          label: t("Courses to restart"),
          route: { name: "AdminRestartCourses" },
          icon: "mdi mdi-history",
          class: "pl-4",
        },
      ]

      items.push({
        icon: "mdi mdi-account-cog",
        label: t("Session admin"),
        items: sessionAdminItems,
      })
    }

    if (showTabs.value.indexOf("platform_administration") > -1) {
      if (securityStore.isAdmin || securityStore.isSessionAdmin) {
        const adminItems = [
          { label: t("Administration"), route: { name: "AdminIndex" } },
          ...(securityStore.isSessionAdmin &&
          "true" === platformConfigStore.getSetting("session.limit_session_admin_list_users")
            ? [{ label: t("Add user"), url: "/main/admin/user_add.php" }]
            : [{ label: t("Users"), url: "/main/admin/user_list.php" }]),
          { label: t("Courses"), url: "/main/admin/course_list.php" },
          { label: t("Sessions"), url: "/main/session/session_list.php" },
          ...(securityStore.isAdmin ? [{ label: t("Settings"), url: "/admin/settings" }] : []),
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
