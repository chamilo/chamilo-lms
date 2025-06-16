import { usePlatformConfig } from "../../store/platformConfig"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { ref } from "vue"
import securityService from "../../services/securityService"
import { useNotification } from "../notification"

function isValidHttpUrl(string) {
  let url

  try {
    url = new URL(string)
  } catch (_) {
    return false
  }

  return url.protocol === "http:" || url.protocol === "https:"
}

export function useLogin() {
  const route = useRoute()
  const router = useRouter()
  const securityStore = useSecurityStore()
  const platformConfigurationStore = usePlatformConfig()
  const { showErrorNotification } = useNotification()

  const isLoading = ref(false)

  async function performLogin(payload) {
    isLoading.value = true

    try {
      const responseData = await securityService.login(payload)

      if (responseData.requires2FA) {
        return { success: true, requires2FA: true }
      }

      securityStore.user = responseData
      await platformConfigurationStore.initialize()

      if (route.query.redirect) {
        // Check if 'redirect' is an absolute URL
        if (isValidHttpUrl(route.query.redirect.toString())) {
          // If it's an absolute URL, redirect directly
          window.location.href = route.query.redirect.toString()
          return
        }

        await router.replace({ path: route.query.redirect.toString() })
        return
      }

      if (responseData.load_terms) {
        window.location.href = responseData.redirect
        return
      }

      const setting = platformConfigurationStore.getSetting("registration.redirect_after_login")
      let target = "/"

      if (setting && typeof setting === "string") {
        try {
          const map = JSON.parse(setting)
          const roles = responseData.roles || []

          const getProfile = () => {
            if (roles.includes("ROLE_ADMIN")) return "ADMIN"
            if (roles.includes("ROLE_SESSION_ADMIN")) return "SESSIONADMIN"
            if (roles.includes("ROLE_TEACHER")) return "COURSEMANAGER"
            if (roles.includes("ROLE_STUDENT_BOSS")) return "STUDENT_BOSS"
            if (roles.includes("ROLE_DRH")) return "DRH"
            if (roles.includes("ROLE_INVITEE")) return "INVITEE"
            if (roles.includes("ROLE_STUDENT")) return "STUDENT"
            return null
          }

          const profile = getProfile()
          const value = profile && map[profile] ? map[profile] : ""

          switch (value) {
            case "user_portal.php":
            case "index.php":
              target = "/home"
              break
            case "main/auth/courses.php":
              target = "/courses"
              break
            case "":
            case null:
              target = "/"
              break
            default:
              target = `/${value.replace(/^\/+/, "")}`
          }
        } catch (e) {
          console.warn("[redirect_after_login] JSON malformado:", e)
        }
      }

      await router.replace({ path: target })
    } catch (error) {
      const errorMessage = error.response?.data?.error || "An error occurred during login."
      showErrorNotification(errorMessage)
    } finally {
      isLoading.value = false
    }
  }

  async function redirectNotAuthenticated() {
    if (!securityStore.isAuthenticated) {
      return
    }

    if (route.query.redirect) {
      await router.push({ path: route.query.redirect.toString() })
    } else {
      await router.replace({ name: "Home" })
    }
  }

  return {
    isLoading,
    performLogin,
    redirectNotAuthenticated,
  }
}
