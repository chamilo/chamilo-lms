import { useI18n } from "vue-i18n"
import { ref, onMounted, watch } from "vue"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"
import { useEnrolledStore } from "../store/enrolledStore"

export function useSidebarMenu() {
  const { t } = useI18n()
  const securityStore = useSecurityStore()
  const platformConfigStore = usePlatformConfig()
  const enrolledStore = useEnrolledStore()
  const showTabsSetting = platformConfigStore.getSetting("platform.show_tabs")

  const items = ref([])
  const coursesItems = ref([])

  const updateItems = () => {
    items.value = []

    if (showTabsSetting.indexOf("campus_homepage") > -1) {
      items.value.push({
        icon: "mdi mdi-home",
        label: t("Home"),
        to: { name: "Home" },
      })
    }

    if (securityStore.isAuthenticated) {
      if (coursesItems.value.length > 0) {
        const coursesMenu = {
          icon: "mdi mdi-book-open-page-variant",
          label: coursesItems.value.length > 1 ? t("Courses") : coursesItems.value[0].label,
          items: coursesItems.value.length > 1 ? coursesItems.value : undefined,
          to: coursesItems.value.length === 1 ? coursesItems.value[0].to : undefined,
        }
        items.value.push(coursesMenu)
      }

      if (showTabsSetting.indexOf("my_agenda") > -1) {
        items.value.push({
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

        items.value.push({
          icon: "mdi mdi-chart-box",
          label: t("Reporting"),
          items: subItems,
        })
      }

      if (showTabsSetting.indexOf("social") > -1) {
        items.value.push({
          icon: "mdi mdi-sitemap-outline",
          label: t("Social network"),
          to: { name: "SocialWall" },
        })
      }

      if (platformConfigStore.plugins?.bbb?.show_global_conference_link) {
        items.value.push({
          icon: "mdi mdi-video",
          label: t("Videoconference"),
          url: platformConfigStore.plugins.bbb.listingURL,
        })
      }
    }

    if (securityStore.isStudentBoss || securityStore.isStudent) {
      items.value.push({
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

      items.value.push({
        icon: "mdi mdi-cog",
        items: adminItems,
        label: t("Administration"),
      })
    }
  }

  const updateCoursesItems = () => {
    coursesItems.value = [];

    if (enrolledStore.isEnrolledInCourses) {
      coursesItems.value.push({
        label: t("My courses"),
        to: { name: "MyCourses" },
      })
    }

    if (enrolledStore.isEnrolledInSessions) {
      coursesItems.value.push({
        label: t("My sessions"),
        to: { name: "MySessions" },
      })
    }

    updateItems();
  }

  onMounted(async () => {
    await enrolledStore.initialize();
    updateCoursesItems();
  })

  watch(() => enrolledStore.isEnrolledInCourses, updateCoursesItems)
  watch(() => enrolledStore.isEnrolledInSessions, updateCoursesItems)

  return items
}
