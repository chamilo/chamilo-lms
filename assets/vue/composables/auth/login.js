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
  const { showSuccessNotification, showErrorNotification } = useNotification()

  const isLoading = ref(false)
  const requires2FA = ref(false)

  async function performLogin(payload) {
    isLoading.value = true
    requires2FA.value = false

    try {
      const responseData = await securityService.login(payload)

      if (responseData.requires2FA && !payload.totp) {
        requires2FA.value = true
        return { success: false, requires2FA: true }
      }

      if (responseData.error) {
        showErrorNotification(responseData.error)
        return { success: false, error: responseData.error }
      }

      securityStore.user = responseData
      await platformConfigurationStore.initialize()

      if (route.query.redirect) {
        const redirect = route.query.redirect.toString()
        if (isValidHttpUrl(redirect)) {
          window.location.href = redirect
        } else {
          await router.replace({ path: redirect })
        }
      } else if (responseData.load_terms) {
        window.location.href = responseData.redirect
      } else {
        await router.replace({ name: "Home" })
      }

      return { success: true }
    } catch (error) {
      const errorMessage = error.response?.data?.error || "An error occurred during login."
      showErrorNotification(errorMessage)
      return { success: false, error: errorMessage }
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
    requires2FA,
  }
}
