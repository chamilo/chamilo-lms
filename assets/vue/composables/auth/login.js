import { usePlatformConfig } from "../../store/platformConfig"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { ref } from "vue"
import securityService from "../../services/securityService"
import { useNotification } from "../notification"

function isValidHttpUrl(string) {
  try {
    const url = new URL(string)
    return url.protocol === "http:" || url.protocol === "https:"
  } catch {
    return false
  }
}

export function useLogin() {
  const route = useRoute()
  const router = useRouter()
  const securityStore = useSecurityStore()
  const platformConfigurationStore = usePlatformConfig()
  const { showErrorNotification } = useNotification()

  const isLoading = ref(false)

  async function performLogin({ login, password, _remember_me, totp = null }) {
    isLoading.value = true

    try {
      const payload = {
        username: login,
        password,
        _remember_me,
      }
      if (totp) {
        payload.totp = totp
      }
      const returnUrl = route.query.redirect?.toString() || null
      if (returnUrl) {
        payload.returnUrl = returnUrl
      }

      const responseData = await securityService.login(payload)

      // 2FA
      if (responseData.requires2FA) {
        return { success: true, requires2FA: true }
      }

      if (responseData.redirect) {
        window.location.href = responseData.redirect
        return
      }

      securityStore.setUser(responseData)
      await platformConfigurationStore.initialize()

      if (route.query.redirect && isValidHttpUrl(route.query.redirect.toString())) {
        await router.replace({ path: route.query.redirect.toString() })
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
            if (roles.includes("ROLE_SESSION_MANAGER")) return "SESSIONADMIN"
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
