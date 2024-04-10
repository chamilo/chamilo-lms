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

  async function performLogin(payload) {
    isLoading.value = true

    try {
      const responseData = await securityService.login(payload)

      if (route.query.redirect) {
        // Check if 'redirect' is an absolute URL
        if (isValidHttpUrl(route.query.redirect.toString())) {
          // If it's an absolute URL, redirect directly
          window.location.href = route.query.redirect.toString()

          return
        }
      } else if (responseData.load_terms) {
        window.location.href = responseData.redirect

        return
      }

      securityStore.user = responseData

      await platformConfigurationStore.initialize()

      if (route.query.redirect) {
        // If 'redirect' is a relative path, use 'router.push' to navigate
        await router.replace({ path: route.query.redirect.toString() })
      } else {
        await router.replace({ name: "Home" })
      }
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
